<div class="flex items-start mb-2 <?php echo e($level > 0 ? 'ml-' . ($level * 4) : ''); ?>">
    <input type="checkbox" name="taxonomy_<?php echo e($taxonomy->name); ?>[]" id="term_<?php echo e($term->id); ?>" value="<?php echo e($term->id); ?>" 
        class="mt-1 h-4 w-4 text-brand-500 border-gray-300 rounded focus:ring-brand-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-brand-500"
        <?php echo e(in_array($term->id, old('taxonomy_' . $taxonomy->name, $selectedTerms)) ? 'checked' : ''); ?>>
    <label for="term_<?php echo e($term->id); ?>" class="ml-2 block text-sm text-gray-700 dark:text-gray-400">
        <?php echo e($term->name); ?>

    </label>
</div>

<?php
    $childTerms = App\Models\Term::where('taxonomy', $taxonomy->name)
        ->where('parent_id', $term->id)
        ->orderBy('name', 'asc')
        ->get();
?>

<?php if($childTerms->count() > 0): ?>
    <div class="ml-6 border-l border-gray-200 dark:border-gray-700 pl-2 space-y-1" data-term-children="<?php echo e($term->id); ?>">
        <?php $__currentLoopData = $childTerms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $childTerm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php echo $__env->make('backend.pages.posts.partials.hierarchical-terms', [
                'term' => $childTerm,
                'taxonomy' => $taxonomy,
                'level' => $level + 1,
                'selectedTerms' => $selectedTerms
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/backend/pages/posts/partials/hierarchical-terms.blade.php ENDPATH**/ ?>