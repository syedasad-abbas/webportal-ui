<?php
    $currentLocale = app()->getLocale();
    $lang = get_languages()[$currentLocale] ?? [
        'code' => strtoupper($currentLocale),
        'name' => strtoupper($currentLocale),
        'icon' => '/images/flags/default.svg',
    ];
?>

<button id="dropdownLocalesButton" data-dropdown-toggle="dropdownLocales" data-dropdown-placement="bottom"
    class="hover:text-dark-900 relative flex h-11 px-3 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
    type="button">
    <img src="<?php echo e($lang['icon']); ?>" alt="<?php echo e($lang['name']); ?> flag" height="20" width="20"
        class="mr-2" />
    <?php echo e($lang['code']); ?>


    <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
        viewBox="0 0 10 6">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="m1 1 4 4 4-4" />
    </svg>
</button>

<div id="dropdownLocales" class="z-10 hidden bg-white rounded-lg shadow-sm dark:bg-gray-700 max-h-[300px] overflow-y-auto w-24">
    <ul class="text-gray-700 dark:text-gray-200" aria-labelledby="dropdownLocalesButton">
        <?php $__currentLoopData = get_languages(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li>
                <a href="<?php echo e(route('locale.switch', $code)); ?>"
                    class="flex px-2 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 pl-3 pr-6">
                    <img src="<?php echo e($lang['icon']); ?>" alt="<?php echo e($lang['name']); ?> flag" height="20"
                        width="20" class="mr-2" />
                    <?php echo e($lang['code']); ?>

                </a>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</div><?php /**PATH /var/www/html/resources/views/backend/layouts/partials/locale-switcher.blade.php ENDPATH**/ ?>