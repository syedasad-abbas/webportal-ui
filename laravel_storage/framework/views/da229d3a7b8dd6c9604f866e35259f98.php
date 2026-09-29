<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'label' => 'Actions',
    'showLabel' => true,
    'align' => 'left', // left, right
    'buttonClass' => '',
    'position' => 'bottom', // bottom, top
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
    'label' => 'Actions',
    'showLabel' => true,
    'align' => 'left', // left, right
    'buttonClass' => '',
    'position' => 'bottom', // bottom, top
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<div
    x-data="{
        isOpen: false,
        openedWithKeyboard: false,
        updatePosition() {
            if (!this.isOpen) return;
            
            const button = this.$refs.button;
            const dropdown = this.$refs.dropdown;
            
            if (!button || !dropdown) return;
            
            const rect = button.getBoundingClientRect();
            
            // Position dropdown relative to viewport
            dropdown.style.position = 'fixed';
            dropdown.style.zIndex = '9999';
            
            if ('<?php echo e($position); ?>' === 'top') {
                dropdown.style.top = (rect.top - dropdown.offsetHeight - 5) + 'px';
            } else {
                dropdown.style.top = (rect.bottom + 5) + 'px';
            }
            
            if ('<?php echo e($align); ?>' === 'right') {
                dropdown.style.left = (rect.right - dropdown.offsetWidth) + 'px';
            } else {
                dropdown.style.left = rect.left + 'px';
            }
        }
    }"
    x-on:keydown.esc.window="isOpen = false; openedWithKeyboard = false"
    x-on:scroll.window="updatePosition()"
    x-on:resize.window="updatePosition()"
    role="menu"
    aria-label="<?php echo e($label); ?>"
>
    <button
        x-ref="button"
        type="button"
        x-on:click="isOpen = !isOpen; $nextTick(() => updatePosition())"
        class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg border border-gray-200 bg-white py-1 px-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 <?php echo e($buttonClass); ?>"
        aria-haspopup="true"
        x-on:keydown.space.prevent="openedWithKeyboard = true; $nextTick(() => updatePosition())"
        x-on:keydown.enter.prevent="openedWithKeyboard = true; $nextTick(() => updatePosition())"
        x-on:keydown.down.prevent="openedWithKeyboard = true; $nextTick(() => updatePosition())"
        x-bind:aria-expanded="isOpen || openedWithKeyboard"
    >
        <i class="bi bi-three-dots-vertical text-lg"></i>
        <?php if($showLabel): ?>
            <span class="hidden sm:inline"><?php echo e($label); ?></span>
        <?php endif; ?>
    </button>

    <template x-teleport="body">
        <div
            x-cloak
            x-ref="dropdown"
            x-show="isOpen || openedWithKeyboard"
            x-transition
            x-trap="openedWithKeyboard"
            x-on:click.outside="isOpen = false; openedWithKeyboard = false"
            x-on:keydown.down.prevent="$focus.wrap().next()"
            x-on:keydown.up.prevent="$focus.wrap().previous()"
            class="w-fit min-w-48 flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
            style="position: fixed; z-index: 9999;"
            role="menu"
        >
            <?php echo e($slot); ?>

        </div>
    </template>
</div>
<?php /**PATH /var/www/html/resources/views/components/buttons/action-buttons.blade.php ENDPATH**/ ?>