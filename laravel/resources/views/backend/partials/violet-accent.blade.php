@once
<style>
/* The blue accent is repointed at the violet used by the record-form buttons
   (#7c3aed). Overriding the Tailwind colour variables re-tints every blue-*
   utility in one place - icons, chips, badges and borders alike - including the
   ones rendered from JS templates, and without needing a Tailwind rebuild.
   The scale keeps its lightness ordering so contrast is unchanged. */
:root {
    --color-blue-50: #f5f3ff;
    --color-blue-100: #ede9fe;
    --color-blue-200: #ddd6fe;
    --color-blue-300: #c4b5fd;
    --color-blue-400: #a78bfa;
    --color-blue-500: #8b5cf6;
    --color-blue-600: #7c3aed;
    --color-blue-700: #6d28d9;
    --color-blue-800: #5b21b6;
    --color-blue-900: #4c1d95;
    --color-blue-950: #2e1065;
}

/* The sidebar selection is hard-coded in app.css rather than themed, so it
   needs its own rules. Dark keeps the translucent shade it already had, now
   violet. Light goes solid violet so the existing white label and icon filters
   stay legible. */
.dark .connectpro-sidebar .menu-item-active,
.dark .connectpro-sidebar .menu-item-active span {
    border-color: rgba(139, 92, 246, .45);
    background: linear-gradient(135deg, rgba(124, 58, 237, .3), rgba(91, 33, 182, .24));
    color: #c4b5fd !important;
    box-shadow: inset 0 0 0 1px rgba(139, 92, 246, .06), 0 14px 30px -24px rgba(124, 58, 237, .8);
}
html:not(.dark) .connectpro-sidebar .menu-item-active,
html:not(.dark) .connectpro-sidebar .menu-item-active span {
    border-color: #7c3aed;
    background: linear-gradient(135deg, #7c3aed, #6d28d9);
    color: #ffffff !important;
    box-shadow: 0 14px 30px -20px rgba(124, 58, 237, .75);
}

/* Unselected hover is the same accent one step down. */
html:not(.dark) .connectpro-sidebar .menu-item-inactive:hover {
    background: #f5f3ff !important;
    color: #5b21b6 !important;
}
.dark .connectpro-sidebar .menu-item-inactive:hover {
    background: rgba(139, 92, 246, .12) !important;
    color: #ede9fe !important;
}
html:not(.dark) .connectpro-sidebar > div:last-child button:hover {
    background: #f5f3ff;
}

/* app.css hard-codes the remaining blue accents, which the variable remap
   above cannot reach because they are literal hex values. The selectors are
   repeated here so the dialer, contacts and pagination accents land on violet
   too. Tailwind is prebuilt and the toolchain in this image is too old to
   rebuild it, so these live alongside the variables instead of in the source. */

/* Accent buttons and pills on the light communication pages. */
html:not(.dark) .connectpro-communication-page button.bg-blue-600,
html:not(.dark) .connectpro-communication-page a.bg-blue-600,
html:not(.dark) .connectpro-communication-page a.bg-blue-700\/80 {
    background-color: #7c3aed !important;
    color: #ffffff;
}
html:not(.dark) .connectpro-communication-page a.rounded-full.bg-blue-600 {
    border-color: #7c3aed;
    background: #7c3aed;
}
html:not(.dark) .connectpro-communication-page a.rounded-full:hover {
    border-color: #a78bfa;
    background: #f5f3ff;
    color: #6d28d9;
}

/* Dialpad keys, both the main grid and the in-call compact grid. */
.connectpro-dialer .dialpad-key:hover {
    border-color: #a78bfa;
}
html:not(.dark) .connectpro-dialer .dialpad-key:hover {
    border-color: #8b5cf6;
    background: #f5f3ff;
    color: #6d28d9;
}
html:not(.dark) .connectpro-dialer #active-call-window [data-compact-key]:hover {
    border-color: #a78bfa;
    color: #7c3aed;
}
html:not(.dark) .connectpro-dialer #customer-avatar {
    border-color: #ede9fe;
}

/* Shared control hover. */
.connectpro-control:hover:not(:disabled) {
    border-color: #8b5cf6;
    background: rgba(124, 58, 237, .15);
    color: #a78bfa;
}
.dark .connectpro-control:hover:not(:disabled) {
    border-color: rgba(139, 92, 246, .5);
    color: #c4b5fd;
}

/* Contact workspace tabs. */
.connectpro-dialer [data-contact-tab]:hover {
    color: #c4b5fd;
}
.connectpro-dialer [data-contact-tab].contact-tab-active {
    border-bottom-color: #8b5cf6;
    color: #a78bfa;
}
html:not(.dark) .connectpro-dialer [data-contact-tab].contact-tab-active {
    color: #7c3aed;
}

/* Pagination current page. The light-mode rule in app.css carries the
   communication-page prefix, so the override has to match that specificity
   rather than the shorter dark-mode selector. */
.connectpro-pagination nav span[aria-current="page"] > span {
    border-color: #8b5cf6 !important;
    background: #7c3aed !important;
}
html:not(.dark) .connectpro-communication-page .connectpro-pagination nav span[aria-current="page"] > span {
    border-color: #7c3aed !important;
    background: #7c3aed !important;
}
</style>
@endonce