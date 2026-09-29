<?php $__env->startSection('title'); ?>
    <?php echo e(__('Reset Password')); ?> | <?php echo e(config('app.name')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('admin-content'); ?>
<div>
    <div class="mb-5 sm:mb-8">
        <h1 class="mb-2 font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">
            <?php echo e(__('Reset Password')); ?>

        </h1>
    </div>
    <div>
        <form action="<?php echo e(route('admin.password.reset.submit')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="token" value="<?php echo e($token); ?>">
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
                        <?php echo e(__('E-Mail Address')); ?><span class="text-error-500">*</span>
                    </label>
                    <input autofocus type="text" id="email" name="email" autocomplete="username"
                        value="<?php echo e($email ?? old('email')); ?>" placeholder="Enter your email address"
                        class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                </div>
                <!-- Password -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        <?php echo e(__('Password')); ?><span class="text-error-500">*</span>
                    </label>
                    <input type="password" name="password" autocomplete="new-password" required
                        placeholder="<?php echo e(__('Enter your password')); ?>"
                        class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800 <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">

                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <span class="text-red-500 text-xs mt-1"><?php echo e($message); ?></span>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        <?php echo e(__('Confirm Password')); ?><span class="text-error-500">*</span>
                    </label>
                    <input type="password" name="password_confirmation" autocomplete="new-password" required
                        placeholder="<?php echo e(__('Confirm your password')); ?>"
                        class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                </div>
                <!-- Button -->
                <div>
                    <button type="submit"
                        class="flex items-center justify-center w-full px-4 py-3 text-sm font-medium text-white transition rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600">
                        Reset Password
                        <i class="bi bi-box-arrow-in-right ml-2"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.auth.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/auth/passwords/reset.blade.php ENDPATH**/ ?>