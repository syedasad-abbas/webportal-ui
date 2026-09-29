<?php
$selectedTerms = [];

if (!empty($post)) {
    foreach ($post->terms as $term) {
        if (!isset($selectedTerms[$term->taxonomy])) {
            $selectedTerms[$term->taxonomy] = [];
        }
        $selectedTerms[$term->taxonomy][] = $term->id;
    }
}
?>
<div id="taxonomy-<?php echo e($taxonomy->name); ?>">
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="px-4 py-3 sm:px-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
            <h3 class="text-base font-medium text-gray-800 dark:text-white"><?php echo e(__($taxonomy->label)); ?></h3>
            <?php if (isset($component)) { $__componentOriginale33140189080288b80be7c18d6be7846 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale33140189080288b80be7c18d6be7846 = $attributes; } ?>
<?php $component = App\View\Components\TermDrawer::resolve(['taxonomy' => $taxonomy,'taxonomyName' => $taxonomy->name] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('term-drawer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\TermDrawer::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['post_id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($post->id ?? null),'post_type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($post_type)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale33140189080288b80be7c18d6be7846)): ?>
<?php $attributes = $__attributesOriginale33140189080288b80be7c18d6be7846; ?>
<?php unset($__attributesOriginale33140189080288b80be7c18d6be7846); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale33140189080288b80be7c18d6be7846)): ?>
<?php $component = $__componentOriginale33140189080288b80be7c18d6be7846; ?>
<?php unset($__componentOriginale33140189080288b80be7c18d6be7846); ?>
<?php endif; ?>
        </div>
        <div class="p-3 space-y-2 sm:p-4" data-taxonomy="<?php echo e($taxonomy->name); ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-400"><?php echo e(__("Select " . strtolower($taxonomy->label)) . ":"); ?></label>
                <div class="mt-2 max-h-60 overflow-y-auto terms-list">
                    <?php
                        if ($taxonomy->hierarchical) {
                            // For hierarchical taxonomies, get parent terms first (terms with no parent)
                            $parentTerms = App\Models\Term::where('taxonomy', $taxonomy->name)
                                ->whereNull('parent_id')
                                ->orderBy('name', 'asc')
                                ->get();

                            // Check if we have any terms
                            $hasTerms = $parentTerms->count() > 0 ||
                                App\Models\Term::where('taxonomy', $taxonomy->name)->count() > 0;
                        } else {
                            // For flat taxonomies, get all terms
                            $terms = App\Models\Term::where('taxonomy', $taxonomy->name)
                                ->orderBy('name', 'asc')
                                ->get();
                            $hasTerms = $terms->count() > 0;
                        }
                    ?>

                    <?php if($hasTerms): ?>
                        <?php if($taxonomy->hierarchical): ?>
                            <div class="space-y-1 px-1">
                                <?php $__currentLoopData = $parentTerms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $parentTerm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php echo $__env->make('backend.pages.posts.partials.hierarchical-terms', [
                                        'term' => $parentTerm,
                                        'taxonomy' => $taxonomy,
                                        'level' => 0,
                                        'selectedTerms' => $selectedTerms[$taxonomy->name] ?? []
                                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php else: ?>
                            <?php $__currentLoopData = $terms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="flex items-start mb-2">
                                    <input type="checkbox" name="taxonomy_<?php echo e($taxonomy->name); ?>[]" id="term_<?php echo e($term->id); ?>" value="<?php echo e($term->id); ?>"
                                        class="mt-1 h-4 w-4 text-brand-500 border-gray-300 rounded focus:ring-brand-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-brand-500"
                                        <?php echo e(in_array($term->id, old('taxonomy_' . $taxonomy->name, $selectedTerms[$taxonomy->name] ?? [])) ? 'checked' : ''); ?>>
                                    <label for="term_<?php echo e($term->id); ?>" class="ml-2 block text-sm text-gray-700 dark:text-gray-400">
                                        <?php echo e(__($term->name)); ?>

                                    </label>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 dark:text-gray-400 no-terms-message"><?php echo e(__('No')); ?> <?php echo e(strtolower($taxonomy->label)); ?> <?php echo e(__('found.')); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php echo ld_apply_filters('post_form_after_taxonomy_' . $taxonomy->name, ''); ?><?php /**PATH /var/www/html/resources/views/backend/pages/posts/partials/post-taxonomy-chooser.blade.php ENDPATH**/ ?>