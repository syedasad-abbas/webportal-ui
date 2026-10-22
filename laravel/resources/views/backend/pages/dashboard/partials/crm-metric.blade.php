<a href="{{ $url }}" class="crm-dashboard-metric crm-dashboard-tone-{{ $tone ?? 'default' }}">
    <span class="crm-dashboard-metric-label">{{ $label }}</span>
    <span class="crm-dashboard-metric-value" @if(!empty($metric)) data-dashboard-metric="{{ $metric }}" @endif>{{ $value }}</span>
</a>
