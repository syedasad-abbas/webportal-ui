<div class="hidden md:block">
    <table class="w-full text-sm text-gray-700 dark:text-gray-300">
        <thead class="bg-gray-50 dark:bg-gray-800 text-left">
            <tr class="border-b border-gray-100 dark:border-gray-700">
                <th class="px-5 py-3 font-semibold"><?php echo e(__('Caller ID')); ?></th>
                <th class="px-5 py-3 font-semibold"><?php echo e(__('Destination')); ?></th>
                <th class="px-5 py-3 font-semibold"><?php echo e(__('User')); ?></th>
                <th class="px-5 py-3 font-semibold"><?php echo e(__('Date & Time')); ?></th>
                <th class="px-5 py-3 font-semibold"><?php echo e(__('Duration')); ?></th>
                <th class="px-5 py-3 font-semibold"><?php echo e(__('Recording')); ?></th>
                <th class="px-5 py-3 font-semibold text-right"><?php echo e(__('Actions')); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $recordings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recording): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="border-b border-gray-100 dark:border-gray-800">
                    <td class="px-5 py-4"><?php echo e($recording->caller_id ?? '—'); ?></td>
                    <td class="px-5 py-4"><?php echo e($recording->destination ?? '—'); ?></td>
                    <td class="px-5 py-4"><?php echo e($recording->user->external_name ?? $recording->user->name ?? '—'); ?></td>
                    <td class="px-5 py-4"><?php echo e($recording->created_at?->format('Y-m-d H:i') ?? '—'); ?></td>
                    <td class="px-5 py-4">
                        <?php if($recording->duration_seconds): ?>
                            <?php echo e(gmdate('i:s', $recording->duration_seconds)); ?>

                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-4">
                        <?php if($recording->recording_path): ?>
                            <div class="flex flex-col gap-2">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-white">
                                    <?php echo e(basename($recording->recording_path)); ?>

                                </span>
                                <audio controls preload="none" class="w-full md:w-56">
                                    <source src="<?php echo e($recording->recording_url ?? ''); ?>">
                                </audio>
                            </div>
                        <?php else: ?>
                            <span class="text-gray-400 dark:text-gray-500"><?php echo e(__('No file')); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-4 text-right">
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
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('recording.download')): ?>
                                <?php if (isset($component)) { $__componentOriginald560e04bcb617ec3f965b99513409ac6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald560e04bcb617ec3f965b99513409ac6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.buttons.action-item','data' => ['href' => route('admin.recordings.download', $recording),'icon' => 'download','label' => __('Download')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('buttons.action-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.recordings.download', $recording)),'icon' => 'download','label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Download'))]); ?>
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

                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('recording.delete')): ?>
                                <form id="delete-recording-<?php echo e($recording->id); ?>" action="<?php echo e(route('admin.recordings.destroy', $recording)); ?>" method="POST" class="hidden">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                </form>
                                <?php if (isset($component)) { $__componentOriginald560e04bcb617ec3f965b99513409ac6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald560e04bcb617ec3f965b99513409ac6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.buttons.action-item','data' => ['type' => 'button','icon' => 'trash','class' => 'text-red-600 dark:text-red-400','label' => __('Delete'),'onClick' => 'event.preventDefault(); if(confirm(\''.e(__('Delete this recording?')).'\')) document.getElementById(\'delete-recording-'.e($recording->id).'\').submit();']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('buttons.action-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','icon' => 'trash','class' => 'text-red-600 dark:text-red-400','label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Delete')),'onClick' => 'event.preventDefault(); if(confirm(\''.e(__('Delete this recording?')).'\')) document.getElementById(\'delete-recording-'.e($recording->id).'\').submit();']); ?>
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
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="7" class="px-5 py-6 text-center text-gray-500 dark:text-gray-400">
                        <?php echo e(__('No recordings found.')); ?>

                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="md:hidden space-y-4">
    <?php $__empty_1 = true; $__currentLoopData = $recordings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recording): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                        <?php echo e($recording->caller_id ?? '—'); ?> → <?php echo e($recording->destination ?? '—'); ?>

                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        <?php echo e($recording->user->external_name ?? $recording->user->name ?? '—'); ?>

                    </p>
                </div>
                <div class="flex shrink-0">
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
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('recording.download')): ?>
                        <?php if (isset($component)) { $__componentOriginald560e04bcb617ec3f965b99513409ac6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald560e04bcb617ec3f965b99513409ac6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.buttons.action-item','data' => ['href' => route('admin.recordings.download', $recording),'icon' => 'download','label' => __('Download')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('buttons.action-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.recordings.download', $recording)),'icon' => 'download','label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Download'))]); ?>
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

                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('recording.delete')): ?>
                        <form id="delete-recording-mobile-<?php echo e($recording->id); ?>" action="<?php echo e(route('admin.recordings.destroy', $recording)); ?>" method="POST" class="hidden">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                        </form>
                        <?php if (isset($component)) { $__componentOriginald560e04bcb617ec3f965b99513409ac6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald560e04bcb617ec3f965b99513409ac6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.buttons.action-item','data' => ['type' => 'button','icon' => 'trash','class' => 'text-red-600 dark:text-red-400','label' => __('Delete'),'onClick' => 'event.preventDefault(); if(confirm(\''.e(__('Delete this recording?')).'\')) document.getElementById(\'delete-recording-mobile-'.e($recording->id).'\').submit();']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('buttons.action-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','icon' => 'trash','class' => 'text-red-600 dark:text-red-400','label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Delete')),'onClick' => 'event.preventDefault(); if(confirm(\''.e(__('Delete this recording?')).'\')) document.getElementById(\'delete-recording-mobile-'.e($recording->id).'\').submit();']); ?>
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
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3 text-xs text-gray-600 dark:text-gray-300">
                <div>
                    <span class="block text-gray-400 dark:text-gray-500"><?php echo e(__('Date & Time')); ?></span>
                    <?php echo e($recording->created_at?->format('Y-m-d H:i') ?? '—'); ?>

                </div>
                <div>
                    <span class="block text-gray-400 dark:text-gray-500"><?php echo e(__('Duration')); ?></span>
                    <?php if($recording->duration_seconds): ?>
                        <?php echo e(gmdate('i:s', $recording->duration_seconds)); ?>

                    <?php else: ?>
                        —
                    <?php endif; ?>
                </div>
            </div>
            <?php if($recording->recording_path): ?>
                <div class="mt-3">
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-white">
                        <?php echo e(basename($recording->recording_path)); ?>

                    </span>
                    <audio controls preload="none" class="w-full mt-2">
                        <source src="<?php echo e($recording->recording_url ?? ''); ?>">
                    </audio>
                </div>
            <?php else: ?>
                <span class="text-gray-400 dark:text-gray-500 text-xs mt-2"><?php echo e(__('No file')); ?></span>
            <?php endif; ?>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 p-6 text-center text-sm text-gray-500 dark:text-gray-400">
            <?php echo e(__('No recordings found.')); ?>

        </div>
    <?php endif; ?>
</div>
<?php /**PATH /var/www/html/resources/views/backend/pages/recordings/_table.blade.php ENDPATH**/ ?>