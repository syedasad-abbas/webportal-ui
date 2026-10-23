@extends('backend.layouts.app')

@section('title')
   {{ $breadcrumbs['title'] }} | {{ config('app.name') }}
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dialer-dashboard.css') }}?v=3">
@endpush

@section('admin-content')
    {{-- Dashboard composition and palette from laravel-crm, inside the existing dialer shell. --}}
    <div class="crm-dashboard">
        <x-breadcrumbs :breadcrumbs="$breadcrumbs" />
        {!! ld_apply_filters('dashboard_after_breadcrumbs', '') !!}
        @if($crm_summary)
            <p class="crm-dashboard-notice crm-dashboard-notice--connected" role="status">{{ __('CRM connected. Revenue and lead values are live. Active Call Transcripts remains connected to the dialer.') }}</p>
        @else
            <p class="crm-dashboard-notice" role="status">{{ __('CRM reporting is not connected. Revenue and lead values are unavailable. Active Call Transcripts remains connected to the dialer.') }}</p>
        @endif
        <div class="crm-dashboard-layout">
            <div class="crm-dashboard-main">
                <section class="crm-dashboard-panel crm-dashboard-overview" aria-label="{{ __('Revenue') }}">
                    <div class="crm-dashboard-highlights">
                        @foreach([['Won Revenue', 'green', 'won_revenue'], ['Lost Revenue', 'red', 'lost_revenue']] as [$label, $tone, $key])
                            <div class="crm-dashboard-metric crm-dashboard-tone-{{ $tone }}">
                                <span class="crm-dashboard-metric-label">{{ __($label) }}</span>
                                @if($crm_summary)
                                    <span class="crm-dashboard-metric-value">{{ number_format((float) ($crm_summary[$key] ?? 0), 2) }}</span>
                                    <span class="crm-dashboard-metric-note">{{ __('Live from CRM') }}</span>
                                @else
                                    <span class="crm-dashboard-metric-value" aria-label="{{ __('Unavailable') }}">—</span>
                                    <span class="crm-dashboard-metric-note">{{ __('CRM not connected') }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="crm-dashboard-chart">
                        @include('backend.pages.dashboard.partials.crm-report', ['title' => __('Revenue'), 'kind' => 'revenue', 'legend' => [__('Won Revenue'), __('Lost Revenue')], 'crm_summary' => $crm_summary])
                    </div>
                </section>

                <div class="crm-dashboard-stats">
                    @if($crm_summary)
                        @foreach([
                            ['Total Customers', $crm_summary['total_customers'] ?? 0],
                            ['Open Leads', $crm_summary['open_leads'] ?? 0],
                            ['Appointments Booked', $crm_summary['appointments_booked'] ?? 0],
                        ] as [$label, $value])
                            <div class="crm-dashboard-metric">
                                <span class="crm-dashboard-metric-label">{{ __($label) }}</span>
                                <span class="crm-dashboard-metric-value">{{ number_format($value) }}</span>
                                <span class="crm-dashboard-metric-note">{{ __('Live from CRM') }}</span>
                            </div>
                        @endforeach
                    @else
                        @foreach(['Average Lead Value', 'Total Leads', 'Average Leads Per Day', 'Total Quotations', 'Total Persons', 'Total Organizations', 'Appointments Booked'] as $label)
                            <div class="crm-dashboard-metric">
                                <span class="crm-dashboard-metric-label">{{ __($label) }}</span>
                                <span class="crm-dashboard-metric-value" aria-label="{{ __('Unavailable') }}">—</span>
                                <span class="crm-dashboard-metric-note">{{ __('CRM not connected') }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
                @include('backend.pages.dashboard.partials.crm-report', ['title' => __('Leads'), 'kind' => 'leads', 'legend' => [__('All'), __('Won'), __('Lost')], 'crm_summary' => $crm_summary])
                <div class="crm-dashboard-pair">
                    @include('backend.pages.dashboard.partials.crm-report', ['title' => __('Top Products'), 'kind' => 'products', 'legend' => [], 'crm_summary' => $crm_summary])
                    @include('backend.pages.dashboard.partials.crm-report', ['title' => __('Top Persons'), 'kind' => 'persons', 'legend' => [], 'crm_summary' => $crm_summary])
                </div>
            </div>
            <aside class="crm-dashboard-summary" aria-label="{{ __('Dashboard summary') }}">
                <div class="crm-dashboard-transcripts">
                    @include('backend.pages.dashboard.partials._call-transcripts')
                </div>
                @include('backend.pages.dashboard.partials.crm-report', ['title' => __('Open Leads By Stages'), 'kind' => 'stages', 'legend' => [], 'crm_summary' => $crm_summary])
                @include('backend.pages.dashboard.partials.crm-report', ['title' => __('Revenue By Sources'), 'kind' => 'sources', 'legend' => [], 'crm_summary' => $crm_summary])
                @include('backend.pages.dashboard.partials.crm-report', ['title' => __('Revenue By Types'), 'kind' => 'types', 'legend' => [], 'crm_summary' => $crm_summary])
            </aside>
        </div>
        {!! ld_apply_filters('dashboard_after', '') !!}
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) return;
            setInterval(() => {
                fetch(@json(route('admin.ping')), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                }).catch(() => {});
            }, 60000);
        });
    </script>
@endpush
