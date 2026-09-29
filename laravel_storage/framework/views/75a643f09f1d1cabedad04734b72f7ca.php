<?php $__env->startSection('title'); ?>
    <?php echo e($breadcrumbs['title']); ?> | <?php echo e(config('app.name')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('admin-content'); ?>
    <div class="p-4 mx-auto max-w-4xl md:p-6">
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

        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="p-5 space-y-6 border-t border-gray-100 dark:border-gray-800 sm:p-6">
                <form action="<?php echo e(route('admin.campaigns.update', $campaign)); ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="list_id" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                <?php echo e(__('List ID')); ?>

                            </label>
                            <input
                                type="text"
                                name="list_id"
                                id="list_id"
                                required
                                value="<?php echo e(old('list_id', $campaign->list_id)); ?>"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            >
                        </div>

                        <div>
                            <label for="list_name" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                <?php echo e(__('List Name')); ?>

                            </label>
                            <input
                                type="text"
                                name="list_name"
                                id="list_name"
                                required
                                value="<?php echo e(old('list_name', $campaign->list_name)); ?>"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            >
                        </div>

                        <div class="sm:col-span-2">
                            <label for="list_description" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                <?php echo e(__('List Description')); ?>

                            </label>
                            <input
                                type="text"
                                name="list_description"
                                id="list_description"
                                required
                                value="<?php echo e(old('list_description', $campaign->list_description)); ?>"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            >
                        </div>

                        <div class="sm:col-span-2">
                            <label for="file" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                <?php echo e(__('Replace File (optional)')); ?>

                            </label>
                            <input
                                type="file"
                                name="file"
                                id="file"
                                accept=".csv,.xls,.xlsx"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            >
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                <?php echo e(__('Uploading a new file will restart the import for this campaign.')); ?>

                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-start gap-4">
                        <button type="submit" class="btn-primary"><?php echo e(__('Save Changes')); ?></button>
                        <a href="<?php echo e(route('admin.campaigns.index')); ?>" class="btn-default"><?php echo e(__('Cancel')); ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/Campaigns/edit.blade.php ENDPATH**/ ?>