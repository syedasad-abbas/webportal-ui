<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'id' => null,
    'title' => '',
    'description' => '',
    'position' => 'top', // top, bottom, left, right
    'width' => ''
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
    'id' => null,
    'title' => '',
    'description' => '',
    'position' => 'top', // top, bottom, left, right
    'width' => ''
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php
$positions = [
    'top' => 'bottom-full mb-2 left-1/2 -translate-x-1/2',
    'bottom' => 'top-full mt-2 left-1/2 -translate-x-1/2',
    'left' => 'right-full mr-2 top-1/2 -translate-y-1/2',
    'right' => 'left-full ml-2 top-1/2 -translate-y-1/2',
];
$positionClass = $positions[$position] ?? $positions['top'];
?>

<div class="relative <?php echo e(!$width ? 'w-fit' : ''); ?>" style="<?php echo e($width ? "width: {$width};" : ''); ?>">
    <div data-tooltip-target="<?php echo e($id); ?>">
        <?php echo e($slot); ?>

    </div>

    <div 
        id="<?php echo e($id); ?>"
        class="pointer-events-none <?php echo e($positionClass); ?> absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700"
        role="tooltip"
    >
        <?php if($title): ?>
            <span class="text-sm font-medium text-white"><?php echo e($title); ?></span>
        <?php endif; ?>

        <?php if($description): ?>
            <p class="text-balance text-white/90"><?php echo e($description); ?></p>
        <?php endif; ?>

        <div class="tooltip-arrow" data-popper-arrow></div>
    </div>
</div><?php /**PATH /var/www/html/resources/views/components/tooltip.blade.php ENDPATH**/ ?>