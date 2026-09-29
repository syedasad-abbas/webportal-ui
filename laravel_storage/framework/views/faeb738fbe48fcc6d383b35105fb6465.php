<?php $__env->startSection('title'); ?>
   <?php echo e($breadcrumbs['title']); ?> | <?php echo e(config('app.name')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('before_vite_build'); ?>
    <script>
        var userGrowthData = <?php echo json_encode($user_growth_data['data'], 15, 512) ?>;
        var userGrowthLabels = <?php echo json_encode($user_growth_data['labels'], 15, 512) ?>;
    </script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('admin-content'); ?>
    <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
        <?php if (isset($component)) { $__componentOriginal360d002b1b676b6f84d43220f22129e2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal360d002b1b676b6f84d43220f22129e2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.breadcrumbs','data' => ['breadcrumbs' => $breadcrumbs]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('breadcrumbs'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($breadcrumbs)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal360d002b1b676b6f84d43220f22129e2)): ?>
<?php $attributes = $__attributesOriginal360d002b1b676b6f84d43220f22129e2; ?>
<?php unset($__attributesOriginal360d002b1b676b6f84d43220f22129e2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal360d002b1b676b6f84d43220f22129e2)): ?>
<?php $component = $__componentOriginal360d002b1b676b6f84d43220f22129e2; ?>
<?php unset($__componentOriginal360d002b1b676b6f84d43220f22129e2); ?>
<?php endif; ?>

        <?php echo ld_apply_filters('dashboard_after_breadcrumbs', ''); ?>


        <div class="grid grid-cols-12 gap-4 md:gap-6">
            <div class="col-span-12 space-y-6">
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-4 md:gap-6">
                    <?php echo ld_apply_filters('dashboard_cards_before_users', ''); ?>

                    <?php echo $__env->make('backend.pages.dashboard.partials.card', [
                        'icon_svg' => asset('images/icons/user.svg'),
                        'label' => __('Users'),
                        'value' => $total_users,
                        'bg' => '#635BFF',
                        'class' => 'bg-white',
                        'url' => route('admin.users.index'),
                        'enable_full_div_click' => true,
                        'value_attr' => 'total_users',
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo ld_apply_filters('dashboard_cards_after_users', ''); ?>

                    <?php echo $__env->make('backend.pages.dashboard.partials.card', [
                        'icon_svg' => asset('images/icons/key.svg'),
                        'label' => __('Roles'),
                        'value' => $total_roles,
                        'bg' => '#00D7FF',
                        'class' => 'bg-white',
                        'url' => route('admin.roles.index'),
                        'enable_full_div_click' => true,
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo ld_apply_filters('dashboard_cards_after_roles', ''); ?>

                    <?php echo $__env->make('backend.pages.dashboard.partials.card', [
                        'icon' => 'bi bi-shield-check',
                        'label' => __('Permissions'),
                        'value' => $total_permissions,
                        'bg' => '#FF4D96',
                        'class' => 'bg-white',
                        'url' => route('admin.permissions.index'),
                        'enable_full_div_click' => true,
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo ld_apply_filters('dashboard_cards_after_permissions', ''); ?>

                    <?php echo $__env->make('backend.pages.dashboard.partials.card', [
                        'icon' => 'bi bi-translate',
                        'label' => __('Translations'),
                        'value' => $languages['total'] . ' / ' . $languages['active'],
                        'bg' => '#22C55E',
                        'class' => 'bg-white',
                        'url' => route('admin.translations.index'),
                        'enable_full_div_click' => true,
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo ld_apply_filters('dashboard_cards_after_translations', ''); ?>

                    <?php echo $__env->make('backend.pages.dashboard.partials.card', [
                        'icon' => 'bi bi-check-circle',
                        'label' => __('Active Users'),
                        'value' => $active_users ?? 0,
                        'bg' => '#34D399',
                        'class' => 'bg-white',
                        'url' => route('admin.users.active'),
                        'enable_full_div_click' => true,
                        'value_attr' => 'active_users',
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo $__env->make('backend.pages.dashboard.partials.card', [
                        'icon' => 'bi bi-power',
                        'label' => __('Offline Users'),
                        'value' => $offline_users ?? 0,
                        'bg' => '#FACC15',
                        'class' => 'bg-white',
                        'url' => route('admin.users.offline'),
                        'enable_full_div_click' => true,
                        'value_attr' => 'offline_users',
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo $__env->make('backend.pages.dashboard.partials.card', [
                        'icon' => 'bi bi-telephone-outbound',
                        'label' => __('Dialing Users'),
                        'value' => $dialing_users ?? 0,
                        'bg' => '#0EA5E9',
                        'class' => 'bg-white',
                        'url' => route('admin.calls.dialing'),
                        'enable_full_div_click' => true,
                        'value_attr' => 'dialing_users',
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo $__env->make('backend.pages.dashboard.partials.card', [
                        'icon' => 'bi bi-telephone-inbound',
                        'label' => __('In-Call Users'),
                        'value' => $in_call_users ?? 0,
                        'bg' => '#A855F7',
                        'class' => 'bg-white',
                        'url' => route('admin.calls.in_call'),
                        'enable_full_div_click' => true,
                        'value_attr' => 'in_call_users',
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
        </div>

        <?php echo ld_apply_filters('dashboard_cards_after', ''); ?>


        <div class="mt-6">
            <div class="grid grid-cols-12 gap-4 md:gap-6">
                <div class="col-span-12">
                    <div class="grid grid-cols-12 gap-4 md:gap-6">
                        <div class="col-span-12 md:col-span-8">
                            <?php echo $__env->make('backend.pages.dashboard.partials.user-growth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <?php echo $__env->make('backend.pages.dashboard.partials.user-history', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-6">
            <div class="grid grid-cols-12 gap-4 md:gap-6">
                <div class="col-span-12">
                    <div class="grid grid-cols-12 gap-4 md:gap-6">
                        <?php echo $__env->make('backend.pages.dashboard.partials.user-call-time-chart', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <div class="grid grid-cols-12 gap-4 md:gap-6">
                <div class="col-span-12">
                    <div class="grid grid-cols-12 gap-4 md:gap-6">
                        <?php echo $__env->make('backend.pages.dashboard.partials.post-chart', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
            </div>
        </div>

        <?php echo ld_apply_filters('dashboard_after', ''); ?>

    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof io === 'undefined') {
                return;
            }

            const wsUrl = <?php echo json_encode(rtrim((string) config('services.backend.ws_url'), '/'), 512) ?> || window.location.origin;
            let socket;
            try {
                socket = io(wsUrl, {
                    transports: ['websocket', 'polling'],
                    auth: { token: <?php echo json_encode(session('admin_token'), 15, 512) ?> }
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
                    window.DashboardActivityChart.selectedPeriod === 'today' &&
                    payload.activity
                ) {
                    window.DashboardActivityChart.update(payload.activity);
                }

                if (window.DashboardUserCallTimeChart && payload.callTimeTimeline) {
                    window.DashboardUserCallTimeChart.update(payload.callTimeTimeline);
                }
            });

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
                setInterval(() => {
                    fetch(<?php echo json_encode(route('admin.ping'), 15, 512) ?>, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    }).catch(() => {});
                }, 60000);
            }
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/dashboard/index.blade.php ENDPATH**/ ?>