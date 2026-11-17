import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createAdaptivePoller } from '../../resources/js/adaptive-poller.js';

function fixture(task = async () => {}) {
    let id = 0;
    const jobs = new Map();
    const polling = createAdaptivePoller({ task,
        schedule: (fn, delay) => { jobs.set(++id, { fn, delay }); return id; }, cancel: key => jobs.delete(key),
    });
    return { polling, jobs, next: () => [...jobs.values()][0], tick: async () => {
        const [key, job] = [...jobs][0]; jobs.delete(key); await job.fn();
    } };
}

test('idle requests decrease only while the private realtime channel is healthy', () => {
    const state = fixture();
    state.polling.start();
    assert.equal(state.next().delay, 5000);
    state.polling.setConnected(true);
    assert.equal(state.next().delay, 30000);
    assert.equal(state.jobs.size, 1);
    state.polling.setConnected(false);
    assert.equal(state.next().delay, 5000);
    state.polling.stop();
    assert.equal(state.jobs.size, 0);
});

test('a failed request still schedules recovery and start cannot create duplicate loops', async () => {
    let calls = 0;
    const state = fixture(async () => { calls++; throw new Error('offline'); });
    state.polling.start(); state.polling.start();
    assert.equal(state.jobs.size, 1);
    await state.tick();
    assert.equal(calls, 1);
    assert.equal(state.next().delay, 5000);
    state.polling.stop();
});

test('leaving during an in-flight request cannot restart polling after it resolves', async () => {
    let release;
    const state = fixture(() => new Promise(resolve => { release = resolve; }));
    state.polling.start();
    const pending = state.tick();
    state.polling.stop(); release(); await pending;
    assert.equal(state.jobs.size, 0);
});
