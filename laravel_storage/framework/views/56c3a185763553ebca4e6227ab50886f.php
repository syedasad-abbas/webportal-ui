<div class="mb-4 border-b border-gray-200 dark:border-gray-700">
    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" data-tabs-toggle="#default-styled-tab-content"
        data-tabs-active-classes="text-primary hover:text-primary border-primary dark:border-primary"
        data-tabs-inactive-classes="dark:border-transparent text-gray-500 hover:text-gray-600 dark:text-gray-400 border-gray-100 hover:border-gray-300 dark:border-gray-700 dark:hover:text-gray-300"
        role="tablist">
        <?php
           $activeTab = request('tab', 'general'); 
        ?>
        <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php echo ld_apply_filters('settings_tab_menu_before_' . $key, ''); ?>

            <li class="me-2" role="presentation">
                <button
                    class="inline-block p-4 border-b-2 rounded-t-lg 
               hover:text-gray-600 hover:border-gray-300 
               dark:hover:text-gray-300 text-primary hover:text-primary
               <?php echo e($activeTab == $key ? 'border-b-2 text-primary border-primary dark:text-primary dark:border-primary' : 'text-gray-500 border-transparent'); ?>"
                    id="<?php echo e($key); ?>-tab" data-tabs-target="#<?php echo e($key); ?>" type="button" role="tab" data-tab="<?php echo e($key); ?>"
                    aria-controls="<?php echo e($key); ?>" aria-selected="<?php echo e($activeTab === $key ? 'true' : 'false'); ?>">
                    <?php echo e($tab['title']); ?> 
                </button>
            </li>
            <?php echo ld_apply_filters('settings_tab_menu_after_' . $key, ''); ?>

        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</div>
<div id="default-styled-tab-content">
    <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php echo ld_apply_filters('settings_tab_content_before_' . $key, ''); ?>

        <div class="hidden rounded-2xl dark:bg-gray-800 mb-3" id="<?php echo e($key); ?>" role="tabpanel"
            aria-labelledby="<?php echo e($key); ?>-tab">
            <?php if(isset($tab['view'])): ?>
                <?php echo $__env->make($tab['view'], $tab['data'] ?? [], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php else: ?>
                <?php echo $tab['content']; ?>

            <?php endif; ?>
        </div>
        <?php echo ld_apply_filters('settings_tab_content_after_' . $key, ''); ?>

    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php /**PATH /var/www/html/resources/views/backend/pages/settings/tabs.blade.php ENDPATH**/ ?>