<script>
(() => {
    const statuses = {
        401: 'Sesi Anda telah berakhir. Silakan masuk kembali untuk melanjutkan.',
        403: 'Anda tidak memiliki izin untuk melakukan tindakan ini.',
        404: 'Data yang diminta tidak ditemukan. Muat ulang lalu periksa kembali.',
        405: 'Tindakan ini tidak tersedia. Muat ulang halaman lalu coba lagi.',
        408: 'Waktu tunggu habis. Periksa koneksi lalu coba lagi.',
        413: 'Ukuran unggahan terlalu besar. Gunakan berkas yang lebih kecil.',
        419: 'Sesi formulir telah berakhir. Muat ulang halaman lalu coba lagi. Jika sedang membayar, periksa status pesanan terlebih dahulu.',
        429: 'Terlalu banyak percobaan. Tunggu beberapa saat sebelum mencoba lagi.',
        500: 'Terjadi kendala pada server. Coba lagi beberapa saat.',
        502: 'Layanan sedang tidak tersedia. Coba lagi beberapa saat.',
        503: 'Layanan sedang tidak tersedia. Coba lagi beberapa saat.',
        504: 'Waktu tunggu server habis. Coba lagi beberapa saat.',
    };
    const technical = /validation\.|SQLSTATE|No query results|App\\Models\\|<\/?(?:html|body|!doctype)|stack trace|Exception|Unexpected token|is not valid JSON|Cannot read|is not a function|is not defined/i;
    function message(error, fallback = 'Permintaan belum dapat diproses. Silakan coba lagi.') {
        const text = typeof error === 'string' ? error.trim() : String(error?.message ?? '').trim();
        if (/^Unauthenticated\.?$/i.test(text)) return statuses[401];
        if (/^Unauthorized\.?$/i.test(text)) return statuses[403];
        if (/Too Many (Attempts|Requests)/i.test(text)) return statuses[429];
        if (/CSRF token mismatch|Page Expired/i.test(text)) return statuses[419];
        if (/Failed to fetch|NetworkError|Load failed|network request failed/i.test(text) || error?.name === 'AbortError' || error?.name === 'TimeoutError') {
            return 'Koneksi ke server terputus. Periksa koneksi internet lalu coba lagi.';
        }
        if (error instanceof SyntaxError || technical.test(text)) return fallback;
        return text || fallback;
    }
    function fromPayload(payload, status, fallback) {
        if (statuses[status]) return statuses[status];
        const errors = payload?.errors;
        const first = errors && typeof errors === 'object' ? Object.values(errors).flat().find(value => typeof value === 'string') : null;
        return message(first || payload?.message, fallback);
    }
    async function readJson(response, fallback, { allowError = false } = {}) {
        if (response.redirected && /\/login(?:[?#]|$)/.test(response.url)) throw new Error(statuses[401]);
        let payload;
        try { payload = await response.json(); }
        catch { throw new Error(statuses[response.status] || 'Respons server tidak sesuai. Muat ulang halaman lalu coba lagi. Jika sedang membayar, periksa status pesanan terlebih dahulu.'); }
        if (!payload || typeof payload !== 'object') throw new Error('Respons server tidak sesuai. Muat ulang halaman lalu coba lagi.');
        if (!response.ok) {
            const text = fromPayload(payload, response.status, fallback);
            if (!allowError) throw new Error(text);
            payload.message = text;
        }
        return payload;
    }
    window.CiksErrors = { message, readJson, fromPayload };

    // Keep native validity enabled, with Indonesian messages on every browser.
    document.addEventListener('invalid', event => {
        const input = event.target;
        if (typeof input.setCustomValidity !== 'function') return;
        const validity = input.validity;
        let text = '';
        if (validity.valueMissing) text = 'Bagian ini wajib diisi.';
        else if (validity.typeMismatch) text = input.type === 'email' ? 'Masukkan alamat email yang valid. Contoh: nama@email.com.' : 'Masukkan alamat situs yang valid.';
        else if (validity.badInput) text = 'Masukkan angka yang valid.';
        else if (validity.rangeUnderflow) text = 'Nilai minimal adalah ' + input.min + '.';
        else if (validity.rangeOverflow) text = 'Nilai maksimal adalah ' + input.max + '.';
        else if (validity.tooShort) text = 'Isian minimal ' + input.minLength + ' karakter.';
        else if (validity.tooLong) text = 'Isian maksimal ' + input.maxLength + ' karakter.';
        else if (validity.patternMismatch) text = input.dataset.validationMessage || 'Format isian belum sesuai. Periksa kembali isian Anda.';
        else if (validity.stepMismatch) text = input.step === '1' || !input.step ? 'Masukkan bilangan bulat.' : 'Masukkan angka dengan kelipatan ' + input.step + '.';
        if (text) input.setCustomValidity(text);
    }, true);
    for (const type of ['input', 'change']) document.addEventListener(type, event => {
        const input = event.target;
        input.setCustomValidity?.('');
        if (input.type === 'radio' && input.form) {
            for (const field of input.form.elements) {
                if (field.type === 'radio' && field.name === input.name) field.setCustomValidity('');
            }
        }
    }, true);
})();
</script>
