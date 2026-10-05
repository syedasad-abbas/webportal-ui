@once
<style>
/* Shared record form theme. Tokens mirror the AI agent configuration page so
   every form in the app uses one palette. Button tokens match the AI page's
   violet Save/Enable pair. */
.connectpro-record-form-page{--record-page:#f5f8fc;--record-card:#fff;--record-border:#d7e2ed;--record-input:#fff;--record-input-text:#0f172a;--record-heading:#0f172a;--record-label:#64748b;--record-copy:#64748b;--record-placeholder:#94a3b8;--record-primary:#7c3aed;--record-primary-hover:#6d28d9;--record-accent:#7c3aed;--record-accent-border:#8b5cf6;--record-accent-hover:#f5f3ff;min-height:100%;max-width:none;margin:0;background:var(--record-page);color:var(--record-heading);overflow-x:hidden}
.dark .connectpro-record-form-page{--record-page:#06111f;--record-card:#091827;--record-border:#2a4055;--record-input:#0b1b2c;--record-input-text:#f8fafc;--record-placeholder:#94a3b8;--record-heading:#fff;--record-label:#94a3b8;--record-copy:#94a3b8;--record-primary:#7c3aed;--record-primary-hover:#6d28d9;--record-accent:#c4b5fd;--record-accent-border:#8b5cf6;--record-accent-hover:rgba(139,92,246,.1)}
.connectpro-record-form-page>.mb-6{max-width:1240px;margin-right:auto;margin-left:auto}
.connectpro-record-form-page>.mb-6 h2,.connectpro-record-form-page>.mb-6 li:last-child{color:var(--record-heading)}
.connectpro-record-form-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(280px,360px);gap:22px;width:100%;max-width:1240px;margin:0 auto}
.connectpro-record-form-card,.connectpro-record-form-context{overflow:hidden;border:1px solid var(--record-border)!important;border-radius:16px!important;background:var(--record-card)!important;box-shadow:0 18px 45px -36px rgba(15,23,42,.35)!important}
.connectpro-record-form-card.is-editing{box-shadow:0 18px 45px -36px rgba(15,23,42,.35),0 0 0 1px rgba(59,130,246,.15)!important}
.connectpro-record-form-body{padding:22px!important;border:0!important}
.connectpro-record-form-heading{margin-bottom:22px;color:var(--record-heading);font-size:1rem;font-weight:700}
.connectpro-record-form-fields{display:grid;grid-template-columns:minmax(0,1fr)!important;gap:18px!important}
.connectpro-record-form-page form label,.connectpro-record-form-page form .text-gray-700,.connectpro-record-form-page form .dark\:text-gray-400{color:var(--record-label)!important;font-size:.75rem;font-weight:600}
.connectpro-record-form-page form input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),.connectpro-record-form-page form select,.connectpro-record-form-page form textarea,.connectpro-record-form-page form [role="combobox"]{width:100%;min-height:42px;border:1px solid var(--record-border)!important;border-radius:9px!important;background:var(--record-input)!important;color:var(--record-input-text)!important;box-shadow:none!important}
.connectpro-record-form-page form input::placeholder,.connectpro-record-form-page form textarea::placeholder{color:var(--record-placeholder)!important}
.connectpro-record-form-page form input:disabled,.connectpro-record-form-page form select:disabled,.connectpro-record-form-page form textarea:disabled{background:#f1f5f9!important;color:#94a3b8!important;cursor:not-allowed}
.connectpro-record-form-page form input:focus,.connectpro-record-form-page form select:focus,.connectpro-record-form-page form textarea:focus,.connectpro-record-form-page form [role="combobox"]:focus{border-color:#4f83f1!important;outline:none;box-shadow:0 0 0 3px rgba(79,131,241,.12)!important}
.connectpro-record-form-actions{display:flex;justify-content:flex-end!important;gap:12px;margin-top:28px!important;flex-wrap:wrap}
.connectpro-record-form-actions .btn-primary,.connectpro-record-form-actions button[type="submit"]{min-width:188px;min-height:42px;border-radius:9px;background:var(--record-primary)!important;color:#fff!important}
.connectpro-record-form-actions .btn-primary:hover,.connectpro-record-form-actions button[type="submit"]:hover{background:var(--record-primary-hover)!important}
.connectpro-record-form-actions .btn-default{min-height:42px;border:1px solid var(--record-accent-border);border-radius:9px;background:var(--record-card);color:var(--record-accent)}
.connectpro-record-form-actions .btn-default:hover{background:var(--record-accent-hover);color:var(--record-accent)}
.connectpro-record-form-context{align-self:start;min-height:360px;padding:22px}
.connectpro-record-form-context h2{color:var(--record-heading);font-size:1rem;font-weight:700}
.connectpro-record-form-context p,.connectpro-record-form-context li{color:var(--record-copy);font-size:.8rem;line-height:1.6}
.connectpro-record-form-context .connectpro-record-context-icon{display:flex;width:42px;height:42px;margin-bottom:18px;align-items:center;justify-content:center;border-radius:10px;background:rgba(79,131,241,.14);color:#6d9af5}
@media(max-width:900px){.connectpro-record-form-layout{grid-template-columns:minmax(0,1fr)}.connectpro-record-form-context{min-height:0}}
@media(max-width:480px){.connectpro-record-form-actions{flex-direction:column;align-items:stretch}.connectpro-record-form-actions .btn-primary,.connectpro-record-form-actions .btn-default{width:100%}.connectpro-record-form-body{padding:16px!important}.connectpro-record-form-context{padding:16px}.connectpro-record-form-heading{margin-bottom:16px;font-size:.95rem}.connectpro-record-form-fields{gap:14px!important}.connectpro-record-form-layout{gap:16px}}
</style>
@endonce
