<?php $__env->startSection('title'); ?>
    <?php echo e(__('Permission Details')); ?> | <?php echo e(config('app.name')); ?>

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

    <?php echo ld_apply_filters('permission_details_after_breadcrumbs', ''); ?>


    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-t border-gray-100 dark:border-gray-800 p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400"><?php echo e(__('Permission Name')); ?></h4>
                            <p class="mt-1 text-lg font-medium text-gray-800 dark:text-white"><?php echo e($permission->name); ?></p>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400"><?php echo e(__('Permission Group')); ?></h4>
                            <p class="mt-1">
                                <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full dark:bg-gray-700 dark:text-white">
                                    <?php echo e($permission->group_name); ?>

                                </span>
                            </p>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2"><?php echo e(__('Assigned Roles')); ?></h4>
                        <?php if($roles->count() > 0): ?>
                            <div class="space-y-2">
                                <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                        <div class="flex items-center">
                                            <span class="text-gray-800 dark:text-white"><?php echo e($role->name); ?></span>
                                        </div>
                                        <a href="<?php echo e(route('admin.roles.edit', $role->id)); ?>" class="text-primary hover:underline text-sm">
                                            <i class="bi bi-eye mr-1"></i> <?php echo e(__('View Role')); ?>

                                        </a>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php else: ?>
                            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
                                <span class="text-gray-500 dark:text-gray-400"><?php echo e(__('No roles have this permission')); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/permissions/show.blade.php ENDPATH**/ ?>