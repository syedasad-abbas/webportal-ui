<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'editorId' => 'editor',
    'height' => '200px',
    'maxHeight' => '500px',
    'type' => 'full', // Options: 'full', 'basic', 'minimal'
    'customToolbar' => null, // For custom toolbar configuration
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
    'editorId' => 'editor',
    'height' => '200px',
    'maxHeight' => '500px',
    'type' => 'full', // Options: 'full', 'basic', 'minimal'
    'customToolbar' => null, // For custom toolbar configuration
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php if (! $__env->hasRenderedOnce('bf10a0a4-404e-4ed6-b205-79963f7d1590')): $__env->markAsRenderedOnce('bf10a0a4-404e-4ed6-b205-79963f7d1590'); ?>
<link rel="stylesheet" href="<?php echo e(asset('vendor/quill/quill.min.css')); ?>" />
<style>
    .ql-editor {
        min-height: 200px;
        max-height: 500px;
        overflow-y: auto;
    }
    .ql-toolbar.ql-snow {
        border-radius: 10px 10px 0px 0px;
    }
    .ql-container {
        min-height: 200px;
    }
    /* Create a container for Quill to target */
    .quill-container {
        border: 1px solid #ccc;
        border-radius: 0 0 10px 10px;
        background: transparent;
    }
    .dark .quill-container {
        border-color: #4b5563;
        color: #e5e7eb;
    }
    .dark .ql-snow {
        border-color: #4b5563;
    }
    .dark .ql-toolbar.ql-snow .ql-picker-label,
    .dark .ql-toolbar.ql-snow .ql-picker-options,
    .dark .ql-toolbar.ql-snow button,
    .dark .ql-toolbar.ql-snow span {
        color: #e5e7eb;
    }
    .dark .ql-snow .ql-stroke {
        stroke: #e5e7eb;
    }
    .dark .ql-snow .ql-fill {
        fill: #e5e7eb;
    }
    .dark .ql-editor.ql-blank::before {
        color: rgba(255, 255, 255, 0.6);
    }
</style>

<script src="<?php echo e(asset('vendor/quill/quill.min.js')); ?>"></script>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editorId = '<?php echo e($editorId); ?>';
        const editorType = '<?php echo e($type); ?>';
        const textareaElement = document.getElementById(editorId);
        const customToolbar = <?php echo json_encode($customToolbar, 15, 512) ?>;

        if (!textareaElement) {
            console.error(`Textarea with ID "${editorId}" not found`);
            return;
        }

        // Create a div after the textarea to host Quill
        const quillContainer = document.createElement('div');
        quillContainer.id = `quill-${editorId}`;
        quillContainer.className = 'quill-container';
        textareaElement.insertAdjacentElement('afterend', quillContainer);

        // Store original textarea content
        const initialContent = textareaElement.value || '';

        // Define toolbar configurations based on type
        const toolbarConfigs = {
            full: [
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                ['blockquote'],
                [{ 'align': [] }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'indent': '-1' }, { 'indent': '+1' }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'font': [] }],
                ['link', 'image', 'video', 'code-block']
            ],
            basic: [
                ['bold', 'italic', 'underline'],
                [{ 'header': [1, 2, 3, false] }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                ['link']
            ],
            minimal: [
                ['bold', 'italic'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }]
            ]
        };

        // Select toolbar configuration based on type or use custom if provided
        const toolbarConfig = customToolbar ? JSON.parse(customToolbar) :
                             (toolbarConfigs[editorType] || toolbarConfigs.basic);

        // Initialize Quill on the container div
        const quill = new Quill(`#quill-${editorId}`, {
            theme: "snow",
            placeholder: '<?php echo e(__('Type here...')); ?>',
            modules: {
                toolbar: toolbarConfig
            }
        });

        // Set initial content from textarea
        if (initialContent) {
            quill.clipboard.dangerouslyPasteHTML(initialContent);
        }

        // Hide textarea visually but keep it in the DOM for form submission
        textareaElement.style.display = 'none';

        // Update textarea on editor change for form submission
        quill.on('text-change', function() {
            textareaElement.value = quill.root.innerHTML;
            
            // Trigger form change detection for the unsaved changes warning
            const event = new Event('input', { bubbles: true });
            textareaElement.dispatchEvent(event);
        });

        // Also update on form submit to ensure the latest content is captured
        const form = textareaElement.closest('form');
        if (form) {
            form.addEventListener('submit', function() {
                textareaElement.value = quill.root.innerHTML;
            });
        }
    });
</script>
<?php /**PATH /var/www/html/resources/views/components/quill-editor.blade.php ENDPATH**/ ?>