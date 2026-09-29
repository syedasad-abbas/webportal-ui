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

    <?php echo ld_apply_filters('posts_edit_after_breadcrumbs', '', $postType); ?>


    <form action="<?php echo e(route('admin.posts.update', [$postType, $post->id])); ?>" method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
        <?php echo $__env->make('backend.pages.posts.partials.form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </form>

    <?php echo ld_apply_filters('after_post_form', ''); ?>

</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<?php if (isset($component)) { $__componentOriginal030291ebfbe01b7b21fc10c5b0c01e4c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal030291ebfbe01b7b21fc10c5b0c01e4c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.quill-editor','data' => ['editorId' => 'content']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('quill-editor'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['editor-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('content')]); ?>
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

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/posts/edit.blade.php ENDPATH**/ ?>