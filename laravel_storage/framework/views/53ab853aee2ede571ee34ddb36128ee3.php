<?php $__env->startSection('title'); ?>
    <?php echo e(__('Forgot Password')); ?> | <?php echo e(config('app.name')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('admin-content'); ?>
<div>
    <div class="mb-5 sm:mb-8">
        <h1 class="mb-2 font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">
            <?php echo e(__('Forgot Password')); ?>

        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            <?php echo e(__('Enter your email address and we will send you a link to reset your password.')); ?>

        </p>
    </div>
    <div>
        <form action="<?php echo e(route('admin.password.update')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div class="space-y-5">
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
                <!-- Email -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        <?php echo e(__('Email')); ?><span class="text-error-500">*</span>
                    </label>
                    <input autofocus type="text" id="email" name="email" autocomplete="username"
                        placeholder="<?php echo e(__('Enter your email address')); ?>"
                        class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                </div>
                <div>
                    <button type="submit"
                        class="flex items-center justify-center w-full px-4 py-3 text-sm font-medium text-white transition rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600">
                        <?php echo e(__('Send Reset Link')); ?>

                        <i class="bi bi-box-arrow-in-right ml-2"></i>
                    </button>
                </div>
            </div>
        </form>
        <div class="flex justify-center items-center mt-5 text-sm font-normal text-center text-gray-700 dark:text-gray-400 sm:text-start">
            <a href="<?php echo e(route('user.login')); ?>" class="text-brand-500 hover:text-brand-600 dark:text-brand-400">
                <i class="bi bi-chevron-left mr-2"></i>
                <?php echo e(__('Back to Login')); ?>

            </a>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.auth.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/auth/passwords/email.blade.php ENDPATH**/ ?>