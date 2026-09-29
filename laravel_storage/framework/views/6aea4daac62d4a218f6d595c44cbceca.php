<?php $__env->startSection('title'); ?>
    <?php echo e($breadcrumbs['title']); ?> | <?php echo e(config('app.name')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('admin-content'); ?>

<div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6" 
     x-data="{ selectedLeads: [], selectAll: false, bulkDeleteModalOpen: false }">

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
            <?php if(auth()->user()->can('leads.create') || auth()->user()->can('leads.edit')): ?>
                <a href="<?php echo e(route('admin.leads.create')); ?>" class="btn-primary ml-2">
                    <i class="bi bi-plus-circle mr-2"></i>
                    <?php echo e(__('New Lead')); ?>

                </a>
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

    <?php echo ld_apply_filters('leads_after_breadcrumbs', ''); ?>


    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5 flex gap-3 md:gap-1 flex-col md:flex-row justify-between items-center">
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90 hidden md:block"><?php echo e(__('Leads')); ?></h3>

                <?php echo $__env->make('backend.partials.search-form', [
                    'placeholder' => __('Search by patient name, phone or doctor'),
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                <div class="flex items-center gap-2">
                    <!-- Bulk Actions -->
                    <div class="flex items-center justify-center" x-show="selectedLeads.length > 0">
                        <button id="bulkActionsButton" data-dropdown-toggle="bulkActionsDropdown" 
                                class="btn-danger flex items-center justify-center gap-2 text-sm" type="button">
                            <i class="bi bi-trash"></i>
                            <span><?php echo e(__('Bulk Actions')); ?> (<span x-text="selectedLeads.length"></span>)</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>

                        <div id="bulkActionsDropdown" class="z-10 hidden w-48 p-3 bg-white rounded-lg shadow dark:bg-gray-700">
                            <h6 class="mb-2 text-sm font-medium text-gray-900 dark:text-white"><?php echo e(__('Bulk Actions')); ?></h6>
                            <ul class="space-y-2">
                                <li class="cursor-pointer text-sm text-red-600 dark:text-red-400 hover:bg-gray-200 dark:hover:bg-gray-600 px-2 py-1 rounded"
                                    @click="bulkDeleteModalOpen = true">
                                    <i class="bi bi-trash mr-1"></i> <?php echo e(__('Delete Selected')); ?>

                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-3 border-t border-gray-100 dark:border-gray-800 overflow-x-auto overflow-y-visible">
                <table id="dataTable" class="w-full dark:text-gray-400">
                    <thead class="bg-light text-capitalize">
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th width="5%" class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">
                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                        x-model="selectAll"
                                        @click="
                                            selectAll = !selectAll;
                                            selectedLeads = selectAll ?
                                                [...document.querySelectorAll('.lead-checkbox')].map(cb => cb.value) :
                                                [];
                                        "
                                    >
                                </div>
                            </th>

                            <th width="20%" class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">
                                <?php echo e(__('Patient Name')); ?>

                            </th>

                            <th width="15%" class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">
                                <?php echo e(__('Patient Phone')); ?>

                            </th>

                            <th width="12%" class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">
                                <?php echo e(__('Date')); ?>

                            </th>

                            <th width="15%" class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">
                                <?php echo e(__('Doctor Name')); ?>

                            </th>

                            <th width="12%" class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">
                                <?php echo e(__('Status')); ?>

                            </th>

                            <th width="10%" class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">
                                <?php echo e(__('Insurance')); ?>

                            </th>

                            <th width="11%" class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">
                                <?php echo e(__('Action')); ?>

                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $leads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lead): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="<?php echo e($loop->index + 1 != count($leads) ? 'border-b border-gray-100 dark:border-gray-800' : ''); ?>">
                                <td class="px-5 py-4 sm:px-6">
                                    <input
                                        type="checkbox"
                                        class="lead-checkbox form-checkbox h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                        value="<?php echo e($lead->id); ?>"
                                        x-model="selectedLeads"
                                    >
                                </td>

                                <td class="px-5 py-4 sm:px-6 font-medium">
                                    <a href="<?php echo e(route('admin.leads.edit', $lead->id)); ?>" 
                                       class="hover:text-primary">
                                        <?php echo e($lead->patient_name ?? 'N/A'); ?>

                                    </a>
                                </td>

                                <td class="px-5 py-4 sm:px-6">
                                    <?php echo e($lead->patient_phone ?? 'N/A'); ?>

                                </td>

                                <td class="px-5 py-4 sm:px-6">
                                    <?php echo e($lead->date?->format('Y-m-d') ?? 'N/A'); ?>

                                </td>

                                <td class="px-5 py-4 sm:px-6">
                                    <?php echo e($lead->doctor_name ?? 'N/A'); ?>

                                </td>

                                <td class="px-5 py-4 sm:px-6">
                                    <?php if($lead->status): ?>
                                        <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium rounded-full 
                                            <?php echo e($lead->status == 'closed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 
                                               ($lead->status == 'qualified' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : 
                                               'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400')); ?>">
                                            <?php echo e(ucfirst($lead->status)); ?>

                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>

                                <td class="px-5 py-4 sm:px-6">
                                    <?php echo e($lead->insurance ?? 'N/A'); ?>

                                </td>

                                <td class="px-5 py-4 sm:px-6">
                                    <?php if (isset($component)) { $__componentOriginalf71400415f89279b5d7a5bbf563c89d0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf71400415f89279b5d7a5bbf563c89d0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.buttons.action-buttons','data' => ['label' => __('Actions'),'showLabel' => false,'align' => 'right']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('buttons.action-buttons'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Actions')),'show-label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'align' => 'right']); ?>
                                        <?php if(auth()->user()->can('leads.edit')): ?>
                                            <?php if (isset($component)) { $__componentOriginald560e04bcb617ec3f965b99513409ac6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald560e04bcb617ec3f965b99513409ac6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.buttons.action-item','data' => ['href' => route('admin.leads.edit', $lead->id),'icon' => 'pencil','label' => __('Edit')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('buttons.action-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.leads.edit', $lead->id)),'icon' => 'pencil','label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Edit'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald560e04bcb617ec3f965b99513409ac6)): ?>
<?php $attributes = $__attributesOriginald560e04bcb617ec3f965b99513409ac6; ?>
<?php unset($__attributesOriginald560e04bcb617ec3f965b99513409ac6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald560e04bcb617ec3f965b99513409ac6)): ?>
<?php $component = $__componentOriginald560e04bcb617ec3f965b99513409ac6; ?>
<?php unset($__componentOriginald560e04bcb617ec3f965b99513409ac6); ?>
<?php endif; ?>
                                        <?php endif; ?>

                                        <?php if(auth()->user()->can('leads.delete')): ?>
                                            <div x-data="{ deleteModalOpen: false }">
                                                <?php if (isset($component)) { $__componentOriginald560e04bcb617ec3f965b99513409ac6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald560e04bcb617ec3f965b99513409ac6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.buttons.action-item','data' => ['type' => 'modal-trigger','modalTarget' => 'deleteModalOpen','icon' => 'trash','label' => __('Delete'),'class' => 'text-red-600 dark:text-red-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('buttons.action-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'modal-trigger','modal-target' => 'deleteModalOpen','icon' => 'trash','label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Delete')),'class' => 'text-red-600 dark:text-red-400']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald560e04bcb617ec3f965b99513409ac6)): ?>
<?php $attributes = $__attributesOriginald560e04bcb617ec3f965b99513409ac6; ?>
<?php unset($__attributesOriginald560e04bcb617ec3f965b99513409ac6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald560e04bcb617ec3f965b99513409ac6)): ?>
<?php $component = $__componentOriginald560e04bcb617ec3f965b99513409ac6; ?>
<?php unset($__componentOriginald560e04bcb617ec3f965b99513409ac6); ?>
<?php endif; ?>

                                                <?php if (isset($component)) { $__componentOriginalca6d1ceba306f01b7889f217adc4dd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalca6d1ceba306f01b7889f217adc4dd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modals.confirm-delete','data' => ['id' => 'delete-modal-'.e($lead->id).'','title' => ''.e(__('Delete Lead')).'','content' => ''.e(__('Are you sure you want to delete this lead? This action cannot be undone.')).'','formId' => 'delete-form-'.e($lead->id).'','formAction' => ''.e(route('admin.leads.destroy', $lead->id)).'','modalTrigger' => 'deleteModalOpen','cancelButtonText' => ''.e(__('No, cancel')).'','confirmButtonText' => ''.e(__('Yes, Confirm')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modals.confirm-delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'delete-modal-'.e($lead->id).'','title' => ''.e(__('Delete Lead')).'','content' => ''.e(__('Are you sure you want to delete this lead? This action cannot be undone.')).'','formId' => 'delete-form-'.e($lead->id).'','formAction' => ''.e(route('admin.leads.destroy', $lead->id)).'','modalTrigger' => 'deleteModalOpen','cancelButtonText' => ''.e(__('No, cancel')).'','confirmButtonText' => ''.e(__('Yes, Confirm')).'']); ?>
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
                                            </div>
                                        <?php endif; ?>
                                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf71400415f89279b5d7a5bbf563c89d0)): ?>
<?php $attributes = $__attributesOriginalf71400415f89279b5d7a5bbf563c89d0; ?>
<?php unset($__attributesOriginalf71400415f89279b5d7a5bbf563c89d0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf71400415f89279b5d7a5bbf563c89d0)): ?>
<?php $component = $__componentOriginalf71400415f89279b5d7a5bbf563c89d0; ?>
<?php unset($__componentOriginalf71400415f89279b5d7a5bbf563c89d0); ?>
<?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="8" class="text-center py-8">
                                    <p class="text-gray-500 dark:text-gray-400"><?php echo e(__('No leads found')); ?></p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="my-4 px-4 sm:px-6">
                    <?php echo e($leads->links()); ?>

                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Confirmation Modal -->
    <div
        x-cloak
        x-show="bulkDeleteModalOpen"
        x-transition.opacity.duration.200ms
        x-trap.inert.noscroll="bulkDeleteModalOpen"
        x-on:keydown.esc.window="bulkDeleteModalOpen = false"
        x-on:click.self="bulkDeleteModalOpen = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 p-4 backdrop-blur-md"
        role="dialog"
        aria-modal="true"
    >
        <div
            x-show="bulkDeleteModalOpen"
            x-transition:enter="transition ease-out duration-200 delay-100 motion-reduce:transition-opacity"
            x-transition:enter-start="opacity-0 scale-50"
            x-transition:enter-end="opacity-100 scale-100"
            class="flex max-w-md flex-col gap-4 overflow-hidden rounded-lg border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-700"
        >
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2 dark:border-gray-800">
                <div class="flex items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 p-1">
                    <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </div>
                <h3 class="font-semibold tracking-wide text-gray-800 dark:text-white">
                    <?php echo e(__('Delete Selected Leads')); ?>

                </h3>
                <button
                    x-on:click="bulkDeleteModalOpen = false"
                    class="text-gray-400 hover:bg-gray-200 hover:text-gray-900 rounded-lg p-1 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" stroke="currentColor" fill="none" stroke-width="1.4" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="px-4 text-center">
                <p class="text-gray-500 dark:text-gray-400">
                    <?php echo e(__('Are you sure you want to delete the selected leads? This action cannot be undone.')); ?>

                </p>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 p-4 dark:border-gray-800">
                <form id="bulk-delete-form" action="<?php echo e(route('admin.leads.bulk-delete')); ?>" method="POST">
                    <?php echo method_field('DELETE'); ?>
                    <?php echo csrf_field(); ?>

                    <template x-for="id in selectedLeads" :key="id">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>

                    <button
                        type="button"
                        x-on:click="bulkDeleteModalOpen = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700"
                    >
                        <?php echo e(__('No, Cancel')); ?>

                    </button>

                    <button
                        type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-800"
                    >
                        <?php echo e(__('Yes, Delete')); ?>

                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    // You can add extra JS here if needed later
</script>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/leads/index.blade.php ENDPATH**/ ?>