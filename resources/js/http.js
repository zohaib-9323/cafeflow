// Small fetch wrapper shared by the site and admin bundles.
const token = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

export async function request(url, { method = 'GET', body = null } = {}) {
    const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token() };
    let payload = body;
    if (body && !(body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
        payload = JSON.stringify(body);
    }
    // Laravel reads _method for PATCH/DELETE sent as FormData.
    let verb = method;
    if (payload instanceof FormData && !['GET', 'POST'].includes(method)) {
        payload.append('_method', method);
        verb = 'POST';
    }

    const response = await fetch(url, { method: verb, headers, body: payload, credentials: 'same-origin' });
    let json = {};
    try { json = await response.json(); } catch { /* non-JSON error page */ }

    if (!response.ok) {
        const first = json.errors ? Object.values(json.errors).flat()[0] : null;
        const error = new Error(first || json.message || `Request failed (${response.status})`);
        error.status = response.status;
        error.payload = json;
        throw error;
    }
    return json;
}

export function toast(message, tone = 'success') {
    const host = document.querySelector('[data-toasts]');
    if (!host || !window.bootstrap) return;
    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${tone === 'error' ? 'danger' : tone} border-0`;
    el.setAttribute('role', tone === 'error' ? 'alert' : 'status');
    el.innerHTML = '<div class="d-flex"><div class="toast-body"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
    el.querySelector('.toast-body').textContent = message; // textContent: never inject HTML
    host.appendChild(el);
    const t = new window.bootstrap.Toast(el, { delay: 3500 });
    el.addEventListener('hidden.bs.toast', () => el.remove());
    t.show();
}

export const money = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value) || 0);
