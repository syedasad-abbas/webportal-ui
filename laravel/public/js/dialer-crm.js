(() => {
    const button = document.getElementById('dialer-crm-lookup');
    if (!button) return;
    const output = document.getElementById('dialer-crm-result');
    button.addEventListener('click', async () => {
        const phone = (document.getElementById('contact-phone-input')?.value || '').replace(/\D/g, '');
        if (!/^[0-9]{7,15}$/.test(phone)) { output.textContent = 'Enter a valid customer phone number including country code.'; return; }
        button.disabled = true; output.textContent = 'Looking up CRM…';
        try {
            const response = await fetch(`${button.dataset.url}?phone=${encodeURIComponent(phone)}`, {
                credentials:'same-origin', cache:'no-store', headers:{Accept:'application/json'}, signal:AbortSignal.timeout(10000)
            });
            const body = await response.json();
            if (!response.ok) throw Error(body.message || 'CRM lookup failed');
            if (!Array.isArray(body.data)) throw Error('Invalid CRM response');
            // Never overwrite a saved dialer contact or change the active call.
            const customer = body.data.find(item => String(item.phone).replace(/\D/g, '') === phone);
            output.textContent = customer ? `${customer.name}\nCompany: ${customer.company || '—'}\nStatus: ${customer.status}\nNotes: ${customer.notes || '—'}` : 'No matching CRM customer.';
        } catch (error) { output.textContent = error.message; }
        finally { button.disabled = false; }
    });
})();
