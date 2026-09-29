<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'href' => '#',
    'icon' => null,
    'label' => '',
    'onClick' => null,
    'class' => '',
    'type' => 'link', // link, button, or modal-trigger
    'modalTarget' => '',
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
    'href' => '#',
    'icon' => null,
    'label' => '',
    'onClick' => null,
    'class' => '',
    'type' => 'link', // link, button, or modal-trigger
    'modalTarget' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php if($type === 'link'): ?>
    <a 
        href="<?php echo e($href); ?>" 
        <?php echo e($attributes->merge(['class' => 'flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 ' . $class])); ?>

        role="menuitem"
    >
        <?php if($icon): ?>
            <i class="bi bi-<?php echo e($icon); ?> text-base"></i>
        <?php endif; ?>
        <?php echo e($label); ?>

    </a>
<?php elseif($type === 'button'): ?>
    <button 
        <?php echo e($attributes->merge(['class' => 'flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 ' . $class])); ?>

        <?php if($onClick): ?> 
            onclick="<?php echo e($onClick); ?>"
        <?php endif; ?>
        role="menuitem"
    >
        <?php if($icon): ?>
            <i class="bi bi-<?php echo e($icon); ?> text-base"></i>
        <?php endif; ?>
        <?php echo e($label); ?>

    </button>
<?php elseif($type === 'modal-trigger'): ?>
    <button 
        <?php echo e($attributes->merge(['class' => 'flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 ' . $class])); ?>

        <?php if($modalTarget): ?>
            x-on:click="<?php echo e($modalTarget); ?> = true"
        <?php endif; ?>
        role="menuitem"
    >
        <?php if($icon): ?>
            <i class="bi bi-<?php echo e($icon); ?> text-base"></i>
        <?php endif; ?>
        <?php echo e($label); ?>

    </button>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/components/buttons/action-item.blade.php ENDPATH**/ ?>