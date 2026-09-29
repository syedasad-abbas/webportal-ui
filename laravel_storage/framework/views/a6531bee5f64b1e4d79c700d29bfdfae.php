<div id="add-language-modal" tabindex="-1" aria-hidden="true" class="hidden fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden">
    <div class="relative p-4 w-full max-w-md">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 border-b rounded-t dark:border-gray-600">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <?php echo e(__('Add New Language')); ?>

                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="add-language-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only"><?php echo e(__('Close modal')); ?></span>
                </button>
            </div>
            <!-- Modal body -->
            <div class="p-6 space-y-6">
                <form action="<?php echo e(route('admin.translations.create')); ?>" method="POST" id="add-language-form">
                    <?php echo csrf_field(); ?>
                    <div class="mb-4">
                        <label for="language-code" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            <?php echo e(__('Select Language')); ?>

                        </label>
                        <select id="language-code" name="language_code" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2 text-sm text-gray-800 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                            <option value=""><?php echo e(__('Select a language')); ?></option>
                            <?php $__currentLoopData = $allLanguages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $languageName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if(!array_key_exists($code, $languages)): ?>
                                    <option value="<?php echo e($code); ?>"><?php echo e($languageName); ?></option>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="translation-group" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            <?php echo e(__('Translation Group')); ?>

                        </label>
                        <select id="translation-group" name="group" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2 text-sm text-gray-800 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                            <option value="json" selected><?php echo e(__('General')); ?></option>
                            <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($key !== 'json'): ?>
                                    <option value="<?php echo e($key); ?>"><?php echo e($name); ?></option>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </form>
            </div>
            <!-- Modal footer -->
            <div class="flex items-center p-4 space-x-2 border-t border-gray-200 rounded-b dark:border-gray-600">
                <button type="submit" form="add-language-form" class="btn-primary">
                    <i class="bi bi-plus-circle mr-2"></i><?php echo e(__('Add Language')); ?>

                </button>
                <button data-modal-hide="add-language-modal" type="button" class="btn-default">
                    <?php echo e(__('Cancel')); ?>

                </button>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /var/www/html/resources/views/backend/pages/translations/create.blade.php ENDPATH**/ ?>