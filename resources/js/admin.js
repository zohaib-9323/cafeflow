import * as bootstrap from 'bootstrap';
import Chart from 'chart.js/auto';
import { request, toast, money } from './http';

window.bootstrap = bootstrap;
const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

// ---------- sidebar: collapse on desktop (remembered), off-canvas on mobile ----------
const desktop = () => window.matchMedia('(min-width: 992px)').matches;
if (localStorage.getItem('cf.sidebar') === 'collapsed') document.body.classList.add('sidebar-collapsed');
$('[data-sidebar-toggle]')?.addEventListener('click', (e) => {
    if (desktop()) {
        const collapsed = document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('cf.sidebar', collapsed ? 'collapsed' : 'open');
        e.currentTarget.setAttribute('aria-expanded', String(!collapsed));
    } else {
        const open = document.body.classList.toggle('sidebar-open');
        e.currentTarget.setAttribute('aria-expanded', String(open));
    }
});
$('[data-sidebar-close]')?.addEventListener('click', () => document.body.classList.remove('sidebar-open'));
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') document.body.classList.remove('sidebar-open'); });

// ---------- confirmation modal for destructive forms ----------
const modalEl = $('#confirmModal');
const confirmModal = modalEl ? new bootstrap.Modal(modalEl) : null;
let pendingForm = null;
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!form.matches('form[data-confirm]') || form.dataset.confirmed) return;
    e.preventDefault();
    pendingForm = form;
    $('[data-confirm-message]', modalEl).textContent = form.dataset.confirm;
    const ok = $('[data-confirm-ok]', modalEl);
    ok.textContent = form.dataset.confirmLabel || 'Confirm';
    ok.className = `btn btn-${form.dataset.confirmTone || 'danger'}`;
    confirmModal.show();
}, true);
$('[data-confirm-ok]')?.addEventListener('click', () => {
    if (!pendingForm) return;
    pendingForm.dataset.confirmed = '1';
    confirmModal.hide();
    pendingForm.requestSubmit();
});

// ---------- charts (config comes from data-chart JSON rendered by Blade) ----------
const palette = ['#6f4e37', '#b08463', '#6b8f71', '#5b7083', '#c9a227', '#9c6b98', '#d9c7b0', '#3f7d58'];
Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
Chart.defaults.color = '#6d635b';
Chart.defaults.plugins.legend.labels.usePointStyle = true;

