<?php $__env->startSection('title'); ?>
    <?php echo e($breadcrumbs['title']); ?> | <?php echo e(config('app.name')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('admin-content'); ?>
<div class="connectpro-admin-page p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6" x-data="{ selectedRoles: [], selectAll: false, bulkDeleteModalOpen: false }">
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
            <?php if(auth()->user()->can('role.create')): ?>
                <a href="<?php echo e(route('admin.roles.create')); ?>" class="btn-primary ml-2">
                    <i class="bi bi-plus-circle mr-2"></i>
                    <?php echo e(__('New Role')); ?>

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

    <?php echo ld_apply_filters('roles_after_breadcrumbs', ''); ?>


    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5 flex flex-col md:flex-row justify-between items-center gap-1">
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90"><?php echo e(__('Roles')); ?></h3>

                <div class="flex items-center gap-2">
                    <!-- Bulk Actions dropdown -->
                    <div class="flex items-center justify-center" x-show="selectedRoles.length > 0">
                        <button id="bulkActionsButton" data-dropdown-toggle="bulkActionsDropdown" class="btn-danger flex items-center justify-center gap-2 text-sm" type="button">
                            <i class="bi bi-trash"></i>
                            <span><?php echo e(__('Bulk Actions')); ?> (<span x-text="selectedRoles.length"></span>)</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>

                        <!-- Bulk Actions dropdown menu -->
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

                <?php echo $__env->make('backend.partials.search-form', [
                    'placeholder' => __('Search by role name'),
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
                                            selectedRoles = selectAll ? 
                                                [...document.querySelectorAll('.role-checkbox')].map(cb => cb.value) : 
                                                [];
                                        "
                                    >
                                </div>
                            </th>
                            <th width="15%" class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">
                                <div class="flex items-center">
                                    <?php echo e(__('Name')); ?>

                                    <a href="<?php echo e(request()->fullUrlWithQuery(['sort' => request()->sort === 'name' ? '-name' : 'name'])); ?>" class="ml-1">
                                        <?php if(request()->sort === 'name'): ?>
                                            <i class="bi bi-sort-alpha-down text-primary"></i>
                                        <?php elseif(request()->sort === '-name'): ?>
                                            <i class="bi bi-sort-alpha-up text-primary"></i>
                                        <?php else: ?>
                                            <i class="bi bi-arrow-down-up text-gray-400"></i>
                                        <?php endif; ?>
                                    </a>
                                </div>
                            </th>
                            <th width="8%" class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">
                                <div class="flex items-center">
                                    <?php echo e(__('Users')); ?>

                                    <a href="<?php echo e(request()->fullUrlWithQuery(['sort' => request()->sort === 'user_count' ? '-user_count' : 'user_count'])); ?>" class="ml-1">
                                        <?php if(request()->sort === 'user_count'): ?>
                                            <i class="bi bi-sort-numeric-down text-primary"></i>
                                        <?php elseif(request()->sort === '-user_count'): ?>
                                            <i class="bi bi-sort-numeric-up text-primary"></i>
                                        <?php else: ?>
                                            <i class="bi bi-arrow-down-up text-gray-400"></i>
                                        <?php endif; ?>
                                    </a>
                                </div>
                            </th>
                            <th width="35%" class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white"><?php echo e(__('Permissions')); ?></th>
                            <th width="12%" class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white"><?php echo e(__('Action')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="<?php echo e($loop->index + 1 != count($roles) ?  'border-b border-gray-100 dark:border-gray-800' : ''); ?>">
                                <td class="px-5 py-4 sm:px-6">
                                    <input 
                                        type="checkbox" 
                                        class="role-checkbox form-checkbox h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" 
                                        value="<?php echo e($role->id); ?>"
                                        x-model="selectedRoles"
                                         <?php echo e($role->name === 'Superadmin' ? 'disabled' : ''); ?>

                                    >
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <?php if(auth()->user()->can('role.edit')): ?>
                                        <a href="<?php echo e(route('admin.roles.edit', $role->id)); ?>" data-tooltip-target="tooltip-role-name-<?php echo e($role->id); ?>" class="text-gray-800 dark:text-white hover:text-primary dark:hover:text-primary">
                                            <?php echo e($role->name); ?>

                                        </a>
                                        <div id="tooltip-role-name-<?php echo e($role->id); ?>" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
                                            <?php echo e(__('Edit Role')); ?>

                                            <div class="tooltip-arrow" data-popper-arrow></div>
                                        </div>
                                    <?php else: ?>
                                        <?php echo e($role->name); ?>

                                    <?php endif; ?>

                                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo e(__('Total Permissions:')); ?> <?php echo e($role->permissions->count()); ?>

                                    </div>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <a href="<?php echo e(route('admin.users.index', ['role' => $role->name])); ?>" class="inline-flex items-center gap-1 text-sm text-primary hover:underline" data-tooltip-target="tooltip-users-role-<?php echo e($role->id); ?>">
                                        <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full dark:bg-gray-700 dark:text-white">
                                            <?php echo e($role->user_count); ?>

                                        </span>
                                    </a>
                                    <div id="tooltip-users-role-<?php echo e($role->id); ?>" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
                                        <?php echo e(__('View')); ?> <?php echo e($role->name); ?> <?php echo e(__('Users')); ?>

                                        <div class="tooltip-arrow" data-popper-arrow></div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <div x-data="{ showAll: false }">
                                        <div>
                                            <?php $__currentLoopData = $role->permissions->take(7); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full dark:bg-gray-800 dark:text-white">
                                                    <?php echo e($permission->name); ?>

                                                </span>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <template x-if="showAll">
                                                <div>
                                                    <?php $__currentLoopData = $role->permissions->skip(7); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full dark:bg-gray-800 dark:text-white">
                                                            <?php echo e($permission->name); ?>

                                                        </span>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </div>
                                            </template>
                                        </div>
                                        <?php if($role->permissions->count() > 7): ?>
                                            <button @click="showAll = !showAll" class="text-primary text-sm mt-2">
                                                <span x-show="!showAll">+<?php echo e($role->permissions->count() - 7); ?> <?php echo e(__('more')); ?></span>
                                                <span x-show="showAll"><?php echo e(__('Show less')); ?></span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-5 py-4 sm:px-6 flex justify-center">
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
                                         <?php if(auth()->user()->can('role.edit') && $role->name !== 'Superadmin'): ?>
                                            <?php if (isset($component)) { $__componentOriginald560e04bcb617ec3f965b99513409ac6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald560e04bcb617ec3f965b99513409ac6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.buttons.action-item','data' => ['href' => route('admin.roles.edit', $role->id),'icon' => 'pencil','label' => __('Edit')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('buttons.action-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.roles.edit', $role->id)),'icon' => 'pencil','label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Edit'))]); ?>
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

                                         <?php if(auth()->user()->can('role.delete') && $role->name !== 'Superadmin'): ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modals.confirm-delete','data' => ['id' => 'delete-modal-'.e($role->id).'','title' => ''.e(__('Delete Role')).'','content' => ''.e(__('Are you sure you want to delete this role?')).'','formId' => 'delete-form-'.e($role->id).'','formAction' => ''.e(route('admin.roles.destroy', $role->id)).'','modalTrigger' => 'deleteModalOpen','cancelButtonText' => ''.e(__('No, cancel')).'','confirmButtonText' => ''.e(__('Yes, Confirm')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modals.confirm-delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'delete-modal-'.e($role->id).'','title' => ''.e(__('Delete Role')).'','content' => ''.e(__('Are you sure you want to delete this role?')).'','formId' => 'delete-form-'.e($role->id).'','formAction' => ''.e(route('admin.roles.destroy', $role->id)).'','modalTrigger' => 'deleteModalOpen','cancelButtonText' => ''.e(__('No, cancel')).'','confirmButtonText' => ''.e(__('Yes, Confirm')).'']); ?>
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
                                        
                                        <?php if (isset($component)) { $__componentOriginald560e04bcb617ec3f965b99513409ac6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald560e04bcb617ec3f965b99513409ac6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.buttons.action-item','data' => ['href' => route('admin.users.index', ['role' => $role->name]),'icon' => 'people','label' => __('View Users')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('buttons.action-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.users.index', ['role' => $role->name])),'icon' => 'people','label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('View Users'))]); ?>
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
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td colspan="5" class="px-5 py-4 sm:px-6 text-center">
                                    <span class="text-gray-500 dark:text-gray-400"><?php echo e(__('No roles found')); ?></span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="my-4 px-4 sm:px-6">
                    <?php echo e($roles->links()); ?>

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
        aria-labelledby="bulk-delete-modal-title"
    >
        <div 
            x-show="bulkDeleteModalOpen" 
            x-transition:enter="transition ease-out duration-200 delay-100 motion-reduce:transition-opacity" 
            x-transition:enter-start="opacity-0 scale-50" 
            x-transition:enter-end="opacity-100 scale-100" 
            class="flex max-w-md flex-col gap-4 overflow-hidden rounded-lg border border-outline border-gray-100 dark:border-gray-800 bg-white text-on-surface dark:border-outline-dark dark:bg-gray-700 dark:text-gray-400"
        >
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2 dark:border-gray-800">
                <div class="flex items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 p-1">
                    <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </div>
                <h3 id="bulk-delete-modal-title" class="font-semibold tracking-wide text-gray-800 dark:text-white">
                    <?php echo e(__('Delete Selected Roles')); ?>

                </h3>
                <button 
                    x-on:click="bulkDeleteModalOpen = false" 
                    aria-label="close modal" 
                    class="text-gray-400 hover:bg-gray-200 hover:text-gray-900 rounded-lg p-1 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" stroke="currentColor" fill="none" stroke-width="1.4" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-4 text-center">
                <p class="text-gray-500 dark:text-gray-400">
                    <?php echo e(__('Are you sure you want to delete the selected roles?')); ?> 
                    <?php echo e(__('This action cannot be undone.')); ?>

                </p>
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-gray-100 p-4 dark:border-gray-800">
                <form id="bulk-delete-form" action="<?php echo e(route('admin.roles.bulk-delete')); ?>" method="POST">
                    <?php echo method_field('DELETE'); ?>
                    <?php echo csrf_field(); ?>
                    
                    <template x-for="id in selectedRoles" :key="id">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>

                    <button 
                        type="button" 
                        x-on:click="bulkDeleteModalOpen = false" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                    >
                        <?php echo e(__('No, Cancel')); ?>

                    </button>

                    <button 
                        type="submit" 
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-300 dark:focus:ring-red-800"
                    >
                        <?php echo e(__('Yes, Delete')); ?>

                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/roles/index.blade.php ENDPATH**/ ?>