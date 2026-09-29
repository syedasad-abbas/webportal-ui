<?php
    $enable_full_div_click = $enable_full_div_click ?? true;
    $value_attr = $value_attr ?? null;
?>

<div class="<?php echo e($class ?? 'bg-white'); ?> dark:bg-slate-800 w-full h-[160px] rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden p-6 pb-0 <?php echo e($enable_full_div_click ? 'cursor-pointer hover:shadow-lg transition-shadow duration-300' : ''); ?>"
    <?php if($enable_full_div_click): ?>
        onclick="window.location.href='<?php echo e($url ?? '#'); ?>'"
    <?php endif; ?>
>
    <div class="flex justify-between">
        <p class="text-[#090909] dark:text-gray-100 text-sm font-medium"><?php echo e($label); ?></p>

        <div class="">
            <button type="button" data-tooltip-target="tooltip-card-<?php echo e(Str::slug($label)); ?>" data-tooltip-placement="bottom"
                onclick="window.location.href='<?php echo e($url ?? '#'); ?>'"
                class="inline-flex items-center justify-center text-gray-500 w-8 h-8 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm">
                <img src="<?php echo e(asset('/images/icons/move.svg')); ?>" class="dark:invert">
            </button>
            <div id="tooltip-card-<?php echo e(Str::slug($label)); ?>" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
                <?php echo e(__('View')); ?> <?php echo e($label); ?>

                <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
        </div>
    </div>

    <div class="flex mt-6 gap-6 items-center">
        <div class="bg-white rounded-lg border border-[#EFEFFF] shadow-sm w-10 h-10 items-center flex justify-center">
            <?php if(!empty($icon)): ?>
                <i class="<?php echo e($icon); ?>"></i>
            <?php elseif(!empty($icon_svg)): ?>
                <img src="<?php echo e($icon_svg); ?>" alt="">
            <?php endif; ?>
        </div>

        <div class="text-[#090909] dark:text-gray-100 text-xl md:text-3xl font-medium"
            <?php if(!empty($value_attr)): ?>
                data-dashboard-metric="<?php echo e($value_attr); ?>"
            <?php endif; ?>
        >
            <?php echo $value ?? 0; ?>

        </div>
    </div>

    <div class="flex justify-end">
        <div class="-mt-10 w-20 h-40 rounded-full blur-3xl" style="background: <?php echo e($bg ?? ''); ?>;"></div>
    </div>
</div>
<?php /**PATH /var/www/html/resources/views/backend/pages/dashboard/partials/card.blade.php ENDPATH**/ ?>