<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title',
    'id' => null,
    'defaultOpen' => false,
    'headerClass' => '',
    'contentClass' => ''
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
    'title',
    'id' => null,
    'defaultOpen' => false,
    'headerClass' => '',
    'contentClass' => ''
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]" 
     x-data="{ open: <?php echo e($defaultOpen ?? false ? 'true' : 'false'); ?> }">
    <button type="button" 
            @click="open = !open"
            class="flex w-full items-center justify-between p-5 text-left <?php echo e($headerClass ?? ''); ?>">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white"><?php echo e($title ?? __('Advanced Fields')); ?></h3>
        <svg class="h-5 w-5 transform transition-transform duration-200 dark:text-gray-400" 
             :class="{ 'rotate-180': open }"
             fill="none" 
             stroke="currentColor" 
             viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>
    
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform scale-y-95"
         x-transition:enter-end="opacity-100 transform scale-y-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform scale-y-100"
         x-transition:leave-end="opacity-0 transform scale-y-95"
         class="border-t border-gray-100 dark:border-gray-800">
        <div class="p-5 <?php echo e($contentClass ?? ''); ?>">
            <?php echo $__env->yieldContent('collapsible-content'); ?>
        </div>
    </div>
</div>
<?php /**PATH /var/www/html/resources/views/components/collapsible-section.blade.php ENDPATH**/ ?>