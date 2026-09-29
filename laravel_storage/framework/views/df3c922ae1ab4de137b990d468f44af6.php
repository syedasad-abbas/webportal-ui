<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['taxonomy', 'taxonomyName', 'post_id' => null, 'post_type' => null]));

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

foreach (array_filter((['taxonomy', 'taxonomyName', 'post_id' => null, 'post_type' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<div x-data="termDrawer('<?php echo e($taxonomyName); ?>')" x-trap="isOpen" class="relative" 
     data-post-id="<?php echo e($post_id); ?>" data-post-type="<?php echo e($post_type); ?>">

    <button
        type="button"
        @click="openDrawer"
        class="btn-default !p-0 !w-8 !h-8 !bg-transparent"
    >
        <i class="bi bi-plus-circle"></i>
    </button>

    <!-- Overlay Background -->
    <div 
        x-show="isOpen" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeDrawer"
        @keydown.escape.window="closeDrawer"
        class="fixed inset-0 bg-gray-900/30 backdrop-blur-sm z-40">
    </div>

    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        @click.stop
        @keydown.escape.window="closeDrawer"
        class="fixed top-0 right-0 bottom-0 sm:w-120 max-w-3xl z-50 flex flex-col bg-white dark:bg-gray-800 shadow-xl border-l border-gray-200 dark:border-gray-700"
    >
        <div
            class="px-5 py-4 sm:px-6 sm:py-5 flex justify-between items-center border-b border-gray-200 dark:border-gray-700"
        >
            <h3 class="text-base font-medium text-gray-900 dark:text-white">
                <?php echo e(__("Add New :taxonomy", ['taxonomy' => $taxonomy->label_singular])); ?>

            </h3>
            <button
                type="button"
                @click="isOpen = false"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <svg
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    ></path>
                </svg>
            </button>
        </div>

        <!-- Drawer Content -->
        <div class="p-5 space-y-4 flex-1 overflow-y-auto">
            <div>
                <label
                    for="term_name"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-400"
                    ><?php echo e(__("Name")); ?> <span class="text-red-500">*</span></label
                >
                <input
                    type="text"
                    id="term_name"
                    x-model="formData.name"
                    class="form-control"
                    placeholder="<?php echo e(__('Enter name')); ?>"
                />
                <p
                    x-show="errors.name"
                    x-text="errors.name"
                    class="mt-1 text-sm text-red-600"
                ></p>
            </div>

            <div>
                <label
                    for="term_description"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-400"
                    ><?php echo e(__("Description")); ?></label
                >
                <textarea
                    id="term_description"
                    x-model="formData.description"
                    rows="3"
                    class="form-control !h-20"
                    placeholder="<?php echo e(__('Enter description (optional)')); ?>"
                ></textarea>
                <p
                    x-show="errors.description"
                    x-text="errors.description"
                    class="mt-1 text-sm text-red-600"
                ></p>
            </div>

            <?php if($taxonomy->hierarchical): ?>
            <?php $parentTerms = App\Models\Term::where('taxonomy', $taxonomyName)->orderBy('name', 'asc')->get(); ?>
            <div class="mt-2">
                <?php if (isset($component)) { $__componentOriginal4e8e44b8bf0a76d0ba41e66c6fad6711 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4e8e44b8bf0a76d0ba41e66c6fad6711 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.posts.term-selector','data' => ['name' => 'parent_term','taxonomyModel' => $taxonomy,'term' => $term ?? null,'parentTerms' => $parentTerms,'placeholder' => __('Select Parent' . ' ' . $taxonomy->label_singular),'label' => __('Parent ' . $taxonomy->label_singular)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('posts.term-selector'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'parent_term','taxonomyModel' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($taxonomy),'term' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($term ?? null),'parentTerms' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($parentTerms),'placeholder' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Select Parent' . ' ' . $taxonomy->label_singular)),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Parent ' . $taxonomy->label_singular))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4e8e44b8bf0a76d0ba41e66c6fad6711)): ?>
<?php $attributes = $__attributesOriginal4e8e44b8bf0a76d0ba41e66c6fad6711; ?>
<?php unset($__attributesOriginal4e8e44b8bf0a76d0ba41e66c6fad6711); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4e8e44b8bf0a76d0ba41e66c6fad6711)): ?>
<?php $component = $__componentOriginal4e8e44b8bf0a76d0ba41e66c6fad6711; ?>
<?php unset($__componentOriginal4e8e44b8bf0a76d0ba41e66c6fad6711); ?>
<?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Hidden inputs -->
        <input type="hidden" name="drawer_post_id" value="<?php echo e($post_id ?? ''); ?>">
        <input type="hidden" name="drawer_post_type" value="<?php echo e($post_type ?? ''); ?>">

        <!-- Footer -->
        <div class="px-5 py-4 sm:px-6 sm:py-5 border-t border-gray-200 dark:border-gray-700">
            <div class="flex justify-end space-x-3">
                <button
                    type="button"
                    @click="isOpen = false"
                    class="btn-default"
                >
                    <?php echo e(__("Cancel")); ?>

                </button>
                <button
                    type="button"
                    @click="saveTerm"
                    class="btn-primary"
                    :disabled="isSubmitting"
                >
                    <span x-show="!isSubmitting"><?php echo e(__("Save")); ?></span>
                    <span x-show="isSubmitting" class="flex items-center">
                        <svg
                            class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <?php echo e(__("Saving...")); ?>

                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /var/www/html/resources/views/backend/pages/posts/partials/term-drawer.blade.php ENDPATH**/ ?>