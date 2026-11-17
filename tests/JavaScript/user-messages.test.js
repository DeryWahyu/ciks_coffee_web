import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

function load() {
    const listeners = {};
    const context = { window: {}, document: { addEventListener: (event, callback) => { listeners[event] = callback; } }, SyntaxError };
    const source = readFileSync(new URL('../../resources/views/components/user-messages.blade.php', import.meta.url), 'utf8').replace(/<\/?script>/g, '');
    vm.runInNewContext(source, context);
    return { errors: context.window.CiksErrors, listeners };
}

test('network, session and technical errors become useful Indonesian messages', () => {
    const { errors } = load();
    assert.match(errors.message(new TypeError('Failed to fetch')), /Periksa koneksi/);
    assert.match(errors.message('CSRF token mismatch.'), /Sesi formulir telah berakhir/);
    assert.match(errors.message('Too Many Attempts.'), /Tunggu/);
    assert.equal(errors.message('No query results for model [App\\Models\\Order] 5', 'Data tidak ditemukan.'), 'Data tidak ditemukan.');
    assert.equal(errors.message(new SyntaxError('Unexpected token'), 'Respons tidak sesuai.'), 'Respons tidak sesuai.');
    assert.equal(errors.message('Stok Kopi tidak cukup.'), 'Stok Kopi tidak cukup.');
});

test('HTML login responses and malformed JSON do not leak parser errors', async () => {
    const { errors } = load();
    await assert.rejects(errors.readJson({ redirected: true, url: 'https://example.test/login' }), /Silakan masuk kembali/);
    await assert.rejects(errors.readJson({ status: 200, ok: true, json: async () => { throw new SyntaxError('Unexpected token'); } }), /Respons server tidak sesuai/);
    await assert.rejects(errors.readJson({ status: 419, ok: false, json: async () => { throw new SyntaxError(); } }), /Sesi formulir telah berakhir/);
});

test('validation payloads remain available for field errors and payment conflicts', async () => {
    const { errors } = load();
    const payload = { errors: { email: ['Email sudah terdaftar.'] } };
    const response = { status: 422, ok: false, json: async () => payload };
    const result = await errors.readJson(response, 'Periksa isian.', { allowError: true });
    assert.equal(result.errors.email[0], 'Email sudah terdaftar.');
    assert.equal(result.message, 'Email sudah terdaftar.');
    const conflict = { status: 409, ok: false, json: async () => ({ message: 'Meja sudah diperbarui.', data: { version: 2 } }) };
    assert.equal((await errors.readJson(conflict, '', { allowError: true })).data.version, 2);
});

test('native email validation uses Indonesian and clears after correcting input', () => {
    const { listeners } = load();
    const field = { type: 'email', validity: { typeMismatch: true }, setCustomValidity: value => { field.message = value; } };
    listeners.invalid({ target: field });
    assert.match(field.message, /alamat email yang valid/);
    listeners.input({ target: field });
    assert.equal(field.message, '');
});
