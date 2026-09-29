<?php $__env->startSection('title'); ?>
    403 - <?php echo e(__('Access Denied')); ?> - <?php echo e(config('app.name')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('admin-content'); ?>
<div class="relative z-1 flex min-h-screen flex-col items-center justify-center overflow-hidden p-6">
    <div class="mx-auto w-full max-w-[242px] text-center sm:max-w-[472px]">
        <h1 class="mb-8 text-title-md font-bold text-gray-800 dark:text-white/90 xl:text-title-2xl">
            <?php echo e(__('ERROR')); ?>

        </h1>
        <h1 class="mb-8 text-title-md font-bold text-gray-800 dark:text-white/90 xl:text-title-2xl">
            403
        </h1>

        <p class="mt-2 bg-red-100 p-4">
            <i class="bi bi-exclamation-circle-fill"></i>&nbsp;
            <?php echo e($exception->getMessage()); ?>

        </p>

        <p class="mb-6 mt-10 text-base text-gray-700 dark:text-gray-400 sm:text-lg">
            <?php echo e(__('Access to this resource on the server is denied')); ?>

        </p>

        <?php echo $__env->make('errors.partials.links', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.auth.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/errors/403.blade.php ENDPATH**/ ?>