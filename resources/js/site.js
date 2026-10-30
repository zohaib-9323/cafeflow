import * as bootstrap from 'bootstrap';
import { request, toast, money } from './http';

window.bootstrap = bootstrap;

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

function setCartCount(count) {
    $$('[data-cart-count]').forEach((el) => {
        el.textContent = count;
        el.classList.toggle('d-none', !count);
        el.closest('a')?.setAttribute('aria-label', `Cart, ${count} items`);
    });
}

// ---------- add to cart (AJAX, falls back to a normal POST without JS) ----------
$$('form[data-cart-add]').forEach((form) => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const button = form.querySelector('[type=submit]');
        button.disabled = true;
        try {
            const res = await request(form.action, { method: 'POST', body: new FormData(form) });
            setCartCount(res.data.item_count);
            toast(res.message);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            button.disabled = false;
        }
    });
});

// ---------- product page: live price preview (display only; the server prices the order) ----------
const calc = $('[data-price-calc]');
if (calc) {
    const base = Number(calc.dataset.base);
    const out = $('[data-price-total]');
    const update = () => {
        const variation = $('input[name="variation_id"]:checked', calc);
        const addons = $$('input[name="addon_ids[]"]:checked', calc).reduce((s, el) => s + Number(el.dataset.price), 0);
        const qty = Math.max(1, Number($('input[name="quantity"]', calc)?.value) || 1);
        out.textContent = money((base + Number(variation?.dataset.adjust || 0) + addons) * qty);
    };
    calc.addEventListener('change', update);
    calc.addEventListener('input', update);
    update();
}

// ---------- quantity steppers ----------
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-qty-step]');
    if (!btn) return;
    const input = btn.parentElement.querySelector('input[type=number]');
    const next = Math.min(Number(input.max || 99), Math.max(Number(input.min || 0), Number(input.value) + Number(btn.dataset.qtyStep)));
    input.value = next;
    input.dispatchEvent(new Event('change', { bubbles: true }));
});

// ---------- product gallery ----------
$$('[data-gallery-thumb]').forEach((thumb) => {
    thumb.addEventListener('click', () => {
        const main = $('[data-gallery-main]');
        main.src = thumb.dataset.src;
        main.alt = thumb.dataset.alt;
        $$('[data-gallery-thumb]').forEach((t) => { t.classList.toggle('active', t === thumb); t.setAttribute('aria-pressed', t === thumb); });
    });
});

// ---------- cart page: AJAX quantity / remove with live totals ----------
function renderSummary(quote) {
    for (const key of ['subtotal', 'discount_total', 'tax_total', 'delivery_fee', 'total']) {
        $$(`[data-summary="${key}"]`).forEach((el) => { el.textContent = (key === 'discount_total' && Number(quote[key]) ? '-' : '') + money(quote[key]); });
    }
    $$('[data-summary-row="discount_total"]').forEach((el) => el.classList.toggle('d-none', !Number(quote.discount_total)));
    $$('[data-summary-row="delivery_fee"]').forEach((el) => el.classList.toggle('d-none', quote.fulfillment_type !== 'delivery'));
    quote.lines.forEach((line) => {
        const el = $(`[data-line-total="${CSS.escape(line.key)}"]`);
        if (el) el.textContent = money(line.line_total);
    });
    setCartCount(quote.item_count);
    const checkout = $('[data-checkout-button]');
    if (checkout) checkout.classList.toggle('disabled', !quote.can_checkout);
    const issues = $('[data-cart-issues]');
    if (issues) {
        issues.replaceChildren(...quote.issues.map((msg) => Object.assign(document.createElement('li'), { textContent: msg })));
        issues.closest('[data-cart-issues-box]')?.classList.toggle('d-none', !quote.issues.length);
    }
    if (!quote.lines.length) window.location.reload(); // show the empty state
}

$$('[data-cart-line]').forEach((row) => {
    const key = row.dataset.cartLine;
    const qty = $('input[name="quantity"]', row);
    let timer;
    qty?.addEventListener('change', () => {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            try {
                const res = await request(row.dataset.updateUrl, { method: 'PATCH', body: { quantity: Number(qty.value) } });
                if (Number(qty.value) === 0) row.remove();
                renderSummary(res.data);
            } catch (err) { toast(err.message, 'error'); }
        }, 300);
    });
    $('[data-cart-remove]', row)?.addEventListener('click', async (e) => {
        e.preventDefault();
        try {
            const res = await request(row.dataset.updateUrl, { method: 'DELETE' });
            row.remove();
            renderSummary(res.data);
            toast(res.message);
        } catch (err) { toast(err.message, 'error'); }
    });
});

// ---------- fulfillment choice (cart + checkout) ----------
$$('input[name="fulfillment_type"][data-fulfillment-url]').forEach((radio) => {
    radio.addEventListener('change', async () => {
        $$('[data-delivery-only]').forEach((el) => {
            el.classList.toggle('d-none', radio.value !== 'delivery');
            $$('input,select,textarea', el).forEach((f) => { if (f.dataset.requiredWhenDelivery !== undefined) f.required = radio.value === 'delivery'; });
        });
        try {
            await request(radio.dataset.fulfillmentUrl, { method: 'POST', body: { fulfillment_type: radio.value } });
            const res = await request(radio.dataset.summaryUrl);
            renderSummary(res.data);
        } catch (err) { toast(err.message, 'error'); }
    });
});

// ---------- checkout: fill delivery fields from a saved address ----------
$('[data-saved-address]')?.addEventListener('change', (e) => {
    const opt = e.target.selectedOptions[0];
    if (!opt?.value) return;
    const data = JSON.parse(opt.dataset.address);
    for (const [field, value] of Object.entries(data)) {
        const input = document.getElementById(`f_delivery_${field}`);
        if (input) input.value = value ?? '';
    }
});

// Prevent double submission of important forms.
$$('form[data-once]').forEach((form) => form.addEventListener('submit', () => {
    form.querySelectorAll('[type=submit]').forEach((b) => { b.disabled = true; b.dataset.label = b.innerHTML; b.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Processing…'; });
}));

// ---------- order tracking: poll for status changes ----------
const tracker = $('[data-order-poll]');
if (tracker) {
    const current = tracker.dataset.status;
    const poll = async () => {
        if (document.hidden) return;
        try {
            const res = await request(tracker.dataset.orderPoll);
            if (res.data.status !== current || res.data.payment_status !== tracker.dataset.paymentStatus) window.location.reload();
        } catch { /* transient; try again next tick */ }
    };
    setInterval(poll, 15000);
}
