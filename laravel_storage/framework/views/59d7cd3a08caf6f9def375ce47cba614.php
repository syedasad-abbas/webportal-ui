<?php $__env->startSection('title'); ?>
    <?php echo e(__('Modules')); ?> | <?php echo e(config('app.name')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('admin-content'); ?>

<div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6"
    x-data="{ showUploadArea: false }"
    x-init="showUploadArea = <?php echo e(count($modules) > 0 ? 'false' : 'true'); ?>"
    x-cloak
    x-on:keydown.escape.window="showUploadArea = false"
    x-on:click.away="showUploadArea = false"
>
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
         <?php $__env->slot('title_after', null, []); ?> 
            <?php if(count($modules) > 0): ?>
                <button
                    @click="showUploadArea = !showUploadArea"
                    class="ml-4 btn-primary btn-upload-module"
                >
                    <i class="bi bi-cloud-upload mr-2"></i>
                    <?php echo e(__('Upload Module')); ?>

                </button>

                <?php if (isset($component)) { $__componentOriginal26ee8ce16f47ac5ad29f6592684ca76f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal26ee8ce16f47ac5ad29f6592684ca76f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.popover','data' => ['position' => 'bottom','width' => 'w-[300px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('popover'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['position' => 'bottom','width' => 'w-[300px]']); ?>
                     <?php $__env->slot('trigger', null, []); ?> 
                        <i class="bi bi-info-circle text-lg ml-3" title="<?php echo e(__('Module Requirements')); ?>"></i>
                     <?php $__env->endSlot(); ?>

                    <div class="w-[300px] p-4 font-normal">
                        <h3 class="font-medium text-gray-900 dark:text-white mb-2"><?php echo e(__('Module Requirements')); ?></h3>
                        <p class="mb-2"><?php echo e(__('You can upload custom modules to extend functionality.')); ?></p>
                        <ul class="list-disc pl-5 space-y-1 text-sm">
                            <li><?php echo e(__('Modules must be in .zip format')); ?></li>
                            <li><?php echo e(__('Each module should have a valid module.json file')); ?></li>
                            <li>
                                <?php echo e(__('Must follow guidelines.')); ?>&nbsp;
                                <a href="https://laradashboard.com/docs/how-to-create-a-module-in-lara-dashboard/" class="text-primary hover:underline" target="_blank">
                                    <?php echo e(__('Learn more')); ?>

                                    <i class="bi bi-arrow-up-right-square text-sm"></i>
                                </a>
                            </li>
                        </ul>
                        <?php if(config('app.demo_mode', false)): ?>
                        <div class="bg-yellow-50 text-yellow-700 rounded-lg mt-4 p-3">
                            <i class="bi bi-exclamation-triangle-fill"></i> &nbsp;
                            <?php echo e(__('Note: Module uploads are disabled in demo mode.')); ?>

                        </div>
                        <?php endif; ?>
                    </div>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal26ee8ce16f47ac5ad29f6592684ca76f)): ?>
<?php $attributes = $__attributesOriginal26ee8ce16f47ac5ad29f6592684ca76f; ?>
<?php unset($__attributesOriginal26ee8ce16f47ac5ad29f6592684ca76f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal26ee8ce16f47ac5ad29f6592684ca76f)): ?>
<?php $component = $__componentOriginal26ee8ce16f47ac5ad29f6592684ca76f; ?>
<?php unset($__componentOriginal26ee8ce16f47ac5ad29f6592684ca76f); ?>
<?php endif; ?>
            <?php endif; ?>
         <?php $__env->endSlot(); ?>
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

    <?php echo ld_apply_filters('modules_after_breadcrumbs', ''); ?>


    <div x-show="showUploadArea" class="mb-6 p-6 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-600"
            @dragover.prevent
            @drop.prevent="$refs.uploadModule.files = $event.dataTransfer.files; $refs.uploadModule.dispatchEvent(new Event('change'))">
        <p class="text-center text-gray-600 dark:text-gray-400">
            <?php echo e(__('Drag and drop your module file here, or')); ?>

            <button
                @click="$refs.uploadModule.click()"
                class="text-primary underline hover:text-blue-600"
            >
                <?php echo e(__('browse')); ?>

            </button>
            <?php echo e(__('to select a file.')); ?>

        </p>
        <form action="<?php echo e(route('admin.modules.store')); ?>" method="POST" enctype="multipart/form-data" class="hidden">
            <?php echo csrf_field(); ?>
            <input type="file" name="module" accept=".zip" x-ref="uploadModule" @change="$event.target.form.submit()">
        </form>
    </div>

    <div class="space-y-6">
        <?php if(empty($modules)): ?>
        <div class="flex flex-col items-center justify-center h-64 bg-gray-100 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300"
                @dragover.prevent
                @drop.prevent="$refs.uploadModule.files = $event.dataTransfer.files; $refs.uploadModule.dispatchEvent(new Event('change'))">
            <svg class="w-16 h-16 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <p class="mt-4 text-gray-600 dark:text-gray-400"><?php echo e(__('Drag and drop your module file here, or')); ?></p>
            <button
                @click="$refs.uploadModule.click()"
                class="mt-4 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-blue-600"
            >
                <i class="bi bi-cloud-upload mr-2"></i>
                <?php echo e(__('Upload')); ?>

            </button>
            <form action="<?php echo e(route('admin.modules.store')); ?>" method="POST" enctype="multipart/form-data" class="hidden">
                <?php echo csrf_field(); ?>
                <input type="file" name="module" accept=".zip" x-ref="uploadModule" @change="$event.target.form.submit()">
            </form>
        </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex justify-between" x-data="{ deleteModalOpen: false, errorModalOpen: false, errorMessage: '' }">
                            <div class="py-3">
                                <h2>
                                    <i class="bi <?php echo e($module['icon']); ?> text-3xl text-gray-500 dark:text-gray-400"></i>
                                </h2>
                                <h3 class="text-lg font-medium text-gray-800 dark:text-white">
                                    <?php echo e($module['title']); ?>

                                </h3>
                            </div>

                            <button id="dropdownMenuIconButton" data-dropdown-toggle="dropdownMore-<?php echo e($module['name']); ?>" class="inline-flex items-right h-9 p-2 text-sm font-medium text-center text-gray-900 bg-white rounded-lg hover:bg-gray-100 focus:ring-4 focus:outline-none dark:text-white focus:ring-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 dark:focus:ring-gray-600" type="button">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>

                            <div id="dropdownMore-<?php echo e($module['name']); ?>" class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44 dark:bg-gray-700 dark:divide-gray-600">
                                <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownMenuIconButton">
                                    <li>
                                        <div>
                                            <button
                                                x-on:click="deleteModalOpen = true"
                                                class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white w-full px-2 text-left"
                                            >
                                                <?php echo e(__('Delete')); ?>

                                            </button>
                                        </div>
                                    </li>
                                    <li>
                                        <button
                                            onclick="toggleModuleStatus('<?php echo e($module['name']); ?>', event)"
                                            class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white w-full px-2 text-left"
                                        >
                                            <?php echo e($module['status'] ? __('Disable') : __('Enable')); ?>

                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <?php if (isset($component)) { $__componentOriginalca6d1ceba306f01b7889f217adc4dd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalca6d1ceba306f01b7889f217adc4dd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modals.confirm-delete','data' => ['id' => 'delete-modal-'.e($module['name']).'','title' => ''.e(__('Delete Module')).'','content' => ''.e(__('Are you sure you want to delete this module?')).'','formId' => 'delete-form-'.e($module['name']).'','formAction' => ''.e(route('admin.modules.delete', $module['name'])).'','modalTrigger' => 'deleteModalOpen','cancelButtonText' => ''.e(__('No, Cancel')).'','confirmButtonText' => ''.e(__('Yes, Confirm')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modals.confirm-delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'delete-modal-'.e($module['name']).'','title' => ''.e(__('Delete Module')).'','content' => ''.e(__('Are you sure you want to delete this module?')).'','formId' => 'delete-form-'.e($module['name']).'','formAction' => ''.e(route('admin.modules.delete', $module['name'])).'','modalTrigger' => 'deleteModalOpen','cancelButtonText' => ''.e(__('No, Cancel')).'','confirmButtonText' => ''.e(__('Yes, Confirm')).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalca6d1ceba306f01b7889f217adc4dd4a)): ?>
<?php $attributes = $__attributesOriginalca6d1ceba306f01b7889f217adc4dd4a; ?>
<?php unset($__attributesOriginalca6d1ceba306f01b7889f217adc4dd4a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalca6d1ceba306f01b7889f217adc4dd4a)): ?>
<?php $component = $__componentOriginalca6d1ceba306f01b7889f217adc4dd4a; ?>
<?php unset($__componentOriginalca6d1ceba306f01b7889f217adc4dd4a); ?>
<?php endif; ?>

                            <?php if (isset($component)) { $__componentOriginal28201ac60b676f2725da8099c2de842e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal28201ac60b676f2725da8099c2de842e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modals.error-message','data' => ['id' => 'error-modal-'.e($module['name']).'','title' => ''.e(__('Operation Failed')).'','modalTrigger' => 'errorModalOpen']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modals.error-message'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'error-modal-'.e($module['name']).'','title' => ''.e(__('Operation Failed')).'','modalTrigger' => 'errorModalOpen']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal28201ac60b676f2725da8099c2de842e)): ?>
