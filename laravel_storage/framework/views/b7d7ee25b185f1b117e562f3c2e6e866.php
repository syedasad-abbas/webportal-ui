<?php $__env->startSection('title'); ?>
   <?php echo e($breadcrumbs['title']); ?> | <?php echo e(config('app.name')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('admin-content'); ?>
<div class="p-4 mx-auto max-w-7xl md:p-6">
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

    <?php echo ld_apply_filters('profile_edit_breadcrumbs', ''); ?>


    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="px-5 py-2.5 sm:px-6 sm:py-5">
                <h3 class="text-base font-medium text-gray-800 dark:text-white"><?php echo e(__('Edit Profile')); ?> - <?php echo e($user->name); ?></h3>
            </div>
            <div class="p-5 space-y-6 border-t border-gray-100 dark:border-gray-800 sm:p-6">
                <?php if (isset($component)) { $__componentOriginala357015bb12b9e55fb70db16bfbde37b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala357015bb12b9e55fb70db16bfbde37b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.messages','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('messages'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala357015bb12b9e55fb70db16bfbde37b)): ?>
<?php $attributes = $__attributesOriginala357015bb12b9e55fb70db16bfbde37b; ?>
<?php unset($__attributesOriginala357015bb12b9e55fb70db16bfbde37b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala357015bb12b9e55fb70db16bfbde37b)): ?>
<?php $component = $__componentOriginala357015bb12b9e55fb70db16bfbde37b; ?>
<?php unset($__componentOriginala357015bb12b9e55fb70db16bfbde37b); ?>
<?php endif; ?>
                <form action="<?php echo e(route('profile.update')); ?>" method="POST" class="space-y-6">
                    <?php echo method_field('PUT'); ?>
                    <?php echo csrf_field(); ?>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-400"><?php echo e(__('Name')); ?></label>
                            <input type="text" name="name" id="name" required value="<?php echo e($user->name); ?>" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-400"><?php echo e(__('Email')); ?></label>
                            <input type="email" name="email" id="email" required value="<?php echo e($user->email); ?>" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-400"><?php echo e(__('Password (Optional)')); ?></label>
                            <input type="password" name="password" id="password" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-400"><?php echo e(__('Confirm Password (Optional)')); ?></label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        </div>
                        <?php echo ld_apply_filters('profile_edit_fields', '', $user); ?>

                    </div>
                    <?php echo ld_apply_filters('profile_edit_after_fields', '', $user); ?>

                    <div class="mt-6 flex justify-start gap-4">
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('profile.update')): ?>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-brand-500 rounded-lg hover:bg-brand-600"><?php echo e(__('Save')); ?></button>
                        <?php endif; ?>
                        <a href="<?php echo e(route('admin.dashboard')); ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 dark:bg-gray-700 dark:text-white"><?php echo e(__('Cancel')); ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/profile/edit.blade.php ENDPATH**/ ?>