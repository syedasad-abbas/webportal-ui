<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['btn' => null, 'isOpen' => false, 'title' => null, 'btnClass' => 'btn-primary', 'btnIcon' => 'bi bi-plus-circle', 'width' => 'sm:w-120', 'drawerId' => null]));

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

foreach (array_filter((['btn' => null, 'isOpen' => false, 'title' => null, 'btnClass' => 'btn-primary', 'btnIcon' => 'bi bi-plus-circle', 'width' => 'sm:w-120', 'drawerId' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php
    // Generate a consistent ID for this drawer
    $actualDrawerId = $drawerId ?? ('drawer-' . uniqid());
?>

<div 
    x-data="{ 
        open: false,
        close() { this.open = false; },
        init() {
            // Register this drawer instance globally
            if (!window.LaraDrawers) window.LaraDrawers = {};
            const drawerId = '<?php echo e($actualDrawerId); ?>';
            this.$el.setAttribute('data-drawer-id', drawerId);
            window.LaraDrawers[drawerId] = this;
            
            // Listen for direct open events with this drawer's ID
            window.addEventListener('open-drawer-' + drawerId, () => {
                console.log('Drawer event received:', drawerId);
                this.open = true;
            });
        }
    }" 
    class="relative" 
    @keydown.escape.window="open = false"
    @close-drawer.window="close()"
    @open-drawer.window="open = true"
    :id="$id('drawer')"
    data-drawer-id="<?php echo e($actualDrawerId); ?>"
>
    <!-- Trigger Button -->
    <?php if($btn): ?>
    <button type="button" @click="open = true" class="<?php echo e($btnClass); ?>">
        <?php if($btnIcon): ?>
        <i class="<?php echo e($btnIcon); ?> mr-2"></i>
        <?php endif; ?>
        <?php echo e($btn); ?>

    </button>
    <?php endif; ?>

    <!-- Overlay Background -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="close()"
         class="fixed inset-0 bg-gray-900/30 backdrop-blur-sm z-40">
    </div>

    <!-- Drawer Sidebar -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         @click.stop
         class="fixed top-0 right-0 bottom-0 <?php echo e($width); ?> max-w-md z-50 flex flex-col crm:bg-white dark:bg-gray-800 bg-white shadow-xl border-l border-gray-200 dark:border-gray-700">
        
        <!-- Header with built-in close button -->
        <div class="px-5 py-4 sm:px-6 sm:py-5 flex justify-between items-center border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-medium text-gray-900 dark:text-white">
                <?php echo e($title ?? $btn); ?>

            </h3>
            <button type="button" @click="close()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Drawer Content -->
        <div class="p-5 space-y-3 flex-1 overflow-y-auto" x-data="{}" x-bind="$data">
            <?php echo e($slot); ?>

        </div>

        <!-- Footer Slot if provided -->
        <?php if(isset($footer)): ?>
        <div class="px-5 py-4 sm:px-6 sm:py-5 border-t border-gray-200 dark:border-gray-700">
            <?php echo e($footer); ?>

        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    (function() {
        if (typeof window.openDrawer !== 'function') {
            window.openDrawer = function(drawerId) {
                console.log('Opening drawer (local):', drawerId);
                
                if (window.LaraDrawers && window.LaraDrawers[drawerId]) {
                    console.log('Drawer found in registry');
                    window.LaraDrawers[drawerId].open = true;
                } else {
                    console.log('Drawer not found in registry, dispatching event');
                    window.dispatchEvent(new CustomEvent('open-drawer-' + drawerId));
                }
            };
        }
    })();
</script>
<?php /**PATH /var/www/html/resources/views/components/drawer.blade.php ENDPATH**/ ?>