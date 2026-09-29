<?php $__env->startSection('title'); ?>
    <?php echo e($breadcrumbs['title']); ?> | <?php echo e(config('app.name')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('admin-content'); ?>
    <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
        <?php if (isset($component)) { $__componentOriginal360d002b1b676b6f84d43220f22129e2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal360d002b1b676b6f84d43220f22129e2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.breadcrumbs','data' => ['breadcrumbs' => $breadcrumbs]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('breadcrumbs'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($breadcrumbs)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal360d002b1b676b6f84d43220f22129e2)): ?>
<?php $attributes = $__attributesOriginal360d002b1b676b6f84d43220f22129e2; ?>
<?php unset($__attributesOriginal360d002b1b676b6f84d43220f22129e2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal360d002b1b676b6f84d43220f22129e2)): ?>
<?php $component = $__componentOriginal360d002b1b676b6f84d43220f22129e2; ?>
<?php unset($__componentOriginal360d002b1b676b6f84d43220f22129e2); ?>
<?php endif; ?>

        <?php echo ld_apply_filters('translation_after_breadcrumbs', ''); ?>


        <div class="bg-white p-6 rounded-lg shadow-md mb-6 dark:bg-gray-800">
            <div class="flex flex-col sm:flex-row mb-6 gap-4 justify-between">
                <div class="flex items-start sm:items-center gap-4">
                    <div class="flex items-center">
                        <label for="language-select" class="mr-4 text-sm font-medium text-gray-700 dark:text-gray-300">
                            <?php echo e(__('Language:')); ?>

                        </label>
                        <select id="language-select"
                                class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2 text-sm text-gray-800 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                onchange="updateLocation()">
                            <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($code); ?>" <?php echo e($selectedLang === $code ? 'selected' : ''); ?>><?php echo e($language['name']); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="flex items-center">
                        <label for="group-select" class="mr-4 text-sm font-medium text-gray-700 dark:text-gray-300">
                            <?php echo e(__('Translation Group')); ?>:
                        </label>
                        <select id="group-select"
                                class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2 text-sm text-gray-800 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                onchange="updateLocation()">
                            <?php $__currentLoopData = $availableGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($group); ?>" <?php echo e($selectedGroup === $group ? 'selected' : ''); ?>>
                                    <?php echo e($groups[$group] ?? ucfirst($group)); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>

                <div class="place-items-end mt-4 sm:mt-0">
                    <?php if(auth()->user()->can('translations.edit')): ?>
                        <button data-modal-target="add-language-modal" data-modal-toggle="add-language-modal" class="btn-primary">
                            <i class="bi bi-plus-circle mr-2"></i><?php echo e(__('New Language')); ?>

                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    <?php echo e(__('Total Keys:')); ?> <span class="font-medium"><?php echo e($translationStats['totalKeys']); ?></span> |
                    <?php echo e(__('Translated')); ?>: <span class="font-medium"><?php echo e($translationStats['translated']); ?></span> |
                    <?php echo e(__('Missing:')); ?> <span class="font-medium"><?php echo e($translationStats['missing']); ?></span>
                </p>
                <div class="h-3 w-full bg-gray-200 rounded-full dark:bg-gray-700">
                    <div class="h-3 bg-blue-600 rounded-full" style="width: <?php echo e($translationStats['percentage']); ?>%"></div>
                </div>
            </div>

            <?php if($selectedLang !== 'en' || ($selectedLang === 'en' && $selectedGroup !== 'json')): ?>
                <form action="<?php echo e(route('admin.translations.update')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="lang" value="<?php echo e($selectedLang); ?>">
                    <input type="hidden" name="group" value="<?php echo e($selectedGroup); ?>">

                    <div class="mb-4 flex justify-end">
                        <button type="submit" class="btn-primary">
                            <i class="bi bi-save mr-2"></i> <?php echo e(__('Save Translations')); ?>

                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full border divide-y divide-gray-200 dark:divide-gray-700 dark:border-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                        <?php echo e(__('Key')); ?>

                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                        <?php echo e(__('English Text')); ?>

                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                        <?php echo e($languages[$selectedLang]['name'] ?? ucfirst($selectedLang)); ?> <?php echo e(__('Translation')); ?>

                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                                <?php $__currentLoopData = $enTranslations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if(!is_array($value)): ?>
                                        <tr class="<?php echo e(!isset($translations[$key]) ? 'bg-yellow-50 dark:bg-yellow-900/20' : ''); ?>">
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                                <?php echo e($key); ?>

                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo e($value); ?>

                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                <textarea name="translations[<?php echo e($key); ?>]" rows="1"
                                                    class="w-full rounded-md border border-gray-300 p-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                                    placeholder="<?php echo e($value); ?>"><?php echo e($translations[$key] ?? ''); ?></textarea>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <tr class="bg-gray-100 dark:bg-gray-800">
                                            <td colspan="3" class="px-6 py-2 text-sm font-medium text-gray-900 dark:text-white">
                                                <strong><?php echo e($key); ?></strong>
                                            </td>
                                        </tr>
                                        <?php $__currentLoopData = $value; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nestedKey => $nestedValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php if(!is_array($nestedValue)): ?>
                                                <tr class="<?php echo e(!isset($translations[$key][$nestedKey]) ? 'bg-yellow-50 dark:bg-yellow-900/20' : ''); ?>">
                                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white pl-12">
                                                        <?php echo e($nestedKey); ?>

                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                        <?php echo e($nestedValue); ?>

                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                        <textarea name="translations[<?php echo e($key); ?>][<?php echo e($nestedKey); ?>]" rows="1"
                                                            class="w-full rounded-md border border-gray-300 p-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                                            placeholder="<?php echo e($nestedValue); ?>"><?php echo e($translations[$key][$nestedKey] ?? ''); ?></textarea>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <tr class="bg-gray-50 dark:bg-gray-700">
                                                    <td colspan="3" class="px-6 py-1 text-sm font-medium text-gray-900 dark:text-white pl-12">
                                                        <strong><?php echo e($key); ?>.<?php echo e($nestedKey); ?></strong>
                                                    </td>
                                                </tr>
                                                <?php $__currentLoopData = $nestedValue; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deepKey => $deepValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <tr class="<?php echo e(!isset($translations[$key][$nestedKey][$deepKey]) ? 'bg-yellow-50 dark:bg-yellow-900/20' : ''); ?>">
                                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white pl-16">
                                                            <?php echo e($deepKey); ?>

                                                        </td>
                                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                            <?php echo e($deepValue); ?>

                                                        </td>
                                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                            <textarea name="translations[<?php echo e($key); ?>][<?php echo e($nestedKey); ?>][<?php echo e($deepKey); ?>]" rows="1"
                                                                class="w-full rounded-md border border-gray-300 p-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                                                placeholder="<?php echo e($deepValue); ?>"><?php echo e($translations[$key][$nestedKey][$deepKey] ?? ''); ?></textarea>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php endif; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="btn-primary">
                            <i class="bi bi-save mr-2"></i> <?php echo e(__('Save Translations')); ?>

                        </button>
                    </div>
                </form>
            <?php elseif($selectedLang === 'en' && $selectedGroup === 'json'): ?>
                <div class="bg-blue-50 p-4 rounded-md dark:bg-blue-900/20">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="bi bi-info-circle text-blue-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                <?php echo e(__('The base JSON translations for English cannot be edited. Please select another language or group to translate.')); ?>

                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php echo $__env->make('backend.pages.translations.create', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php $__env->startPush('scripts'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-resize textareas based on content
            const textareas = document.querySelectorAll('textarea');
            textareas.forEach(textarea => {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });

                // Initialize height
                textarea.style.height = 'auto';
                textarea.style.height = (textarea.scrollHeight) + 'px';
            });
        });

        function updateLocation() {
            const lang = document.getElementById('language-select').value;
            const group = document.getElementById('group-select').value;
            window.location.href = '<?php echo e(route('admin.translations.index')); ?>?lang=' + lang + '&group=' + group;
        }
    </script>
    <?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/translations/index.blade.php ENDPATH**/ ?>