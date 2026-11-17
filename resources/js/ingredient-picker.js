/** A searchable ingredient choice; only a chosen ingredient ID is submitted. */
export function createIngredientPicker({ ingredients, name, id, label, selectedId = '' }) {
    const root = document.createElement('div');
    root.className = 'relative min-w-0 flex-1';
    root.dataset.ingredientPicker = '';

    const field = document.createElement('input');
    field.type = 'hidden';
    field.name = name;

    const input = document.createElement('input');
    input.type = 'text';
    input.id = id;
    input.required = true;
    input.autocomplete = 'off';
    input.spellcheck = false;
    input.placeholder = 'Cari bahan baku...';
    input.className = 'w-full pl-3 pr-9 py-2 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition';
    input.setAttribute('aria-label', label);
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-haspopup', 'listbox');
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('aria-controls', id + '-list');

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.tabIndex = -1;
    toggle.className = 'absolute right-1 top-1 p-1.5 text-caramel hover:text-espresso rounded-lg';
    toggle.setAttribute('aria-label', 'Buka pilihan bahan baku');
    toggle.innerHTML = '<svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>';

    const popup = document.createElement('div');
    popup.className = 'absolute left-0 right-0 top-full mt-1 z-30 p-1 bg-white border border-latte/60 rounded-xl shadow-lg';
    popup.hidden = true;

    const list = document.createElement('div');
    list.id = id + '-list';
    list.setAttribute('role', 'listbox');
    list.setAttribute('aria-label', label);
    list.className = 'max-h-48 overflow-y-auto overscroll-contain';

    const empty = document.createElement('p');
    empty.className = 'px-3 py-2 text-xs text-caramel-dark';
    empty.setAttribute('role', 'status');
    empty.textContent = 'Bahan baku tidak ditemukan.';
    empty.hidden = true;
    popup.append(list, empty);
    root.append(field, input, toggle, popup);

    let selected = ingredients.find(item => String(item.id) === String(selectedId)) ?? null;
    let matches = [];
    let activeIndex = -1;
    const text = item => `${item.nama_bahan} (${item.satuan})`;
    const normalize = value => String(value).normalize('NFD').replace(/\p{Diacritic}/gu, '').toLocaleLowerCase('id').trim();

    function restoreSelection() {
        field.value = selected ? String(selected.id) : '';
        input.value = selected ? text(selected) : '';
        input.setCustomValidity('');
    }

    function highlight(index, scroll = false) {
        activeIndex = index;
        Array.from(list.children).forEach((option, i) => option.classList.toggle('bg-cream', i === index));
        const option = list.children[index];
        if (option) {
            input.setAttribute('aria-activedescendant', option.id);
            if (scroll) option.scrollIntoView({ block: 'nearest' });
        } else {
            input.removeAttribute('aria-activedescendant');
        }
    }

    function close() {
        popup.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        restoreSelection();
    }

    function choose(item) {
        selected = item;
        restoreSelection();
        input.focus({ preventScroll: true });
        close();
        field.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function render(query) {
        const words = normalize(query).split(/\s+/).filter(Boolean);
        matches = ingredients.filter(item => words.every(word => normalize(text(item)).includes(word)));
        list.replaceChildren();
        matches.forEach((item, index) => {
            const option = document.createElement('button');
            option.type = 'button';
            option.tabIndex = -1;
            option.id = `${id}-option-${index}`;
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', String(String(selected?.id) === String(item.id)));
            option.className = 'block w-full text-left px-3 py-2 text-sm text-espresso rounded-lg hover:bg-cream whitespace-normal break-words';
            option.textContent = text(item);
            option.addEventListener('mousedown', event => event.preventDefault());
            option.addEventListener('click', () => choose(item));
            list.appendChild(option);
        });
        empty.hidden = matches.length > 0;
        highlight(matches.length ? 0 : -1);
    }

    function open() {
        if (input.disabled) return;
        popup.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        render(selected && input.value === text(selected) ? '' : input.value);
    }

    input.addEventListener('focus', () => {
        open();
        input.select();
    });
    input.addEventListener('click', () => { if (popup.hidden) open(); });
    input.addEventListener('input', () => {
        input.setCustomValidity('Pilih bahan baku dari daftar hasil pencarian.');
        open();
    });
    input.addEventListener('keydown', event => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (popup.hidden) {
                open();
                if (event.key === 'ArrowUp') highlight(matches.length - 1, true);
            } else if (matches.length) {
                highlight((activeIndex + (event.key === 'ArrowDown' ? 1 : -1) + matches.length) % matches.length, true);
            }
        } else if (event.key === 'Enter' && !popup.hidden) {
            event.preventDefault();
            if (matches[activeIndex]) choose(matches[activeIndex]);
        } else if (event.key === 'Escape') {
            event.preventDefault();
            close();
        } else if (event.key === 'Tab') {
            close();
        }
    });
    toggle.addEventListener('mousedown', event => event.preventDefault());
    toggle.addEventListener('click', () => {
        if (popup.hidden) {
            input.focus();
            open();
        } else {
            close();
        }
    });
    root.addEventListener('focusout', event => {
        if (!root.contains(event.relatedTarget)) close();
    });
    const outside = event => { if (!root.contains(event.target)) close(); };
    document.addEventListener('pointerdown', outside);
    restoreSelection();

    return {
        element: root,
        setDisabled(disabled) {
            close();
            input.disabled = disabled;
            field.disabled = disabled;
            toggle.disabled = disabled;
        },
        destroy() { document.removeEventListener('pointerdown', outside); },
    };
}
