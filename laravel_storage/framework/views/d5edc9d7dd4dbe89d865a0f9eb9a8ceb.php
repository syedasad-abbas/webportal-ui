<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'id' => 'popover-' . uniqid(),
    'position' => 'bottom',
    'width' => 'max-w-xs',
    'trigger' => 'click', // 'hover' or 'click'
    'triggerClass' => 'text-gray-500 dark:text-gray-400 hover:text-primary dark:hover:text-primary transition-colors',
    'contentClass' => '',
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
    'id' => 'popover-' . uniqid(),
    'position' => 'bottom',
    'width' => 'max-w-xs',
    'trigger' => 'click', // 'hover' or 'click'
    'triggerClass' => 'text-gray-500 dark:text-gray-400 hover:text-primary dark:hover:text-primary transition-colors',
    'contentClass' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<div x-data="{ open: false }" class="relative inline-block">
    <button 
        @click="open = !open"
        type="button"
        class="cursor-pointer <?php echo e($triggerClass); ?>"
    >
        <?php echo e($trigger); ?>

    </button>

    <div
        x-show="open"
        x-trap="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.away="open = false"
        @keydown.escape.window="open = false"
        class="absolute z-50 <?php echo e($width); ?> bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-0 text-sm text-gray-700 dark:text-gray-300
            <?php echo e($position === 'top' ? 'bottom-full mb-2' : ''); ?>

            <?php echo e($position === 'bottom' ? 'top-full mt-2' : ''); ?>

            <?php echo e($position === 'left' ? 'right-full mr-2' : ''); ?>

            <?php echo e($position === 'right' ? 'left-full ml-2' : ''); ?>

            <?php echo e($contentClass); ?>"
        style="display: none;"
        tabindex="0"
    >
        <?php echo e($slot); ?>

    </div>
</div>
<?php /**PATH /var/www/html/resources/views/components/popover.blade.php ENDPATH**/ ?>