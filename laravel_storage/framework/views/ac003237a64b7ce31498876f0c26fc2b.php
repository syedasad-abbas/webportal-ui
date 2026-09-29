<?php $__env->startSection('title'); ?>
    <?php echo e($breadcrumbs['title']); ?> | <?php echo e(config('app.name')); ?>

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

    <?php echo ld_apply_filters('terms_edit_breadcrumbs', '', $taxonomyModel); ?>


    <div class="max-w-4xl mx-auto">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    <?php echo e(__('Edit')); ?> <?php echo e($taxonomyModel->label_singular); ?>

                </h3>
            </div>
            <div class="p-5 space-y-5 sm:p-6">
                <form action="<?php echo e(route('admin.terms.update', [$taxonomy, $term->id])); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo method_field('PUT'); ?>
                    <?php echo $__env->make('backend.pages.terms.partials.form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<?php if (isset($component)) { $__componentOriginal030291ebfbe01b7b21fc10c5b0c01e4c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal030291ebfbe01b7b21fc10c5b0c01e4c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.quill-editor','data' => ['editorId' => 'description']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('quill-editor'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['editor-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('description')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal030291ebfbe01b7b21fc10c5b0c01e4c)): ?>
<?php $attributes = $__attributesOriginal030291ebfbe01b7b21fc10c5b0c01e4c; ?>
<?php unset($__attributesOriginal030291ebfbe01b7b21fc10c5b0c01e4c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal030291ebfbe01b7b21fc10c5b0c01e4c)): ?>
<?php $component = $__componentOriginal030291ebfbe01b7b21fc10c5b0c01e4c; ?>
<?php unset($__componentOriginal030291ebfbe01b7b21fc10c5b0c01e4c); ?>
<?php endif; ?>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/terms/edit.blade.php ENDPATH**/ ?>