$$('canvas[data-chart]').forEach((canvas) => {
    const cfg = JSON.parse(canvas.dataset.chart);
    const isPie = ['doughnut', 'pie'].includes(cfg.type);
    const datasets = cfg.datasets.map((ds, i) => {
        const color = ds.color || palette[i % palette.length];
        return {
            borderWidth: isPie ? 0 : 2,
            tension: 0.3,
            pointRadius: cfg.labels.length > 40 ? 0 : 3,
            ...ds,
            borderColor: isPie ? '#fff' : color,
            backgroundColor: isPie ? (cfg.colors || palette) : ds.type === 'bar' || cfg.type === 'bar' ? `${color}cc` : `${color}22`,
            fill: ds.fill ?? (!isPie && cfg.type === 'line' && i === 0),
        };
    });
    const moneyAxis = cfg.money ?? [];
    new Chart(canvas, {
        type: cfg.type,
        data: { labels: cfg.labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: isPie || datasets.length > 1, position: isPie ? 'right' : 'top' },
                tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label || ctx.label}: ${moneyAxis.includes(ctx.dataset.yAxisID || 'y') && !isPie ? money(ctx.parsed.y) : (isPie ? ctx.parsed : ctx.parsed.y)}` } },
            },
            scales: isPie ? {} : {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } },
                y: { beginAtZero: true, grid: { color: '#efe7dc' }, ticks: { callback: (v) => (moneyAxis.includes('y') ? money(v) : v) } },
                ...(datasets.some((d) => d.yAxisID === 'y1') ? { y1: { beginAtZero: true, position: 'right', grid: { display: false } } } : {}),
            },
        },
    });
    canvas.setAttribute('role', 'img');
});

// ---------- order status buttons (AJAX) ----------
document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (!form.matches('form[data-status-form]')) return;
    e.preventDefault();
    const buttons = $$('button', form);
    buttons.forEach((b) => { b.disabled = true; });
    try {
        const res = await request(form.action, { method: 'PATCH', body: new FormData(form) });
        toast(res.message);
        bootstrap.Modal.getInstance(form.closest('.modal'))?.hide();
        setTimeout(() => window.location.reload(), 600); // refresh timeline, payments and stock movements
    } catch (err) {
        toast(err.message, 'error');
        buttons.forEach((b) => { b.disabled = false; });
    }
});

// ---------- AJAX filters (orders list) ----------
$$('form[data-ajax-filter]').forEach((form) => {
    const target = $(form.dataset.ajaxFilter);
    let timer;
    const run = async () => {
        const params = new URLSearchParams(new FormData(form));
        [...params.keys()].forEach((k) => { if (!params.get(k)) params.delete(k); });
        const url = `${form.action}?${params}`;
        target.setAttribute('aria-busy', 'true');
        try {
            params.set('partial', '1');
            const res = await request(`${form.action}?${params}`);
            target.innerHTML = res.html; // server-rendered Blade partial (escaped by Blade)
            history.replaceState(null, '', url);
            $('[data-result-count]')?.replaceChildren(document.createTextNode(`${res.total} orders`));
        } catch (err) { toast(err.message, 'error'); }
        target.removeAttribute('aria-busy');
    };
    form.addEventListener('input', (e) => { if (e.target.type === 'search') { clearTimeout(timer); timer = setTimeout(run, 350); } });
    form.addEventListener('change', (e) => { if (e.target.type !== 'search') run(); });
    form.addEventListener('submit', (e) => { e.preventDefault(); run(); });
});

// ---------- repeatable form rows (variations, add-ons, recipe, opening hours) ----------
$$('[data-repeater]').forEach((wrap) => {
    const list = $('[data-repeater-list]', wrap);
    const template = $('template', wrap);
    let index = Number(wrap.dataset.next || list.children.length);
    $('[data-repeater-add]', wrap)?.addEventListener('click', () => {
        const html = template.innerHTML.replaceAll('__INDEX__', index++);
        list.insertAdjacentHTML('beforeend', html);
        list.lastElementChild?.querySelector('input,select')?.focus();
    });
    wrap.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-repeater-remove]');
        if (btn) btn.closest('[data-repeater-row]').remove();
    });
});

// ---------- reorder lists (FAQs) ----------
$$('[data-sortable]').forEach((list) => {
    const save = async () => {
        const ids = $$('[data-id]', list).map((li) => Number(li.dataset.id));
        try { toast((await request(list.dataset.sortable, { method: 'POST', body: { ids } })).message); } catch (err) { toast(err.message, 'error'); }
    };
    list.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-move]');
        if (!btn) return;
        const row = btn.closest('[data-id]');
        if (btn.dataset.move === 'up' && row.previousElementSibling) row.parentNode.insertBefore(row, row.previousElementSibling);
        else if (btn.dataset.move === 'down' && row.nextElementSibling) row.parentNode.insertBefore(row.nextElementSibling, row);
        else return;
        btn.focus();
        save();
    });
});

// ---------- stock adjustment modal ----------
$$('[data-adjust]').forEach((btn) => btn.addEventListener('click', () => {
    const modal = $('#adjustModal');
    const form = $('form', modal);
    form.action = btn.dataset.adjust;
    $('[data-adjust-name]', modal).textContent = btn.dataset.name;
    $('[data-adjust-unit]', modal).textContent = btn.dataset.unit;
    $('[data-adjust-current]', modal).textContent = btn.dataset.current;
}));
$$('#adjustModal select[name="type"]').forEach((select) => {
    const hint = $('[data-adjust-hint]', select.form);
    const hints = { restock: 'Amount received (added to stock).', waste: 'Amount thrown away (removed from stock).', adjustment: 'The new total you counted on the shelf.' };
    const update = () => { hint.textContent = hints[select.value] || ''; };
    select.addEventListener('change', update);
    update();
});

// ---------- image preview + slug helper ----------
$$('input[type=file][data-preview]').forEach((input) => input.addEventListener('change', () => {
    const img = $(input.dataset.preview);
    const file = input.files?.[0];
    if (img && file) { img.src = URL.createObjectURL(file); img.classList.remove('d-none'); }
}));
$$('[data-slug-source]').forEach((slug) => {
    const source = $(slug.dataset.slugSource);
    let touched = Boolean(slug.value);
    slug.addEventListener('input', () => { touched = true; });
    source?.addEventListener('input', () => {
        if (!touched) slug.value = source.value.toLowerCase().normalize('NFKD').replace(/[^\w\s-]/g, '').trim().replace(/[\s_]+/g, '-');
    });
});

// Date range preset: custom shows the date inputs, presets apply immediately.
$$('select[data-range-select]').forEach((select) => select.addEventListener('change', () => {
    select.form.querySelector('[data-custom]').classList.toggle('d-none', select.value !== 'custom');
    if (select.value !== 'custom') select.form.submit();
}));

// Print buttons
$$('[data-print]').forEach((b) => b.addEventListener('click', () => window.print()));

// Copy-to-clipboard buttons (media library)
$$('[data-copy]').forEach((b) => b.addEventListener('click', async () => {
    try { await navigator.clipboard.writeText(b.dataset.copy); toast('Link copied.'); } catch { toast('Copy failed. Select the link manually.', 'error'); }
}));
