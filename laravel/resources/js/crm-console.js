(function () {
  const consoleEl = document.getElementById('crm-console');
  if (!consoleEl) return;
  const baseUrl = consoleEl.dataset.url || '/admin/crm/api';

  const els = {
    msg: consoleEl.querySelector('[data-crm-message]'),
    search: consoleEl.querySelector('[data-crm-search]'),
    load: consoleEl.querySelector('[data-crm-load]'),
    summary: consoleEl.querySelector('[data-crm-summary]'),
    rows: consoleEl.querySelector('[data-crm-rows]'),
    prev: consoleEl.querySelector('[data-crm-prev]'),
    page: consoleEl.querySelector('[data-crm-page]'),
    next: consoleEl.querySelector('[data-crm-next]'),
    form: consoleEl.querySelector('[data-crm-form]'),
    perms: consoleEl.querySelector('[data-crm-permissions]'),
  };

  let page = 1;
  let canEdit = els.perms ? els.perms.dataset.edit === '1' : false;
  let canDelete = els.perms ? els.perms.dataset.delete === '1' : false;

  async function request(method, resource, body) {
    const url = new URL(`${baseUrl}/${resource}`, window.location.origin);
    const opts = { method, headers: { 'X-Requested-With': 'XMLHttpRequest' } };
    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) opts.headers['X-CSRF-TOKEN'] = csrf.getAttribute('content');
    if (method === 'GET' && body) {
      Object.entries(body).forEach(([k, v]) => url.searchParams.set(k, String(v)));
    } else if (body) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }
    const res = await fetch(url.toString(), opts);
    if (!res.ok) {
      const txt = await res.text().catch(() => res.statusText);
      throw new Error(`${res.status} ${txt}`);
    }
    return res.json();
  }

  function setMsg(text, type = 'info') {
    if (!els.msg) return;
    els.msg.textContent = text;
    els.msg.className = `p-2 rounded ${type === 'error' ? 'bg-red-50 border-red-200 text-red-800' : type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-blue-50 border-blue-200 text-blue-800'}`;
  }

  function renderSummary(data) {
    if (!els.summary) return;
    const d = data || {};
    els.summary.innerHTML = `
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-slate-100 p-3 rounded"><div class="text-xs text-slate-500 uppercase">${__('Total Customers')}</div><div class="text-xl font-semibold">${d.total_customers ?? 0}</div></div>
        <div class="bg-slate-100 p-3 rounded"><div class="text-xs text-slate-500 uppercase">${__('Open Leads')}</div><div class="text-xl font-semibold">${d.open_leads ?? 0}</div></div>
        <div class="bg-slate-100 p-3 rounded"><div class="text-xs text-slate-500 uppercase">${__('Appointments Booked')}</div><div class="text-xl font-semibold">${d.appointments_booked ?? 0}</div></div>
        <div class="bg-slate-100 p-3 rounded"><div class="text-xs text-slate-500 uppercase">${__('Won Revenue')}</div><div class="text-xl font-semibold">${d.won_revenue ?? 0}</div></div>
      </div>
    `;
  }

  function renderRows(data) {
    if (!els.rows) return;
    const list = Array.isArray(data) ? data : (data.data || []);
    if (!list.length) {
      els.rows.innerHTML = '<tr><td colspan="5" class="p-3 text-slate-500">${__("No records")}</td></tr>';
      return;
    }
    els.rows.innerHTML = list.map(c => `
      <tr class="border-b">
        <td class="p-2">${escapeHtml(c.name || '')}</td>
        <td class="p-2 font-mono text-sm">${escapeHtml(c.phone || '')}</td>
        <td class="p-2"><span class="px-2 py-1 rounded text-xs border ${statusColor(c.status)}">${escapeHtml(c.status || '')}</span></td>
        <td class="p-2">${escapeHtml(c.value != null ? parseFloat(c.value).toFixed(2) : '0.00')}</td>
        <td class="p-2">
          <button type="button" data-edit="${c.id}" class="mr-2 underline text-blue-600 hover:underline">${__('Edit')}</button>
          ${canDelete ? `<button type="button" data-del="${c.id}" class="underline text-red-600 hover:underline">${__('Delete')}</button>` : ''}
        </td>
      </tr>
    `).join('');

    els.rows.querySelectorAll('[data-edit]').forEach(btn => {
      btn.onclick = () => loadRecord(btn.dataset.edit);
    });
    els.rows.querySelectorAll('[data-del]').forEach(btn => {
      btn.onclick = () => delRecord(btn.dataset.del);
    });
  }

  function escapeHtml(t) {
    return String(t || '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  function statusColor(s) {
    const m = { new: 'border-blue-200 bg-blue-50 text-blue-700', qualified: 'border-yellow-200 bg-yellow-50 text-yellow-700', won: 'border-green-200 bg-green-50 text-green-700', lost: 'border-red-200 bg-red-50 text-red-700' };
    return m[s] || 'border-slate-200 bg-slate-50 text-slate-700';
  }

  async function loadRecord(id) {
    try {
      const res = await request('GET', `customers/${id}`);
      const c = res.data || {};
      els.form.id.value = c.id || '';
      els.form.name.value = c.name || '';
      els.form.phone.value = c.phone || '';
      els.form.email.value = c.email || '';
      els.form.company.value = c.company || '';
      els.form.status.value = c.status || 'new';
      els.form.value.value = c.value != null ? c.value : 0;
      els.form.notes.value = c.notes || '';
      setMsg(__('Record loaded. Edit and click Save.'), 'info');
    } catch (e) {
      setMsg(e.message, 'error');
    }
  }

  async function delRecord(id) {
    if (!confirm(__('Delete this CRM record?'))) return;
    try {
      await request('DELETE', `customers/${id}`);
      setMsg(__('Record deleted.'), 'success');
      loadPage(page);
    } catch (e) {
      setMsg(e.message, 'error');
    }
  }

  async function loadPage(p = 1, phone) {
    page = p;
    try {
      const data = await request('GET', 'customers', phone ? { phone, page } : { page });
      renderSummary(await request('GET', 'reports/summary'));
      renderRows(data);
      els.page.textContent = `${__('Page')} ${data.current_page ?? page} ${__('of')} ${data.last_page ?? page}`;
      els.prev.disabled = page <= 1;
      els.next.disabled = page >= (data.last_page || page);
    } catch (e) {
      setMsg(e.message, 'error');
    }
  }

  if (els.load) {
    els.load.onclick = () => loadPage(1, els.search ? els.search.value : '');
  }
  if (els.search) {
    els.search.onkeydown = (e) => { if (e.key === 'Enter') { e.preventDefault(); els.load.click(); } };
  }
  if (els.prev) els.prev.onclick = () => loadPage(page - 1, els.search ? els.search.value : '');
  if (els.next) els.next.onclick = () => loadPage(page + 1, els.search ? els.search.value : '');

  if (els.form) {
    els.form.onsubmit = async (e) => {
      e.preventDefault();
      const id = els.form.id.value;
      const body = {
        name: els.form.name.value,
        phone: els.form.phone.value,
        email: els.form.email.value || null,
        company: els.form.company.value || null,
        status: els.form.status.value,
        value: parseFloat(els.form.value.value || 0),
        notes: els.form.notes.value || null,
      };
      try {
        await request(id ? 'PUT' : 'POST', id ? `customers/${id}` : 'customers', body);
        setMsg(id ? __('Record updated.') : __('Record created.'), 'success');
        els.form.reset();
        els.form.id.value = '';
        loadPage(page, els.search ? els.search.value : '');
      } catch (e) {
        setMsg(e.message, 'error');
      }
    };
  }

  loadPage(1);
})();
