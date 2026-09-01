@extends('backend.layouts.app')

@php
    $webrtcConfig = $webrtcConfig ?? [
        'wsUrl' => config('services.webrtc.ws'),
        'domain' => config('services.webrtc.domain'),
        'username' => null,
        'password' => null,
        'iceServers' => config('services.webrtc.ice_servers'),
    ];
@endphp

@section('title')
    {{ __('Dialer') }} | {{ config('app.name') }}
@endsection

@push('styles')
<style>
@media (min-width: 768px) {
    .connectpro-dialer { height: 100%; min-height: 0; overflow: hidden; background: #08111e !important; }
    body:has(.connectpro-dialer) .connectpro-sidebar + div { min-width: 0; width: auto; }
    .connectpro-dialer-toolbar { min-height: 58px; border-color: #1d2d42; background: #0b1524; padding-right: 1.25rem; padding-left: 1.25rem; }
    .connectpro-dialer-toolbar input { height: 38px; border-radius: 8px; border-color: #263951; background: #132137; }
    .connectpro-dialer-toolbar > button { display: none !important; }
    .connectpro-dialer-toolbar > .relative { display: none; }
    .connectpro-dialer-toolbar > .ml-auto > a { display: none; }
    .connectpro-reference-nav { display: flex !important; }
    .connectpro-agent-status { display: inline-flex !important; }
    .connectpro-reference-nav a { display: inline-flex; align-items: center; min-height: 32px; padding: 0 .9rem; border-radius: 8px; color: #8ea0b8; font-size: .65rem; font-weight: 600; }
    .connectpro-reference-nav a:hover, .connectpro-reference-nav .connectpro-reference-nav-active { background: #1b3154; color: #f8fafc; }
    .connectpro-agent-status { background: #064e3b; color: #34d399; }
    .connectpro-dialer > div:not(.connectpro-dialer-toolbar) { width: 100%; max-width: 1180px; height: calc(100dvh - 58px); min-height: 0; overflow: hidden; padding: .875rem; }
    .connectpro-two-panel-grid { grid-template-columns: minmax(170px, 220px) minmax(0, 1fr) minmax(220px, 266px) !important; grid-template-rows: minmax(0, 1fr) auto; gap: .875rem; height: 100%; min-height: 0 !important; }
    .connectpro-dialer .connectpro-dialer-panel:first-child { grid-column: 2; grid-row: 1; min-height: 100%; border-radius: 12px; border-color: #20344c; background: #111c2b; padding: 1.125rem; box-shadow: none; }
    .connectpro-dialer .connectpro-dialer-panel:first-child > div:first-child { margin-bottom: 1rem; }
    .connectpro-dialer .connectpro-dialer-panel:first-child form { min-height: 0; }
    .connectpro-dialer #dialer-form > .relative { border-radius: 8px; border-color: #263951; background: #19283d; padding: .7rem 2.5rem; }
    .connectpro-dialer #dialpad-display { font-size: .875rem; text-align: left; }
    .connectpro-dialer .dialpad-grid { width: 100%; aspect-ratio: auto; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; margin-top: 1rem; padding: 0; border: 0; border-radius: 0; background: transparent; }
    .connectpro-dialer .connectpro-dialer-panel:first-child .dialpad-key { width: auto; height: 42px; min-height: 42px; border-radius: 8px; border-color: #263951; background: #18283d; font-size: 1rem; }
    .connectpro-dialer .connectpro-dialer-panel:first-child .dialpad-key > div:nth-child(2) { font-size: .5rem; }
    .connectpro-dialer .connectpro-call-actions { margin-top: 1rem; }
    .connectpro-dialer .connectpro-call-actions { grid-template-columns: 1fr !important; }
    .connectpro-dialer .connectpro-call-actions button[type="button"] { display: none; }
    .connectpro-dialer .connectpro-call-actions button[type="submit"] { background: #4f83f1 !important; box-shadow: none; }
    .connectpro-dialer .connectpro-call-actions button { min-height: 38px; border-radius: 8px; padding-top: .5rem; padding-bottom: .5rem; font-size: .75rem; }
    .connectpro-dialer .connectpro-customer-workspace-card { grid-column: 1; grid-row: 1; min-height: 100%; border-radius: 12px; border-color: #20344c; background: #111c2b; box-shadow: none; }
    .connectpro-dialer .connectpro-customer-workspace-card > #customer-call-panel { border-bottom: 1px solid #20344c; padding: 1.125rem; }
    .connectpro-dialer #customer-call-panel { background: #111c2b; background-image: none; }
    .connectpro-dialer #customer-avatar { width: 58px; height: 58px; border-width: 2px; font-size: 1.25rem; }
    .connectpro-dialer #customer-name { font-size: 1.25rem; }
    .connectpro-dialer .connectpro-customer-workspace-card > #contact-workspace-panel { padding: 1.125rem; }
    .connectpro-dialer .connectpro-customer-workspace-card > #contact-workspace-panel > .contact-tabs { margin-right: -1.125rem; margin-left: -1.125rem; padding-right: 1.125rem; padding-left: 1.125rem; }
    .connectpro-dialer #contact-workspace-panel > div.rounded-xl { border-radius: 8px; border-color: #263951; background: #19283d; }

    .connectpro-two-panel-grid { grid-template-columns: 220px minmax(0, 1fr) 266px !important; grid-template-rows: auto minmax(0, 1fr); align-items: stretch; }
    .connectpro-dialer .connectpro-customer-workspace-card { display: contents; }
    .connectpro-dialer .connectpro-customer-workspace-card > #customer-call-panel { grid-column: 2; grid-row: 1; min-height: 0; border: 1px solid #20344c; border-radius: 12px; }
    .connectpro-dialer .connectpro-customer-workspace-card > #contact-workspace-panel { display: contents; }
    .connectpro-dialer #contact-workspace-panel > .connectpro-contact-search { grid-column: 1; grid-row: 1 / span 2; align-self: stretch; margin: 0; padding: 1.125rem; border: 1px solid #20344c; border-radius: 12px; background: #111c2b; }
    .connectpro-dialer #contact-workspace-panel > .connectpro-contact-search label { font-size: 1rem; text-transform: none; letter-spacing: 0; color: #f8fafc; }
    .connectpro-dialer #contact-workspace-panel > .connectpro-contact-search select { display: none; }
    .connectpro-dialer #contact-workspace-panel > .connectpro-contact-search .grid { display: block; }
    .connectpro-dialer #contact-workspace-panel > .connectpro-contact-search .grid > div { width: 100%; }
    .connectpro-dialer #contact-workspace-panel > .contact-tabs { grid-column: 2; grid-row: 2; align-self: start; margin: 0; padding: 0 1.125rem; border: 1px solid #20344c; border-radius: 12px 12px 0 0; }
    .connectpro-dialer #contact-workspace-panel > [data-contact-tab-panel="notes"].pt-5 { grid-column: 2; grid-row: 2; align-self: start; margin: 4.25rem 0 0; padding: 1rem 1.125rem; }
    .connectpro-dialer #contact-workspace-panel > [data-contact-tab-panel="notes"]:last-child { grid-column: 2; grid-row: 2; align-self: stretch; margin: 8rem 0 0; min-height: 0; }
    .connectpro-dialer .connectpro-dialer-panel:first-child { grid-column: 3; grid-row: 1; }
    .connectpro-dialer .connectpro-labels-card { grid-column: 3; grid-row: 2; border-color: #20344c; background: #111c2b; box-shadow: none; }
    .connectpro-dialer .connectpro-labels-card input { border-color: #263951; background: #19283d; }
    .connectpro-dialer .connectpro-customer-workspace-card > #contact-workspace-panel > [data-contact-tab-panel] { min-height: 0; overflow: hidden; }
    .connectpro-dialer #contact-comments { overflow-y: auto; }
}

@media (min-width: 768px) {
    body:has(.connectpro-dialer) .connectpro-sidebar { position: relative !important; inset: auto !important; transform: translateX(0) !important; translate: 0 0 !important; }
}

@media (min-width: 768px) and (max-width: 1100px) {
    body:has(.connectpro-dialer) .connectpro-sidebar { width: 180px !important; }
    .connectpro-two-panel-grid { grid-template-columns: minmax(130px, 180px) minmax(0, 1fr) minmax(190px, 220px) !important; }
    .connectpro-dialer #contact-workspace-panel > [data-contact-tab-panel="notes"]:last-child { margin-top: 10rem; }
}

@media (min-width: 768px) and (orientation: landscape) {
    .connectpro-dialer { height: 100dvh; overflow: hidden; }
    .connectpro-dialer > div:not(.connectpro-dialer-toolbar) { height: calc(100dvh - 58px); max-width: none; padding: .75rem 1.25rem .75rem .875rem; }
    .connectpro-two-panel-grid { grid-template-columns: minmax(180px, 220px) minmax(0, 1fr) minmax(232px, 266px) !important; grid-template-rows: minmax(0, 1fr); height: 100%; min-height: 0 !important; gap: .75rem; }
    .connectpro-dialer .connectpro-dialer-panel:first-child { grid-column: 3; grid-row: 1; height: 100%; min-height: 0; overflow: hidden; padding: 1rem; }
    .connectpro-dialer .connectpro-dialer-panel:first-child > div:first-child { margin-bottom: .75rem; }
    .connectpro-dialer .connectpro-dialer-panel:first-child .dialpad-key { height: clamp(34px, 8vh, 48px); min-height: clamp(34px, 8vh, 48px); }
    .connectpro-dialer .connectpro-call-actions { margin-top: .75rem; }
    .connectpro-dialer .connectpro-customer-workspace-card { grid-column: 2; grid-row: 1; display: flex; flex-direction: column; min-height: 0; height: 100%; gap: .75rem; background: transparent; border: 0; }
    .connectpro-dialer .connectpro-customer-workspace-card > #customer-call-panel { flex: 0 0 145px !important; height: 145px !important; min-height: 0 !important; border: 1px solid #20344c; border-radius: 12px; background: #111c2b; padding: 1rem; overflow: hidden; }
    .connectpro-dialer #customer-call-panel > div:nth-of-type(2) { grid-template-columns: 56px minmax(0, 1fr); gap: .6rem; padding-top: .25rem; padding-bottom: .25rem; }
    .connectpro-dialer #customer-call-panel > div:nth-of-type(2) > div:last-child { text-align: left; }
    .connectpro-dialer #customer-avatar { width: 56px; height: 56px; }
    .connectpro-dialer #customer-name { font-size: 1.25rem; }
    .connectpro-dialer .connectpro-customer-workspace-card > #contact-workspace-panel { display: flex; flex: 1 1 auto; height: auto !important; min-height: 0 !important; flex-direction: column; padding: 0; overflow: hidden; background: transparent; }
    .connectpro-dialer #contact-workspace-panel > .connectpro-contact-search { display: none; }
    .connectpro-dialer #contact-workspace-panel > .contact-tabs { flex: 0 0 auto; margin: 0; padding: 0 .875rem; border: 1px solid #20344c; border-radius: 10px 10px 0 0; }
    .connectpro-dialer #contact-workspace-panel > [data-contact-tab-panel="notes"].pt-5 { display: none; }
    .connectpro-dialer #contact-workspace-panel > [data-contact-tab-panel="notes"]:last-child { display: flex; flex: 1 1 auto; min-height: 0; margin: 0; flex-direction: column; overflow: hidden; border: 1px solid #20344c; border-top: 0; border-radius: 0 0 10px 10px; background: #111c2b; }
    .connectpro-dialer #contact-comments { flex: 1 1 auto; min-height: 0; max-height: none; overflow-y: auto; }
    .connectpro-dialer .connectpro-labels-card { display: none; }
}

@media (min-width: 768px) and (orientation: landscape) {
    body:has(.connectpro-dialer) .connectpro-sidebar { display: none !important; }
    body:has(.connectpro-dialer) .connectpro-sidebar + div { width: 100% !important; margin-left: 0 !important; }
    .connectpro-two-panel-grid { grid-template-columns: 227px minmax(0, 1fr) 266px !important; grid-template-rows: 145px minmax(0, 1fr) 70px; }
    .connectpro-dialer .connectpro-customer-workspace-card { display: contents !important; }
    .connectpro-dialer .connectpro-customer-workspace-card > #customer-call-panel { grid-column: 2; grid-row: 1; width: 100%; height: 145px !important; min-height: 145px !important; padding: .75rem !important; }
    .connectpro-dialer .connectpro-customer-workspace-card > #contact-workspace-panel { display: contents !important; }
    .connectpro-dialer #contact-workspace-panel > .connectpro-contact-search { display: block; grid-column: 1; grid-row: 1 / span 3; width: 100%; height: 100%; padding: .875rem; overflow: hidden; }
    .connectpro-dialer #contact-workspace-panel > .contact-tabs { grid-column: 2; grid-row: 2; width: 100%; height: 42px; padding: 0 .75rem; overflow: hidden; }
    .connectpro-dialer #contact-workspace-panel > [data-contact-tab-panel="notes"].pt-5 { display: flex; grid-column: 2; grid-row: 2; width: 100%; height: 42px; margin: 0; padding: .75rem; align-items: center; overflow: hidden; }
    .connectpro-dialer #contact-workspace-panel > [data-contact-tab-panel="notes"]:last-child { display: flex; grid-column: 2; grid-row: 2; width: 100%; height: 100%; margin: 42px 0 0 !important; min-height: 0 !important; flex-direction: column; overflow: hidden; border: 1px solid #20344c; border-radius: 0 0 10px 10px; background: #111c2b; }
    .connectpro-dialer #contact-workspace-panel > [data-contact-tab-panel="history"] { display: flex !important; grid-column: 2; grid-row: 3; width: 100%; height: 100%; min-height: 0; margin: 0; flex-direction: column; overflow: hidden; border: 1px solid #20344c; border-radius: 10px; background: #111c2b; }
    .connectpro-dialer #contact-workspace-panel > [data-contact-tab-panel="info"],
    .connectpro-dialer #contact-workspace-panel > [data-contact-tab-panel="activity"] { display: none !important; }
    .connectpro-dialer .connectpro-labels-card { display: block; grid-column: 3; grid-row: 3; width: 100%; height: 100%; overflow: hidden; padding: .6rem 1rem; }
    .connectpro-dialer .connectpro-labels-card h2 { font-size: .9rem; }
    .connectpro-dialer .connectpro-labels-card > div:not(#contact-labels) { display: none; }
    .connectpro-dialer .connectpro-labels-card #contact-labels { max-height: 5rem; overflow-y: auto; }
    .connectpro-dialer #customer-call-panel > div:nth-of-type(2) { grid-template-columns: 56px minmax(0, 1fr); gap: .6rem; padding-top: .25rem; padding-bottom: .25rem; }
    .connectpro-dialer #customer-call-panel > div:nth-of-type(2) > div:last-child { min-width: 0; }
    .connectpro-dialer #customer-name, .connectpro-dialer #customer-company, .connectpro-dialer #customer-phone { overflow: visible; white-space: normal; }
    .connectpro-dialer #customer-name { font-size: 1.15rem; line-height: 1.25; }
    .connectpro-dialer #customer-company { font-size: .75rem; }
    .connectpro-dialer #customer-phone { font-size: .75rem; }
    .connectpro-dialer .connectpro-dialer-panel:first-child { grid-row: 1 / span 2; }
    .connectpro-dialer .connectpro-dialer-panel:first-child .dialpad-key { height: 32px; min-height: 32px; border-radius: 7px; padding: 0; font-size: .9rem; box-shadow: none; }
    .connectpro-dialer .connectpro-dialer-panel:first-child .dialpad-key > div:nth-child(2) { margin-top: .1rem; font-size: .45rem; letter-spacing: .05em; }
    .connectpro-dialer .connectpro-dialer-panel:first-child .dialpad-grid { gap: .4rem; margin-top: .75rem; }
    .connectpro-dialer #contact-workspace-panel > .contact-tabs { display: none; }
    .connectpro-dialer #contact-workspace-panel > [data-contact-tab-panel="notes"].pt-5 { display: none; }
    .connectpro-dialer #contact-workspace-panel > [data-contact-tab-panel="notes"]:last-child { grid-row: 2; margin: 0 !important; border-top: 1px solid #20344c; border-radius: 10px; }
    body:has(.connectpro-dialer) .connectpro-sidebar { display: flex !important; position: relative !important; inset: auto !important; width: 248px !important; transform: none !important; translate: 0 0 !important; }
    body:has(.connectpro-dialer) .connectpro-sidebar + div { width: auto !important; margin-left: 0 !important; }
}

@media (min-width: 768px) and (max-width: 1100px) and (orientation: landscape) {
    .connectpro-two-panel-grid { grid-template-columns: 168px minmax(0, 1fr) 197px !important; }
}

@media (min-width: 1101px) and (max-width: 1599px) and (orientation: landscape) {
    .connectpro-two-panel-grid { grid-template-columns: 220px minmax(0, 1fr) 280px !important; }
    .connectpro-dialer #contact-workspace-panel > .connectpro-contact-search,
    .connectpro-dialer .connectpro-dialer-panel:first-child,
    .connectpro-dialer .connectpro-labels-card { width: 100%; }
    .connectpro-dialer .connectpro-customer-workspace-card > #customer-call-panel,
    .connectpro-dialer #contact-workspace-panel > [data-contact-tab-panel="notes"]:last-child,
    .connectpro-dialer #contact-workspace-panel > [data-contact-tab-panel="history"] { width: 100%; }
}

@media (min-width: 1101px) and (orientation: landscape) {
    .connectpro-dialer > div:not(.connectpro-dialer-toolbar) { padding-top: 36px; padding-right: 18px; padding-left: 94px; }
    .connectpro-two-panel-grid { grid-template-columns: 224px 716px 352px !important; grid-template-rows: 190px minmax(0, 1fr); gap: 18px; }
    .connectpro-dialer .connectpro-customer-workspace-card > #customer-call-panel { width: 716px; height: 190px !important; min-height: 190px !important; border-radius: 16px; border-width: 1px; }
    .connectpro-dialer .connectpro-dialer-panel:first-child { width: 352px; height: 488px; min-height: 488px; border-radius: 16px; border-width: 1px; }
    .connectpro-dialer .connectpro-labels-card { grid-column: 3; grid-row: 2; width: 352px; }
}

@media (min-width: 1101px) and (orientation: landscape) {
    .connectpro-dialer > div:not(.connectpro-dialer-toolbar) { padding-top: 36px; padding-right: 18px; padding-left: 18px; }
    .connectpro-two-panel-grid { grid-template-columns: 300px 716px 352px !important; grid-template-rows: 190px 278px 284px; gap: 18px; height: 788px; min-height: 788px !important; }
    .connectpro-dialer #contact-workspace-panel > .connectpro-contact-search { grid-column: 1; grid-row: 1 / span 3; width: 300px; height: 788px; border-radius: 16px; border-width: 1px; }
    .connectpro-dialer .connectpro-customer-workspace-card > #customer-call-panel { grid-column: 2; grid-row: 1; width: 716px; height: 190px !important; min-height: 190px !important; border-radius: 16px; border-width: 1px; }
    .connectpro-dialer #contact-workspace-panel > [data-contact-tab-panel="notes"]:last-child { grid-column: 2; grid-row: 2; width: 716px; height: 278px; min-height: 278px !important; margin: 0 !important; border-radius: 16px; border-width: 1px; }
    .connectpro-dialer #contact-workspace-panel > [data-contact-tab-panel="history"] { grid-column: 2; grid-row: 3; width: 716px; height: 284px; min-height: 284px !important; margin: 0 !important; border-radius: 16px !important; border-width: 1px; }
    .connectpro-dialer .connectpro-dialer-panel:first-child { grid-column: 3; grid-row: 1; width: 352px; height: 488px; min-height: 488px; border-radius: 16px; border-width: 1px; }
    .connectpro-dialer .connectpro-labels-card { grid-column: 3; grid-row: 3; width: 352px; height: 282px; min-height: 282px; transform: translateY(2px); border-radius: 16px; border-width: 1px; }
    .connectpro-dialer .connectpro-call-actions button[type="submit"] { display: flex !important; }
}

.connectpro-activity-history-card { display: contents; }

/* NightWave desktop balance: every rail occupies a deliberate grid area. */
@media (min-width: 1101px) and (orientation: landscape) {
    .connectpro-dialer > div:not(.connectpro-dialer-toolbar) {
        width: 100%;
        max-width: none;
        height: calc(100dvh - 58px);
        padding: 18px;
    }
    .connectpro-two-panel-grid {
        grid-template-columns: clamp(230px, 20vw, 300px) minmax(0, 1fr) minmax(0, 1fr) clamp(280px, 23vw, 352px) !important;
        grid-template-rows: 180px minmax(210px, .95fr) minmax(210px, 1fr);
        width: 100%;
        height: 100%;
        min-height: 0 !important;
        gap: 18px;
    }
    .connectpro-dialer #contact-workspace-panel > .connectpro-contact-search {
        grid-column: 1;
        grid-row: 1 / span 3;
        width: auto;
        height: auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .connectpro-dialer #contact-workspace-panel > .connectpro-contact-search #contact-search-results {
        position: static;
        inset: auto;
        z-index: auto;
        display: block !important;
        flex: 1 1 auto;
        min-height: 0;
        max-height: none;
        margin-top: 14px;
        overflow-y: auto;
        border: 0;
        background: transparent;
        padding: 0;
        box-shadow: none;
    }
    .connectpro-dialer .connectpro-customer-workspace-card > #customer-call-panel {
        grid-column: 2 / span 2;
        grid-row: 1;
        width: auto;
        height: auto !important;
        min-height: 0 !important;
    }
    .connectpro-dialer #contact-workspace-panel > .connectpro-notes-card {
        grid-column: 2 / span 2 !important;
        grid-row: 2;
        width: auto !important;
        height: auto !important;
        min-height: 0 !important;
        margin: 0 !important;
        display: flex !important;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid #20344c;
        border-radius: 16px;
        background: #111c2b;
    }
    .connectpro-dialer .connectpro-notes-card #contact-comments {
        flex: 1 1 auto;
        min-height: 0;
        max-height: none;
    }
    .connectpro-dialer #contact-workspace-panel > .connectpro-activity-history-card {
        grid-column: 2 / span 2;
        grid-row: 3;
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        min-width: 0;
        min-height: 0;
        overflow: hidden;
        border: 1px solid #20344c;
        border-radius: 16px;
        background: #111c2b;
    }
    .connectpro-dialer .connectpro-activity-history-card > [data-contact-tab-panel="activity"],
    .connectpro-dialer .connectpro-activity-history-card > [data-contact-tab-panel="history"] {
        display: flex !important;
        width: auto;
        height: auto;
        min-width: 0;
        min-height: 0;
        margin: 0;
        flex-direction: column;
        overflow: hidden;
        border: 0;
        border-radius: 0 !important;
        background: transparent;
    }
    .connectpro-dialer .connectpro-activity-history-card > [data-contact-tab-panel="history"] {
        border-left: 1px solid #20344c;
    }
    .connectpro-dialer .connectpro-activity-history-card > [data-contact-tab-panel="activity"] > div:last-child,
    .connectpro-dialer .connectpro-activity-history-card > [data-contact-tab-panel="history"] > div:last-child {
        flex: 1 1 auto;
        min-height: 0;
        max-height: none;
        overflow-y: auto;
    }
    .connectpro-dialer .connectpro-dialer-panel:first-child {
        grid-column: 4;
        grid-row: 1 / span 2;
        width: auto;
        height: auto;
        min-height: 0;
    }
    .connectpro-dialer .connectpro-dialer-panel:first-child form {
        display: flex;
        height: auto;
        min-height: 0;
        flex-direction: column;
    }
    .connectpro-dialer .connectpro-dialer-panel:first-child .dialpad-grid {
        width: 100%;
        min-height: 0;
        aspect-ratio: auto;
        margin-top: 16px;
        padding: 0;
        gap: 8px;
        justify-items: stretch;
        border: 0;
        border-radius: 0;
        background: transparent;
    }
    .connectpro-dialer .connectpro-dialer-panel:first-child .dialpad-key {
        width: 100% !important;
        height: 42px;
        min-height: 42px;
        padding: 0;
        border-radius: 8px;
        font-size: 1rem;
    }
    .connectpro-dialer .connectpro-labels-card {
        grid-column: 4;
        grid-row: 3;
        width: auto;
        height: auto;
        min-height: 0;
        transform: none;
    }
    .connectpro-dialer .connectpro-labels-card > div:not(#contact-labels) {
        display: block;
    }
    .connectpro-dialer .connectpro-labels-card > div:last-child {
        display: flex;
    }
    .connectpro-dialer .connectpro-labels-card #contact-labels:empty::before {
        content: "{{ __('No labels added') }}";
        color: #64748b;
        font-size: .75rem;
    }
    .connectpro-dialer .connectpro-call-actions {
        margin-top: 16px !important;
        grid-template-columns: 1fr !important;
    }
    .connectpro-dialer .connectpro-call-actions button {
        min-height: 40px !important;
        padding-top: 8px;
        padding-bottom: 8px;
        font-size: .8rem;
    }
    .connectpro-dialer .connectpro-call-actions button[type="button"] {
        display: none;
    }
    .connectpro-dialer .connectpro-call-actions .connectpro-call-button {
        display: flex !important;
        width: 100%;
        background: #2563eb !important;
        color: #fff !important;
        box-shadow: 0 12px 28px -18px rgba(79, 131, 241, .95);
    }
    .connectpro-dialer .connectpro-call-actions button.connectpro-call-button[type="submit"] {
        background: #2563eb !important;
    }
    .connectpro-dialer .connectpro-call-actions .connectpro-call-button:hover {
        background: #1d4ed8 !important;
    }
    html:not(.dark) .connectpro-dialer #contact-workspace-panel > .connectpro-contact-search,
    html:not(.dark) .connectpro-dialer #contact-workspace-panel > .connectpro-activity-history-card {
        border-color: #d6e0eb;
        background: #fff;
    }
    html:not(.dark) .connectpro-dialer #contact-workspace-panel > .connectpro-contact-search label,
    html:not(.dark) .connectpro-dialer #contact-search-results strong,
    html:not(.dark) .connectpro-dialer .connectpro-activity-history-card h3 {
        color: #0f172a !important;
    }
    html:not(.dark) .connectpro-dialer .connectpro-activity-history-card > [data-contact-tab-panel="history"] {
        border-left-color: #d6e0eb;
    }
    html:not(.dark) .connectpro-dialer .connectpro-dialer-panel:first-child .dialpad-key {
        border-color: #d6e0eb;
        background: #f1f5f9;
        color: #0f172a;
    }
    /* Light mode for full call history panel */
    html:not(.dark) #dialer-full-history section {
        border-color: #d6e0eb !important;
        background: #fff !important;
    }
    html:not(.dark) #dialer-full-history h2,
    html:not(.dark) #dialer-full-history .font-semibold {
        color: #0f172a !important;
    }
    html:not(.dark) #dialer-full-history .text-slate-400 {
        color: #64748b !important;
    }
    html:not(.dark) #dialer-full-history .text-slate-300 {
        color: #334155 !important;
    }
    html:not(.dark) #dialer-full-history .text-slate-500 {
        color: #64748b !important;
    }
    html:not(.dark) #dialer-full-history article {
        border-color: #e2e8f0 !important;
    }
    html:not(.dark) #dialer-full-history article:hover {
        background: #f8fafc !important;
    }
    html:not(.dark) #dialer-full-history .bg-\[#0a1a2e\] {
        background: #f1f5f9 !important;
    }
    /* Light mode for full activity panel */
    html:not(.dark) #dialer-full-activity section {
        border-color: #d6e0eb !important;
        background: #fff !important;
    }
    html:not(.dark) #dialer-full-activity h2,
    html:not(.dark) #dialer-full-activity .font-semibold {
        color: #0f172a !important;
    }
    html:not(.dark) #dialer-full-activity .text-slate-400 {
        color: #64748b !important;
    }
    html:not(.dark) #dialer-full-activity .text-slate-300 {
        color: #334155 !important;
    }
    html:not(.dark) #dialer-full-activity .text-slate-500 {
        color: #64748b !important;
    }
    html:not(.dark) #dialer-full-activity .text-white {
        color: #0f172a !important;
    }
    html:not(.dark) #dialer-full-activity .text-blue-400 {
        color: #2563eb !important;
    }
    html:not(.dark) #dialer-full-activity .text-purple-300 {
        color: #9333ea !important;
    }
    html:not(.dark) #dialer-full-activity article {
        border-color: #e2e8f0 !important;
    }
    html:not(.dark) #dialer-full-activity article:hover {
        background: #f8fafc !important;
    }
    html:not(.dark) #dialer-full-activity .bg-slate-800\/50 {
        background: #f1f5f9 !important;
    }
    html:not(.dark) #dialer-full-activity .border-slate-700\/50 {
        border-color: #e2e8f0 !important;
    }
    /* Light mode icon backgrounds */
    html:not(.dark) #dialer-full-activity .bg-emerald-500\/15 {
        background: #d1fae5 !important;
    }
    html:not(.dark) #dialer-full-activity .bg-blue-500\/15 {
        background: #dbeafe !important;
    }
    html:not(.dark) #dialer-full-activity .bg-cyan-500\/15 {
        background: #cffafe !important;
    }
    html:not(.dark) #dialer-full-activity .bg-purple-500\/15 {
        background: #f3e8ff !important;
    }
    html:not(.dark) #dialer-full-activity .bg-amber-500\/15 {
        background: #fef3c7 !important;
    }
    html:not(.dark) #dialer-full-activity .bg-slate-500\/15 {
        background: #f1f5f9 !important;
    }
    html:not(.dark) #dialer-full-activity .text-emerald-400 {
        color: #059669 !important;
    }
    html:not(.dark) #dialer-full-activity .text-blue-400 {
        color: #2563eb !important;
    }
    html:not(.dark) #dialer-full-activity .text-cyan-400 {
        color: #0891b2 !important;
    }
    html:not(.dark) #dialer-full-activity .text-purple-400 {
        color: #9333ea !important;
    }
    html:not(.dark) #dialer-full-activity .text-amber-400 {
        color: #d97706 !important;
    }
    html:not(.dark) #dialer-full-activity .text-slate-400 {
        color: #64748b !important;
    }
    html:not(.dark) #dialer-full-activity .ring-emerald-500\/20 {
        --tw-ring-color: rgba(16, 185, 129, 0.2) !important;
    }
    html:not(.dark) #dialer-full-activity .ring-blue-500\/20 {
        --tw-ring-color: rgba(59, 130, 246, 0.2) !important;
    }
    html:not(.dark) #dialer-full-activity .ring-cyan-500\/20 {
        --tw-ring-color: rgba(6, 182, 212, 0.2) !important;
    }
    html:not(.dark) #dialer-full-activity .ring-purple-500\/20 {
        --tw-ring-color: rgba(168, 85, 247, 0.2) !important;
    }
    html:not(.dark) #dialer-full-activity .ring-amber-500\/20 {
        --tw-ring-color: rgba(245, 158, 11, 0.2) !important;
    }
    /* Light mode for inbound call banner */
    html:not(.dark) .connectpro-incoming-card {
        border-color: #d6e0eb !important;
        background: rgba(255, 255, 255, 0.95) !important;
        box-shadow: 0 30px 100px rgba(0, 0, 0, 0.15) !important;
    }
    html:not(.dark) .connectpro-incoming-shell {
        color: #0f172a !important;
    }
    html:not(.dark) .connectpro-incoming-card > p:first-child {
        color: #2563eb !important;
    }
    html:not(.dark) .connectpro-incoming-card > p:nth-child(2) {
        color: #475569 !important;
    }
    html:not(.dark) #incoming-caller {
        color: #0f172a !important;
    }
    html:not(.dark) #incoming-company {
        color: #2563eb !important;
    }
    html:not(.dark) #incoming-did {
        color: #475569 !important;
    }
    html:not(.dark) .connectpro-incoming-avatar {
        border-color: #94a3b8 !important;
        background: #e2e8f0 !important;
        box-shadow: 0 0 0 8px rgba(59, 130, 246, 0.1), 0 0 0 18px rgba(59, 130, 246, 0.05), 0 0 48px rgba(37, 99, 235, 0.2) !important;
    }
    html:not(.dark) .connectpro-incoming-context {
        border-color: #d6e0eb !important;
        background: rgba(255, 255, 255, 0.9) !important;
    }
    html:not(.dark) .connectpro-incoming-context > div {
        border-color: #e2e8f0 !important;
        background: #f8fafc !important;
    }
    html:not(.dark) .connectpro-incoming-context p {
        color: #334155 !important;
    }
    html:not(.dark) .connectpro-incoming-context p:first-child {
        color: #0f172a !important;
    }
}
</style>
@endpush

