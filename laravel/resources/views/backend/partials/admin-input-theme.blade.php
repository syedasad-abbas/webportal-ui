@once
<style>
/* Keep text-entry controls distinct from disabled fields in both themes.
    Checkboxes, radios, hidden fields and button-like inputs are left alone.

   File, colour and range inputs are also excluded on purpose: they are not text
   entry, they render their own picker or control, and every one of them already
   carries explicit dark classes in the markup. Forcing them white overrode
   those and left the settings uploads looking like dark mode had not been
   applied at all.

   Disabled text fields keep a grey fill so the two states stay
   distinguishable - they should not look enabled. The exclusion list repeats
   the one above so a disabled checkbox or radio keeps its own dark styling
   rather than picking up this rule. */
input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="button"]):not([type="submit"]):not([type="reset"]):not([type="file"]):not([type="color"]):not([type="range"]),
select,
textarea {
    background-color: #fff !important;
    color: #0f172a !important;
}
input:not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([type="color"]):not([type="range"])::placeholder,
textarea::placeholder {
    color: #94a3b8 !important;
    opacity: 1 !important;
}
input:not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([type="color"]):not([type="range"]):disabled,
select:disabled,
textarea:disabled {
    background-color: #f1f5f9 !important;
    color: #94a3b8 !important;
    cursor: not-allowed;
}
html.dark input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="button"]):not([type="submit"]):not([type="reset"]):not([type="file"]):not([type="color"]):not([type="range"]),
html.dark select,
html.dark textarea {
    color-scheme: dark;
    background-color: #0b1b2c !important;
    color: #f8fafc !important;
}
html.dark input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="button"]):not([type="submit"]):not([type="reset"]):not([type="file"]):not([type="color"]):not([type="range"])::placeholder,
html.dark textarea::placeholder {
    color: #94a3b8 !important;
}
html.dark input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="button"]):not([type="submit"]):not([type="reset"]):not([type="file"]):not([type="color"]):not([type="range"]):disabled,
html.dark select:disabled,
html.dark textarea:disabled {
    background-color: #14263a !important;
    color: #94a3b8 !important;
}
</style>
@endonce