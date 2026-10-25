import { test, beforeEach } from 'node:test';
import assert from 'node:assert/strict';
import { createEmployeeOrderSound as createSound } from '../../resources/js/employee-order-sound.js';

const createEmployeeOrderSound = options => createSound({ bellUrl: '/bell.wav', ...options });
const voices = context => context.buffers.filter(source => source.buffer.duration === 2);

class Source extends EventTarget {
    frequency = { value: 0 };
    starts = [];
    stops = [];
    connect() { return this; }
    disconnect() {}
    start(at) { this.starts.push(at); }
    stop(at) { this.stops.push(at); }
    end() { this.dispatchEvent(new Event('ended')); }
}

class AudioContext extends EventTarget {
    static latest;
    static blocked = false;
    state = 'suspended';
    currentTime = 10;
    destination = {};
    buffers = [];
    oscillators = [];
    gains = [];
    constructor() { super(); AudioContext.latest = this; }
    async resume() {
        if (AudioContext.blocked) throw new Error('Autoplay blocked');
        this.state = 'running';
        this.dispatchEvent(new Event('statechange'));
    }
    createGain() {
        const gain = {
            gain: { value: 0, setValueAtTime() {}, linearRampToValueAtTime() {}, exponentialRampToValueAtTime() {}, setTargetAtTime(value) { this.value = value; } },
            connect() { return this; }, disconnect() {},
        };
        this.gains.push(gain);
        return gain;
    }
    createOscillator() { const source = new Source(); this.oscillators.push(source); return source; }
    createBufferSource() { const source = new Source(); this.buffers.push(source); return source; }
    async decodeAudioData(data) {
        const samples = new Float32Array([0.4, -0.5, 0.1]);
        return { duration: new Uint8Array(data)[0] === 2 ? 1.44 : 2, numberOfChannels: 1, getChannelData: () => samples };
    }
}

const flush = () => new Promise(resolve => setImmediate(resolve));
let fetches;
beforeEach(() => {
    AudioContext.latest = null;
    AudioContext.blocked = false;
    global.window = { AudioContext };
    fetches = 0;
    global.fetch = async url => { fetches++; return { ok: true, arrayBuffer: async () => new Uint8Array([url.includes('bell') ? 2 : 1]).buffer }; };
});

test('an order cannot start audio before browser activation', () => {
    const sound = createEmployeeOrderSound({ url: '/voice.mp3' });
    assert.equal(sound.notify(), false);
    assert.equal(AudioContext.latest, null);
    assert.equal(fetches, 0);
});

test('blocked autoplay is reported and a later user activation can recover', async () => {
    const sound = createEmployeeOrderSound({ url: '/voice.mp3' });
    AudioContext.blocked = true;
    await assert.rejects(sound.unlock(), /Autoplay blocked/);
    assert.equal(sound.unlocked, false);
    assert.equal(sound.notify(), false);
    AudioContext.blocked = false;
    await sound.unlock();
    assert.equal(sound.unlocked, true);
});

test('one order schedules exactly three separated voice announcements and bells', async () => {
    const states = [];
    const sound = createEmployeeOrderSound({ url: '/voice.mp3', onChange: state => states.push(state) });
    await sound.unlock();
    assert.equal(sound.notify(), true);
    await flush();
    const context = AudioContext.latest;
    assert.equal(context.buffers.length, 6);
    assert.equal(voices(context).length, 3);
    assert.equal(context.oscillators.length, 0);
    assert.equal(fetches, 2);
    for (let i = 1; i < 6; i++) assert.ok(context.buffers[i].starts[0] > context.buffers[i - 1].starts[0] + context.buffers[i - 1].buffer.duration);
    const samples = voices(context)[0].buffer.getChannelData(0);
    assert.ok(Math.abs(Math.min(...samples) + 0.95) < 0.00001);
    context.buffers.at(-1).end();
    await flush();
    assert.equal(states.at(-1).playing, false);
});

test('consecutive orders wait for the previous announcement instead of overlapping', async () => {
    const sound = createEmployeeOrderSound({ url: '/voice.mp3' });
    await sound.unlock();
    sound.notify(); sound.notify();
    await flush();
    const context = AudioContext.latest;
    assert.equal(context.buffers.length, 6);
    context.currentTime = 30;
    context.buffers.at(-1).end();
    await flush();
    assert.equal(context.buffers.length, 12);
    assert.ok(context.buffers[6].starts[0] > 30);
    assert.equal(fetches, 2);
    sound.stop();
});

