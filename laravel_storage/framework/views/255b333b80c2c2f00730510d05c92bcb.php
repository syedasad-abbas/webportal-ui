<div class="flex items-center space-x-4" x-data="{ open: false }">
    <span
        class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800 dark:bg-gray-700 dark:text-gray-200"
    >
        <?php echo e(__(ucfirst(str_replace("_", " ", $currentFilter)))); ?>

    </span>

    <button 
        id="dropdownDefaultButton" 
        @click="open = !open"
        class="btn-primary flex items-center justify-center gap-2" 
        type="button"
    >
        <i class="bi bi-sliders"></i>
        <?php echo e(__('Filter')); ?>

        <i class="bi bi-chevron-down"></i>
    </button>

    <div
        id="dropdown"
        x-show="open"
        x-trap="open"
        @click.outside="open = false"
        @keydown.escape="open = false"
        class="z-10 bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44 dark:bg-gray-700"
    >
        <ul
            class="py-2 text-sm text-gray-700 dark:text-gray-200"
            aria-labelledby="dropdownDefaultButton"
            role="menu"
        >
            <li>
                <a
                    href="<?php echo e(route('admin.dashboard')); ?>?chart_filter_period=last_12_Months"
                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white <?php echo e($currentFilter === 'last_12_months'
                            ? 'bg-blue-100 dark:bg-gray-600'
                            : ''); ?>"
                >
                    <span class="ml-2"> <?php echo e(__('Last 12 months')); ?></span>
                </a>
            </li>
            <li>
                <a
                    href="<?php echo e(route('admin.dashboard')); ?>?chart_filter_period=this_year"
                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white <?php echo e($currentFilter === 'this_year'
                            ? 'bg-blue-100 dark:bg-gray-600'
                            : ''); ?>"
                >
                    <span class="ml-2"> <?php echo e(__('This year')); ?></span>
                </a>
            </li>
            <li>
                <a
                    href="<?php echo e(route('admin.dashboard')); ?>?chart_filter_period=last_year"
                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white <?php echo e($currentFilter === 'last_year'
                            ? 'bg-blue-100 dark:bg-gray-600'
                            : ''); ?>"
                >
                    <span class="ml-2"> <?php echo e(__('Last year')); ?></span>
                </a>
            </li>
            <li>
                <a
                    href="<?php echo e(route('admin.dashboard')); ?>?chart_filter_period=last_30_days"
                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white <?php echo e($currentFilter === 'last_30_days'
                            ? 'bg-blue-100 dark:bg-gray-600'
                            : ''); ?>"
                >
                    <span class="ml-2"> <?php echo e(__('Last 30 days')); ?></span>
                </a>
            </li>
            <li>
                <a
                    href="<?php echo e(route('admin.dashboard')); ?>?chart_filter_period=last_7_days"
                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white <?php echo e($currentFilter === 'last_7_days'
                            ? 'bg-blue-100 dark:bg-gray-600'
                            : ''); ?>"
                >
                    <span class="ml-2"> <?php echo e(__('Last 7 days')); ?></span>
                </a>
            </li>
            <li>
                <a
                    href="<?php echo e(route('admin.dashboard')); ?>?chart_filter_period=this_month"
                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white <?php echo e($currentFilter === 'this_month'
                            ? 'bg-blue-100 dark:bg-gray-600'
                            : ''); ?>"
                >
                    <span class="ml-2"> <?php echo e(__('This month')); ?></span>
                </a>
            </li>
        </ul>
    </div>
</div>
<?php /**PATH /var/www/html/resources/views/components/filters/date-filter.blade.php ENDPATH**/ ?>