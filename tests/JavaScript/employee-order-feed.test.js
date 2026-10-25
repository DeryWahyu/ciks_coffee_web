import { test, beforeEach } from 'node:test';
import assert from 'node:assert/strict';
import { createOrderFeed } from '../../resources/js/employee-order-feed.js';

beforeEach(() => { global.window = { location: { href: 'https://audit.test/karyawan/pos' } }; });
const reply = (orders, next_cursor = null) => ({ ok: true, status: 200, json: async () => ({ orders, next_cursor }) });
function fixture(fetcher) {
    const received = [], snapshots = [], statuses = [], expired = [];
    const feed = createOrderFeed({ url: '/karyawan/orders/notifications', fetcher,
        receive: payload => received.push(payload.order), reconcile: orders => snapshots.push(orders),
        onStatus: status => statuses.push(status), onExpired: () => expired.push(true),
    });
    return { feed, received, snapshots, statuses, expired };
}

test('the next scan recovers after a network failure', async () => {
    const urls = []; let fail = true;
    const state = fixture(async url => { urls.push(url); if (fail) throw new Error('Offline'); return reply([{ id: 1 }]); });
    await state.feed.poll(); fail = false; await state.feed.poll();
    assert.deepEqual(state.statuses, [false, true]);
    assert.deepEqual(state.received, [{ id: 1 }]);
    assert.ok(urls.every(url => new URL(url).searchParams.get('after_id') === '0'));
});

test('all pages are collected before reconciling or delivering the snapshot', async () => {
    const urls = [];
    const state = fixture(async url => {
        urls.push(url);
        return urls.length === 1 ? reply(Array.from({ length: 50 }, (_, i) => ({ id: i + 1 })), 50) : reply([{ id: 51 }]);
    });
    await state.feed.poll();
    assert.equal(state.snapshots.length, 1); assert.equal(state.snapshots[0].length, 51);
    assert.equal(state.received.length, 51);
    assert.equal(new URL(urls[1]).searchParams.get('after_id'), '50');
});

test('a failed partial scan cannot delete pending alerts and restarts from zero', async () => {
    let calls = 0;
    const state = fixture(async () => { calls++; if (calls === 2) throw new Error('Offline'); return calls === 1 ? reply([{ id: 1 }], 1) : reply([{ id: 1 }, { id: 2 }]); });
    await state.feed.poll();
    assert.equal(state.snapshots.length, 0); assert.equal(state.received.length, 0);
    await state.feed.poll();
    assert.deepEqual(state.received, [{ id: 1 }, { id: 2 }]);
});

test('a lower ID committed after a previous scan is still discovered', async () => {
    const urls = [];
    const state = fixture(async url => { urls.push(url); return reply(urls.length === 1 ? [{ id: 5 }] : [{ id: 3 }, { id: 5 }]); });
    await state.feed.poll(); await state.feed.poll();
    assert.ok(urls.every(url => new URL(url).searchParams.get('after_id') === '0'));
    assert.ok(state.received.some(order => order.id === 3));
});

test('expired or forbidden sessions stop polling and signal reauthentication', async () => {
    for (const status of [401, 403]) {
        let calls = 0;
        const state = fixture(async () => { calls++; return { ok: false, status }; });
        await state.feed.poll(); await state.feed.poll();
        assert.equal(calls, 1); assert.equal(state.expired.length, 1); assert.equal(state.received.length, 0);
    }
});

test('concurrent checks do not duplicate in-flight requests', async () => {
    let release; let calls = 0;
    const state = fixture(() => { calls++; return new Promise(resolve => { release = resolve; }); });
    const running = state.feed.poll(); await state.feed.poll();
    assert.equal(calls, 1); release(reply([{ id: 1 }])); await running;
    assert.equal(state.received.length, 1);
});

test('stopping a page suppresses late responses', async () => {
    let release;
    const state = fixture(() => new Promise(resolve => { release = resolve; }));
    const running = state.feed.poll(); state.feed.stop(); release(reply([{ id: 1 }])); await running;
    assert.equal(state.received.length, 0); assert.equal(state.snapshots.length, 0);
});

test('invalid pagination cannot loop indefinitely', async () => {
    const state = fixture(async () => reply([{ id: 1 }], 0));
    await state.feed.poll();
    assert.deepEqual(state.statuses, [false]); assert.equal(state.received.length, 0);
});