<?php $attributes = $__attributesOriginal28201ac60b676f2725da8099c2de842e; ?>
<?php unset($__attributesOriginal28201ac60b676f2725da8099c2de842e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal28201ac60b676f2725da8099c2de842e)): ?>
<?php $component = $__componentOriginal28201ac60b676f2725da8099c2de842e; ?>
<?php unset($__componentOriginal28201ac60b676f2725da8099c2de842e); ?>
<?php endif; ?>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo e($module['description']); ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <?php echo e(__('Tags:')); ?>

                            <?php $__currentLoopData = $module['tags']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <span class="inline-block px-2 py-1 text-xs font-medium text-white bg-gray-400 rounded-full mr-1 mb-1"><?php echo e($tag); ?></span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </p>
                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-sm font-medium <?php echo e($module['status'] ? 'text-green-500' : 'text-red-500'); ?>">
                                <?php echo e($module['status'] ? __('Enabled') : __('Disabled')); ?>

                            </span>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
    </div>
</div>


<script>
    function toggleModuleStatus(moduleName, event) {
        const moduleElement = event.target.closest('[x-data]');
        const Alpine = window.Alpine;
        
        fetch(`/admin/modules/toggle-status/${moduleName}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const button = event.target;
                button.textContent = data.status ? '<?php echo e(__("Disable")); ?>' : '<?php echo e(__("Enable")); ?>';
                
                // Refresh the page to show updated status
                window.location.reload();
            } else {
                // Show error modal instead of alert
                if (moduleElement && Alpine) {
                    const component = Alpine.$data(moduleElement);
                    component.errorMessage = data.message || '<?php echo e(__("An error occurred while processing your request.")); ?>';
                    component.errorModalOpen = true;
                }
            }
        })
        .catch(error => {
            // Handle network errors
            if (moduleElement && Alpine) {
                const component = Alpine.$data(moduleElement);
                component.errorMessage = '<?php echo e(__("Network error. Please check your connection and try again.")); ?>';
                component.errorModalOpen = true;
            }
        });
    }
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/modules/index.blade.php ENDPATH**/ ?>