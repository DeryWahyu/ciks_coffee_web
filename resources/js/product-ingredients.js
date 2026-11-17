import { createIngredientPicker } from './ingredient-picker';

const configElement = document.getElementById('product-ingredient-config');
if (configElement) {
    const { ingredients, rows } = JSON.parse(configElement.textContent);
    const pickers = new Map();
    let nextIndex = 0;

    function syncSections() {
        for (const variant of ['default', 'base', 'lite']) {
            const section = document.getElementById('ingredients-section-' + variant);
            const disabled = section.classList.contains('hidden');
            section.querySelectorAll('input, select, button').forEach(field => { field.disabled = disabled; });
            for (const { picker, row } of pickers.values()) {
                if (section.contains(row)) picker.setDisabled(disabled);
            }
        }
    }

    window.addIngredientRow = function (variant, selectedId = '', selectedQty = '') {
        const index = nextIndex++;
        const prefix = { default: 'ingredients', base: 'ingredients_base', lite: 'ingredients_lite' }[variant];
        const variantLabel = { default: 'produk', base: 'Base', lite: 'Lite' }[variant];
        const container = document.getElementById('ingredients-container-' + variant);
        const row = document.createElement('div');
        row.className = 'flex items-start gap-2 sm:gap-3';
        row.id = 'ingredient-row-' + index;
        const picker = createIngredientPicker({
            ingredients, name: `${prefix}[${index}][id]`, id: 'ingredient-choice-' + index,
            label: `Bahan baku ${index + 1} (${variantLabel})`, selectedId,
        });
        const quantityWrap = document.createElement('div');
        quantityWrap.className = 'w-24 sm:w-32 shrink-0';
        const quantity = document.createElement('input');
        quantity.type = 'number';
        quantity.name = `${prefix}[${index}][quantity]`;
        quantity.value = selectedQty;
        quantity.required = true;
        quantity.min = '0.01';
        quantity.step = '0.01';
        quantity.placeholder = 'Takaran';
        quantity.setAttribute('aria-label', `Takaran bahan ${index + 1} (${variantLabel})`);
        quantity.className = 'w-full px-3 py-2 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition';
        quantityWrap.appendChild(quantity);
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'p-2 rounded-lg text-red-400 hover:bg-red-50 hover:text-red-600 transition shrink-0';
        remove.setAttribute('aria-label', `Hapus bahan ${index + 1} (${variantLabel})`);
        remove.innerHTML = '<svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>';
        remove.addEventListener('click', () => {
            picker.destroy();
            pickers.delete(index);
            row.remove();
        });
        row.append(picker.element, quantityWrap, remove);
        container.appendChild(row);
        pickers.set(index, { picker, row });
        syncSections();
    };

    window.CiksProductIngredients = { syncSections };
    for (const variant of ['default', 'base', 'lite']) {
        for (const item of rows[variant]) window.addIngredientRow(variant, item.id, item.quantity);
    }
    window.toggleLitePrice(document.getElementById('category_id'));
}
