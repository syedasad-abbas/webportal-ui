@extends('backend.layouts.app')

@section('title')
   {{ $breadcrumbs['title'] }} | {{ config('app.name') }}
@endsection

@section('before_vite_build')
    <script>
        var userGrowthData = @json($user_growth_data['data']);
        var userGrowthLabels = @json($user_growth_data['labels']);
    </script>
@endsection

@section('admin-content')
    <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
        <x-breadcrumbs :breadcrumbs="$breadcrumbs" />

        {!! ld_apply_filters('dashboard_after_breadcrumbs', '') !!}

        <div class="grid grid-cols-12 gap-4 md:gap-6">
            <div class="col-span-12 space-y-6">
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-4 md:gap-6">
                    {!! ld_apply_filters('dashboard_cards_before_users', '') !!}
                    @include('backend.pages.dashboard.partials.card', [
                        'icon_svg' => asset('images/icons/user.svg'),
                        'label' => __('Users'),
                        'value' => $total_users,
                        'bg' => '#635BFF',
                        'class' => 'bg-white',
                        'url' => route('admin.users.index'),
                        'enable_full_div_click' => true,
                        'value_attr' => 'total_users',
                    ])
                    {!! ld_apply_filters('dashboard_cards_after_users', '') !!}
                    @include('backend.pages.dashboard.partials.card', [
                        'icon_svg' => asset('images/icons/key.svg'),
                        'label' => __('Roles'),
                        'value' => $total_roles,
                        'bg' => '#00D7FF',
                        'class' => 'bg-white',
                        'url' => route('admin.roles.index'),
                        'enable_full_div_click' => true,
                    ])
                    {!! ld_apply_filters('dashboard_cards_after_roles', '') !!}
                    @include('backend.pages.dashboard.partials.card', [
                        'icon' => 'bi bi-shield-check',
                        'label' => __('Permissions'),
                        'value' => $total_permissions,
                        'bg' => '#FF4D96',
                        'class' => 'bg-white',
                        'url' => route('admin.permissions.index'),
                        'enable_full_div_click' => true,
                    ])
                    {!! ld_apply_filters('dashboard_cards_after_permissions', '') !!}
                    @include('backend.pages.dashboard.partials.card', [
                        'icon' => 'bi bi-translate',
                        'label' => __('Translations'),
                        'value' => $languages['total'] . ' / ' . $languages['active'],
                        'bg' => '#22C55E',
                        'class' => 'bg-white',
                        'url' => route('admin.translations.index'),
                        'enable_full_div_click' => true,
                    ])
                    {!! ld_apply_filters('dashboard_cards_after_translations', '') !!}
                    @include('backend.pages.dashboard.partials.card', [
                        'icon' => 'bi bi-check-circle',
                        'label' => __('Active Users'),
                        'value' => $active_users ?? 0,
                        'bg' => '#34D399',
                        'class' => 'bg-white',
                        'url' => route('admin.users.active'),
                        'enable_full_div_click' => true,
                        'value_attr' => 'active_users',
                    ])
                    @include('backend.pages.dashboard.partials.card', [
                        'icon' => 'bi bi-power',
                        'label' => __('Offline Users'),
                        'value' => $offline_users ?? 0,
                        'bg' => '#FACC15',
                        'class' => 'bg-white',
                        'url' => route('admin.users.offline'),
                        'enable_full_div_click' => true,
                        'value_attr' => 'offline_users',
                    ])
                    @include('backend.pages.dashboard.partials.card', [
                        'icon' => 'bi bi-telephone-outbound',
                        'label' => __('Dialing Users'),
                        'value' => $dialing_users ?? 0,
                        'bg' => '#0EA5E9',
                        'class' => 'bg-white',
                        'url' => route('admin.calls.dialing'),
                        'enable_full_div_click' => true,
                        'value_attr' => 'dialing_users',
                    ])
                    @include('backend.pages.dashboard.partials.card', [
                        'icon' => 'bi bi-telephone-inbound',
                        'label' => __('In-Call Users'),
                        'value' => $in_call_users ?? 0,
                        'bg' => '#A855F7',
                        'class' => 'bg-white',
                        'url' => route('admin.calls.in_call'),
                        'enable_full_div_click' => true,
                        'value_attr' => 'in_call_users',
                    ])
                </div>
            </div>
        </div>

        {!! ld_apply_filters('dashboard_cards_after', '') !!}

        <div class="mt-6">
            <div class="grid grid-cols-12 gap-4 md:gap-6">
                <div class="col-span-12">
                    <div class="grid grid-cols-12 gap-4 md:gap-6">
                        <div class="col-span-12 md:col-span-8">
                            @include('backend.pages.dashboard.partials.user-growth')
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            @include('backend.pages.dashboard.partials.user-history')
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-6">
            <div class="grid grid-cols-12 gap-4 md:gap-6">
                <div class="col-span-12">
                    <div class="grid grid-cols-12 gap-4 md:gap-6">
                        @include('backend.pages.dashboard.partials.post-chart')
                    </div>
                </div>
            </div>
        </div>

        {!! ld_apply_filters('dashboard_after', '') !!}
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js" integrity="sha384-wsgO4YJ9h5tqcfp/J2vh7vHwlGHNirMRHkRkNvztNFVQVw1Gc7YCOUMIqFZp2kOz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof io === 'undefined') {
                return;
            }

            const wsUrl = @json(rtrim(config('services.backend.ws_url'), '/'));
            let socket;
            try {
                socket = io(wsUrl, {
                    transports: ['websocket', 'polling']
                });
            } catch (error) {
                console.warn('Unable to establish dashboard socket connection', error);
                return;
            }

            const formatNumber = (value) => Number(value || 0).toLocaleString();
            const setMetric = (name, value) => {
                const el = document.querySelector(`[data-dashboard-metric="${name}"]`);
                if (el) {
                    el.textContent = formatNumber(value);
                }
            };

            socket.on('dashboard.metrics', (payload) => {
                if (!payload) {
                    return;
                }

                if (payload.presence) {
                    setMetric('total_users', payload.presence.total);
                    setMetric('active_users', payload.presence.active);
                    setMetric('offline_users', payload.presence.offline);
                }

                setMetric('dialing_users', payload.dialingUsers ?? 0);
                setMetric('in_call_users', payload.inCallUsers ?? 0);

                if (
                    window.DashboardActivityChart &&
                    window.DashboardActivityChart.selectedUser === 0 &&
                    payload.activity
                ) {
                    window.DashboardActivityChart.update(payload.activity);
                }
            });
        });
    </script>
@endpush
