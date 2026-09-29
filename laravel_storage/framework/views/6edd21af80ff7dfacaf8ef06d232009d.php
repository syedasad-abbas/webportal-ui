<?php $__env->startSection('title'); ?>
    <?php echo e(ucfirst($tab ?? '') . ' ' . __('Settings')); ?> | <?php echo e(config('app.name')); ?>

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

        <?php echo ld_apply_filters('settings_after_breadcrumbs', ''); ?>


        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="px-5 py-4 sm:px-6 sm:py-5">
                    <form method="POST" action="<?php echo e(route('admin.settings.store')); ?>" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <?php echo $__env->make('backend.pages.settings.tabs', [
                            'tabs' => ld_apply_filters('settings_tabs', [
                                'general' => [
                                    'title' => __('General Settings'),
                                    'view' => 'backend.pages.settings.general-tab',
                                ],
                                'appearance' => [
                                    'title' => __('Site Appearance'),
                                    'view' => 'backend.pages.settings.appearance-tab',
                                ],
                                'content' => [
                                    'title' => __('Content Settings'),
                                    'view' => 'backend.pages.settings.content-settings',
                                ],
                                'integrations' => [
                                    'title' => __('Integrations'),
                                    'view' => 'backend.pages.settings.integration-settings',
                                ],
                            ]),
                        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('settings.edit')): ?>
                        <!-- Submit Button -->
                        <div class="flex justify-start">
                            <button type="submit" class="btn-primary">
                                <?php echo e(__('Save')); ?>

                            </button>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

        </div>

    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabButtons = document.querySelectorAll('[role="tab"]');

        function setActiveTab(tabKey) {
            tabButtons.forEach(button => {
                const isActive = button.getAttribute('data-tab') === tabKey;

                button.classList.toggle('text-primary', isActive);
                button.classList.toggle('border-primary', isActive);
                button.classList.toggle('dark:text-primary', isActive);
                button.classList.toggle('dark:border-primary', isActive);
                button.classList.toggle('text-gray-500', !isActive);
                button.classList.toggle('border-transparent', !isActive);
            });

            // Optional: Show/hide corresponding tab content
            document.querySelectorAll('[role="tabpanel"]').forEach(panel => {
                panel.style.display = panel.id === tabKey ? 'block' : 'none';
            });
        }

        // Handle click
        tabButtons.forEach(button => {
            button.addEventListener('click', function () {
                const tabKey = this.getAttribute('data-tab');
                const url = new URL(window.location);
                url.searchParams.set('tab', tabKey);
                window.history.pushState({}, '', url);

                setActiveTab(tabKey);
            });
        });

        // On page load, set active tab from URL
        const urlTab = new URL(window.location).searchParams.get('tab') || 'general';
        setActiveTab(urlTab);
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/settings/index.blade.php ENDPATH**/ ?>