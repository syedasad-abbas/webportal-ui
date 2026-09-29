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

    <?php echo ld_apply_filters('permissions_after_breadcrumbs', ''); ?>


    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5 flex justify-between items-center">
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90"><?php echo e(__('Permissions')); ?></h3>

                <?php echo $__env->make('backend.partials.search-form', [
                    'placeholder' => __('Search by name or group'),
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
            <div class="space-y-3 border-t border-gray-100 dark:border-gray-800 overflow-x-auto overflow-y-visible">
                <table id="dataTable" class="w-full dark:text-gray-400">
                    <thead class="bg-light text-capitalize">
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th width="5%" class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5"><?php echo e(__('Sl')); ?></th>
                            <th width="20%" class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">
                                <div class="flex items-center">
                                    <?php echo e(__('Name')); ?>

                                    <a href="<?php echo e(request()->fullUrlWithQuery(['sort' => request()->sort === 'name' ? '-name' : 'name'])); ?>" class="ml-1">
                                        <?php if(request()->sort === 'name'): ?>
                                            <i class="bi bi-sort-alpha-down text-primary"></i>
                                        <?php elseif(request()->sort === '-name'): ?>
                                            <i class="bi bi-sort-alpha-up text-primary"></i>
                                        <?php else: ?>
                                            <i class="bi bi-arrow-down-up text-gray-400"></i>
                                        <?php endif; ?>
                                    </a>
                                </div>
                            </th>
                            <th width="15%" class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">
                                <div class="flex items-center">
                                    <?php echo e(__('Group')); ?>

                                    <a href="<?php echo e(request()->fullUrlWithQuery(['sort' => request()->sort === 'group_name' ? '-group_name' : 'group_name'])); ?>" class="ml-1">
                                        <?php if(request()->sort === 'group_name'): ?>
                                            <i class="bi bi-sort-alpha-down text-primary"></i>
                                        <?php elseif(request()->sort === '-group_name'): ?>
                                            <i class="bi bi-sort-alpha-up text-primary"></i>
                                        <?php else: ?>
                                            <i class="bi bi-arrow-down-up text-gray-400"></i>
                                        <?php endif; ?>
                                    </a>
                                </div>
                            </th>
                            <th width="45%" class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">
                                <div class="flex items-center">
                                    <?php echo e(__('Roles')); ?>

                                    <a href="<?php echo e(request()->fullUrlWithQuery(['sort' => request()->sort === 'role_count' ? '-role_count' : 'role_count'])); ?>" class="ml-1">
                                        <?php if(request()->sort === 'role_count'): ?>
                                            <i class="bi bi-sort-numeric-down text-primary"></i>
                                        <?php elseif(request()->sort === '-role_count'): ?>
                                            <i class="bi bi-sort-numeric-up text-primary"></i>
                                        <?php else: ?>
                                            <i class="bi bi-arrow-down-up text-gray-400"></i>
                                        <?php endif; ?>
                                    </a>
                                </div>
                            </th>
                           
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="<?php echo e($loop->index + 1 != count($permissions) ?  'border-b border-gray-100 dark:border-gray-800' : ''); ?>">
                                <td class="px-5 py-4 sm:px-6"><?php echo e($loop->index + 1); ?></td>
                                <td class="px-5 py-4 sm:px-6">
                                    <?php echo e(ucfirst($permission->name)); ?>

                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full dark:bg-gray-700 dark:text-white">
                                        <?php echo e(ucfirst($permission->group_name)); ?>

                                    </span>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <?php if($permission->role_count > 0): ?>
                                        <div class="flex items-center">
                                            <a href="<?php echo e(route('admin.permissions.show', $permission->id)); ?>" class="text-primary hover:underline">
                                                <span class="inline-flex items-center justify-center px-2 py-1 mr-2 text-xs font-medium text-gray-800 bg-gray-100 rounded-full dark:bg-gray-700 dark:text-white">
                                                    <?php echo e($permission->role_count); ?>

                                                </span>
                                                <?php echo e($permission->roles_list); ?>

                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400"><?php echo e(__('No roles assigned')); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td colspan="5" class="px-5 py-4 sm:px-6 text-center">
                                    <span class="text-gray-500 dark:text-gray-400"><?php echo e(__('No permissions found')); ?></span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="my-4 px-4 sm:px-6">
                    <?php echo e($permissions->links()); ?>

                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/permissions/index.blade.php ENDPATH**/ ?>