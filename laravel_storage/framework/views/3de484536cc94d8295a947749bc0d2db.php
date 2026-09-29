<?php
    $breadcrumbs = $breadcrumbs ?? [];
?>

<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'disabled' => $breadcrumbs['disabled'] ?? false,
    'title' => $breadcrumbs['title'] ?? '',
    'items' => $breadcrumbs['items'] ?? [],
    'show_home' => $breadcrumbs['show_home'] ?? true,
    'show_current' => $breadcrumbs['show_current'] ?? true,
    'title_after' => $breadcrumbs['title_after'] ?? '',
    'show_messages_after' => $breadcrumbs['show_messages_after'] ?? true,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'disabled' => $breadcrumbs['disabled'] ?? false,
    'title' => $breadcrumbs['title'] ?? '',
    'items' => $breadcrumbs['items'] ?? [],
    'show_home' => $breadcrumbs['show_home'] ?? true,
    'show_current' => $breadcrumbs['show_current'] ?? true,
    'title_after' => $breadcrumbs['title_after'] ?? '',
    'show_messages_after' => $breadcrumbs['show_messages_after'] ?? true,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php if(!$disabled): ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <?php if(!empty($title)): ?>
    <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
        <?php echo e(__($title)); ?>


        <?php echo $title_after; ?>

    </h2>
    <?php endif; ?>

    <?php if(count($items) || ($show_home || $show_current)): ?>
    <nav>
        <ol class="flex items-center gap-1.5">
            <?php if($show_home): ?>
                <li>
                    <a
                        class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                        href="<?php echo e(route('admin.dashboard')); ?>"
                    >
                        <?php echo e(__("Home")); ?>

                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            <?php endif; ?>

            <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li>
                    <a
                        class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                        href="<?php echo e($item['url']); ?>"
                    >
                        <?php echo e(__($item['label'])); ?>

                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if($show_current): ?>
                <li class="text-sm text-gray-800 dark:text-white/90">
                    <?php echo e(__($title)); ?>

                </li>
            <?php endif; ?>
        </ol>
    </nav>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if($show_messages_after): ?>
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
<?php endif; ?><?php /**PATH /var/www/html/resources/views/components/breadcrumbs.blade.php ENDPATH**/ ?>