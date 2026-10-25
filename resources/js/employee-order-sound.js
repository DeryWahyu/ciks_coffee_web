/** A local voice recording plus a bell, repeated three times for each alert. */
export function createEmployeeOrderSound({ url, bellUrl, onChange = () => {} }) {
    let context;
    let master;
    let voice;
    let voiceLoading;
    let voiceError = false;
    let ringingBell;
    let bellLoading;
    let bellError = false;
    let volume = 1;
    const pending = [];
    let activeTask;
    let playing = false;
    let generation = 0;
    let finishBatch;
    const sources = new Set();
    const changed = () => onChange({ unlocked: context?.state === 'running', playing, voiceError, bellError });

    function getContext() {
        if (context) return context;
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) throw new Error('Audio tidak didukung browser ini.');
        context = new AudioContext();
        master = context.createGain();
        master.gain.value = volume;
        master.connect(context.destination);
        context.addEventListener('statechange', () => {
            if (context.state !== 'running' && playing) stopPlayback('interrupted');
            else changed();
        });
        return context;
    }

    async function download(url) {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 10000);
        try {
            const response = await fetch(url, { cache: 'force-cache', signal: controller.signal });
            if (!response.ok) throw new Error('Rekaman tidak tersedia.');
            return await response.arrayBuffer();
        } finally { clearTimeout(timeout); }
    }

    async function loadVoice() {
        if (voice) return voice;
        if (!voiceLoading) {
            voiceLoading = (async () => {
                const decoded = await context.decodeAudioData(await download(url));
                // Normalize the recording's peak without clipping or extra runtime services.
                let peak = 0;
                for (let channel = 0; channel < decoded.numberOfChannels; channel++) {
                    for (const sample of decoded.getChannelData(channel)) peak = Math.max(peak, Math.abs(sample));
                }
                const scale = peak > 0 ? Math.min(4, 0.95 / peak) : 1;
                for (let channel = 0; channel < decoded.numberOfChannels; channel++) {
                    const samples = decoded.getChannelData(channel);
                    for (let i = 0; i < samples.length; i++) samples[i] *= scale;
                }
                voiceError = false;
                voice = decoded;
                return decoded;
            })().finally(() => { voiceLoading = null; });
        }
        return voiceLoading;
    }

    async function loadBell() {
        if (ringingBell) return ringingBell;
        if (!bellLoading) {
            bellLoading = (async () => {
                ringingBell = await context.decodeAudioData(await download(bellUrl));
                return ringingBell;
            })().finally(() => { bellLoading = null; });
        }
        return bellLoading;
    }

    function track(source, output = source) {
        sources.add(source);
        source.addEventListener('ended', () => {
            sources.delete(source);
            source.disconnect();
            if (output !== source) output.disconnect();
        }, { once: true });
        return source;
    }

    function bell(start) {
        let last;
        for (const [offset, frequency] of [[0, 880], [0.16, 1174.66], [0.34, 880], [0.50, 1174.66]]) {
            const oscillator = context.createOscillator();
            const envelope = context.createGain();
            oscillator.type = 'square';
            oscillator.frequency.value = frequency;
            envelope.gain.setValueAtTime(0, start + offset);
            envelope.gain.linearRampToValueAtTime(0.22, start + offset + 0.008);
            envelope.gain.exponentialRampToValueAtTime(0.0001, start + offset + 0.14);
            oscillator.connect(envelope).connect(master);
            track(oscillator, envelope);
            oscillator.start(start + offset);
            oscillator.stop(start + offset + 0.15);
            last = oscillator;
        }
        return last;
    }

    function batch(recording, recordedBell) {
        return new Promise(resolve => {
            finishBatch = resolve;
            let start = context.currentTime + 0.03;
            let last;
            for (let repeat = 0; repeat < 3; repeat++) {
                if (recordedBell) {
                    const source = track(context.createBufferSource());
                    source.buffer = recordedBell;
                    source.connect(master);
                    source.start(start);
                    last = source;
                } else {
                    last = bell(start);
                }
                const lead = (recordedBell?.duration ?? 0.65) + 0.14;
                if (recording) {
                    const source = track(context.createBufferSource());
                    source.buffer = recording;
                    source.connect(master);
                    source.start(start + lead);
                    last = source;
                }
                start += lead + (recording?.duration ?? 0) + 0.45;
            }
            last.addEventListener('ended', () => {
                if (finishBatch === resolve) finishBatch = null;
                resolve();
            }, { once: true });
        });
    }

    async function drain() {
        if (playing) return;
        playing = true;
        const current = generation;
        changed();
        try {
            while (pending.length > 0 && current === generation) {
                activeTask = pending.shift();
                const [recording, recordedBell] = await Promise.allSettled([loadVoice(), loadBell()]);
                voiceError = recording.status === 'rejected';
                bellError = recordedBell.status === 'rejected';
                changed();
                if (current !== generation) return;
                if (context.state !== 'running') { stopPlayback('interrupted'); return; }
                await batch(recording.value, recordedBell.value);
                if (current !== generation) return;
                completeTask(activeTask, true, 'completed');
                activeTask = null;
            }
        } finally {
            if (current === generation) {
                playing = false;
                changed();
            }
        }
    }

    function completeTask(task, completed, reason) {
        try { task?.onComplete?.({ completed, reason }); }
        catch { /* A consumer callback cannot break audio recovery. */ }
    }

    function stopPlayback(reason = 'stopped') {
        generation++;
        completeTask(activeTask, false, reason);
        activeTask = null;
        for (const task of pending.splice(0)) completeTask(task, false, reason);
        for (const source of sources) {
            try { source.stop(); } catch { /* A source may already have ended. */ }
            source.disconnect();
        }
        sources.clear();
        finishBatch?.();
        finishBatch = null;
        playing = false;
        changed();
    }

    return {
        get unlocked() { return context?.state === 'running'; },
        async unlock() {
            const activeContext = getContext();
            let timer;
            try {
                await Promise.race([
                    activeContext.resume(),
                    new Promise((_, reject) => { timer = setTimeout(() => reject(new Error('Klik Aktifkan suara untuk mengizinkan audio.')), 2000); }),
                ]);
            } finally { clearTimeout(timer); changed(); }
            if (activeContext.state !== 'running') throw new Error('Browser belum mengizinkan audio.');
            // Warm the recordings after permission, before the next customer orders.
            Promise.allSettled([loadVoice(), loadBell()]);
        },
        notify(callbacks = {}) {
            if (context?.state !== 'running' || volume <= 0) return false;
            pending.push(callbacks);
            // Keep playback errors from escaping the realtime event handler.
            drain().catch(() => stopPlayback('error'));
            return true;
        },
        setVolume(value) {
            volume = Number.isFinite(Number(value)) ? Math.min(1, Math.max(0, Number(value))) : 1;
            if (master) master.gain.setTargetAtTime(volume, context.currentTime, 0.015);
        },
        stop: stopPlayback,
    };
}
