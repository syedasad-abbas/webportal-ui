(() => {
    const root = document.getElementById('crm-console');
    if (!root) return;
    const get = name => root.querySelector(`[data-crm-${name}]`);
    const form = get('form'), permissions = get('permissions').dataset;
    let page = 1, last = 1, busy = false;
    const request = async (path, method = 'GET', body) => {
        const response = await fetch(`${root.dataset.url}/${path}`, { method, credentials: 'same-origin',
            headers: {'Accept':'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
            ...(body ? {body:JSON.stringify(body)} : {}), signal:AbortSignal.timeout(10000) });
        const data = await response.json();
        if (!response.ok) throw Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'CRM request failed');
        return data;
    };
    const action = async fn => {
        if (busy) return;
        busy = true; get('message').textContent = 'Working…';
        try { await fn(); get('message').textContent = 'CRM updated.'; }
        catch (error) { get('message').textContent = error.message; }
        finally { busy = false; }
    };
    const load = async () => {
        const phone = get('search').value.replace(/\D/g, '');
        const result = await request(`customers?page=${page}${phone ? '&phone='+encodeURIComponent(phone) : ''}`);
        if (!Array.isArray(result.data)) throw Error('Invalid CRM records');
        last = Number(result.last_page || 1);
        get('rows').replaceChildren();
        for (const customer of result.data) {
            const row = document.createElement('tr');
            for (const key of ['name','phone','status','value']) {
                const cell = document.createElement('td'); cell.textContent = customer[key] ?? ''; cell.className = 'p-2 border-b'; row.append(cell);
            }
            const cell = document.createElement('td');
            const button = (text, fn) => { const b = document.createElement('button'); b.type = 'button'; b.textContent = text; b.className = 'p-2 underline'; b.addEventListener('click', fn); cell.append(b); };
            if (permissions.edit === '1' && form) button('Edit', () => { for (const key of ['id','name','phone','email','company','status','value','notes']) form.elements.namedItem(key).value = customer[key] ?? ''; form.querySelectorAll('[data-crm-intake]').forEach(input => { input.value = customer.intake?.[input.dataset.crmIntake] ?? ''; });
                form.querySelectorAll('[data-crm-review]').forEach(input => { input.value = customer.review?.[input.dataset.crmReview] ?? ''; });
                form.querySelector('[data-crm-accounts]').value = (customer.review?.accounts || []).map(a=>`${a.creditor} | ${a.balance}`).join('\n');
                get('verified').checked = false; if (get('is-demo')) get('is-demo').checked = false; });
            if (permissions.delete === '1') button('Delete', () => { if (confirm('Delete this CRM customer?')) void action(async () => { await request(`customers/${customer.id}`, 'DELETE'); await load(); }); });
            row.append(cell); get('rows').append(row);
        }
        get('page').textContent = `${page} / ${last}`;
        get('prev').disabled = page <= 1; get('next').disabled = page >= last;
        const summary = (await request('reports/summary')).data;
        get('summary').textContent = `Customers: ${summary.total_customers} · Open leads: ${summary.open_leads} · Won revenue: ${summary.won_revenue} · Lost revenue: ${summary.lost_revenue}`;
    };
    get('load').addEventListener('click', () => { if (!busy) { page = 1; void action(load); } });
    get('prev').addEventListener('click', () => { if (!busy && page > 1) { page--; void action(load); } });
    get('next').addEventListener('click', () => { if (!busy && page < last) { page++; void action(load); } });
    form?.addEventListener('submit', event => {
        event.preventDefault(); void action(async () => {
            const data = Object.fromEntries(new FormData(form)); const id = data.id; delete data.id;
            data.intake = Object.fromEntries(Array.from(form.querySelectorAll('[data-crm-intake]')).map(input => [input.dataset.crmIntake, input.value || null]));
            data.review = Object.fromEntries(Array.from(form.querySelectorAll('[data-crm-review]')).map(input=>[input.dataset.crmReview,input.value || null]));
            const accountLines = form.querySelector('[data-crm-accounts]').value.split('\n').filter(line=>line.trim());
            data.review.accounts = accountLines.map(line=>{const pos=line.lastIndexOf('|'); if(pos<1) throw Error('Enter each account as creditor | balance');return {creditor:line.slice(0,pos).trim(),balance:line.slice(pos+1).trim()};});
            await request(id ? `customers/${id}` : 'customers', id ? 'PUT' : 'POST', data);
            form.reset(); form.elements.namedItem('id').value = ''; page = 1; await load();
        });
    });
    form?.addEventListener('reset', () => { form.elements.namedItem('id').value = ''; });
    const link=get('link');
    const linkRequest=async (url,body)=>{
        const res=await fetch(url,{method:body?'POST':'GET',credentials:'same-origin',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},...(body?{body:JSON.stringify(body)}:{})});
        const data=await res.json();if(!res.ok)throw Error(data.message||'Cannot link this call');return data;
    };
    get('refresh-calls')?.addEventListener('click',()=>void action(async()=>{
        const result=await linkRequest(link.dataset.callsUrl);get('call').replaceChildren();
        for(const call of result.data){const option=document.createElement('option');option.value=call.call_uuid;option.textContent=call.call_uuid;get('call').append(option);}
        get('verified').checked=false;
    }));
    get('verify')?.addEventListener('click',()=>void action(async()=>{
        const id=form?.elements.namedItem('id').value;
        if(!id || !get('call').value || !get('verified').checked)throw Error('Select a customer with Edit, select an active call, and confirm verification first.');
        await linkRequest(link.dataset.verifyUrl,{customer_id:id,call_uuid:get('call').value,verified:true,is_demo:!!get('is-demo')?.checked});
        get('verified').checked=false;
    }));
    void action(load);
})();
