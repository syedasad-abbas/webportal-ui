<?php $__env->startSection('title'); ?>
    <?php echo e($breadcrumbs['title']); ?> | <?php echo e(config('app.name')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('admin-content'); ?>
<div class="connectpro-admin-page p-6 mx-auto max-w-7xl">
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

    <?php echo ld_apply_filters('roles_create_after_breadcrumbs', ''); ?>


    <form action="<?php echo e(route('admin.roles.store')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <div class="space-y-8">
            <!-- Role Details Section -->
            <div class="rounded-lg border border-gray-200 bg-white shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                        <?php echo e(__('Role Details')); ?>

                    </h3>
                    <div class="flex gap-4">
                        <button type="submit" class="btn-primary">
                            <?php echo e(__('Save')); ?>

                        </button>
                        <a href="<?php echo e(route('admin.roles.index')); ?>" class="btn-default">
                            <?php echo e(__('Cancel')); ?>

                        </a>
                    </div>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                <?php echo e(__('Role Name')); ?>

                            </label>
                            <input required autofocus name="name" value="<?php echo e(old('name')); ?>" type="text" placeholder="<?php echo e(__('Enter a Role Name')); ?>" class="mt-2 form-control">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permissions Section -->
            <div class="rounded-lg border border-gray-200 bg-white shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                        <?php echo e(__('Permissions')); ?>

                    </h3>
                </div>
                <div class="p-4">
                    <div class="mb-4">
                        <input type="checkbox" id="checkPermissionAll" class="mr-2 border-gray-400 dark:border-gray-500 rounded focus:ring-2 focus:ring-primary text-primary">
                        <label for="checkPermissionAll" class="text-sm text-gray-800 dark:text-gray-200">
                            <?php echo e(__('Select All')); ?>

                        </label>
                    </div>
                    <hr class="mb-6">
                    <?php $i = 1; ?>
                    <?php $__currentLoopData = $permission_groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="mb-6">
                        <div class="flex items-center mb-2">
                        <input type="checkbox" id="group<?php echo e($i); ?>Management" class="mr-2 border-gray-400 dark:border-gray-500 rounded focus:ring-2 focus:ring-primary text-primary">
                        <label for="group<?php echo e($i); ?>Management" class="capitalize text-sm font-medium text-gray-800 dark:text-gray-200">
                                <?php echo e(ucfirst($group->name)); ?>

                            </label>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 xl:grid-cols-6 gap-4" data-group="group<?php echo e($i); ?>Management">
                            <?php
                                $permissions = $roleService->getPermissionsByGroupName($group->name);
                            ?>
                            <?php $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div>
                                <input type="checkbox" id="checkPermission<?php echo e($permission->id); ?>" name="permissions[]" value="<?php echo e($permission->name); ?>" class="mr-2 border-gray-400 dark:border-gray-500 rounded focus:ring-2 focus:ring-primary text-primary">
                                <label for="checkPermission<?php echo e($permission->id); ?>" class="capitalize text-sm text-gray-800 dark:text-gray-200">
                                    <?php echo e($permission->name); ?>

                                </label>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                    <?php $i++; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-start gap-4">
                <button type="submit" class="btn-primary">
                    <?php echo e(__('Save')); ?>

                </button>
                <a href="<?php echo e(route('admin.roles.index')); ?>" class="btn-default">
                    <?php echo e(__('Cancel')); ?>

                </a>
            </div>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <?php echo $__env->make('backend.pages.roles.partials.scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/roles/create.blade.php ENDPATH**/ ?>