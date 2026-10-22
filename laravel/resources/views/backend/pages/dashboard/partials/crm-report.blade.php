<section class="crm-dashboard-panel crm-report crm-report-{{ $kind }}" aria-label="{{ $title }}">
    <h2>{{ $title }}</h2>
    <div class="crm-report-empty">
        <svg width="48" height="48" viewBox="0 0 48 48" fill="none" aria-hidden="true">
            <rect x="4" y="4" width="40" height="40" rx="8" fill="currentColor" opacity=".08"/>
            <path d="M13 34V24M24 34V14M35 34V20" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
        </svg>
        <p>{{ __('CRM not connected') }}</p>
        <span>{{ __('Report values will appear after CRM reporting is configured.') }}</span>
    </div>
    @if(count($legend))
        <ul class="crm-report-legend" aria-label="{{ __('Chart legend') }}">
            @foreach($legend as $label)
                <li><span class="crm-report-swatch" aria-hidden="true"></span>{{ $label }}</li>
            @endforeach
        </ul>
    @endif
</section>
