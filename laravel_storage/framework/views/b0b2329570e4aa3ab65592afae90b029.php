<?php
    $editing = isset($inboundDid);
    $selectedCarrier = old('carrier_id', $editing ? $inboundDid->carrier_id : '');
    $active = old('is_active', $editing ? (int) $inboundDid->is_active : 1);
    $inputClass = 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
?>

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <label for="carrier_id" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
            <?php echo e(__('Carrier')); ?> *
        </label>
        <select id="carrier_id" name="carrier_id" required class="<?php echo e($inputClass); ?>">
            <option value=""><?php echo e(__('Select a carrier')); ?></option>
            <?php $__currentLoopData = $carriers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $carrier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($carrier->id); ?>" <?php echo e((string) $selectedCarrier === (string) $carrier->id ? 'selected' : ''); ?>>
                    <?php echo e($carrier->name); ?>

                </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php $__errorArgs = ['carrier_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div>
        <label for="did" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
            <?php echo e(__('Inbound DID')); ?> *
        </label>
        <input id="did" name="did" type="tel" required inputmode="tel"
               value="<?php echo e(old('did', $editing ? $inboundDid->did : '')); ?>"
               placeholder="<?php echo e(__('e.g. +15551234567')); ?>" class="<?php echo e($inputClass); ?>">
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            <?php echo e(__('Enter the number delivered by your carrier. Formatting and a leading + are accepted; it is saved as digits.')); ?>

        </p>
        <?php $__errorArgs = ['did'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div>
        <label for="label" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
            <?php echo e(__('Label')); ?>

        </label>
        <input id="label" name="label" type="text" maxlength="255"
               value="<?php echo e(old('label', $editing ? $inboundDid->label : '')); ?>"
               placeholder="<?php echo e(__('e.g. Main sales line')); ?>" class="<?php echo e($inputClass); ?>">
        <?php $__errorArgs = ['label'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div class="flex items-center pt-6">
        <input type="hidden" name="is_active" value="0">
        <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-400">
            <input type="checkbox" name="is_active" value="1" <?php echo e((int) $active === 1 ? 'checked' : ''); ?>

                   class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700">
            <span><?php echo e(__('Active — accept inbound calls to this DID')); ?></span>
        </label>
    </div>
</div>

<div class="mt-6 flex gap-4">
    <button type="submit" class="btn-primary">
        <?php echo e($editing ? __('Save DID') : __('Add DID')); ?>

    </button>
    <a href="<?php echo e(route('admin.carrier.inbound-dids.index')); ?>" class="btn-default"><?php echo e(__('Cancel')); ?></a>
</div>
<?php /**PATH /var/www/html/resources/views/backend/pages/carrier/inbound-dids/_form.blade.php ENDPATH**/ ?>