@once
<style>
#floor-canvas { container-type:inline-size; }
.plan-element { position:absolute; display:flex; align-items:center; justify-content:center; box-sizing:border-box; border:1px solid var(--color-espresso-light); background:transparent; color:var(--color-espresso); border-radius:0; padding:0; text-align:center; line-height:1.2; overflow:visible; pointer-events:none; transform-origin:center; }
.plan-element .plan-element-label { max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:pre-line; pointer-events:none; }
.plan-element.is-editable { pointer-events:auto; cursor:grab; touch-action:none; }
.plan-element.is-selected { outline:2px solid var(--color-caramel); outline-offset:4px; }
.plan-element.is-locked { pointer-events:none; }
.plan-element--ellipse,.plan-element--plant { border-radius:50%; }
.plan-element--plant { color:var(--color-caramel-dark); border-color:var(--color-caramel); }
.plan-element--area,.plan-element--parking { align-items:flex-start; justify-content:flex-start; padding:8px; border-color:var(--color-caramel); }
.plan-element--area .plan-element-label,.plan-element--parking .plan-element-label { font-size:.83em; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
.plan-element--parking { background:transparent; }
.plan-element--text { border:0; background:transparent !important; padding:0; }
.plan-element--line,.plan-element--wall { border:1px solid var(--color-espresso-light); padding:0; border-radius:0; background:white !important; }
.plan-element--line .plan-element-label,.plan-element--wall .plan-element-label { background:#fff; padding:2px 5px; transform:translateY(-100%); }
.plan-element--counter,.plan-element--window,.plan-element--entrance,.plan-element--toilet { background:white; border:1px solid var(--color-espresso-light); color:var(--color-espresso); }
.plan-element--stairs { background:repeating-linear-gradient(0deg,white 0,white 9px,var(--color-latte) 9px,var(--color-latte) 10px); }
.plan-element[data-fill=stone],.plan-element[data-fill=sand],.plan-element[data-fill=sage] { background-color:var(--color-cream-light); }
.plan-element[data-fill=latte] { background-color:var(--color-latte-light); }
.plan-element[data-fill=cream] { background-color:var(--color-cream-light); }
.plan-element[data-fill=espresso] { background-color:var(--color-espresso); color:var(--color-cream); }
.plan-resize { position:absolute; right:-6px; bottom:-6px; width:12px; height:12px; border:2px solid white; background:var(--color-espresso); border-radius:2px; cursor:nwse-resize; z-index:2; }
.plan-furniture { position:absolute; display:flex; align-items:center; justify-content:center; box-sizing:border-box; border:1px solid var(--color-espresso-light); background:white; color:var(--color-espresso); font-size:clamp(6px,1cqw,12px); font-weight:400; line-height:1; pointer-events:auto; }
.plan-furniture.is-round { border-radius:50%; }
.plan-furniture.is-sofa { font-size:clamp(7px,1.1cqw,14px); }
</style>
<script>
window.CiksFloorPlan = (() => {
    const types = {
        rectangle: { label: 'Kotak', width: 20, height: 16 },
        ellipse: { label: 'Lingkaran', width: 14, height: 20 },
        line: { label: 'Garis', width: 24, height: .5 },
        wall: { label: 'Dinding', width: 28, height: 1 },
        text: { label: 'Teks area', width: 22, height: 7 },
        area: { label: 'INDOOR', width: 40, height: 42, fill: 'stone' },
        parking: { label: 'PARKIR', width: 35, height: 24, fill: 'stone' },
        counter: { label: 'Kasir / Bar', width: 18, height: 8, fill: 'sand' },
        entrance: { label: 'Pintu masuk', width: 14, height: 6 },
        window: { label: 'Jendela', width: 3, height: 20 },
        toilet: { label: 'WC', width: 10, height: 12, fill: 'stone' },
        plant: { label: '', width: 5, height: 7, fill: 'sage' },
        stairs: { label: 'Tangga', width: 12, height: 16 },
    };
    function render(marker, options = {}) {
        const type = Object.hasOwn(types, marker.type) ? marker.type : 'rectangle';
        const defaults = types[type];
        const element = document.createElement('div');
        element.className = 'plan-element plan-element--' + type;
        element.dataset.elementType = type;
        element.dataset.fill = ['none', 'latte', 'cream', 'espresso', 'stone', 'sand', 'sage'].includes(marker.fill) ? marker.fill : (defaults.fill ?? 'none');
        element.style.left = Number(marker.position_x ?? 50) + '%';
        element.style.top = Number(marker.position_y ?? 50) + '%';
        element.style.width = Number(marker.width ?? defaults.width) + '%';
        element.style.height = Number(marker.height ?? defaults.height) + '%';
        const fontSize = Number(marker.font_size ?? 12);
        element.style.fontSize = 'clamp(7px,' + (fontSize / 7) + 'cqw,' + fontSize + 'px)';
        element.style.transform = 'translate(-50%, -50%) rotate(' + Number(marker.rotation ?? 0) + 'deg)';
        const label = document.createElement('span');
        label.className = 'plan-element-label';
        label.textContent = marker.label ?? '';
        label.hidden = !label.textContent;
        element.append(label);
        if (options.editable) {
            element.classList.add('is-editable');
            element.tabIndex = 0;
            element.setAttribute('role', 'button');
            element.setAttribute('aria-label', marker.label || defaults.label || type);
            element.addEventListener('click', event => { event.stopPropagation(); options.select?.(); });
            element.addEventListener('keydown', event => { if (event.key === 'Enter') options.select?.(); });
            element.addEventListener('pointerdown', event => options.drag?.(event, element));
        }
        if (options.selected) {
            element.classList.add('is-selected');
            const handle = document.createElement('span');
            handle.className = 'plan-resize';
            handle.addEventListener('pointerdown', event => { event.stopPropagation(); options.resize?.(event, element); });
            element.append(handle);
        }
        return element;
    }
    function appendSeating(parent, table, config = {}) {
        const style = config.table_seating?.['id:'+table.id] ?? config.table_seating?.[table.code] ?? (table.shape === 'round' && Number(table.capacity) === 2 ? 'round_pair' : 'standard');
        parent.dataset.seatingStyle = style;
        const seat = (left, top, width, height, kind = '', label = '') => {
            const node = document.createElement('span'); node.className = 'plan-furniture' + (kind ? ' is-' + kind : '');
            node.setAttribute('aria-hidden', 'true'); node.textContent = label;
            Object.assign(node.style, { left:left+'%', top:top+'%', width:width+'%', height:height+'%' });
            parent.append(node);
        };
        if (style === 'sofa_pair') { seat(-104,-6,86,111,'sofa','sofa'); seat(120,-6,86,111,'sofa','sofa'); return; }
        if (style === 'bench') { seat(16,125,41,23); return; }
        if (style === 'round_pair') { seat(-74,23,53,53,'round'); seat(121,23,53,53,'round'); return; }
        if (table.shape === 'round') {
            const count = Math.max(1, Math.min(20, Number(table.capacity) || 1));
            for (let i=0;i<count;i++) { const angle=Math.PI*2*i/count; seat(35+Math.cos(angle)*80,35+Math.sin(angle)*80,30,30,'round'); }
            return;
        }
        const count = Math.max(1, Math.min(20, Number(table.capacity) || 1));
        const topCount = Math.ceil(count/2); const bottomCount = Math.floor(count/2);
        for (let i=0;i<topCount;i++) seat((i+.5)/topCount*100-15,-18,30,8);
        for (let i=0;i<bottomCount;i++) seat((i+.5)/bottomCount*100-15,111,30,8);
    }
    function applyBackground(canvas, layout) {
        const background=layout.background_image;
        const hasImage=Boolean(background?.url);
        const grid=layout.background_config?.show_grid!==false;
        canvas.classList.toggle('has-background-image',hasImage);
        if(!hasImage){
            for(const property of ['background-image','background-size','background-position','background-repeat']) canvas.style.removeProperty(property);
            return;
        }
        const image='url('+JSON.stringify(background.url)+')';
        canvas.style.backgroundImage=grid?'radial-gradient(var(--color-latte) .7px,transparent .7px), '+image:image;
        canvas.style.backgroundSize=grid?'20px 20px, contain':'contain';
        canvas.style.backgroundPosition=grid?'0 0, center':'center';
        canvas.style.backgroundRepeat=grid?'repeat, no-repeat':'no-repeat';
    }
    return { types, render, appendSeating, applyBackground };
})();
</script>
@endonce
