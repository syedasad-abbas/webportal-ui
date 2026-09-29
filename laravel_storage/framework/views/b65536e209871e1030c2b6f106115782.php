<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name',
    'taxonomyModel',
    'term' => null,
    'parentTerms' => [],
    'placeholder' => __('Select'),
    'label' => null,
    'searchable' => false,
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
    'name',
    'taxonomyModel',
    'term' => null,
    'parentTerms' => [],
    'placeholder' => __('Select'),
    'label' => null,
    'searchable' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php
    // Create a recursive function that references itself and handles different term types
    $buildOptions = function($terms, $parentId = null, $depth = 0, $currentTermId = null) use (&$buildOptions) {
        $options = [];
        foreach ($terms as $term) {
            // Check if $term is an object or an array with parent_id property/key
            $termParentId = null;
            if (is_object($term)) {
                $termParentId = $term->parent_id;
                $termId = $term->id;
                $termName = $term->name;
            } elseif (is_array($term)) {
                $termParentId = $term['parent_id'] ?? null;
                $termId = $term['id'] ?? null;
                $termName = $term['name'] ?? '';
            } else {
                // Skip this item if it's neither an object nor an array
                continue;
            }
            
            if ($termParentId == $parentId && (!$currentTermId || $termId !== $currentTermId)) {
                $indent = str_repeat('— ', $depth);
                $options[] = [
                    'value' => $termId,
                    'label' => $indent . $termName
                ];
                
                $childOptions = $buildOptions($terms, $termId, $depth + 1, $currentTermId);
                $options = array_merge($options, $childOptions);
            }
        }
        return $options;
    };
    
    $parentOptions = [];
    
    // Handle different parentTerms formats
    if (is_array($parentTerms) || $parentTerms instanceof \Traversable) {
        $hierarchicalOptions = $buildOptions($parentTerms, null, 0, $term ? $term->id : null);
        $parentOptions = array_merge($parentOptions, $hierarchicalOptions);
    }
?>

<?php if (isset($component)) { $__componentOriginald9e3f05661d2b31b6e566c65f0284ef2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald9e3f05661d2b31b6e566c65f0284ef2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inputs.combobox','data' => ['name' => $name,'label' => $label,'placeholder' => $placeholder,'options' => $parentOptions,'searchable' => $searchable,'xModel' => 'formData.'.e($name).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inputs.combobox'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($name),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($label),'placeholder' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($placeholder),'options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($parentOptions),'searchable' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($searchable),'x-model' => 'formData.'.e($name).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald9e3f05661d2b31b6e566c65f0284ef2)): ?>
<?php $attributes = $__attributesOriginald9e3f05661d2b31b6e566c65f0284ef2; ?>
<?php unset($__attributesOriginald9e3f05661d2b31b6e566c65f0284ef2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald9e3f05661d2b31b6e566c65f0284ef2)): ?>
<?php $component = $__componentOriginald9e3f05661d2b31b6e566c65f0284ef2; ?>
<?php unset($__componentOriginald9e3f05661d2b31b6e566c65f0284ef2); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/components/posts/term-selector.blade.php ENDPATH**/ ?>