test('mute or stop cancels the current recording and all queued announcements', async () => {
    const states = [];
    const sound = createEmployeeOrderSound({ url: '/voice.mp3', onChange: state => states.push(state) });
    await sound.unlock();
    sound.notify(); sound.notify();
    await flush();
    const context = AudioContext.latest;
    sound.stop();
    assert.equal(states.at(-1).playing, false);
    assert.ok(context.buffers.every(source => source.stops.length === 1));
    context.buffers.at(-1).end();
    await flush();
    assert.equal(context.buffers.length, 6);
    sound.setVolume(0);
    assert.equal(sound.notify(), false);
    sound.setVolume(0.5);
    assert.equal(context.gains[0].gain.value, 0.5);
});

test('stopping while the audio downloads prevents a late unwanted announcement', async () => {
    const releases = [];
    global.fetch = () => new Promise(resolve => { releases.push(resolve); });
    const sound = createEmployeeOrderSound({ url: '/voice.mp3' });
    await sound.unlock();
    sound.notify();
    sound.stop();
    for (const release of releases) release({ ok: true, arrayBuffer: async () => new ArrayBuffer(1) });
    await flush();
    assert.equal(AudioContext.latest.buffers.length, 0);
    assert.equal(AudioContext.latest.oscillators.length, 0);
});

test('an unavailable recording still sounds three bells and reports voice fallback', async () => {
    global.fetch = async () => ({ ok: false });
    const states = [];
    const sound = createEmployeeOrderSound({ url: '/missing.mp3', onChange: state => states.push(state) });
    await sound.unlock();
    sound.notify();
    await flush();
    const context = AudioContext.latest;
    assert.equal(context.buffers.length, 0);
    assert.equal(context.oscillators.length, 12);
    assert.equal(states.at(-1).voiceError, true);
    context.oscillators.at(-1).end();
    await flush();
    assert.equal(states.at(-1).playing, false);
});

test('a missing voice still plays the generated busy bell three times', async () => {
    global.fetch = async url => ({ ok: url.includes('bell'), arrayBuffer: async () => new Uint8Array([2]).buffer });
    const sound = createEmployeeOrderSound({ url: '/missing.mp3' });
    await sound.unlock(); sound.notify(); await flush();
    assert.equal(AudioContext.latest.buffers.length, 3);
    assert.equal(voices(AudioContext.latest).length, 0);
    assert.equal(AudioContext.latest.oscillators.length, 0);
    sound.stop();
});

test('a missing busy bell keeps the voice plus the synthesized fallback bell', async () => {
    global.fetch = async url => ({ ok: !url.includes('bell'), arrayBuffer: async () => new Uint8Array([1]).buffer });
    const sound = createEmployeeOrderSound({ url: '/voice.mp3' });
    await sound.unlock(); sound.notify(); await flush();
    assert.equal(voices(AudioContext.latest).length, 3);
    assert.equal(AudioContext.latest.oscillators.length, 12);
    sound.stop();
});

test('a browser audio interruption releases playback so the cashier can activate it again', async () => {
    const states = [];
    const sound = createEmployeeOrderSound({ url: '/voice.mp3', onChange: state => states.push(state) });
    await sound.unlock(); sound.notify(); await flush();
    const context = AudioContext.latest;
    context.state = 'suspended';
    context.dispatchEvent(new Event('statechange'));
    assert.equal(states.at(-1).playing, false);
    assert.equal(sound.unlocked, false);
    assert.equal(sound.notify(), false);
    await sound.unlock();
    assert.equal(sound.notify(), true);
    await flush();
    assert.equal(voices(context).length, 6);
    sound.stop();
});

test('delivery is completed only after the third voice finishes', async () => {
    const results = [];
    const sound = createEmployeeOrderSound({ url: '/voice.mp3' });
    await sound.unlock();
    sound.notify({ onComplete: result => results.push(result) });
    await flush();
    const sources = voices(AudioContext.latest);
    sources[0].end(); sources[1].end(); await flush();
    assert.deepEqual(results, []);
    sources[2].end(); await flush();
    assert.deepEqual(results, [{ completed: true, reason: 'completed' }]);
});

test('an interrupted voice reports incomplete delivery exactly once', async () => {
    const results = [];
    const sound = createEmployeeOrderSound({ url: '/voice.mp3' });
    await sound.unlock();
    sound.notify({ onComplete: result => results.push(result) });
    await flush();
    sound.stop('interrupted');
    voices(AudioContext.latest).at(-1).end(); await flush();
    assert.deepEqual(results, [{ completed: false, reason: 'interrupted' }]);
});
