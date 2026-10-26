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
        <p class="crm-dashboard-notice crm-dashboard-notice--connected" role="status" id="crm-status-notice">{{ __('CRM connected. Revenue and lead values are live. Active Call Transcripts remains connected to the dialer.') }}</p>
        <div class="crm-dashboard-health" id="crm-dashboard-health">
            <span class="health-dot health-dot--ok" id="health-dot-db"></span>
            <span class="health-label">{{ __('DB') }}</span>
            <span class="health-dot health-dot--ok" id="health-dot-fs"></span>
            <span class="health-label">{{ __('FreeSWITCH') }}</span>
            <span class="health-dot health-dot--ok" id="health-dot-gemini"></span>
            <span class="health-label">{{ __('Gemini') }}</span>
            <span class="health-dot health-dot--ok" id="health-dot-crm"></span>
            <span class="health-label">{{ __('CRM') }}</span>
        </div>
        <div class="crm-dashboard-layout">
            <div class="crm-dashboard-main">
                <section class="crm-dashboard-panel crm-dashboard-overview" aria-label="{{ __('Revenue') }}">
                    <div class="crm-dashboard-highlights">
                        @foreach(collect($crm_cards)->where('enabled', true)->whereIn('key', ['won_revenue','lost_revenue']) as $card)
                            @php $key=$card['key']; $label=$card['label']; $tone=$key==='won_revenue'?'green':'red'; @endphp
                            <div class="crm-dashboard-metric crm-dashboard-tone-{{ $tone }}">
                                <span class="crm-dashboard-metric-label">{{ __($label) }}</span>
                                <span class="crm-dashboard-metric-value" data-crm-key="{{ $key }}">@if($crm_summary){{ number_format((float) ($crm_summary[$key] ?? 0), 2) }}@else—@endif</span>
                                <span class="crm-dashboard-metric-note" data-crm-key="{{ $key }}-note">@if($crm_summary){{ __('Live from CRM') }}@else{{ __('CRM not connected') }}@endif</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="crm-dashboard-chart">
                        @include('backend.pages.dashboard.partials.crm-report', ['title' => __('Revenue'), 'kind' => 'revenue', 'legend' => [__('Won Revenue'), __('Lost Revenue')], 'crm_summary' => $crm_summary])
                    </div>
                </section>

                <div class="crm-dashboard-stats">
                    @foreach(collect($crm_cards)->where('enabled', true)->whereNotIn('key', ['won_revenue','lost_revenue']) as $card)
                        @php $key=$card['key']; $label=$card['label']; @endphp
                        <div class="crm-dashboard-metric">
                            <span class="crm-dashboard-metric-label">{{ __($label) }}</span>
                            <span class="crm-dashboard-metric-value" data-crm-key="{{ $key }}">@if($crm_summary){{ number_format($crm_summary[$key] ?? 0) }}@else—@endif</span>
                            <span class="crm-dashboard-metric-note" data-crm-key="{{ $key }}-note">@if($crm_summary){{ __('Live from CRM') }}@else{{ __('CRM not connected') }}@endif</span>
                        </div>
                    @endforeach
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
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) return;

            const apiHeaders = {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            };

            let socket = null;

            async function connectSocket() {
                try {
                    const res = await fetch(@json(route('dashboard.api.socket-token')), {
                        method: 'GET',
                        headers: apiHeaders,
                        credentials: 'same-origin'
                    });
                    const data = await res.json();
                    if (!data.token) throw new Error('No socket token');

                    const nodeUrl = @json(config('app.node_backend_url', 'http://127.0.0.1:4000'));
                    socket = io(nodeUrl, {
                        auth: { token: data.token },
                        transports: ['websocket', 'polling']
                    });

                    socket.on('connect', () => {
                        console.log('[dashboard] socket connected');
                    });

                    socket.on('disconnect', () => {
                        console.log('[dashboard] socket disconnected, reconnecting in 5s');
                        setTimeout(connectSocket, 5000);
                    });

                    socket.on('call:started', (call) => {
                        console.log('[dashboard] call started', call);
                        fetchActiveCalls();
                        updateCrmNotice('connected');
                    });

                    socket.on('call:ended', (call) => {
                        console.log('[dashboard] call ended', call);
                        fetchActiveCalls();
                    });

                    socket.on('appointment:booked', (appt) => {
                        console.log('[dashboard] appointment booked', appt);
                        fetchCrmSummary();
                        // Show brief toast/notification
                        const notice = document.getElementById('crm-status-notice');
                        if (notice) {
                            const orig = notice.textContent;
                            notice.textContent = `{{ __("New appointment booked:") }} ${appt.customerName} - ${appt.appointmentDate}`;
                            notice.className = 'crm-dashboard-notice crm-dashboard-notice--connected';
                            setTimeout(() => { notice.textContent = orig; }, 5000);
                        }
                    });
                } catch (e) {
                    console.warn('Socket connection failed', e);
                    setTimeout(connectSocket, 5000);
                }
            }

            async function fetchCrmSummary() {
                try {
                    const res = await fetch(@json(route('dashboard.api.crm-summary')), {
                        method: 'GET',
                        headers: apiHeaders,
                        credentials: 'same-origin'
                    });
                    const data = await res.json();
                    if (data.ok && data.data) {
                        updateCrmMetrics(data.data);
                        const notice = document.getElementById('crm-status-notice');
                        if (notice) notice.textContent = '{{ __("CRM connected. Revenue and lead values are live. Active Call Transcripts remains connected to the dialer.") }}';
                        notice.className = 'crm-dashboard-notice crm-dashboard-notice--connected';
                    }
                } catch (e) {
                    console.warn('CRM summary fetch failed', e);
                }
            }

            async function fetchActiveCalls() {
                try {
                    const res = await fetch(@json(route('dashboard.api.active-calls')), {
                        method: 'GET',
                        headers: apiHeaders,
                        credentials: 'same-origin'
                    });
                    const data = await res.json();
                    if (data.ok) {
                        updateActiveCalls(data.calls);
                    }
                } catch (e) {
                    console.warn('Active calls fetch failed', e);
                }
            }

            function updateCrmMetrics(data) {
                const keyMap = {
                    'won_revenue': 'won_revenue',
                    'lost_revenue': 'lost_revenue',
                    'total_customers': 'total_customers',
                    'open_leads': 'open_leads',
                    'appointments_booked': 'appointments_booked',
                    'intakes_recorded': 'intakes_recorded',
                    'requested_amount_total': 'requested_amount_total'
                };
                Object.entries(keyMap).forEach(([dataKey, domKey]) => {
                    const el = document.querySelector(`[data-crm-key="${domKey}"]`);
                    const noteEl = document.querySelector(`[data-crm-key="${domKey}-note"]`);
                    if (el && data[dataKey] !== undefined) {
                        const val = dataKey.includes('revenue') ? Number(data[dataKey]).toFixed(2) : Number(data[dataKey]);
                        el.textContent = val.toLocaleString();
                    }
                    if (noteEl) noteEl.textContent = '{{ __("Live from CRM") }}';
                });
            }

            function updateActiveCalls(calls) {
                // Active calls are rendered server-side; this hook is available for future UI updates
                console.log('[dashboard] active calls', calls.length);
            }

            function updateCrmNotice(status) {
                const notice = document.getElementById('crm-status-notice');
                if (notice) {
                    notice.textContent = status === 'connected'
                        ? '{{ __("CRM connected. Revenue and lead values are live. Active Call Transcripts remains connected to the dialer.") }}'
                        : '{{ __("CRM not connected") }}';
                }
            }

            async function fetchHealth() {
                try {
                    const res = await fetch(@json(route('dashboard.api.health')), {
                        method: 'GET',
                        headers: apiHeaders,
                        credentials: 'same-origin'
                    });
                    const data = await res.json();
                    updateHealthUI(data.dependencies || {});
                } catch (e) {
                    console.warn('Health fetch failed', e);
                }
            }

            function updateHealthUI(deps) {
                const dotMap = {
                    db: 'health-dot-db',
                    freeswitch: 'health-dot-fs',
                    gemini: 'health-dot-gemini',
                    crm: 'health-dot-crm'
                };
                Object.entries(deps).forEach(([key, dep]) => {
                    const el = document.getElementById(dotMap[key]);
                    if (el) {
                        el.className = 'health-dot ' + (dep.status === 'ok' ? 'health-dot--ok' : 'health-dot--err');
                    }
                });
            }

            // Initial fetch
            fetchCrmSummary();
            fetchActiveCalls();
            fetchHealth();
            connectSocket();

            // Keep-alive ping
            setInterval(() => {
                fetch(@json(route('admin.ping')), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                }).catch(() => {});
            }, 60000);

            // Refresh health every 60 seconds
            setInterval(fetchHealth, 60000);
        });
    </script>
@endpush