@section('admin-content')
<div class="connectpro-dialer min-h-full bg-[#06111f] text-white">
    <div class="connectpro-dialer-toolbar flex min-h-[82px] items-center gap-4 border-b border-[#20364c] bg-[#071526]/95 px-3 backdrop-blur-xl sm:px-6">
        <button type="button" @click.stop="sidebarToggle = true" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[#2a4055] bg-[#091827] text-slate-200 lg:hidden" aria-label="{{ __('Open navigation') }}"><i class="bi bi-list text-2xl"></i></button>
        <nav class="connectpro-reference-nav hidden items-center gap-2 lg:flex" aria-label="{{ __('Dialer navigation') }}">
            <a href="{{ route('admin.contacts.index') }}">{{ __('Contacts') }}</a>
            <a class="connectpro-reference-nav-active" href="#">{{ __('Dialpad') }}</a>
            <a href="{{ route('admin.contacts.call-history') }}">{{ __('History') }}</a>
            <a href="{{ route('admin.contacts.activity') }}">{{ __('Activity') }}</a>
            <a href="#">{{ __('Reports') }}</a>
        </nav>
        <div class="relative mx-auto hidden w-full max-w-xl md:block">
            <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="search" data-dialer-toolbar-search placeholder="{{ __('Search contacts or numbers…') }}" class="h-12 w-full rounded-2xl border border-[#2a4055] bg-[#0b1b2c] pl-11 pr-4 text-sm text-white outline-none placeholder:text-slate-500 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
        </div>
        <div class="ml-auto flex items-center gap-2">
            <span class="connectpro-agent-status hidden items-center rounded-full px-3 py-1 text-[10px] font-semibold lg:inline-flex">{{ __('Agent online') }}</span>
            <a href="{{ route('admin.contacts.index') }}" class="flex h-11 w-11 items-center justify-center rounded-xl border border-[#2a4055] bg-[#0b1b2c] text-blue-400 hover:border-blue-500" title="{{ __('Contacts') }}"><i class="bi bi-people-fill text-lg"></i></a>
            <a href="{{ route('admin.settings.index') }}" class="flex h-11 w-11 items-center justify-center rounded-xl border border-[#2a4055] bg-[#0b1b2c] text-slate-300 hover:border-blue-500 hover:text-blue-400" title="{{ __('Settings') }}"><i class="bi bi-gear-fill text-lg"></i></a>
        </div>
    </div>
    <div class="mx-auto max-w-[1580px] space-y-4 p-3 sm:p-6">

        @if (!empty($webrtcError))
            <div class="sr-only" role="status">{{ $webrtcError }}</div>
        @endif

        <div class="hidden">
            <div>
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ __('Keypad') }}</h1>
                <p class="mt-1 flex items-center gap-2 text-sm text-slate-300"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>{{ __('Ready') }}</p>
            </div>
            <div class="connectpro-sip-badge flex max-w-full items-center gap-3 rounded-2xl border border-[#2a4055] bg-[#091827] px-3 py-2 shadow-lg shadow-black/10 sm:px-4" title="{{ __('Web phone account') }}: {{ $webrtcConfig['username'] ?? '—' }}@{{ $webrtcConfig['domain'] ?? '—' }}">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600/15 text-blue-400 ring-1 ring-inset ring-blue-500/25">
                    <i class="bi bi-headset text-xl"></i>
                </span>
                <span class="min-w-0">
                    <span class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                        {{ __('Web phone') }}
                        <span id="web-phone-state-dot" class="h-1.5 w-1.5 rounded-full bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.65)]"></span>
                    </span>
                    <span class="mt-0.5 block truncate text-sm font-semibold text-slate-100">
                        {{ __('Extension') }} {{ $webrtcConfig['username'] ?? '—' }} · <span id="web-phone-state">{{ __('Connecting') }}</span>
                    </span>
                </span>
            </div>
        </div>

        <div class="connectpro-two-panel-grid grid min-h-[calc(100vh-150px)] grid-cols-1 gap-4 lg:grid-cols-[minmax(360px,1fr)_minmax(480px,1fr)]">
            <section class="connectpro-dialer-panel flex min-h-full flex-col rounded-2xl border border-[#2a4055] bg-[#091827] p-3 shadow-2xl sm:p-5">
                <div class="mb-5 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-white">{{ __('Keypad') }}</h2>
                        <p class="mt-1 flex items-center gap-2 text-xs text-slate-400"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ __('Ready') }}<span class="text-slate-600">•</span>{{ __('SIP') }}: {{ $webrtcConfig['username'] ?? '—' }}@<span>{{ $webrtcConfig['domain'] ?? '—' }}</span></p>
                    </div>
                    <div id="call-status" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-[#365068] text-slate-300"><i class="bi bi-arrow-clockwise"></i></div>
                </div>

                <form id="dialer-form" method="POST" action="{{ route('admin.dialer.dial') }}">
                    @csrf
                    <div class="relative rounded-xl border border-[#365068] bg-[#0b1b2c] px-11 py-3 sm:px-12 sm:py-3.5">
                        <i class="bi bi-phone absolute left-4 top-1/2 -translate-y-1/2 text-xl text-slate-300"></i>
                        <input type="text" id="dialpad-display" placeholder="{{ __('Enter number or name') }}" inputmode="tel" autocomplete="tel" class="w-full border-0 bg-transparent p-0 text-left text-lg font-normal tracking-normal text-white outline-none ring-0 placeholder:text-slate-400 focus:border-0 focus:ring-0">
                        <input type="hidden" name="destination" id="dialpad-input" required>
                        <button type="button" id="dialpad-clear" class="sr-only" title="{{ __('Clear') }}">{{ __('Clear') }}</button>
                        <button type="button" id="dialpad-backspace" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-2 text-xl text-slate-300 hover:text-white" title="{{ __('Delete') }}"><i class="bi bi-x-lg"></i></button>
                    </div>

                    <div class="dialpad-grid mt-5" aria-label="Dial pad">
                        @php($keys = [['1',''],['2','ABC'],['3','DEF'],['4','GHI'],['5','JKL'],['6','MNO'],['7','PQRS'],['8','TUV'],['9','WXYZ'],['*',''],['0','+'],['#','']])
                        @foreach($keys as [$k,$sub])
                            <button type="button" class="dialpad-key" data-value="{{ $k }}">
                                <div class="leading-none">{{ $k }}</div><div class="mt-1 text-[10px]">{{ $sub ?: ' ' }}</div>
                            </button>
                        @endforeach
                    </div>

                    <div class="connectpro-call-actions mt-5 grid grid-cols-2 gap-3">
                        <button type="submit" class="connectpro-call-button flex items-center justify-center gap-2 rounded-xl bg-blue-600 py-3.5 font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60"><i class="bi bi-telephone-fill text-xl"></i><span>{{ __('Call') }}</span></button>
                        <button type="button" class="flex items-center justify-center gap-2 rounded-xl bg-red-500 py-3.5 font-semibold text-white shadow-lg shadow-red-600/20 transition hover:bg-red-400 disabled:cursor-not-allowed disabled:opacity-50" data-action="hangup" disabled><i class="bi bi-telephone-x-fill text-xl"></i><span>{{ __('Hangup') }}</span></button>
                    </div>
                </form>

                <div id="dialer-alert" class="mt-4 hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"></div>

            </section>

            <section id="customer-workspace-card" class="connectpro-customer-workspace-card min-w-0 rounded-2xl border border-[#2a4055] bg-[#091827] shadow-2xl">
            <section id="customer-call-panel" class="connectpro-dialer-panel min-w-0 border-0 bg-transparent p-3 shadow-none sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="flex items-center gap-2 text-xl font-semibold text-white"><i class="bi bi-person-fill text-blue-500"></i>{{ __('Customer') }}</h2>
                    <button id="contact-flag-toggle" type="button" disabled class="flex h-9 w-9 items-center justify-center rounded-full border border-[#365068] text-slate-400 transition hover:border-amber-400 hover:text-amber-400 disabled:cursor-not-allowed disabled:opacity-40" title="{{ __('Flag contact') }}" aria-label="{{ __('Flag contact') }}"><i class="bi bi-flag"></i></button>
                </div>

                <div class="grid items-center gap-4 py-4 sm:grid-cols-[96px_minmax(0,1fr)] sm:text-left">
                    <div id="customer-avatar" class="mx-auto flex h-24 w-24 items-center justify-center rounded-full border-4 border-[#20384f] bg-blue-600 text-3xl font-bold text-white shadow-xl">?</div>
                    <div class="min-w-0 text-center sm:text-left">
                    <p id="customer-name" class="max-w-full truncate text-2xl font-bold text-white">{{ __('No customer selected') }}</p>
                    <p id="customer-company" class="mt-1 max-w-full truncate text-base font-medium text-blue-400">{{ __('Call workspace') }}</p>
                    <p id="customer-phone" class="mt-2 max-w-full break-all text-sm text-slate-200"><i class="bi bi-telephone mr-2"></i>—</p>
                    </div>
                </div>

                <audio id="dialer-audio" class="hidden" autoplay playsinline></audio>
            </section>

            <aside id="contact-workspace-panel" class="connectpro-contact-workspace min-w-0 border-0 bg-transparent p-3 shadow-none sm:p-5">
                <div class="relative mb-5 connectpro-contact-search">
                    <label for="contact-search" class="mb-2 block text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Contacts') }}</label>
                    @if (! $contactPermissions['view'])
                        <p class="mb-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-xs text-amber-300">{{ __('Your role does not have permission to view the global contact book.') }}</p>
                    @endif
                    <div class="grid gap-2 sm:grid-cols-[1fr_auto]">
                      <div class="relative">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input id="contact-search" type="search" autocomplete="off" @disabled(! $contactPermissions['view']) placeholder="{{ __('Search name, company, phone…') }}" class="w-full rounded-xl border border-[#365068] bg-[#0b1b2c] py-2.5 pl-10 pr-3 text-sm text-white outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 disabled:opacity-50">
                      </div>
                      <select id="contact-label-filter" @disabled(! $contactPermissions['view']) class="rounded-xl border border-[#365068] bg-[#0b1b2c] px-3 py-2.5 text-sm text-white outline-none focus:border-blue-500 disabled:opacity-50">
                          <option value="">{{ __('All labels') }}</option>
                      </select>
                    </div>
                    <div id="contact-search-results" class="absolute inset-x-0 top-full z-20 mt-2 hidden max-h-64 overflow-y-auto rounded-xl border border-[#365068] bg-[#0b1b2c] p-2 shadow-2xl"></div>
                </div>

                <div class="contact-tabs -mx-3 flex overflow-x-auto border-b border-[#2a4055] px-3 sm:-mx-5 sm:px-5" role="tablist" aria-label="{{ __('Contact workspace') }}">
                    <button type="button" data-contact-tab="notes" class="contact-tab-active flex shrink-0 items-center gap-2 border-b-2 px-3 py-3 text-xs font-semibold sm:text-sm" role="tab" aria-selected="true"><i class="bi bi-file-earmark-text"></i>{{ __('Notes & Comments') }}</button>
                    <button type="button" data-contact-tab="activity" class="flex shrink-0 items-center gap-2 border-b-2 border-transparent px-3 py-3 text-xs font-semibold text-slate-400 sm:text-sm" role="tab" aria-selected="false"><i class="bi bi-activity"></i>{{ __('Activity Log') }}</button>
                    <button type="button" data-contact-tab="history" class="flex shrink-0 items-center gap-2 border-b-2 border-transparent px-3 py-3 text-xs font-semibold text-slate-400 sm:text-sm" role="tab" aria-selected="false"><i class="bi bi-clock-history"></i>{{ __('Call History') }}</button>
                    <button type="button" data-contact-tab="info" class="flex shrink-0 items-center gap-2 border-b-2 border-transparent px-3 py-3 text-xs font-semibold text-slate-400 sm:text-sm" role="tab" aria-selected="false"><i class="bi bi-person"></i>{{ __('Contact Info') }}</button>
                </div>

                <div data-contact-tab-panel="notes" class="pt-5">
                    <div class="flex items-center gap-2 text-lg font-semibold text-white"><i class="bi bi-chat-left-text text-slate-200"></i>{{ __('Notes & Comments') }}</div>
                </div>

                <div data-contact-tab-panel="info" class="mt-4 hidden rounded-xl border border-[#263b50] bg-[#102338] p-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <input id="contact-name-input" type="text" maxlength="255" placeholder="{{ __('Contact name') }}" class="rounded-lg border border-[#365068] bg-[#091827] px-3 py-2.5 text-sm text-white outline-none placeholder:text-slate-400 focus:border-blue-500">
                        <input id="contact-company-input" type="text" maxlength="255" placeholder="{{ __('Company') }}" class="rounded-lg border border-[#365068] bg-[#091827] px-3 py-2.5 text-sm text-white outline-none placeholder:text-slate-400 focus:border-blue-500">
                        <input id="contact-phone-input" type="tel" maxlength="40" placeholder="{{ __('Phone number') }}" class="rounded-lg border border-[#365068] bg-[#091827] px-3 py-2.5 text-sm text-white outline-none placeholder:text-slate-400 focus:border-blue-500">
                        <input id="contact-email-input" type="email" maxlength="255" placeholder="{{ __('Email') }}" class="rounded-lg border border-[#365068] bg-[#091827] px-3 py-2.5 text-sm text-white outline-none placeholder:text-slate-400 focus:border-blue-500">
                        <input id="contact-avatar-input" type="url" maxlength="2048" placeholder="{{ __('Profile image URL') }}" class="rounded-lg border border-[#365068] bg-[#091827] px-3 py-2.5 text-sm text-white outline-none placeholder:text-slate-400 focus:border-blue-500 sm:col-span-2">
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <span id="contact-feedback" class="text-xs text-slate-400"></span>
                        <button id="contact-save" type="button" class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-500">{{ __('Save contact') }}</button>
                    </div>
                </div>

                <div class="connectpro-activity-history-card">
                <div data-contact-tab-panel="activity" class="mt-4 hidden rounded-xl border border-[#263b50] bg-[#102338]">
                    <div class="flex items-center justify-between border-b border-[#263b50] px-4 py-3">
                        <div>
                            <h3 class="font-semibold text-white">{{ __('Activity Log') }}</h3>
                            <p class="mt-1 text-xs text-gray-400">{{ __('Timestamped contact, label, flag, and comment changes') }}</p>
                        </div>
                        <button id="contact-activity-refresh" type="button" disabled class="flex h-9 w-9 items-center justify-center rounded-full border border-[#365068] text-slate-400 hover:border-blue-500 hover:text-blue-400 disabled:opacity-40" title="{{ __('Refresh activity') }}"><i class="bi bi-arrow-clockwise"></i></button>
                    </div>
                    <div id="contact-activity" class="max-h-[32rem] space-y-3 overflow-y-auto p-4"><p class="text-sm text-slate-400">{{ __('Select a saved contact to view activity.') }}</p></div>
                </div>

                <div data-contact-tab-panel="history" class="mt-4 hidden rounded-xl border border-[#263b50] bg-[#102338]">
                    <div class="flex items-center justify-between border-b border-[#263b50] px-4 py-3">
                        <div>
                            <h3 class="font-semibold text-white">{{ __('Call History') }}</h3>
                            <p class="mt-1 text-xs text-gray-400">{{ __('All inbound and outbound calls with this contact') }}</p>
                        </div>
                        <button id="contact-call-history-refresh" type="button" disabled class="flex h-9 w-9 items-center justify-center rounded-full border border-[#365068] text-slate-400 hover:border-blue-500 hover:text-blue-400 disabled:opacity-40" title="{{ __('Refresh call history') }}"><i class="bi bi-arrow-clockwise"></i></button>
                    </div>
                    <div id="contact-call-history" class="max-h-[32rem] space-y-3 overflow-y-auto p-4"><p class="text-sm text-slate-400">{{ __('Select a saved contact to view call history.') }}</p></div>
                </div>
                </div>

                <div data-contact-tab-panel="notes" class="connectpro-notes-card mt-4 rounded-xl border border-[#263b50] bg-[#102338]">
                    <div class="border-b border-[#263b50] px-4 py-3">
                        <h3 class="font-semibold text-white">{{ __('Contact comments') }}</h3>
                        <p class="mt-1 text-xs text-gray-400">{{ __('Shared history for this contact across calls') }}</p>
                    </div>
                    <div id="contact-comments" class="max-h-72 space-y-2 overflow-y-auto p-4"><p class="text-sm text-slate-400">{{ __('Save or select a contact to view comments.') }}</p></div>
                    <div class="border-t border-[#263b50] p-3">
                        <div class="flex items-end gap-2">
                            <textarea id="contact-comment-input" rows="2" maxlength="2000" disabled placeholder="{{ __('Add a comment…') }}" class="min-w-0 flex-1 resize-none rounded-xl border border-[#365068] bg-[#091827] px-3 py-2.5 text-sm text-white outline-none placeholder:text-slate-400 focus:border-blue-500 disabled:opacity-50"></textarea>
                            <button id="contact-comment-add" type="button" disabled class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white hover:bg-blue-500 disabled:opacity-40" title="{{ __('Add comment') }}"><i class="bi bi-send-fill"></i></button>
                        </div>
                    </div>
                </div>

            </aside>
            </section>

            <section class="connectpro-labels-card min-w-0 rounded-2xl border border-[#2a4055] bg-[#091827] p-3 shadow-2xl sm:p-5">
                <h2 class="text-lg font-semibold text-white">{{ __('Labels & Flags') }}</h2>
                <div id="contact-labels" class="mt-4 flex max-w-full flex-wrap gap-2"></div>
                <div class="mt-5">
                    <p class="text-xs font-semibold text-slate-300">{{ __('Follow-up flag') }}</p>
                    <p class="mt-2 text-xs leading-5 text-slate-400">{{ __('Flag contacts for follow-up after a call.') }}</p>
                </div>
                <div class="mt-5 flex w-full gap-2">
                    <input id="contact-label-input" type="text" maxlength="30" disabled placeholder="{{ __('Add label') }}" class="min-w-0 flex-1 rounded-lg border border-[#365068] bg-[#0b1b2c] px-3 py-2 text-xs text-white outline-none placeholder:text-slate-400 focus:border-blue-500 disabled:opacity-50">
                    <button id="contact-label-add" type="button" disabled class="rounded-lg border border-blue-500/50 px-3 py-2 text-xs font-semibold text-blue-400 hover:bg-blue-500/10 disabled:opacity-40">{{ __('Add') }}</button>
                </div>
            </section>
        </div>

        <div id="incoming-call-banner" class="connectpro-incoming fixed inset-0 z-[100] hidden overflow-y-auto p-4 sm:p-8">
            <div class="flex min-h-full items-center justify-center">
                <div class="connectpro-incoming-shell w-full max-w-[480px] text-center text-white">
                    <div class="connectpro-incoming-card relative mx-auto aspect-square w-full max-w-[480px] rounded-[24px] border border-[#344b63] bg-[#081522]/85 px-6 pb-6 pt-5 shadow-[0_30px_100px_rgba(0,0,0,.65)] sm:px-12 sm:pb-7">
                    <p class="text-base font-semibold uppercase tracking-[0.12em] text-blue-400">{{ __('Incoming call') }}</p>
                    <p class="mt-1 text-sm text-slate-300"><span class="mr-2 inline-block h-2 w-2 rounded-full bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,.8)]"></span>{{ __('Ringing...') }}</p>
                    <div class="connectpro-incoming-avatar relative mx-auto mt-5 flex h-36 w-36 items-center justify-center rounded-full border-4 border-[#526c86] bg-[#64748b] text-4xl font-bold text-white shadow-[0_0_0_8px_rgba(59,130,246,.12),0_0_0_18px_rgba(59,130,246,.08),0_0_48px_rgba(37,99,235,.45)]">
                        <span class="absolute inset-[-12px] animate-ping rounded-full border border-blue-500/25"></span><img id="incoming-avatar" src="{{ asset('images/user/user-01.jpg') }}" alt="" class="relative h-full w-full rounded-full object-cover">
                    </div>
                    <h3 id="incoming-caller" class="mt-5 text-3xl font-bold text-white">{{ __('Unknown caller') }}</h3>
                    <p id="incoming-company" class="mt-1 flex items-center justify-center gap-2 text-lg text-blue-400"><i class="bi bi-buildings"></i><span>{{ __('Contact') }}</span></p>
                    <p id="incoming-did" class="mt-1 flex items-center justify-center gap-2 text-base text-slate-300"><i class="bi bi-telephone-fill text-blue-400"></i><span>—</span></p>
                    <div class="mt-8 grid grid-cols-2 gap-4">
                        <button type="button" id="incoming-accept" class="flex items-center justify-center gap-2 rounded-xl bg-emerald-500 py-3.5 text-lg font-semibold text-white shadow-lg shadow-emerald-600/20 hover:bg-emerald-400"><i class="bi bi-telephone-fill text-xl"></i>{{ __('Answer') }}</button>
                        <button type="button" id="incoming-decline" class="flex items-center justify-center gap-2 rounded-xl bg-red-500 py-3.5 text-lg font-semibold text-white shadow-lg shadow-red-600/20 hover:bg-red-400"><i class="bi bi-telephone-x-fill text-xl"></i>{{ __('Decline') }}</button>
                    </div>
                    </div>
                    <div class="connectpro-incoming-context mt-3 grid gap-3 rounded-[20px] border border-[#263d54] bg-[#091827]/85 p-4 text-left sm:px-5">
                        <div class="rounded-xl border border-[#294158] bg-[#071625] p-4"><p class="flex items-center gap-3 text-sm font-semibold text-slate-200"><i class="bi bi-calendar3 text-2xl text-blue-400"></i>{{ __('Last contact') }}</p><p class="mt-2 pl-9 text-sm text-slate-300">{{ __('No recent conversation') }}</p></div>
                        <div class="rounded-xl border border-[#294158] bg-[#071625] p-4"><p class="flex items-center gap-3 text-sm font-semibold text-slate-200"><i class="bi bi-file-earmark-text text-2xl text-blue-400"></i>{{ __('Last note') }}</p><p class="mt-2 pl-9 text-sm leading-5 text-slate-300">{{ __('Call notes and contact context remain available in the workspace.') }}</p></div>
                        <p class="flex items-center justify-center gap-3 text-sm text-slate-300 sm:col-span-2"><i class="bi bi-shield-check text-xl"></i>{{ __('Secure encrypted call') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="active-call-window" class="fixed inset-0 z-[90] hidden overflow-y-auto bg-[#020914]/80 p-4 backdrop-blur-md sm:p-8">
            <div class="mx-auto w-full max-w-[520px] rounded-[30px] border border-[#486078] bg-[#0b1725] p-5 text-white shadow-[0_30px_100px_rgba(0,0,0,.65)] sm:p-7">
                <div class="flex items-center gap-3 border-b border-[#33485d] pb-4"><i class="bi bi-telephone-fill text-xl text-blue-400"></i><span class="text-lg font-medium">{{ __('VoIP Softphone') }}</span><button type="button" data-compact-minimize class="ml-auto text-2xl text-slate-300">−</button><button type="button" data-compact-close class="text-2xl text-slate-300">×</button></div>
                <div class="mt-6 grid items-center gap-4 sm:grid-cols-[104px_minmax(0,1fr)_auto]">
                    <div class="flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-blue-900 text-3xl font-bold shadow-[0_0_0_8px_rgba(59,130,246,.08)]" data-compact-avatar>?</div>
                    <div class="min-w-0"><h2 class="truncate text-2xl font-bold" data-compact-name>{{ __('Unknown caller') }}</h2><p class="mt-1 truncate text-lg text-slate-300" data-compact-phone>—</p><p class="mt-1 flex items-center gap-2 text-sm text-blue-400"><span class="h-2 w-2 rounded-full bg-blue-500"></span>{{ __('Connected') }}</p></div>
                    <span class="rounded-full border border-blue-500 bg-blue-500/10 px-3 py-2 text-sm font-semibold text-blue-400"><i class="bi bi-soundwave mr-1"></i>HD</span>
                </div>
                <div class="py-7 text-center"><p class="font-mono text-5xl tracking-wide" data-compact-timer>00:00</p><p class="mt-2 text-lg text-slate-400">{{ __('Talking time') }}</p></div>
                <div class="grid grid-cols-2 gap-8 border-y border-[#33485d] py-6">
                    <button type="button" data-call-proxy="mute" class="flex flex-col items-center gap-3 text-blue-400"><span class="flex h-20 w-20 items-center justify-center rounded-full border-2 border-blue-500 text-3xl shadow-[0_0_25px_rgba(59,130,246,.2)]"><i data-compact-mute-icon class="bi bi-mic-fill"></i></span><span data-compact-mute-label class="text-lg">{{ __('Mute') }}</span></button>
                    <button type="button" data-call-proxy="hangup" class="flex flex-col items-center gap-3 text-red-400"><span class="flex h-20 w-20 items-center justify-center rounded-full bg-red-600 text-3xl text-white shadow-[0_12px_30px_rgba(220,38,38,.3)]"><i class="bi bi-telephone-x-fill"></i></span><span class="text-lg">{{ __('End Call') }}</span></button>
                </div>
                <button type="button" data-compact-keypad-toggle class="mt-5 flex w-full items-center gap-4 rounded-2xl border border-[#33485d] px-5 py-4 text-left"><i class="bi bi-grid-3x3-gap text-2xl text-blue-400"></i><span><strong class="block text-lg">{{ __('Keypad') }}</strong><span class="text-sm text-slate-400">{{ __('Show dialpad') }}</span></span><i class="bi bi-chevron-down ml-auto text-xl text-slate-400"></i></button>
                <div data-compact-keypad class="mt-3 hidden grid grid-cols-3 gap-2 rounded-2xl border border-[#33485d] bg-[#071625] p-3">@foreach(['1','2','3','4','5','6','7','8','9','*','0','#'] as $compactKey)<button type="button" data-compact-key="{{ $compactKey }}" class="rounded-xl border border-[#33485d] py-2 text-lg hover:border-blue-500 hover:text-blue-400">{{ $compactKey }}</button>@endforeach</div>
                <div class="mt-5 rounded-2xl border border-[#33485d] p-4"><div class="flex items-center gap-3"><i class="bi bi-journal-text text-xl text-blue-400"></i><strong>{{ __('Notes') }}</strong><span class="text-slate-500">• {{ __('Latest') }}</span><a href="{{ route('admin.contacts.index') }}" class="ml-auto text-blue-400"><i class="bi bi-pencil"></i></a></div><p class="mt-3 text-sm italic leading-6 text-slate-300" data-compact-note>{{ __('Call notes and contact context remain available in the workspace.') }}</p><div class="mt-3 flex items-end gap-2"><textarea data-compact-comment-input rows="2" maxlength="2000" disabled placeholder="{{ __('Write a note or comment...') }}" class="min-w-0 flex-1 resize-none rounded-xl border border-[#33485d] bg-[#071625] px-3 py-2 text-sm text-white outline-none placeholder:text-slate-500 focus:border-blue-500 disabled:opacity-50"></textarea><button type="button" data-compact-comment-add disabled class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white hover:bg-blue-500 disabled:opacity-40" title="{{ __('Add comment') }}"><i class="bi bi-send-fill"></i></button></div></div>
                <p class="mt-5 border-t border-[#33485d] pt-4 text-center text-sm text-slate-400">{{ __('Secure') }} <span class="mx-2">•</span> {{ __('Encrypted') }} <i class="bi bi-lock-fill ml-1 text-blue-400"></i></p>
            </div>
        </div>

        <div id="dialer-webrtc-config" data-config='@json($webrtcConfig)' class="hidden" aria-hidden="true"></div>
        <div id="dialer-inbound-socket" data-config='@json($inboundSocket ?? [])' class="hidden" aria-hidden="true"></div>

        <!-- Full Call History Panel (shown when History tab is clicked) -->
        <div id="dialer-full-history" class="hidden mt-4">
            <section class="overflow-hidden rounded-2xl border border-[#294158] bg-[#091827] shadow-2xl shadow-black/20">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#294158] p-4 sm:px-5">
                    <div><h2 class="text-lg font-semibold">{{ __('Call History') }}</h2><p class="mt-1 text-xs text-slate-400">{{ __('All inbound and outbound calls') }}</p></div>
                    <button id="dialer-history-refresh" type="button" class="flex h-9 w-9 items-center justify-center rounded-full border border-[#365068] text-slate-400 hover:border-blue-500 hover:text-blue-400" title="{{ __('Refresh') }}"><i class="bi bi-arrow-clockwise"></i></button>
                </div>
                <!-- Column Headers -->
                <div class="hidden grid-cols-[48px_minmax(160px,1fr)_140px_110px_120px_auto] gap-3 border-b border-[#294158] bg-[#0a1a2e] px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400 sm:grid sm:px-5">
                    <span></span>
                    <span>{{ __('Number') }}</span>
                    <span>{{ __('Direction') }}</span>
                    <span>{{ __('Status') }}</span>
                    <span>{{ __('Duration') }}</span>
                    <span class="text-right">{{ __('Date') }}</span>
                </div>
                 <div id="dialer-history-list" class="divide-y divide-[#1e3347] max-h-[calc(100vh-280px)] overflow-y-auto">
                     <div class="px-6 py-16 text-center text-sm text-slate-400">{{ __('Loading call history...') }}</div>
                 </div>
             </section>
         </div>

         <!-- Full Activity Panel (shown when Activity tab is clicked) -->
         <div id="dialer-full-activity" class="hidden mt-4">
             <section class="overflow-hidden rounded-2xl border border-[#294158] bg-[#091827] shadow-2xl shadow-black/20">
                 <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#294158] p-4 sm:px-5">
                     <div><h2 class="text-lg font-semibold">{{ __('Activity Log') }}</h2><p class="mt-1 text-xs text-slate-400">{{ __('Contact and system activity') }}</p></div>
                     <button id="dialer-activity-refresh" type="button" class="flex h-9 w-9 items-center justify-center rounded-full border border-[#365068] text-slate-400 hover:border-blue-500 hover:text-blue-400" title="{{ __('Refresh') }}"><i class="bi bi-arrow-clockwise"></i></button>
                 </div>
                 <div id="dialer-activity-list" class="divide-y divide-[#1e3347] max-h-[calc(100vh-280px)] overflow-y-auto">
                     <div class="px-6 py-16 text-center text-sm text-slate-400">{{ __('Loading activity...') }}</div>
                 </div>
             </section>
         </div>
     </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('dialer-form');
    const alertBox = document.getElementById('dialer-alert');

    const displayInput = document.getElementById('dialpad-display');
    const hiddenInput = document.getElementById('dialpad-input');
    const dialpadButtons = document.querySelectorAll('.dialpad-key');
    const clearButton = document.getElementById('dialpad-clear');
    const backspaceButton = document.getElementById('dialpad-backspace');

    // Live session UI
    const liveSession = document.getElementById('live-call-session');
    const statusEl = document.getElementById('call-status');
    const alertEl = document.getElementById('call-alert');
    const actionButtons = document.querySelectorAll('[data-action]');
    const callIdBadge = document.getElementById('call-id-badge');
    const callTimerBadge = document.getElementById('call-timer-badge');
    const callTimerEl = document.getElementById('call-timer');
    const browserAudioStatus = document.getElementById('browser-audio-status');
    const webrtcConfigEl = document.getElementById('dialer-webrtc-config');
    const remoteAudioEl = document.getElementById('dialer-audio');
    const webPhoneStateEl = document.getElementById('web-phone-state');
    const webPhoneStateDotEl = document.getElementById('web-phone-state-dot');
    const customerPhoneEl = document.getElementById('customer-phone');
    const customerNameEl = document.getElementById('customer-name');
    const customerCompanyEl = document.getElementById('customer-company');
    const customerAvatarEl = document.getElementById('customer-avatar');
    const incomingAvatarEl = document.getElementById('incoming-avatar');
    const incomingCompanyEl = document.getElementById('incoming-company');
    const incomingPhoneEl = document.querySelector('#incoming-did span');
    const compactCallWindow = document.getElementById('active-call-window');
    const compactNameEl = compactCallWindow?.querySelector('[data-compact-name]');
    const compactPhoneEl = compactCallWindow?.querySelector('[data-compact-phone]');
    const compactAvatarEl = compactCallWindow?.querySelector('[data-compact-avatar]');
    const compactTimerEl = compactCallWindow?.querySelector('[data-compact-timer]');
    const compactMuteButton = compactCallWindow?.querySelector('[data-call-proxy="mute"]');
    const compactMuteIcon = compactCallWindow?.querySelector('[data-compact-mute-icon]');
    const compactMuteLabel = compactCallWindow?.querySelector('[data-compact-mute-label]');
    const compactNoteEl = compactCallWindow?.querySelector('[data-compact-note]');
    const compactCommentInput = compactCallWindow?.querySelector('[data-compact-comment-input]');
    const compactCommentAddBtn = compactCallWindow?.querySelector('[data-compact-comment-add]');
    const toolbarSearchEl = document.querySelector('[data-dialer-toolbar-search]');
    const contactSearchEl = document.getElementById('contact-search');
    const contactSearchResultsEl = document.getElementById('contact-search-results');
    const contactLabelFilterEl = document.getElementById('contact-label-filter');
    const contactFlagBtn = document.getElementById('contact-flag-toggle');
    const contactLabelsEl = document.getElementById('contact-labels');
    const contactLabelInput = document.getElementById('contact-label-input');
    const contactLabelAddBtn = document.getElementById('contact-label-add');
    const contactNameInput = document.getElementById('contact-name-input');
    const contactCompanyInput = document.getElementById('contact-company-input');
    const contactPhoneInput = document.getElementById('contact-phone-input');
    const contactEmailInput = document.getElementById('contact-email-input');
    const contactAvatarInput = document.getElementById('contact-avatar-input');
    const contactSaveBtn = document.getElementById('contact-save');
    const contactFeedbackEl = document.getElementById('contact-feedback');
    const contactCommentsEl = document.getElementById('contact-comments');
    const contactCommentInput = document.getElementById('contact-comment-input');
    const contactCommentAddBtn = document.getElementById('contact-comment-add');
    const contactTabButtons = document.querySelectorAll('[data-contact-tab]');
    const contactTabPanels = document.querySelectorAll('[data-contact-tab-panel]');
    const contactActivityEl = document.getElementById('contact-activity');
    const contactActivityRefreshBtn = document.getElementById('contact-activity-refresh');
    const contactCallHistoryEl = document.getElementById('contact-call-history');
    const contactCallHistoryRefreshBtn = document.getElementById('contact-call-history-refresh');

    if (!form) return;

    const csrfToken = form.querySelector('input[name="_token"]').value;
    const startCallButton = form.querySelector('button[type="submit"]');
    const contactsUrl = @json(route('admin.dialer.contacts.index'));
    const contactPermissions = @json($contactPermissions);

    let activeContact = null;
    let inboundCall = null;
    let contactSearchTimer = null;
    let dialContactLookupTimer = null;
    let lastContactLookupPhone = '';
    let activeContactTab = 'notes';
    let availableContactLabels = [];

    const normalizeContactPhone = (value = '') => String(value).replace(/\D+/g, '');
    const escapeContactText = (value = '') => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const contactInitials = (name = '') => {
        const parts = String(name).trim().split(/\s+/).filter(Boolean);
        return (parts.slice(0, 2).map((part) => part[0]).join('') || '?').toUpperCase();
    };

    const generateInitialsAvatarUrl = (name) => {
        const initials = contactInitials(name);
        const colors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'];
        const colorIndex = name.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0) % colors.length;
        const bgColor = colors[colorIndex];
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="144" height="144" viewBox="0 0 144 144"><rect width="144" height="144" rx="72" fill="${bgColor}"/><text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" fill="white" font-family="Arial,sans-serif" font-size="56" font-weight="bold">${initials}</text></svg>`;
        return 'data:image/svg+xml,' + encodeURIComponent(svg);
    };

    const updateIncomingContact = (contact, fallbackPhone = '') => {
        if (typeof inboundCall === 'undefined' || !inboundCall) return;
        const phone = contact?.phone || fallbackPhone || inboundCall.callerIdNumber || inboundCall.did || '—';
        if (incomingCallerEl) incomingCallerEl.textContent = contact?.name || inboundCall.callerIdNumber || '{{ __('Unknown caller') }}';
        if (incomingCompanyEl) {
            incomingCompanyEl.querySelector('span').textContent = contact?.company || '{{ __('Contact') }}';
        }
        if (incomingPhoneEl) incomingPhoneEl.textContent = phone;
        if (incomingAvatarEl) {
            const avatarContainer = incomingAvatarEl.closest('.connectpro-incoming-avatar');
            if (contact?.avatar_url) {
                incomingAvatarEl.src = contact.avatar_url;
                incomingAvatarEl.alt = contact.name || '{{ __('Caller') }}';
                incomingAvatarEl.style.display = 'block';
                if (avatarContainer) avatarContainer.style.display = 'flex';
            } else if (contact?.name) {
                incomingAvatarEl.src = generateInitialsAvatarUrl(contact.name);
                incomingAvatarEl.alt = contact.name;
                incomingAvatarEl.style.display = 'block';
                if (avatarContainer) avatarContainer.style.display = 'flex';
            } else {
                incomingAvatarEl.removeAttribute('src');
                incomingAvatarEl.alt = '';
                incomingAvatarEl.style.display = 'none';
                if (avatarContainer) avatarContainer.style.display = 'none';
            }
        }
    };

    const setContactFeedback = (message = '', error = false) => {
        if (!contactFeedbackEl) return;
        contactFeedbackEl.textContent = message;
        contactFeedbackEl.classList.toggle('text-red-400', error);
        contactFeedbackEl.classList.toggle('text-slate-400', !error);
    };

    const renderContactComments = (comments = []) => {
        if (!contactCommentsEl) return;
        if (compactNoteEl) compactNoteEl.textContent = comments[0]?.body || '{{ __('No notes yet.') }}';
        if (!comments.length) {
            contactCommentsEl.innerHTML = `<p class="text-sm text-slate-400">${activeContact ? '{{ __('No comments yet.') }}' : '{{ __('Save or select a contact to view comments.') }}'}</p>`;
            return;
        }
        contactCommentsEl.innerHTML = comments.map((comment) => {
            const author = comment.user?.external_name || comment.user?.email || '{{ __('User') }}';
            const timestamp = comment.created_at ? new Date(comment.created_at).toLocaleString() : '';
            return `<article class="rounded-xl border border-[#263b50] bg-[#091827] px-3 py-2.5">
                <div class="flex items-center justify-between gap-3 text-xs">
                    <strong class="truncate text-slate-200">${escapeContactText(author)}</strong>
                    <time class="shrink-0 text-slate-500">${escapeContactText(timestamp)}</time>
                </div>
                <p class="mt-1 whitespace-pre-wrap break-words text-sm leading-5 text-slate-300">${escapeContactText(comment.body)}</p>
            </article>`;
        }).join('');
    };

    const syncCommentInputs = () => {
        const enabled = Boolean(activeContact && contactPermissions.comment);
        if (contactCommentInput) contactCommentInput.disabled = !enabled;
        if (contactCommentAddBtn) contactCommentAddBtn.disabled = !enabled;
        if (compactCommentInput) compactCommentInput.disabled = !enabled;
        if (compactCommentAddBtn) compactCommentAddBtn.disabled = !enabled;
    };

    const addContactComment = async (input, button) => {
        const body = input?.value.trim();
        if (!activeContact || !body || !contactPermissions.comment) return;
        try {
            if (button) button.disabled = true;
            const data = await contactRequest(`${contactsUrl}/${activeContact.id}/comments`, {
                method: 'POST',
                body: JSON.stringify({ body })
            });
            if (contactCommentInput) contactCommentInput.value = '';
            if (compactCommentInput) compactCommentInput.value = '';
            activeContact.comments = [data.comment, ...(activeContact.comments || [])];
            renderContactComments(activeContact.comments);
            setContactFeedback('{{ __('Comment added') }}');
        } catch (error) {
            setContactFeedback(error.message || '{{ __('Unable to add comment') }}', true);
        } finally {
            syncCommentInputs();
        }
    };

    const renderContactLabels = () => {
        if (!contactLabelsEl) return;
        const labels = activeContact?.labels || [];
        contactLabelsEl.innerHTML = labels.map((label) => `<button type="button" data-contact-label="${escapeContactText(label)}" ${contactPermissions.labels ? '' : 'disabled'} class="inline-flex items-center gap-1.5 rounded-full border border-blue-500/40 bg-blue-500/10 px-2.5 py-1 text-xs font-semibold text-blue-300 disabled:cursor-default" title="${contactPermissions.labels ? '{{ __('Remove label') }}' : '{{ __('Label') }}'}">${escapeContactText(label)} ${contactPermissions.labels ? '<i class="bi bi-x"></i>' : ''}</button>`).join('');
        contactLabelsEl.querySelectorAll('[data-contact-label]').forEach((button) => {
            button.addEventListener('click', () => updateActiveContact({
                labels: labels.filter((label) => label !== button.dataset.contactLabel)
            }));
        });
    };

    const renderContact = (contact, fallbackPhone = '') => {
        activeContact = contact || null;
        const phone = contact?.phone || fallbackPhone || '';
        if (customerNameEl) customerNameEl.textContent = contact?.name || '{{ __('Unknown caller') }}';
        if (customerCompanyEl) customerCompanyEl.textContent = contact?.company || (contact ? '{{ __('Contact') }}' : '{{ __('Not saved as contact') }}');
        if (customerPhoneEl) customerPhoneEl.innerHTML = `<i class="bi bi-telephone mr-2"></i>${escapeContactText(phone || '—')}`;
        if (customerAvatarEl) customerAvatarEl.textContent = contactInitials(contact?.name || '');
        updateIncomingContact(contact, phone);
        if (contactNameInput) contactNameInput.value = contact?.name || '';
        if (contactCompanyInput) contactCompanyInput.value = contact?.company || '';
        if (contactPhoneInput) contactPhoneInput.value = phone;
        if (contactEmailInput) contactEmailInput.value = contact?.email || '';
        if (contactAvatarInput) contactAvatarInput.value = contact?.avatar_url || '';
        if (contactFlagBtn) {
            contactFlagBtn.disabled = !contact || !contactPermissions.edit;
            contactFlagBtn.classList.toggle('border-amber-400', Boolean(contact?.is_flagged));
            contactFlagBtn.classList.toggle('bg-amber-400/10', Boolean(contact?.is_flagged));
            contactFlagBtn.classList.toggle('text-amber-400', Boolean(contact?.is_flagged));
            const icon = contactFlagBtn.querySelector('i');
            if (icon) icon.className = contact?.is_flagged ? 'bi bi-flag-fill' : 'bi bi-flag';
        }
        if (contactLabelInput) contactLabelInput.disabled = !contact || !contactPermissions.labels;
        if (contactLabelAddBtn) contactLabelAddBtn.disabled = !contact || !contactPermissions.labels;
        syncCommentInputs();
        if (contactActivityRefreshBtn) contactActivityRefreshBtn.disabled = !contact || !contactPermissions.view;
        if (contactCallHistoryRefreshBtn) contactCallHistoryRefreshBtn.disabled = !contact || !contactPermissions.view;
        if (contactSaveBtn) contactSaveBtn.disabled = contact ? !contactPermissions.edit : !contactPermissions.create;
        if (contactSaveBtn) contactSaveBtn.textContent = contact ? '{{ __('Update contact') }}' : '{{ __('Save contact') }}';
        renderContactLabels();
        renderContactComments(contact?.comments || []);
        if (activeContactTab === 'activity') loadContactActivity();
        if (activeContactTab === 'history') loadContactCallHistory();
    };

    const contactRequest = async (url, options = {}) => {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                ...(options.headers || {})
            }
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const validationMessage = data.errors ? Object.values(data.errors).flat()[0] : null;
            throw new Error(validationMessage || data.message || `HTTP ${response.status}`);
        }
        return data;
    };

    const updateContactLabelOptions = (labels = []) => {
        availableContactLabels = labels;
        if (!contactLabelFilterEl) return;
        const selected = contactLabelFilterEl.value;
        contactLabelFilterEl.innerHTML = `<option value="">{{ __('All labels') }}</option>${labels.map((label) => `<option value="${escapeContactText(label)}">${escapeContactText(label)}</option>`).join('')}`;
        contactLabelFilterEl.value = labels.includes(selected) ? selected : '';
    };

    const refreshGlobalContactLabels = async () => {
        if (!contactPermissions.view) return;
        try {
            const data = await contactRequest(contactsUrl);
            updateContactLabelOptions(data.labels || []);
        } catch (error) {
            // A contact save already succeeded at this point. Keep the workspace
            // usable and let the next search/load retry the filter refresh.
        }
    };

    const renderContactActivity = (activity = []) => {
        if (!contactActivityEl) return;
        if (!activeContact) {
            contactActivityEl.innerHTML = '<p class="text-sm text-slate-400">{{ __('Select a saved contact to view activity.') }}</p>';
            return;
        }
        if (!activity.length) {
            contactActivityEl.innerHTML = '<p class="text-sm text-slate-400">{{ __('No contact changes recorded yet.') }}</p>';
            return;
        }
        contactActivityEl.innerHTML = activity.map((item) => {
            const timestamp = item.created_at ? new Date(item.created_at).toLocaleString() : '';
            const actor = item.user?.external_name || item.user?.email || '{{ __('Deleted user') }}';
            const action = String(item.action || '').replaceAll('_', ' ');
            const labelAction = String(item.action || '').startsWith('label_');
            const commentAction = String(item.action || '').startsWith('comment_');
            const icon = labelAction ? 'bi-tag' : (commentAction ? 'bi-chat-left-text' : (String(item.action || '').startsWith('flag_') ? 'bi-flag' : 'bi-person-check'));
            const palette = labelAction
                ? { border: 'border-amber-500/50', dot: 'bg-amber-500', text: 'text-amber-400' }
                : (commentAction
                    ? { border: 'border-violet-500/50', dot: 'bg-violet-500', text: 'text-violet-400' }
                    : { border: 'border-blue-500/50', dot: 'bg-blue-500', text: 'text-blue-400' });
            return `<article class="relative border-l-2 ${palette.border} pl-4">
                <span class="absolute -left-[7px] top-1 flex h-3 w-3 rounded-full ${palette.dot}"></span>
                <div class="rounded-xl border border-[#263b50] bg-[#091827] p-3">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <strong class="flex items-center gap-2 text-sm capitalize text-slate-100"><i class="bi ${icon} ${palette.text}"></i>${escapeContactText(action)}</strong>
                            <p class="mt-1 text-xs text-slate-400">{{ __('by') }} ${escapeContactText(actor)}</p>
                        </div>
                        <time class="text-[11px] text-slate-500">${escapeContactText(timestamp)}</time>
                    </div>
                    <p class="mt-2 text-xs leading-5 text-slate-300">${escapeContactText(item.description || '')}</p>
                </div>
            </article>`;
        }).join('');
    };

    const loadContactActivity = async () => {
        if (!activeContact) {
            renderContactActivity([]);
            return;
        }
        if (contactActivityRefreshBtn) contactActivityRefreshBtn.disabled = true;
        if (contactActivityEl) contactActivityEl.innerHTML = '<p class="text-sm text-slate-400">{{ __('Loading activity…') }}</p>';
        try {
            const data = await contactRequest(`${contactsUrl}/${activeContact.id}/activity`);
            renderContactActivity(data.activity || []);
        } catch (error) {
            if (contactActivityEl) contactActivityEl.innerHTML = `<p class="text-sm text-red-400">${escapeContactText(error.message || '{{ __('Unable to load activity') }}')}</p>`;
        } finally {
            if (contactActivityRefreshBtn) contactActivityRefreshBtn.disabled = !activeContact || !contactPermissions.view;
        }
    };

    const renderContactCallHistory = (calls = []) => {
        if (!contactCallHistoryEl) return;
        if (!activeContact) {
            contactCallHistoryEl.innerHTML = '<p class="text-sm text-slate-400">{{ __('Select a saved contact to view call history.') }}</p>';
            return;
        }
        if (!calls.length) {
            contactCallHistoryEl.innerHTML = '<p class="text-sm text-slate-400">{{ __('No calls recorded for this contact yet.') }}</p>';
            return;
        }
        contactCallHistoryEl.innerHTML = calls.map((item) => {
            const inbound = item.direction === 'inbound';
            const timestamp = item.created_at ? new Date(item.created_at).toLocaleString() : '';
            const duration = Number(item.duration_seconds || 0);
            const durationText = duration > 0 ? `${Math.floor(duration / 60)}m ${duration % 60}s` : '';
            const status = String(item.status || '{{ __('Unknown') }}').replaceAll('_', ' ');
            const number = inbound ? (item.caller_id || item.destination) : (item.destination || item.caller_id);
            const agent = item.user?.external_name || item.user?.email || '';
            return `<article class="relative border-l-2 ${inbound ? 'border-emerald-500/50' : 'border-blue-500/50'} pl-4">
                <span class="absolute -left-[7px] top-1 flex h-3 w-3 rounded-full ${inbound ? 'bg-emerald-500' : 'bg-blue-500'}"></span>
                <div class="rounded-xl border border-[#263b50] bg-[#091827] p-3">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div><strong class="flex items-center gap-2 text-sm text-slate-100"><i class="bi ${inbound ? 'bi-telephone-inbound text-emerald-400' : 'bi-telephone-outbound text-blue-400'}"></i>${inbound ? '{{ __('Inbound call') }}' : '{{ __('Outbound call') }}'}</strong><p class="mt-1 break-all text-xs text-slate-400">${escapeContactText(number || '—')}</p></div>
                        <time class="text-[11px] text-slate-500">${escapeContactText(timestamp)}</time>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px]"><span class="rounded-full bg-slate-700/60 px-2 py-1 capitalize text-slate-300">${escapeContactText(status)}</span>${durationText ? `<span class="text-slate-400"><i class="bi bi-clock mr-1"></i>${durationText}</span>` : ''}${agent ? `<span class="text-slate-400"><i class="bi bi-person mr-1"></i>${escapeContactText(agent)}</span>` : ''}</div>
                    ${item.notes ? `<p class="mt-2 line-clamp-3 whitespace-pre-wrap text-xs leading-5 text-slate-300">${escapeContactText(item.notes)}</p>` : ''}
                </div>
            </article>`;
        }).join('');
    };

    const loadContactCallHistory = async () => {
        if (!activeContact) return renderContactCallHistory([]);
        if (contactCallHistoryRefreshBtn) contactCallHistoryRefreshBtn.disabled = true;
        if (contactCallHistoryEl) contactCallHistoryEl.innerHTML = '<p class="text-sm text-slate-400">{{ __('Loading call history…') }}</p>';
        try {
            const data = await contactRequest(`${contactsUrl}/${activeContact.id}/call-history`);
            renderContactCallHistory(data.calls || []);
        } catch (error) {
            if (contactCallHistoryEl) contactCallHistoryEl.innerHTML = `<p class="text-sm text-red-400">${escapeContactText(error.message || '{{ __('Unable to load call history') }}')}</p>`;
        } finally {
            if (contactCallHistoryRefreshBtn) contactCallHistoryRefreshBtn.disabled = !activeContact || !contactPermissions.view;
        }
    };

    const activateContactTab = (tab) => {
        activeContactTab = tab;
        contactTabButtons.forEach((button) => {
            const active = button.dataset.contactTab === tab;
            button.setAttribute('aria-selected', active ? 'true' : 'false');
            button.classList.toggle('contact-tab-active', active);
            button.classList.toggle('border-transparent', !active);
            button.classList.toggle('text-slate-400', !active);
        });
        contactTabPanels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.contactTabPanel !== tab));
        if (tab === 'activity') loadContactActivity();
        if (tab === 'history') loadContactCallHistory();
    };

    contactTabButtons.forEach((button) => button.addEventListener('click', () => activateContactTab(button.dataset.contactTab)));
    contactActivityRefreshBtn?.addEventListener('click', loadContactActivity);
    contactCallHistoryRefreshBtn?.addEventListener('click', loadContactCallHistory);

    // Top bar History tab - show full call history within dialer page
    const dialerMainContent = document.querySelector('.connectpro-two-panel-grid');
    const dialerHistoryPanel = document.getElementById('dialer-full-history');
    const dialerHistoryList = document.getElementById('dialer-history-list');
    const dialerHistoryRefreshBtn = document.getElementById('dialer-history-refresh');
    const dialerActivityPanel = document.getElementById('dialer-full-activity');
    const dialerActivityList = document.getElementById('dialer-activity-list');
    const dialerActivityRefreshBtn = document.getElementById('dialer-activity-refresh');
    const topBarLinks = document.querySelectorAll('.connectpro-reference-nav a');

    const loadFullCallHistory = async () => {
        if (!dialerHistoryList) return;
        dialerHistoryList.innerHTML = '<div class="px-6 py-16 text-center text-sm text-slate-400">{{ __('Loading call history...') }}</div>';
        try {
            const response = await fetch('/admin/contacts/call-history?format=json', {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (data.calls && data.calls.data && data.calls.data.length > 0) {
                dialerHistoryList.innerHTML = data.calls.data.map((call) => {
                    const isMissed = ['failed', 'missed', 'declined', 'busy', 'no_answer'].includes((call.status || '').toLowerCase());
                    const directionIcon = call.direction === 'inbound' ? 'bi-telephone-inbound' : 'bi-telephone-outbound';
                    const statusColor = isMissed ? 'text-red-400' : 'text-emerald-400';
                    const iconBg = isMissed ? 'bg-red-500/10 text-red-400' : 'bg-blue-500/10 text-blue-400';
                    const duration = call.duration_seconds ? String(Math.floor(call.duration_seconds / 60)).padStart(2, '0') + ':' + String(call.duration_seconds % 60).padStart(2, '0') : '00:00';
                    const number = call.direction === 'inbound' ? call.caller_id : call.destination;
                    const date = call.created_at ? new Date(call.created_at).toLocaleString() : '';
                    return `<article class="grid items-center gap-3 px-4 py-4 transition hover:bg-white/[.025] sm:grid-cols-[48px_minmax(160px,1fr)_140px_110px_120px_auto] sm:px-5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full ${iconBg}"><i class="bi ${directionIcon} text-lg"></i></span>
                        <div class="min-w-0"><p class="truncate font-semibold">${number || '{{ __('Unknown') }}'}</p><p class="truncate text-xs text-slate-400">${call.user?.external_name || call.user?.email || ''}</p></div>
                        <span class="text-sm capitalize text-slate-300">${(call.direction || 'outbound').replace('_', ' ')}</span>
                        <span class="text-sm ${statusColor}">${(call.status || 'unknown').replace('_', ' ')}</span>
                        <span class="text-sm text-slate-400">${duration}</span>
                        <div class="flex items-center justify-end gap-2"><span class="hidden text-xs text-slate-500 xl:inline">${date}</span><a href="{{ route('admin.dialer.index') }}?destination=${number}" class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-600 text-white hover:bg-emerald-500"><i class="bi bi-telephone-fill"></i></a></div>
                    </article>`;
                }).join('');
            } else {
                dialerHistoryList.innerHTML = '<div class="px-6 py-16 text-center text-sm text-slate-400">{{ __('No calls have been recorded yet.') }}</div>';
            }
        } catch (err) {
            dialerHistoryList.innerHTML = '<div class="px-6 py-16 text-center text-sm text-red-400">{{ __('Failed to load call history') }}</div>';
        }
    };

    const loadFullActivity = async () => {
        if (!dialerActivityList) return;
        dialerActivityList.innerHTML = '<div class="px-6 py-16 text-center text-sm text-slate-400">{{ __('Loading activity...') }}</div>';
        try {
            const response = await fetch('/admin/contacts/activity?format=json', {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (data.activities && data.activities.data && data.activities.data.length > 0) {
                dialerActivityList.innerHTML = data.activities.data.map((activity) => {
                    const contact = activity.contact;
                    const user = activity.user;
                    const date = activity.created_at ? new Date(activity.created_at).toLocaleString() : '';
                    const iconMap = {
                        'contact_created': { icon: 'bi-person-plus', color: 'emerald' },
                        'contact_updated': { icon: 'bi-pencil', color: 'blue' },
                        'comment_added': { icon: 'bi-chat-left-text', color: 'cyan' },
                        'label_added': { icon: 'bi-tag', color: 'purple' },
                        'flag_toggled': { icon: 'bi-flag', color: 'amber' }
                    };
                    const style = iconMap[activity.action] || { icon: 'bi-activity', color: 'slate' };
                    const colorClasses = {
                        emerald: 'bg-emerald-500/15 text-emerald-400 ring-emerald-500/20',
                        blue: 'bg-blue-500/15 text-blue-400 ring-blue-500/20',
                        cyan: 'bg-cyan-500/15 text-cyan-400 ring-cyan-500/20',
                        purple: 'bg-purple-500/15 text-purple-400 ring-purple-500/20',
                        amber: 'bg-amber-500/15 text-amber-400 ring-amber-500/20',
                        slate: 'bg-slate-500/15 text-slate-400 ring-slate-500/20'
                    };
                    const iconColor = colorClasses[style.color] || colorClasses.slate;

                    // Build detailed description from changes
                    let detailHtml = '';
                    if (activity.changes) {
                        if (activity.changes.comment) {
                            detailHtml = `<p class="mt-2 text-xs text-slate-300 bg-slate-800/50 rounded-lg px-3 py-2 border border-slate-700/50">"${activity.changes.comment.new}"</p>`;
                        } else if (activity.changes.contact) {
                            const changes = activity.changes.contact;
                            if (changes.new) {
                                const newVals = changes.new;
                                const details = [];
                                if (newVals.name) details.push(`<span class="text-slate-400">Name:</span> <span class="text-white">${newVals.name}</span>`);
                                if (newVals.company) details.push(`<span class="text-slate-400">Company:</span> <span class="text-white">${newVals.company}</span>`);
                                if (newVals.phone) details.push(`<span class="text-slate-400">Phone:</span> <span class="text-white">${newVals.phone}</span>`);
                                if (newVals.email) details.push(`<span class="text-slate-400">Email:</span> <span class="text-white">${newVals.email}</span>`);
                                if (newVals.labels) details.push(`<span class="text-slate-400">Labels:</span> <span class="text-purple-300">${Array.isArray(newVals.labels) ? newVals.labels.join(', ') : newVals.labels}</span>`);
                                if (details.length > 0) {
                                    detailHtml = `<div class="mt-2 text-xs space-y-1 bg-slate-800/50 rounded-lg px-3 py-2 border border-slate-700/50">${details.join('<br>')}</div>`;
                                }
                            }
                        }
                    }

                    return `<article class="flex items-start gap-3 px-4 py-4 transition hover:bg-white/[.025] sm:px-5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full ring-1 ${iconColor}"><i class="bi ${style.icon} text-lg"></i></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-white">${activity.description || activity.action.replace(/_/g, ' ')}</p>
                            ${detailHtml}
                            <p class="mt-1.5 text-xs text-slate-400">
                                ${contact ? `<span class="font-medium text-blue-400">${contact.name || contact.company || '{{ __('Unknown') }}'}</span>` : ''}
                                ${user ? ` <span class="text-slate-500">by</span> <span class="text-slate-300">${user.external_name || user.email || ''}</span>` : ''}
                            </p>
                        </div>
                        <span class="shrink-0 text-xs text-slate-500">${date}</span>
                    </article>`;
                }).join('');
            } else {
                dialerActivityList.innerHTML = '<div class="px-6 py-16 text-center text-sm text-slate-400">{{ __('No activity recorded yet.') }}</div>';
            }
        } catch (err) {
            dialerActivityList.innerHTML = '<div class="px-6 py-16 text-center text-sm text-red-400">{{ __('Failed to load activity') }}</div>';
        }
    };

    topBarLinks.forEach((link) => {
        const tabText = link.textContent.trim();
        if (tabText === '{{ __('History') }}') {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                if (dialerMainContent && dialerHistoryPanel && dialerActivityPanel) {
                    dialerMainContent.classList.add('hidden');
                    dialerHistoryPanel.classList.remove('hidden');
                    dialerActivityPanel.classList.add('hidden');
                    // Update active tab highlighting
                    topBarLinks.forEach(l => l.classList.remove('connectpro-reference-nav-active'));
                    link.classList.add('connectpro-reference-nav-active');
                    loadFullCallHistory();
                }
            });
        } else if (tabText === '{{ __('Activity') }}') {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                if (dialerMainContent && dialerHistoryPanel && dialerActivityPanel) {
                    dialerMainContent.classList.add('hidden');
                    dialerActivityPanel.classList.remove('hidden');
                    dialerHistoryPanel.classList.add('hidden');
                    // Update active tab highlighting
                    topBarLinks.forEach(l => l.classList.remove('connectpro-reference-nav-active'));
                    link.classList.add('connectpro-reference-nav-active');
                    loadFullActivity();
                }
            });
        } else {
            // Other tabs (Contacts, Live calls, etc.) go back to main dialer view
            link.addEventListener('click', (e) => {
                if (dialerMainContent && dialerHistoryPanel && dialerActivityPanel) {
                    const isOnHistory = dialerHistoryPanel.classList.contains('hidden') === false;
                    const isOnActivity = dialerActivityPanel.classList.contains('hidden') === false;
                    if (isOnHistory || isOnActivity) {
                        if (link.getAttribute('href') === '#' || link.getAttribute('href') === window.location.href.split('#')[0] + '#') {
                            e.preventDefault();
                        }
                        dialerMainContent.classList.remove('hidden');
                        dialerHistoryPanel.classList.add('hidden');
                        dialerActivityPanel.classList.add('hidden');
                        // Update active tab highlighting
                        topBarLinks.forEach(l => l.classList.remove('connectpro-reference-nav-active'));
                        link.classList.add('connectpro-reference-nav-active');
                    }
                }
            });
        }
    });

    dialerHistoryRefreshBtn?.addEventListener('click', loadFullCallHistory);
    dialerActivityRefreshBtn?.addEventListener('click', loadFullActivity);

    const updateActiveContact = async (changes) => {
        if (!activeContact) return;
        try {
            setContactFeedback('{{ __('Saving…') }}');
            const data = await contactRequest(`${contactsUrl}/${activeContact.id}`, {
                method: 'PATCH',
                body: JSON.stringify(changes)
            });
            renderContact(data.contact);
            await refreshGlobalContactLabels();
            setContactFeedback('{{ __('Saved') }}');
        } catch (error) {
            setContactFeedback(error.message || '{{ __('Unable to save contact') }}', true);
        }
    };

    const lookupContactByPhone = async (phone, force = false) => {
        if (!contactPermissions.view) return null;
        const normalized = normalizeContactPhone(phone);
        if (!normalized) {
            lastContactLookupPhone = '';
            renderContact(null, phone);
            return null;
        }
        if (!force && normalized === lastContactLookupPhone && activeContact?.phone_normalized === normalized) {
            return activeContact;
        }
        lastContactLookupPhone = normalized;
        try {
            const data = await contactRequest(`${contactsUrl}?phone=${encodeURIComponent(phone)}`);
            updateContactLabelOptions(data.labels || availableContactLabels);
            const contact = data.contacts?.[0] || null;
            renderContact(contact, phone);
            return contact;
        } catch (error) {
            setContactFeedback('{{ __('Contact lookup failed') }}', true);
            return null;
        }
    };

    const renderContactSearchResults = (contacts = []) => {
        if (!contactSearchResultsEl) return;
        if (!contacts.length) {
            contactSearchResultsEl.innerHTML = '<p class="px-3 py-2 text-sm text-slate-400">{{ __('No contacts found') }}</p>';
        } else {
            contactSearchResultsEl.innerHTML = contacts.map((contact) => `<button type="button" data-contact-id="${contact.id}" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left hover:bg-white/5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">${contactInitials(contact.name)}</span>
                <span class="min-w-0 flex-1"><strong class="block truncate text-sm text-white">${escapeContactText(contact.name)}</strong><span class="block truncate text-xs text-slate-400">${escapeContactText(contact.company || contact.phone)}</span><span class="mt-1 flex flex-wrap gap-1">${(contact.labels || []).map((label) => `<span class="rounded-full bg-blue-500/10 px-1.5 py-0.5 text-[10px] text-blue-300">${escapeContactText(label)}</span>`).join('')}</span></span>
                ${contact.is_flagged ? '<i class="bi bi-flag-fill ml-auto text-amber-400"></i>' : ''}
            </button>`).join('');
            contactSearchResultsEl.querySelectorAll('[data-contact-id]').forEach((button) => {
                button.addEventListener('click', () => {
                    const contact = contacts.find((item) => String(item.id) === button.dataset.contactId);
                    if (contact) {
                        renderContact(contact);
                        if (displayInput && !callActive) displayInput.value = contact.phone;
                        if (hiddenInput && !callActive) hiddenInput.value = contact.phone;
                    }
                    contactSearchResultsEl.classList.add('hidden');
                    contactSearchEl.value = '';
                });
            });
        }
        contactSearchResultsEl.classList.remove('hidden');
    };

    const runContactSearch = async () => {
        if (!contactPermissions.view) return;
        const search = contactSearchEl?.value.trim() || '';
        const label = contactLabelFilterEl?.value || '';
        if (!search && !label) {
            contactSearchResultsEl?.classList.add('hidden');
            return;
        }
        try {
            const query = new URLSearchParams();
            if (search) query.set('search', search);
            if (label) query.set('label', label);
            const data = await contactRequest(`${contactsUrl}?${query.toString()}`);
            updateContactLabelOptions(data.labels || []);
            if (label && contactLabelFilterEl) contactLabelFilterEl.value = label;
            renderContactSearchResults(data.contacts || []);
        } catch (error) {
            setContactFeedback('{{ __('Contact search failed') }}', true);
        }
    };

    contactSearchEl?.addEventListener('input', () => {
        window.clearTimeout(contactSearchTimer);
        const search = contactSearchEl.value.trim();
        if (!search && !contactLabelFilterEl?.value) {
            contactSearchResultsEl?.classList.add('hidden');
            return;
        }
        contactSearchTimer = window.setTimeout(runContactSearch, 250);
    });
    toolbarSearchEl?.addEventListener('input', () => {
        if (!contactSearchEl) return;
        contactSearchEl.value = toolbarSearchEl.value;
        window.clearTimeout(contactSearchTimer);
        contactSearchTimer = window.setTimeout(runContactSearch, 250);
    });
    contactLabelFilterEl?.addEventListener('change', runContactSearch);

    document.addEventListener('click', (event) => {
        if (!contactSearchEl?.contains(event.target) && !contactSearchResultsEl?.contains(event.target)) {
            contactSearchResultsEl?.classList.add('hidden');
        }
    });

    contactSaveBtn?.addEventListener('click', async () => {
        try {
            setContactFeedback('{{ __('Saving…') }}');
            const payload = {
                name: contactNameInput?.value.trim() || '',
                company: contactCompanyInput?.value.trim() || null,
                phone: contactPhoneInput?.value.trim() || displayInput?.value.trim() || '',
                email: contactEmailInput?.value.trim() || null,
                avatar_url: contactAvatarInput?.value.trim() || null,
                is_flagged: Boolean(activeContact?.is_flagged)
            };
            if (contactPermissions.labels) payload.labels = activeContact?.labels || [];
            const data = activeContact
                ? await contactRequest(`${contactsUrl}/${activeContact.id}`, { method: 'PATCH', body: JSON.stringify(payload) })
                : await contactRequest(contactsUrl, { method: 'POST', body: JSON.stringify(payload) });
            lastContactLookupPhone = data.contact.phone_normalized;
            renderContact(data.contact);
            await refreshGlobalContactLabels();
            setContactFeedback('{{ __('Contact saved') }}');
        } catch (error) {
            setContactFeedback(error.message || '{{ __('Unable to save contact') }}', true);
        }
    });

    contactFlagBtn?.addEventListener('click', () => updateActiveContact({ is_flagged: !activeContact?.is_flagged }));
    contactLabelAddBtn?.addEventListener('click', () => {
        const label = contactLabelInput?.value.trim();
        if (!activeContact || !label) return;
        const labels = [...(activeContact.labels || []), label];
        contactLabelInput.value = '';
        updateActiveContact({ labels });
    });
    contactLabelInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            contactLabelAddBtn?.click();
        }
    });

    contactCommentAddBtn?.addEventListener('click', () => addContactComment(contactCommentInput, contactCommentAddBtn));
    compactCommentAddBtn?.addEventListener('click', () => addContactComment(compactCommentInput, compactCommentAddBtn));
    [contactCommentInput, compactCommentInput].forEach((input) => input?.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
            event.preventDefault();
            addContactComment(input, input === compactCommentInput ? compactCommentAddBtn : contactCommentAddBtn);
        }
    }));

    displayInput?.addEventListener('input', () => {
        window.clearTimeout(dialContactLookupTimer);
        const phone = displayInput.value;
        if (normalizeContactPhone(phone).length < 3) return;
        dialContactLookupTimer = window.setTimeout(() => lookupContactByPhone(phone), 350);
    });

    renderContact(null);
    if (new URLSearchParams(window.location.search).get('new_contact') === '1' && contactPermissions.create) {
        activateContactTab('info');
        contactNameInput?.focus();
    }
    if (contactPermissions.view) {
        contactRequest(contactsUrl)
            .then((data) => {
                updateContactLabelOptions(data.labels || []);
                renderContactSearchResults(data.contacts || []);
            })
            .catch(() => {});
    }

    let callUuid = null;
    let pollHandle = null;
    let callActive = false;
    let conferenceName = null;
    let browserAudioActive = false;
    let webRtcClient = null;
    let browserAudioConnecting = false;
    let browserAudioRetryTimer = null;
    let browserAudioRetryCount = 0;
    let hangupInProgress = false;
    let isMuted = false;
    let callControlsEnabled = false;
    let directSipActive = false;

    let callConnectedAt = null;
    let timerHandle = null;

    let manualDialLocked = false;
    let campaignSubmission = false;
    const refreshStartButton = () => {
        if (!startCallButton) {
            return;
        }
        startCallButton.disabled = manualDialLocked || callActive;
    };

    const lockManualDial = () => {
        manualDialLocked = true;
        refreshStartButton();
    };

    const unlockManualDial = () => {
        manualDialLocked = false;
        refreshStartButton();
    };

    const showManualDialLocked = () => {
        if (!alertBox) return;
        alertBox.textContent = 'Campaign is running. Stop it to dial manually.';
        alertBox.classList.remove('hidden');
    };

    refreshStartButton();

    // ===== DTMF local tone =====
    const dtmfMap = {
        '1': [697, 1209],
        '2': [697, 1336],
        '3': [697, 1477],
        '4': [770, 1209],
        '5': [770, 1336],
        '6': [770, 1477],
        '7': [852, 1209],
        '8': [852, 1336],
        '9': [852, 1477],
        '*': [941, 1209],
        '0': [941, 1336],
        '#': [941, 1477]
    };

    let toneContext = null;
    let toneGain = null;
    let toneOscillators = [];

    const ensureToneContext = () => {
        if (!toneContext) {
            toneContext = new (window.AudioContext || window.webkitAudioContext)();
            toneGain = toneContext.createGain();
            toneGain.gain.value = 0.12;
            toneGain.connect(toneContext.destination);
        }
    };

    const stopTone = () => {
        toneOscillators.forEach((osc) => {
            try { osc.stop(); } catch (e) {}
        });
        toneOscillators = [];
    };

    const playTone = async (value) => {
        const freqs = dtmfMap[value];
        if (!freqs) return;

        ensureToneContext();
        try {
            if (toneContext.state === 'suspended') {
                await toneContext.resume();
            }
        } catch (e) {
            return;
        }

        stopTone();
        toneOscillators = freqs.map((freq) => {
            const osc = toneContext.createOscillator();
            osc.type = 'sine';
            osc.frequency.value = freq;
            osc.connect(toneGain);
            osc.start();
            return osc;
        });

        setTimeout(stopTone, 120);
    };

    const syncDisplay = (value) => {
        if (displayInput) displayInput.value = value;
        if (hiddenInput) hiddenInput.value = value;
        const normalized = normalizeContactPhone(value);
        if (!activeContact || activeContact.phone_normalized !== normalized) {
            renderContact(null, value);
        }
        window.clearTimeout(dialContactLookupTimer);
        if (normalized.length >= 3) {
            dialContactLookupTimer = window.setTimeout(() => lookupContactByPhone(value), 350);
        }
    };

    const setDestination = (value) => {
        syncDisplay(value || '');
    };

    // Allow paste into readonly display
    const sanitizePhone = (value) => (value || '').toString().replace(/[^\d+*#]/g, '');
    const applyPastedValue = (text) => syncDisplay(sanitizePhone(text));

    if (displayInput) {
        displayInput.addEventListener('input', (e) => {
            if (callActive || manualDialLocked) {
                if (manualDialLocked && !callActive) {
                    showManualDialLocked();
                }
                syncDisplay(hiddenInput.value || '');
                return;
            }
            const cleaned = sanitizePhone(e.target.value);
            if (cleaned !== e.target.value) {
                e.target.value = cleaned;
            }
            syncDisplay(cleaned);
        });

        displayInput.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            if (!callActive && !manualDialLocked) {
                applyPastedValue(text);
            } else if (!callActive && manualDialLocked) {
                showManualDialLocked();
            }
        });

        document.addEventListener('paste', (e) => {
            if (document.activeElement !== displayInput) return;
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            if (!callActive && !manualDialLocked) {
                applyPastedValue(text);
            } else if (!callActive && manualDialLocked) {
                showManualDialLocked();
            }
        });

        displayInput.addEventListener('click', () => displayInput.focus());
    }

    // ===== Live call state =====

    const formatDuration = (seconds) => {
        const s = Math.max(0, Number(seconds) || 0);
        const mm = Math.floor(s / 60);
        const ss = s % 60;
        const mmStr = mm < 10 ? `0${mm}` : `${mm}`;
        const ssStr = ss < 10 ? `0${ss}` : `${ss}`;
        return `${mmStr}:${ssStr}`;
    };

    const updateTimer = () => {
        if (!callConnectedAt) return;
        const seconds = Math.floor((Date.now() - callConnectedAt) / 1000);
        if (callTimerEl) callTimerEl.textContent = formatDuration(seconds);
        if (compactTimerEl) compactTimerEl.textContent = formatDuration(seconds);
    };

    const getCallDurationSeconds = () => {
        if (!callConnectedAt) return null;
        return Math.max(0, Math.floor((Date.now() - callConnectedAt) / 1000));
    };

    const startTimer = (initialSeconds = 0) => {
        const baseSeconds = Number.isFinite(Number(initialSeconds)) ? Math.max(0, Number(initialSeconds)) : 0;
        if (timerHandle) {
            if (!callConnectedAt && baseSeconds > 0) {
                callConnectedAt = Date.now() - (baseSeconds * 1000);
            }
            return;
        }
        callConnectedAt = Date.now() - (baseSeconds * 1000);
        if (callTimerBadge) callTimerBadge.classList.remove('hidden');
        updateTimer();
        timerHandle = setInterval(updateTimer, 1000);
    };

    const stopTimer = () => {
        if (timerHandle) {
            clearInterval(timerHandle);
            timerHandle = null;
        }
        callConnectedAt = null;
        if (callTimerEl) callTimerEl.textContent = '00:00';
        if (compactTimerEl) compactTimerEl.textContent = '00:00';
        if (callTimerBadge) callTimerBadge.classList.add('hidden');
    };

    const updateActionButtons = () => {
        actionButtons.forEach((btn) => {
            const action = btn.dataset.action;
            let disabled = !callControlsEnabled || !callActive || hangupInProgress;
            if (!disabled) {
                if (action === 'mute') {
                    disabled = isMuted;
                } else if (action === 'unmute') {
                    disabled = !isMuted;
                }
            }
            btn.disabled = disabled;
        });
    };

    const setControls = (enabled) => {
        callControlsEnabled = enabled;
        updateActionButtons();
    };

    const isConnectedStatus = (normalized) => (
        normalized === 'in_call' ||
        normalized === 'incall' ||
        normalized === 'in-call' ||
        normalized === 'active' ||
        normalized === 'answered' ||
        normalized === 'connected' ||
        normalized === 'bridged'
    );

    const isTerminalStatus = (normalized) => (
        normalized === 'ended' ||
        normalized === 'completed' ||
        normalized === 'failed'
    );

    const setStatus = (status, sipStatus = null, sipReason = null, durationSeconds = 0) => {
        const normalized = (status || '').toLowerCase();
        const sipCode = sipStatus !== null && sipStatus !== undefined && !Number.isNaN(Number(sipStatus))
            ? Number(sipStatus)
            : null;

        const labelMap = {
            queued: 'Trying',
            trying: 'Trying',
            ringing: 'Ringing',
            in_call: 'In Call',
            incall: 'In Call',
            'in-call': 'In Call',
            active: 'In Call',
            answered: 'In Call',
            connected: 'In Call',
            bridged: 'In Call',
            completed: 'Bye',
            ended: 'Bye',
            failed: 'Bye'
        };

        let label = labelMap[normalized] || 'Ready';
        if (sipCode && sipCode >= 400) {
            label = `Error ${sipCode}${sipReason ? ` ${sipReason}` : ''}`;
        } else if (normalized === 'ringing' && sipCode && sipCode >= 180 && sipCode < 200) {
            label = `Ringing${sipReason ? ` (${sipReason})` : ''}`;
        } else if ((normalized === 'trying' || normalized === 'queued') && sipCode && sipCode < 180) {
            label = `Trying${sipReason ? ` (${sipReason})` : ''}`;
        } else if ((normalized === 'ended' || normalized === 'completed') && (!sipCode || sipCode < 400)) {
            label = 'Bye';
        }

        if (statusEl) {
            statusEl.textContent = label;
            statusEl.classList.remove('bg-amber-100','text-amber-800','dark:bg-amber-500/30','dark:text-amber-100','bg-blue-100','text-blue-800','dark:bg-blue-500/30','dark:text-blue-100','bg-green-100','text-green-800','dark:bg-green-500/30','dark:text-green-100','bg-red-100','text-red-800','dark:bg-red-500/30','dark:text-red-100','bg-gray-100','text-gray-800','dark:bg-gray-700','dark:text-gray-200');
            if (label.startsWith('Trying')) {
                statusEl.classList.add('bg-amber-100','text-amber-800','dark:bg-amber-500/30','dark:text-amber-100');
            } else if (label.startsWith('Ringing')) {
                statusEl.classList.add('bg-blue-100','text-blue-800','dark:bg-blue-500/30','dark:text-blue-100');
            } else if (label === 'In Call') {
                statusEl.classList.add('bg-green-100','text-green-800','dark:bg-green-500/30','dark:text-green-100');
            } else if (label === 'Bye') {
                statusEl.classList.add('bg-gray-100','text-gray-800','dark:bg-gray-700','dark:text-gray-200');
            } else if (label.startsWith('Error')) {
                statusEl.classList.add('bg-red-100','text-red-800','dark:bg-red-500/30','dark:text-red-100');
            }
        }

        if (isConnectedStatus(normalized)) {
            compactCallWindow?.classList.remove('hidden');
            startTimer(durationSeconds);
            if (conferenceName && webRtcClient && !browserAudioActive && !browserAudioConnecting && !hangupInProgress) {
                connectBrowserAudio();
            }
        }

        if (isTerminalStatus(normalized)) {
            compactCallWindow?.classList.add('hidden');
            stopTimer();
            disconnectBrowserAudio();
            applyMuteState(false);
            if (typeof handleCampaignCallComplete === 'function') {
                handleCampaignCallComplete(normalized);
            }
            conferenceName = null;
            callUuid = null;
        }
    };

    const showError = (message) => {
        if (!alertEl) return;
        alertEl.textContent = message || 'Unable to update call.';
        alertEl.classList.remove('hidden');
    };

    const initWebRtcClient = () => {
        if (!window.DialerWebRTC || !webrtcConfigEl || !remoteAudioEl) {
            return null;
        }
        try {
            const config = JSON.parse(webrtcConfigEl.dataset.config || '{}');
            if (!config.wsUrl || !config.domain || !config.username || !config.password) {
                return null;
            }
            config.remoteAudioSelector = '#dialer-audio';
            return new window.DialerWebRTC(config);
        } catch (error) {
            console.error('Invalid WebRTC config', error);
            return null;
        }
    };

    const updateBrowserAudioStatus = (text, hasError = false) => {
        if (!browserAudioStatus) return;
        if (!text) {
            browserAudioStatus.classList.add('hidden');
            browserAudioStatus.textContent = '';
            return;
        }
        browserAudioStatus.textContent = text;
        browserAudioStatus.classList.remove('hidden');
        if (hasError) {
            browserAudioStatus.classList.add('text-red-600', 'dark:text-red-300');
        } else {
            browserAudioStatus.classList.remove('text-red-600', 'dark:text-red-300');
        }
    };

    const updateWebPhoneState = (state, status = 'ready') => {
        if (webPhoneStateEl) webPhoneStateEl.textContent = state;
        if (!webPhoneStateDotEl) return;
        webPhoneStateDotEl.classList.remove(
            'bg-amber-400', 'bg-emerald-400', 'bg-red-400',
            'shadow-[0_0_8px_rgba(251,191,36,0.65)]',
            'shadow-[0_0_8px_rgba(52,211,153,0.8)]',
            'shadow-[0_0_8px_rgba(248,113,113,0.75)]'
        );
        const classes = status === 'error'
            ? ['bg-red-400', 'shadow-[0_0_8px_rgba(248,113,113,0.75)]']
            : (status === 'pending'
                ? ['bg-amber-400', 'shadow-[0_0_8px_rgba(251,191,36,0.65)]']
                : ['bg-emerald-400', 'shadow-[0_0_8px_rgba(52,211,153,0.8)]']);
        webPhoneStateDotEl.classList.add(...classes);
    };

    const connectBrowserAudio = async () => {
        if (!webRtcClient || !conferenceName || browserAudioActive || browserAudioConnecting || hangupInProgress) {
            return;
        }
        browserAudioConnecting = true;
        updateBrowserAudioStatus('Connecting browser audio…');
        try {
            await webRtcClient.joinConference(conferenceName);
            browserAudioActive = true;
            browserAudioRetryCount = 0;
            if (browserAudioRetryTimer) {
                clearTimeout(browserAudioRetryTimer);
                browserAudioRetryTimer = null;
            }
            updateBrowserAudioStatus('Browser audio connected');
        } catch (error) {
            console.error('Failed to connect browser audio', error);
            browserAudioActive = false;
            browserAudioRetryCount += 1;
            const errorMessage = error && error.message ? String(error.message) : '';
            updateBrowserAudioStatus('Browser audio unavailable', true);
            showError('Unable to connect browser audio.');
            if (errorMessage) {
                console.warn(`[dialer] browser audio join failed: ${errorMessage}`);
            }
            if (callActive && conferenceName && browserAudioRetryCount < 4) {
                const delayMs = 1200 * browserAudioRetryCount;
                browserAudioRetryTimer = setTimeout(() => {
                    browserAudioRetryTimer = null;
                    connectBrowserAudio();
                }, delayMs);
            }
        } finally {
            browserAudioConnecting = false;
        }
    };

    const disconnectBrowserAudio = async () => {
        if (browserAudioRetryTimer) {
            clearTimeout(browserAudioRetryTimer);
            browserAudioRetryTimer = null;
        }
        browserAudioRetryCount = 0;
        if (!webRtcClient) return;
        try {
            await webRtcClient.leaveConference();
        } catch (error) {
            console.error('Failed to disconnect browser audio', error);
        }
        browserAudioActive = false;
        browserAudioConnecting = false;
        updateBrowserAudioStatus(webRtcClient ? 'Browser audio idle' : '');
    };

    const applyMuteState = async (muted) => {
        isMuted = muted;
        updateActionButtons();
        if (compactMuteButton) compactMuteButton.setAttribute('aria-pressed', muted ? 'true' : 'false');
        if (compactMuteIcon) compactMuteIcon.className = muted ? 'bi bi-mic-mute-fill' : 'bi bi-mic-fill';
        if (compactMuteLabel) compactMuteLabel.textContent = muted ? '{{ __('Unmute') }}' : '{{ __('Mute') }}';
        if (webRtcClient && typeof webRtcClient.setMuted === 'function') {
            try {
                await webRtcClient.setMuted(muted);
            } catch (error) {
                console.error('Failed to toggle microphone mute', error);
            }
        }
        if (browserAudioActive) {
            updateBrowserAudioStatus(muted ? 'Microphone muted' : 'Browser audio connected');
        }
    };

    const ensureWebRtcClient = () => {
        if (!webRtcClient) {
            webRtcClient = initWebRtcClient();
            if (webRtcClient) {
                updateWebPhoneState('{{ __('Connecting') }}', 'pending');
                updateBrowserAudioStatus('Browser audio idle');
                webRtcClient.ensureClient().then(() => {
                    updateWebPhoneState('{{ __('Ready') }}');
                    updateBrowserAudioStatus('Browser audio ready');
                }).catch((error) => {
                    console.error('Failed to register WebRTC client', error);
                    updateWebPhoneState('{{ __('Unavailable') }}', 'error');
                    updateBrowserAudioStatus('Browser audio unavailable', true);
                });
            } else {
                updateWebPhoneState('{{ __('Not configured') }}', 'error');
                updateBrowserAudioStatus('');
            }
        }
        return webRtcClient;
    };

    ensureWebRtcClient();

    const pollStatus = async () => {
        if (hangupInProgress) return;
        try {
            const response = await fetch(`/admin/dialer/calls/${callUuid}/status`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                setStatus('ended');
                showError(`HTTP ${response.status}`);
                callActive = false;
                setControls(false);
                refreshStartButton();
                stopTimer();
                return;
            }

            const data = await response.json();
            console.log('[pollStatus] status:', data.status, data);
            if (hangupInProgress) return;
            if (data.conferenceName) {
                conferenceName = data.conferenceName;
            }
            setStatus(data.status, data.sipStatus, data.sipReason, data.durationSeconds);

            const currentStatus = (data.status || '').toLowerCase();
            if (currentStatus === 'in_call' || currentStatus === 'ringing' || currentStatus === 'queued' || currentStatus === 'trying' || isConnectedStatus(currentStatus)) {
                callActive = true;
                setControls(true);
            }

            if (isTerminalStatus(currentStatus)) {
                clearInterval(pollHandle);
                callActive = false;
                setControls(false);
                refreshStartButton();
                stopTimer();
                disconnectBrowserAudio();
                conferenceName = null;
                callUuid = null;
            }
        } catch (e) {
            setStatus('ended');
            showError('Network error while updating the call.');
            callActive = false;
            setControls(false);
            refreshStartButton();
            stopTimer();
            disconnectBrowserAudio();
        }
    };

    // Live call actions
    actionButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const action = button.dataset.action;
            if (!callUuid && directSipActive) {
                if (action === 'mute' || action === 'unmute') {
                    await applyMuteState(action === 'mute');
                } else if (action === 'hangup') {
                    await webRtcClient?.leaveConference();
                    directSipActive = false;
                    callActive = false;
                    browserAudioActive = false;
                    setControls(false);
                    setStatus('ended');
                    stopTimer();
                    updateBrowserAudioStatus('Browser audio ready');
                    refreshStartButton();
                }
                return;
            }
            if (!callUuid) return;
            if (hangupInProgress) return;
            const isMuteAction = action === 'mute' || action === 'unmute';
            if (isMuteAction && !callActive) return;

            if (alertEl) alertEl.classList.add('hidden');

            try {
                if (action === 'hangup') {
                    hangupInProgress = true;
                    setControls(false);
                } else if (isMuteAction) {
                    button.disabled = true;
                }
                const requestOptions = {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                };

                if (action === 'hangup') {
                    const payload = {};
                    const durationSeconds = getCallDurationSeconds();
                    if (durationSeconds !== null) {
                        payload.durationSeconds = durationSeconds;
                    }
                    requestOptions.body = JSON.stringify(payload);
                }

                const response = await fetch(`/admin/dialer/calls/${callUuid}/${action}`, requestOptions);

                if (!response.ok) {
                    let data = {};
                    try { data = await response.json(); } catch (e) {}
                    showError(data.message || `HTTP ${response.status}`);
                    if (action === 'hangup') {
                        hangupInProgress = false;
                        setControls(callActive);
                    } else if (isMuteAction) {
                        updateActionButtons();
                    }
                    return;
                }

                if (action === 'hangup') {
                    setStatus('completed');
                    clearInterval(pollHandle);
                    callActive = false;
                    setControls(false);
                    refreshStartButton();
                    stopTimer();
                    await disconnectBrowserAudio();
                    await applyMuteState(false);
                } else if (isMuteAction) {
                    await applyMuteState(action === 'mute');
                }
            } catch (e) {
                showError('Network error while updating the call.');
                if (action === 'hangup') {
                    hangupInProgress = false;
                    setControls(callActive);
                } else if (isMuteAction) {
                    updateActionButtons();
                }
            }
        });
    });

    // ===== Dialpad logic =====
    const LONG_PRESS_MS = 500;
    let longPressTimer = null;
    let longPressActive = false;

    dialpadButtons.forEach((button) => {
        const value = button.dataset.value || '';

        const handlePress = () => {
            if (value !== '0') return;
            longPressActive = false;
            clearTimeout(longPressTimer);
            longPressTimer = setTimeout(() => {
                longPressActive = true;

                if (!callActive && !manualDialLocked) {
                    syncDisplay(`${hiddenInput.value || ''}+`);
                    playTone('0');
                } else if (!callActive && manualDialLocked) {
                    showManualDialLocked();
                }
            }, LONG_PRESS_MS);
        };

        const handleRelease = () => {
            if (value !== '0') return;
            clearTimeout(longPressTimer);
        };

        button.addEventListener('mousedown', handlePress);
        button.addEventListener('touchstart', handlePress, { passive: true });
        button.addEventListener('mouseup', handleRelease);
        button.addEventListener('mouseleave', handleRelease);
        button.addEventListener('touchend', handleRelease);
        button.addEventListener('touchcancel', handleRelease);

        button.addEventListener('click', async () => {
            if (value === '0' && longPressActive) {
                longPressActive = false;
                return;
            }

            // During call: send DTMF
            if (callActive) {
                playTone(value);
                if (alertEl) alertEl.classList.add('hidden');

                try {
                    if (directSipActive) {
                        await webRtcClient?.sendDtmf(value);
                    } else if (callUuid) {
                        const response = await fetch(`/admin/dialer/calls/${callUuid}/dtmf`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ digits: value })
                        });

                        if (!response.ok) {
                            let data = {};
                            try { data = await response.json(); } catch (e) {}
                            showError(data.message || `HTTP ${response.status}`);
                        }
                    }
                } catch (e) {
                    showError('Network error while sending DTMF.');
                }
                return;
            }

            // Before call: build number
            if (manualDialLocked) {
                showManualDialLocked();
                return;
            }
            syncDisplay(`${hiddenInput.value || ''}${value}`);
            playTone(value);
        });
    });

    if (clearButton) {
        clearButton.addEventListener('click', () => {
            if (callActive) return;
            if (manualDialLocked) {
                showManualDialLocked();
                return;
            }
            syncDisplay('');
        });
    }

    if (backspaceButton) {
        backspaceButton.addEventListener('click', () => {
            if (callActive) return;
            if (manualDialLocked) {
                showManualDialLocked();
                return;
            }
            const current = hiddenInput.value || '';
            syncDisplay(current.slice(0, -1));
        });
    }

    // ===== Campaign automation =====
    const campaignSelect = document.getElementById('campaign_id');
    const agentInput = document.getElementById('agent_name');
    const btnStartCampaign = document.getElementById('btnStartCampaign');
    const btnRestartFailedCampaign = document.getElementById('btnRestartFailedCampaign');
    const btnStopCampaign = document.getElementById('btnStopCampaign');
    const campaignActionSelect = document.getElementById('campaign_action_select');
    const campaignModeBadge = document.getElementById('campaignModeBadge');
    const campaignRoutes = {
        start: '{{ route('admin.dialer.campaign.start') }}',
        restartFailed: '{{ route('admin.dialer.campaign.restart_failed') }}',
        stop: '{{ route('admin.dialer.campaign.stop') }}',
        next: '{{ route('admin.dialer.campaign.next') }}',
    };

    const campaignState = {
        running: false,
        currentLeadId: null,
        fetchingNext: false,
        leadScope: 'all',
    };

    const updateCampaignModeBadge = () => {
        if (!campaignModeBadge) return;
        const failedOnly = campaignState.leadScope === 'failed';
        campaignModeBadge.textContent = failedOnly ? 'Mode: Failed Only' : 'Mode: All Leads';
        campaignModeBadge.classList.remove(
            'border-gray-300','bg-gray-100','text-gray-700',
            'dark:border-gray-700','dark:bg-gray-800','dark:text-gray-200',
            'border-amber-300','bg-amber-100','text-amber-800',
            'dark:border-amber-700','dark:bg-amber-900/40','dark:text-amber-200'
        );
        if (failedOnly) {
            campaignModeBadge.classList.add(
                'border-amber-300','bg-amber-100','text-amber-800',
                'dark:border-amber-700','dark:bg-amber-900/40','dark:text-amber-200'
            );
        } else {
            campaignModeBadge.classList.add(
                'border-gray-300','bg-gray-100','text-gray-700',
                'dark:border-gray-700','dark:bg-gray-800','dark:text-gray-200'
            );
        }
    };
    updateCampaignModeBadge();

    const campaignAlert = (message = '') => {
        if (!alertBox) return;
        if (!message) {
            alertBox.classList.add('hidden');
            alertBox.textContent = '';
            return;
        }
        alertBox.textContent = message;
        alertBox.classList.remove('hidden');
    };

    const buildUrl = (base, params = {}) => {
        const url = new URL(base, window.location.origin);
        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                url.searchParams.set(key, value);
            }
        });
        return url.toString();
    };

    const campaignRequest = async (url, { method = 'GET', body = null, params = null } = {}) => {
        const target = params ? buildUrl(url, params) : url;
        const headers = {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        };
        const options = { method, headers };
        if (body) {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }

        const response = await fetch(target, options);
        let data = {};
        try { data = await response.json(); } catch (error) {}

        if (!response.ok || data.ok === false) {
            throw new Error(data.message || `HTTP ${response.status}`);
        }

        return data;
    };

    const submitDialerForm = () => {
        if (!form) return;
        campaignSubmission = true;
        try {
            form.requestSubmit();
        } finally {
            campaignSubmission = false;
        }
    };

    const dialCampaignLead = (lead) => {
        if (!lead || !lead.phone) {
            campaignAlert('Campaign started but no leads were returned.');
            campaignState.running = false;
            campaignState.currentLeadId = null;
            campaignState.leadScope = 'all';
            updateCampaignModeBadge();
            unlockManualDial();
            return;
        }

        campaignState.currentLeadId = lead.id;
        campaignState.running = true;
        lockManualDial();
        campaignAlert('');
        setDestination(lead.phone);
        submitDialerForm();
    };

    const fetchNextLead = async ({ lastLeadId, lastLeadStatus } = {}) => {
        if (!campaignState.running || campaignState.fetchingNext) {
            return;
        }

        campaignState.fetchingNext = true;
        try {
            const data = await campaignRequest(campaignRoutes.next, {
                params: {
                    last_lead_id: lastLeadId,
                    last_lead_status: lastLeadStatus,
                    lead_scope: campaignState.leadScope || 'all'
                }
            });

            if (data.next?.phone) {
                dialCampaignLead(data.next);
            } else {
                campaignAlert('Campaign completed. No more leads available.');
                campaignState.running = false;
                campaignState.currentLeadId = null;
                campaignState.leadScope = 'all';
                updateCampaignModeBadge();
                setDestination('');
                unlockManualDial();
            }
        } catch (error) {
            campaignAlert(error.message || 'Unable to fetch next lead.');
            campaignState.running = false;
            campaignState.currentLeadId = null;
            campaignState.leadScope = 'all';
            updateCampaignModeBadge();
            unlockManualDial();
        } finally {
            campaignState.fetchingNext = false;
        }
    };

    const startCampaignFlow = async () => {
        if (!campaignSelect || !agentInput) return;
        if (campaignState.running) {
            campaignAlert('Campaign is already running.');
            return;
        }
        const campaignId = campaignSelect.value;
        const agent = (agentInput.value || '').trim();

        if (!campaignId || !agent) {
            campaignAlert('Select campaign and enter agent name.');
            return;
        }

        campaignState.running = true;
        campaignState.leadScope = 'all';
        updateCampaignModeBadge();
        lockManualDial();

        try {
            const data = await campaignRequest(campaignRoutes.start, {
                method: 'POST',
                body: {
                    campaign_id: campaignId,
                    agent: agent
                }
            });

            if (data.next?.phone) {
                dialCampaignLead(data.next);
            } else {
                campaignAlert('Campaign started but no leads were returned.');
                campaignState.running = false;
                campaignState.currentLeadId = null;
                unlockManualDial();
            }
        } catch (error) {
            campaignAlert(error.message || 'Unable to start campaign.');
            campaignState.running = false;
            campaignState.leadScope = 'all';
            updateCampaignModeBadge();
            unlockManualDial();
        }
    };

    const restartFailedCampaignFlow = async () => {
        if (!campaignSelect || !agentInput) return;
        if (campaignState.running) {
            campaignAlert('Campaign is already running.');
            return;
        }

        const campaignId = campaignSelect.value;
        const agent = (agentInput.value || '').trim();
        if (!campaignId || !agent) {
            campaignAlert('Select campaign and enter agent name.');
            return;
        }

        campaignState.running = true;
        campaignState.leadScope = 'failed';
        updateCampaignModeBadge();
        lockManualDial();

        try {
            const data = await campaignRequest(campaignRoutes.restartFailed, {
                method: 'POST',
                body: {
                    campaign_id: campaignId,
                    agent: agent
                }
            });

            if (data.next?.phone) {
                dialCampaignLead(data.next);
            } else {
                campaignAlert('No failed leads available for this campaign.');
                campaignState.running = false;
                campaignState.currentLeadId = null;
                campaignState.leadScope = 'all';
                updateCampaignModeBadge();
                unlockManualDial();
            }
        } catch (error) {
            campaignAlert(error.message || 'Unable to restart failed campaign.');
            campaignState.running = false;
            campaignState.leadScope = 'all';
            updateCampaignModeBadge();
            unlockManualDial();
        }
    };

    const stopCampaignFlow = async () => {
        try {
            await campaignRequest(campaignRoutes.stop, { method: 'POST' });
            campaignState.running = false;
            campaignState.currentLeadId = null;
            campaignState.leadScope = 'all';
            updateCampaignModeBadge();
            campaignAlert('');
            setDestination('');
            unlockManualDial();
        } catch (error) {
            campaignAlert(error.message || 'Unable to stop campaign.');
        }
    };

    const handleCampaignCallComplete = (status) => {
        if (!campaignState.running || !campaignState.currentLeadId) {
            return;
        }

        const finalStatus = status === 'completed' ? 'called' : 'failed';
        const finishedLeadId = campaignState.currentLeadId;
        campaignState.currentLeadId = null;
        fetchNextLead({
            lastLeadId: finishedLeadId,
            lastLeadStatus: finalStatus
        });
    };

    document.querySelectorAll('[data-campaign-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const action = button.getAttribute('data-campaign-action');
            const handlerMap = {
                start: btnStartCampaign,
                restart: btnRestartFailedCampaign,
                stop: btnStopCampaign,
            };
            handlerMap[action]?.click();
        });
    });

    btnStartCampaign?.addEventListener('click', startCampaignFlow);
    btnRestartFailedCampaign?.addEventListener('click', restartFailedCampaignFlow);
    btnStopCampaign?.addEventListener('click', stopCampaignFlow);

    // ===== Start call (inline; no popup) =====
    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (manualDialLocked && !campaignSubmission) {
            showManualDialLocked();
            return;
        }

        // hide alert
        alertBox.classList.add('hidden');
        alertBox.textContent = '';

        const destination = hiddenInput ? hiddenInput.value.trim() : '';
        if (!destination) {
            alertBox.textContent = '{{ __('Enter a phone number before starting a call.') }}';
            alertBox.classList.remove('hidden');
            displayInput?.focus();
            return;
        }

        await disconnectBrowserAudio();
        conferenceName = null;
        callUuid = null;

        // show live session
        if (liveSession) liveSession.classList.remove('hidden');
        if (callIdBadge) callIdBadge.classList.add('hidden');
        if (alertEl) alertEl.classList.add('hidden');

        setStatus('trying');
        stopTimer();
        hangupInProgress = false;

        const payload = {
            destination
        };

        lookupContactByPhone(payload.destination);

        startCallButton.disabled = true;

        try {
            // Local outbound audio test. This deliberately bypasses the carrier;
            // all normal destinations continue through the backend call flow.
            if (payload.destination === '9196') {
                const client = ensureWebRtcClient();
                if (!client) {
                    throw new Error('Browser audio is not configured for this user.');
                }

                await client.joinConference('9196');
                directSipActive = true;
                browserAudioActive = true;
                callActive = true;
                setStatus('in_call');
                setControls(true);
                startTimer();
                updateBrowserAudioStatus('Echo test connected · speak to hear your voice');
                return;
            }

            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                let error = {};
                try { error = await response.json(); } catch (e) {}
                alertBox.textContent = error.message || `HTTP ${response.status}`;
                alertBox.classList.remove('hidden');
                refreshStartButton();
                setStatus('ended');
                showError(`HTTP ${response.status}`);
                return;
            }

            const data = await response.json();
            if (data.callUuid) {
                callUuid = data.callUuid;
                conferenceName = data.conference || null;

                if (callIdBadge) {
                    callIdBadge.textContent = `Call ID · ${callUuid}`;
                    callIdBadge.classList.remove('hidden');
                }

                callActive = true;
                await applyMuteState(false);
                setControls(true);
                if (conferenceName && webRtcClient) {
                    connectBrowserAudio();
                }

                // poll status
                pollStatus();
                if (pollHandle) clearInterval(pollHandle);
                pollHandle = setInterval(pollStatus, 1000);
            } else {
                alertBox.textContent = 'Call queued but no call identifier returned.';
                alertBox.classList.remove('hidden');
                refreshStartButton();
                setStatus('ended');
                await disconnectBrowserAudio();
            }
        } catch (error) {
            const errorMessage = error?.message ? String(error.message) : '';
            const isEchoTest = payload.destination === '9196';
            const message = isEchoTest
                ? `Echo test failed${errorMessage ? `: ${errorMessage}` : '. Check the Web phone connection and microphone permission.'}`
                : `Network error while queuing the call${errorMessage ? `: ${errorMessage}` : '.'}`;
            alertBox.textContent = message;
            alertBox.classList.remove('hidden');
            refreshStartButton();
            setStatus('ended');
            showError(message);
            await disconnectBrowserAudio();
        }
    });

    // ===== Inbound (round-robin) incoming-call handling =====
    const inboundSocketEl = document.getElementById('dialer-inbound-socket');
    const incomingBanner = document.getElementById('incoming-call-banner');
    const incomingCallerEl = document.getElementById('incoming-caller');
    const incomingAcceptBtn = document.getElementById('incoming-accept');
    const incomingDeclineBtn = document.getElementById('incoming-decline');

    const showIncomingBanner = (show) => {
        if (!incomingBanner) return;
        incomingBanner.classList.toggle('hidden', !show);
    };

    const hideIncoming = () => {
        inboundCall = null;
        showIncomingBanner(false);
    };

    const acceptInbound = async () => {
        if (!inboundCall) return;
        const call = inboundCall;
        showIncomingBanner(false);

        if (call.directSip) {
            try {
                ensureWebRtcClient();
                await webRtcClient?.answerIncoming();
                callActive = true;
                directSipActive = true;
                browserAudioActive = true;
                if (liveSession) liveSession.classList.remove('hidden');
                setStatus('in_call');
                setControls(true);
                updateBrowserAudioStatus('Incoming browser audio connected');
            } catch (error) {
                showError(error?.message || 'Unable to answer incoming call.');
            }
            inboundCall = null;
            return;
        }

        callUuid = call.callUuid;
        conferenceName = call.conference || null;
        if (callIdBadge) {
            callIdBadge.textContent = `Call ID · ${callUuid}`;
            callIdBadge.classList.remove('hidden');
        }
        if (liveSession) liveSession.classList.remove('hidden');

        callActive = true;
        await applyMuteState(false);
        setControls(true);
        setStatus('in_call');
        ensureWebRtcClient();
        if (conferenceName && webRtcClient) {
            connectBrowserAudio();
        }
        pollStatus();
        if (pollHandle) clearInterval(pollHandle);
        pollHandle = setInterval(pollStatus, 1000);
        inboundCall = null;
    };

    const declineInbound = async () => {
        if (!inboundCall) return;
        if (inboundCall.directSip) {
            hideIncoming();
            try {
                await webRtcClient?.declineIncoming();
            } catch (e) {
                console.warn('[inbound] SIP decline failed', e);
            }
            return;
        }
        const uuid = inboundCall.callUuid;
        hideIncoming();
        try {
            await fetch(`/admin/dialer/calls/${uuid}/decline`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
        } catch (e) {
            console.warn('[inbound] decline failed', e);
        }
    };

    if (incomingAcceptBtn) incomingAcceptBtn.addEventListener('click', acceptInbound);
    if (incomingDeclineBtn) incomingDeclineBtn.addEventListener('click', declineInbound);

    window.addEventListener('dialer:sip-incoming', (event) => {
        if (callActive || inboundCall) return;
        inboundCall = {
            directSip: true,
            callerIdNumber: event.detail?.callerIdNumber || 'Unknown',
            did: null
        };
        if (incomingCallerEl) incomingCallerEl.textContent = inboundCall.callerIdNumber;
        if (incomingPhoneEl) incomingPhoneEl.textContent = inboundCall.callerIdNumber;
        updateIncomingContact(null, inboundCall.callerIdNumber);
        lookupContactByPhone(inboundCall.callerIdNumber);
        showIncomingBanner(true);
    });
    window.addEventListener('dialer:sip-hangup', () => {
        if (inboundCall?.directSip) hideIncoming();
        if (!callUuid && callActive) {
            directSipActive = false;
            callActive = false;
            browserAudioActive = false;
            setControls(false);
            setStatus('ended');
            stopTimer();
            updateBrowserAudioStatus('Browser audio idle');
            refreshStartButton();
        }
    });

    const initInboundSocket = () => {
        if (!window.io || !inboundSocketEl) return;
        let cfg = {};
        try {
            cfg = JSON.parse(inboundSocketEl.dataset.config || '{}');
        } catch (e) {
            console.warn('[inbound] invalid socket config', e);
            return;
        }
        if (!cfg.url || !cfg.userId) return;

        const socket = window.io(cfg.url, {
            transports: ['websocket', 'polling'],
            auth: { userId: cfg.userId }
        });
        socket.on('connect', () => {
            socket.emit('identify', cfg.userId);
        });
        socket.on('incoming.call', (payload) => {
            if (!payload || !payload.callUuid) return;
            // Don't interrupt an active call.
            if (callActive) return;
            inboundCall = {
                callUuid: payload.callUuid,
                conference: payload.conference || null,
                callerIdNumber: payload.callerIdNumber || null,
                did: payload.did || null
            };
            if (incomingCallerEl) incomingCallerEl.textContent = payload.callerIdNumber || '{{ __('Unknown caller') }}';
            if (incomingPhoneEl) incomingPhoneEl.textContent = payload.callerIdNumber || payload.did || '—';
            updateIncomingContact(null, payload.callerIdNumber || payload.did || '');
            lookupContactByPhone(payload.callerIdNumber || payload.did || '');
            showIncomingBanner(true);
        });
        socket.on('incoming.call.cancel', (payload) => {
            if (!inboundCall || !payload || payload.callUuid !== inboundCall.callUuid) return;
            hideIncoming();
        });
    };
    initInboundSocket();
    const syncCompactCall = () => {
        if (compactNameEl) compactNameEl.textContent = customerNameEl?.textContent?.trim() || '{{ __('Unknown caller') }}';
        if (compactPhoneEl) compactPhoneEl.textContent = customerPhoneEl?.textContent?.trim() || '—';
        if (compactAvatarEl) compactAvatarEl.textContent = customerAvatarEl?.textContent?.trim() || '?';
        if (compactTimerEl) compactTimerEl.textContent = callTimerEl?.textContent?.trim() || '00:00';
    };
    [customerNameEl, customerPhoneEl, customerAvatarEl, callTimerEl].filter(Boolean).forEach((element) => new MutationObserver(syncCompactCall).observe(element, {childList: true, subtree: true, characterData: true}));
    syncCompactCall();
    compactCallWindow?.querySelector('[data-compact-close]')?.addEventListener('click', () => compactCallWindow.classList.add('hidden'));
    compactCallWindow?.querySelector('[data-compact-minimize]')?.addEventListener('click', () => compactCallWindow.classList.add('hidden'));
    compactCallWindow?.querySelector('[data-compact-keypad-toggle]')?.addEventListener('click', () => compactCallWindow.querySelector('[data-compact-keypad]')?.classList.toggle('hidden'));
    compactCallWindow?.querySelectorAll('[data-call-proxy]').forEach((button) => button.addEventListener('click', () => document.querySelector(`[data-action="${button.dataset.callProxy}"]`)?.click()));
    compactMuteButton?.addEventListener('click', () => applyMuteState(!isMuted));
    compactCallWindow?.querySelectorAll('[data-compact-key]').forEach((button) => button.addEventListener('click', () => document.querySelector(`.dialpad-key[data-value="${button.dataset.compactKey}"]`)?.click()));
     const initialDestination = new URLSearchParams(window.location.search).get('destination');
     if (initialDestination) {
         displayInput.value = initialDestination;
         hiddenInput.value = initialDestination;
         lookupContactByPhone(initialDestination);
     }
     // initial
     setControls(false);
     if (!webRtcClient) {
         updateBrowserAudioStatus('');
     }
 });

</script>
@endpush
