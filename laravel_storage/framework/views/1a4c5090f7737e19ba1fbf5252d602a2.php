<?php $__env->startSection('title'); ?>
    <?php echo e($breadcrumbs['title']); ?> | <?php echo e(config('app.name')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('admin-content'); ?>
<div class="connectpro-admin-page p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
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

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-5">
            <div>
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90"><?php echo e(__('Inbound DIDs')); ?></h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <?php echo e(__('Only active numbers in this list are accepted and routed to online agents.')); ?>

                </p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <?php echo $__env->make('backend.partials.search-form', ['placeholder' => __('Search DID, label, or carrier')], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <a href="<?php echo e(route('admin.carrier.inbound-dids.create')); ?>" class="btn-primary whitespace-nowrap">
                    <?php echo e(__('Add Inbound DID')); ?>

                </a>
            </div>
        </div>

        <div class="overflow-x-auto border-t border-gray-100 dark:border-gray-800">
            <table class="w-full dark:text-gray-400">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-800">
                        <th class="px-5 py-3 text-left dark:text-white"><?php echo e(__('DID')); ?></th>
                        <th class="px-5 py-3 text-left dark:text-white"><?php echo e(__('Label')); ?></th>
                        <th class="px-5 py-3 text-left dark:text-white"><?php echo e(__('Carrier')); ?></th>
                        <th class="px-5 py-3 text-left dark:text-white"><?php echo e(__('Status')); ?></th>
                        <th class="px-5 py-3 text-right dark:text-white"><?php echo e(__('Actions')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $dids; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $did): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-5 py-4 font-medium text-gray-800 dark:text-white/90">+<?php echo e($did->did); ?></td>
                            <td class="px-5 py-4"><?php echo e($did->label ?: '—'); ?></td>
                            <td class="px-5 py-4"><?php echo e($did->carrier?->name ?: '—'); ?></td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium <?php echo e($did->is_active ? 'bg-green-100 text-green-800 dark:bg-green-500/30 dark:text-green-100' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200'); ?>">
                                    <?php echo e($did->is_active ? __('Active') : __('Inactive')); ?>

                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex justify-end gap-3">
                                    <a href="<?php echo e(route('admin.carrier.inbound-dids.edit', $did)); ?>" class="text-brand-600 hover:underline"><?php echo e(__('Edit')); ?></a>
                                    <form method="POST" action="<?php echo e(route('admin.carrier.inbound-dids.destroy', $did)); ?>" onsubmit="return confirm('<?php echo e(__('Delete this inbound DID? Calls to it will be rejected.')); ?>')">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="text-red-600 hover:underline"><?php echo e(__('Delete')); ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                                <?php echo e(__('No inbound DIDs configured. Add your carrier-provided DID to receive calls.')); ?>

                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 sm:px-6"><?php echo e($dids->links()); ?></div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/carrier/inbound-dids/index.blade.php ENDPATH**/ ?>