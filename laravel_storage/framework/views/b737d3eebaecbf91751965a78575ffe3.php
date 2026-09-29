<?php $__env->startSection('title'); ?>
    <?php echo e($breadcrumbs['title']); ?> | <?php echo e(config('app.name')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('admin-content'); ?>
<?php
  $isAdmin = auth()->check() && auth()->user()->hasAnyPermission(['carrier.edit', 'carrier.delete']);
?>

<div class="connectpro-admin-page p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
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
            <div class="flex items-center gap-2">
            </div>
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

    <?php echo ld_apply_filters('carrier_after_breadcrumbs', ''); ?>


    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5 flex gap-3 flex-col md:flex-row md:justify-between md:items-center">
                <div>
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90 hidden md:block">
                        <?php echo e(__('Configured carrier')); ?>

                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 hidden md:block">
                        <?php echo e(__('Monitor registration health and manage routing rules.')); ?>

                    </p>
                </div>

                <a href="<?php echo e(route('admin.carrier.inbound-dids.index')); ?>" class="btn-default">
                    <?php echo e(__('Manage Inbound DIDs')); ?>

                </a>

                <?php echo $__env->make('backend.partials.search-form', [
                    'placeholder' => __('Search by name or domain'),
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>

            <div class="space-y-3 border-t border-gray-100 dark:border-gray-800 overflow-x-auto overflow-y-visible">
                <table class="w-full dark:text-gray-400">
                    <thead class="bg-light text-capitalize">
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5"><?php echo e(__('Name')); ?></th>
                            <th class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5"><?php echo e(__('Default Caller ID')); ?></th>
                            <th class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5"><?php echo e(__('Domain / Port')); ?></th>
                            <th class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5"><?php echo e(__('Transport')); ?></th>

                            
                            <th class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5"><?php echo e(__('Outbound Proxy')); ?></th>

                            <th class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5"><?php echo e(__('Registration')); ?></th>
                            <th class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5"><?php echo e(__('Prefixes')); ?></th>

                            <?php if($isAdmin): ?>
                                <th class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-right px-5"><?php echo e(__('Actions')); ?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $carrier; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $carrierItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $status = $carrierItem['registration_status'] ?? null;
                                $state = $status['state'] ?? null;

                                $chipClass = 'text-gray-800 bg-gray-100 dark:bg-gray-800/70 dark:text-gray-100 dark:border dark:border-gray-600';
                                if ($state === 'success') {
                                    $chipClass = 'text-green-800 bg-green-100 dark:bg-green-500/30 dark:text-green-100 dark:border dark:border-green-400/60';
                                } elseif ($state === 'error') {
                                    $chipClass = 'text-red-800 bg-red-100 dark:bg-red-500/30 dark:text-red-100 dark:border dark:border-red-400/60';
                                } elseif ($state === 'warning') {
                                    $chipClass = 'text-amber-800 bg-amber-100 dark:bg-amber-500/30 dark:text-amber-100 dark:border dark:border-amber-400/60';
                                }

                                $statusLabel = $status['label'] ?? (!empty($carrierItem['registration_required']) ? __('Pending') : __('Not required'));
                                $domain = $carrierItem['sip_domain'] ?? '—';
                                $port = !empty($carrierItem['sip_port']) ? ':'.$carrierItem['sip_port'] : '';
                                $transport = strtoupper($carrierItem['transport'] ?? 'udp');

                                // ✅ NEW: Outbound Proxy (display)
                                $outboundProxy = $carrierItem['outbound_proxy'] ?? '—';
                            ?>

                            <tr class="<?php echo e($loop->last ? '' : 'border-b border-gray-100 dark:border-gray-800'); ?>">
                                <td class="px-5 py-4 sm:px-6">
                                    <?php echo e($carrierItem['name'] ?? '—'); ?>

                                </td>

                                <td class="px-5 py-4 sm:px-6">
                                    <?php echo e($carrierItem['default_caller_id'] ?? '—'); ?>

                                </td>

                                <td class="px-5 py-4 sm:px-6">
                                    <?php echo e($domain); ?><?php echo e($port); ?>

                                </td>

                                <td class="px-5 py-4 sm:px-6">
                                    <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full dark:bg-gray-800 dark:text-white">
                                        <?php echo e($transport); ?>

                                    </span>
                                </td>

                                
                                <td class="px-5 py-4 sm:px-6">
                                    <?php echo e($outboundProxy); ?>

                                </td>

                                <td class="px-5 py-4 sm:px-6">
                                    <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium rounded-full <?php echo e($chipClass); ?>">
                                        <?php echo e($statusLabel); ?>

                                    </span>

                                    <?php if(!empty($status['detail'])): ?>
                                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            <?php echo e($status['detail']); ?>

                                        </div>
                                    <?php endif; ?>

                                    <?php if(!empty($carrierItem['registration_username'])): ?>
                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            <?php echo e($carrierItem['registration_username']); ?>

                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="px-5 py-4 sm:px-6">
                                    <?php if(!empty($carrierItem['prefixes'])): ?>
                                        <div class="flex flex-col gap-2">
                                            <?php $__currentLoopData = $carrierItem['prefixes']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prefix): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full dark:bg-gray-800 dark:text-white">
                                                    <?php echo e($prefix['prefix'] ?: '—'); ?> · <?php echo e($prefix['callerId'] ?? '—'); ?>

                                                </span>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full dark:bg-gray-800 dark:text-white">
                                            —
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <?php if($isAdmin): ?>
                                    <td class="px-5 py-4 sm:px-6 text-right">
                                        <div class="flex justify-end">
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
                                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('carrier.edit')): ?>
                                                <?php if (isset($component)) { $__componentOriginald560e04bcb617ec3f965b99513409ac6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald560e04bcb617ec3f965b99513409ac6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.buttons.action-item','data' => ['href' => route('admin.carrier.edit', $carrierItem['id']),'icon' => 'pencil','label' => __('Edit')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('buttons.action-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.carrier.edit', $carrierItem['id'])),'icon' => 'pencil','label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Edit'))]); ?>
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
                                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('carrier.delete')): ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modals.confirm-delete','data' => ['id' => 'delete-carrier-'.e($carrierItem['id']).'','title' => ''.e(__('Delete Carrier')).'','content' => ''.e(__('Are you sure you want to delete this carrier?')).'','formId' => 'delete-carrier-form-'.e($carrierItem['id']).'','formAction' => ''.e(route('admin.carrier.destroy', $carrierItem['id'])).'','modalTrigger' => 'deleteModalOpen','cancelButtonText' => ''.e(__('No, cancel')).'','confirmButtonText' => ''.e(__('Yes, delete')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modals.confirm-delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'delete-carrier-'.e($carrierItem['id']).'','title' => ''.e(__('Delete Carrier')).'','content' => ''.e(__('Are you sure you want to delete this carrier?')).'','formId' => 'delete-carrier-form-'.e($carrierItem['id']).'','formAction' => ''.e(route('admin.carrier.destroy', $carrierItem['id'])).'','modalTrigger' => 'deleteModalOpen','cancelButtonText' => ''.e(__('No, cancel')).'','confirmButtonText' => ''.e(__('Yes, delete')).'']); ?>
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
                                        </div>
                                    </td>
                                <?php endif; ?>

                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                
                                <td colspan="<?php echo e($isAdmin ? 8 : 7); ?>" class="text-center py-6">
                                    <p class="text-gray-500 dark:text-gray-400"><?php echo e(__('No carrier found')); ?></p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if($carrier instanceof \Illuminate\Contracts\Pagination\Paginator): ?>
                    <div class="my-4 px-4 sm:px-6">
                        <?php echo e($carrier->links()); ?>

                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/carrier/index.blade.php ENDPATH**/ ?>