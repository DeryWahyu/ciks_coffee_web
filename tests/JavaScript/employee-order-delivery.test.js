import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createOrderDelivery } from '../../resources/js/employee-order-delivery.js';

const flush = () => new Promise(resolve => setImmediate(resolve));
const order = id => ({ id, order_number: `CK-${id}` });
function fixture(options = {}) {
    let stored = [];
    const tasks = [];
    const sound = {
        unlocked: true, tasks,
        notify(callbacks) { if (!this.unlocked) return false; tasks.push(callbacks); return true; },
        stop(reason) { tasks.splice(0).forEach(task => task.onComplete({ completed: false, reason })); },
    };
    const delivery = createOrderDelivery({ sound, key: 'audit', read: () => stored, write: values => { stored = values; }, ...options });
    return { sound, delivery, stored: () => stored };
}

test('orders received before audio activation stay pending and are acknowledged only after playback', async () => {
    const { sound, delivery, stored } = fixture();
    sound.unlocked = false;
    await delivery.receive(order(1));
    assert.equal(delivery.count, 1); assert.equal(sound.tasks.length, 0);
    sound.unlocked = true;
    const running = delivery.flush();
    assert.equal(sound.tasks.length, 1); assert.deepEqual(stored(), []);
    sound.tasks.shift().onComplete({ completed: true });
    await running;
    assert.deepEqual(stored(), ['1']); assert.equal(delivery.count, 0);
});

test('an interrupted playback is retried instead of being marked as delivered', async () => {
    const { sound, delivery, stored } = fixture();
    const first = delivery.receive(order(2));
    sound.tasks.shift().onComplete({ completed: false, reason: 'interrupted' });
    await first;
    assert.deepEqual(stored(), []); assert.equal(delivery.count, 1);
    const second = delivery.flush();
    sound.tasks.shift().onComplete({ completed: true }); await second;
    assert.deepEqual(stored(), ['2']);
});

test('two employee tabs share one lock until the complete announcement finishes', async () => {
    let busy = false; let stored = [];
    const locks = { async request(key, options, callback) {
        if (busy) return callback(null);
        busy = true;
        try { return await callback({ name: key }); } finally { busy = false; }
    } };
    const shared = { locks, read: () => stored, write: values => { stored = values; } };
    const first = fixture(shared); const second = fixture(shared);
    const running = first.delivery.receive(order(3));
    await second.delivery.receive(order(3));
    assert.equal(first.sound.tasks.length, 1); assert.equal(second.sound.tasks.length, 0);
    assert.deepEqual(stored, []);
    first.sound.tasks.shift().onComplete({ completed: true }); await running;
    await second.delivery.flush();
    assert.equal(second.sound.tasks.length, 0); assert.equal(second.delivery.count, 0);
});

test('a denied Web Locks request falls back to playable audio', async () => {
    const { sound, delivery, stored } = fixture({ locks: { request: async () => { throw new Error('Denied'); } } });
    const running = delivery.receive(order(4)); await flush();
    assert.equal(sound.tasks.length, 1);
    sound.tasks.shift().onComplete({ completed: true }); await running;
    assert.deepEqual(stored(), ['4']);
});

test('manual stop dismisses current alerts so the next poll does not restart them', async () => {
    const { sound, delivery, stored } = fixture();
    const running = delivery.receive(order(5));
    delivery.dismiss(); await running;
    await delivery.receive(order(5));
    assert.deepEqual(stored(), ['5']); assert.equal(sound.tasks.length, 0);
});

test('snapshot cleanup retains a websocket order that arrived after the scan began', async () => {
    let clock = 100;
    const { sound, delivery } = fixture({ now: () => clock });
    sound.unlocked = false;
    await delivery.receive(order(6));
    clock = 120; await delivery.receive(order(7));
    delivery.reconcile([], 110);
    assert.equal(delivery.count, 1);
    sound.unlocked = true;
    const running = delivery.flush();
    sound.tasks.shift().onComplete({ completed: true }); await running;
    assert.equal(delivery.isAcknowledged('6'), false);
    assert.equal(delivery.isAcknowledged('7'), true);
});

test('restricted storage does not suppress audio or same-tab deduplication', async () => {
    const { sound, delivery } = fixture({ read() { throw new Error('Unavailable'); }, write() { throw new Error('Quota'); } });
    const running = delivery.receive(order(8));
    sound.tasks.shift().onComplete({ completed: true }); await running;
    await delivery.receive(order(8));
    assert.equal(sound.tasks.length, 0); assert.equal(delivery.isAcknowledged('8'), true);
});

test('page disposal leaves an interrupted order eligible on the next page', async () => {
    const { delivery, stored } = fixture();
    const running = delivery.receive(order(9));
    delivery.dispose(); await running;
    assert.deepEqual(stored(), []);
    const next = fixture({ read: stored });
    const replay = next.delivery.receive(order(9));
    assert.equal(next.sound.tasks.length, 1);
    next.sound.tasks.shift().onComplete({ completed: true }); await replay;
});

test('muted or zero-volume alerts wait until sound is enabled again', async () => {
    let audible = false;
    const { sound, delivery } = fixture({ audible: () => audible });
    await delivery.receive(order(10));
    assert.equal(sound.tasks.length, 0);
    audible = true;
    const running = delivery.flush();
    sound.tasks.shift().onComplete({ completed: true }); await running;
    assert.equal(delivery.count, 0);
});
