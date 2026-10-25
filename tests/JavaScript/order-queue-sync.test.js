import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createOrderQueueSync } from '../../resources/js/order-queue-sync.js';

const reply = revision => ({ ok: true, status: 200, json: async () => ({ revision, html: '<div>Queue</div>' }) });
function fixture(fetcher) {
    const applied = [], errors = [], expired = [];
    let revision = 'old';
    const sync = createOrderQueueSync({ url: () => '/snapshot?revision=' + revision, fetcher,
        apply: data => { revision = data.revision; applied.push(data); },
        onError: () => errors.push(true), onExpired: () => expired.push(true),
    });
    return { sync, applied, errors, expired };
}

test('an unchanged snapshot leaves the current queue intact', async () => {
    const state = fixture(async () => ({ ok: true, status: 204 }));
    await state.sync.refresh();
    assert.equal(state.applied.length, 0); assert.equal(state.errors.length, 0);
});

test('a changed queue is applied without navigation', async () => {
    const state = fixture(async () => reply('new'));
    await state.sync.refresh();
    assert.equal(state.applied[0].revision, 'new');
});

test('an event during an in-flight request triggers a second snapshot', async () => {
    let release; const urls = [];
    const state = fixture(url => {
        urls.push(url);
        return urls.length === 1 ? new Promise(resolve => { release = resolve; }) : Promise.resolve(reply('latest'));
    });
    const running = state.sync.refresh();
    await state.sync.refresh(); await state.sync.refresh();
    release(reply('intermediate')); await running;
    assert.equal(urls.length, 2); assert.equal(urls[1], '/snapshot?revision=intermediate');
    assert.equal(state.applied.at(-1).revision, 'latest');
});

test('network failure retains the old queue and a later request recovers', async () => {
    let fail = true;
    const state = fixture(async () => { if (fail) throw new Error('Offline'); return reply('recovered'); });
    await state.sync.refresh();
    assert.equal(state.applied.length, 0); assert.equal(state.errors.length, 1);
    fail = false; await state.sync.refresh();
    assert.equal(state.applied[0].revision, 'recovered');
});

test('expired sessions stop further snapshot requests', async () => {
    let calls = 0;
    const state = fixture(async () => { calls++; return { ok: false, status: 401 }; });
    await state.sync.refresh(); await state.sync.refresh();
    assert.equal(calls, 1); assert.equal(state.expired.length, 1);
});

test('leaving the page prevents a late response from changing its queue', async () => {
    let release;
    const state = fixture(() => new Promise(resolve => { release = resolve; }));
    const running = state.sync.refresh(); state.sync.stop(); release(reply('late')); await running;
    assert.equal(state.applied.length, 0);
});
