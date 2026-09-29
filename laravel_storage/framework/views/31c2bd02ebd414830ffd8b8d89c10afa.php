<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'id' => 'datetime-picker',
    'name' => 'datetime',
    'label' => 'Date and Time',
    'value' => '',
    'required' => false,
    'placeholder' => 'Select date and time',
    'minDate' => null,
    'maxDate' => null,
    'enableTime' => true,
    'dateFormat' => 'Y-m-d H:i',
    'altFormat' => 'F j, Y at h:i K',
    'showAltFormat' => true,
    'class' => '',
    'helpText' => '',
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
    'id' => 'datetime-picker',
    'name' => 'datetime',
    'label' => 'Date and Time',
    'value' => '',
    'required' => false,
    'placeholder' => 'Select date and time',
    'minDate' => null,
    'maxDate' => null,
    'enableTime' => true,
    'dateFormat' => 'Y-m-d H:i',
    'altFormat' => 'F j, Y at h:i K',
    'showAltFormat' => true,
    'class' => '',
    'helpText' => '',
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
        init() {
            const options = {
                enableTime: <?php echo e($enableTime ? 'true' : 'false'); ?>,
                dateFormat: '<?php echo e($dateFormat); ?>',
                altInput: <?php echo e($showAltFormat ? 'true' : 'false'); ?>,
                altFormat: '<?php echo e($altFormat); ?>',
                time_24hr: false,
                defaultDate: '<?php echo e($value); ?>',
                minDate: <?php echo e($minDate ? '\''.$minDate.'\'' : 'null'); ?>,
                maxDate: <?php echo e($maxDate ? '\''.$maxDate.'\'' : 'null'); ?>,
                disableMobile: true,
                static: true,
                position: 'auto',
                locale: {
                    firstDayOfWeek: 1
                },
                onChange: function(selectedDates, dateStr, instance) {
                    // Dispatch an input event to ensure Alpine.js and other listeners are notified
                    instance.element.dispatchEvent(new Event('input', { bubbles: true }));
                },
                // Fix for the form validation issue with unnamed inputs
                onReady: function(selectedDates, dateStr, instance) {
                    // Add names to hour and minute inputs to prevent validation errors
                    const hourInput = instance.hourElement;
                    const minuteInput = instance.minuteElement;
                    
                    if (hourInput) {
                        hourInput.name = '<?php echo e($name); ?>_hour';
                        hourInput.setAttribute('form', 'none'); // Prevent it from being included in form submission
                    }
                    
                    if (minuteInput) {
                        minuteInput.name = '<?php echo e($name); ?>_minute';
                        minuteInput.setAttribute('form', 'none'); // Prevent it from being included in form submission
                    }
                }
            };
            
            flatpickr(this.$refs.datetimePicker, options);
        }
    }"
>
    <?php if($label): ?>
        <label for="<?php echo e($id); ?>" class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1">
            <?php echo e($label); ?>

            <?php if($required): ?>
                <span class="text-red-500">*</span>
            <?php endif; ?>
        </label>
    <?php endif; ?>

    <div class="relative">
        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
            <i class="bi bi-calendar text-gray-400 dark:text-gray-500 z-1"></i>
        </div>
        <input 
            x-ref="datetimePicker"
            type="text"
            id="<?php echo e($id); ?>"
            name="<?php echo e($name); ?>"
            value="<?php echo e($value); ?>"
            placeholder="<?php echo e($placeholder); ?>"
            <?php echo e($required ? 'required' : ''); ?>

            <?php echo e($attributes->merge(['class' => 'form-control !ps-10' . $class])); ?>

        />
    </div>
    
    <?php if($helpText): ?>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?php echo e($helpText); ?></p>
    <?php endif; ?>
</div>
<?php /**PATH /var/www/html/resources/views/components/inputs/datetime-picker.blade.php ENDPATH**/ ?>