<?php $__env->startSection('title'); ?>
    <?php echo e($breadcrumbs['title']); ?> | <?php echo e(config('app.name')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('admin-content'); ?>
    <div class="p-4 mx-auto max-w-7xl md:p-6">
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

        <div class="space-y-6">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('campaign.add')): ?>
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="p-5 space-y-6 border-t border-gray-100 dark:border-gray-800 sm:p-6">
                    <form action="<?php echo e(route('admin.campaigns.store')); ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <?php echo csrf_field(); ?>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">

                            
                            <div>
                                <label for="List id " class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('List id')); ?>

                                </label>
                                <input
                                    type="text"
                                    name="list_id"
                                    id="list_id"
                                    required
                                    value="<?php echo e(old('list_id')); ?>"
                                    placeholder="<?php echo e(__('Displayed to others')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="List_name" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('List Name')); ?>

                                </label>
                                <input
                                    type="text"
                                    name="list_name"
                                    id="list_name"
                                    required
                                    value="<?php echo e(old('list_name')); ?>"
                                    placeholder="<?php echo e(__('For internal reference')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="list_description" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('List Description')); ?>

                                </label>
                                <input
                                    type="text"
                                    name="list_description"
                                    id="list_description"
                                    required
                                    value="<?php echo e(old('list_description')); ?>"
                                    placeholder="<?php echo e(__('List Description')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="file" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('File')); ?>

                                </label>
                                <input
                                    type="file"
                                    name="file"
                                    id="file"
                                    accept=".csv,.xls,.xlsx"
                                    required
                                    placeholder="<?php echo e(__('File')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>


                        </div>

                        <div class="mt-6 flex justify-start gap-4">
                            <button type="submit" class="btn-primary"><?php echo e(__('Submit')); ?></button>
                            <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn-default"><?php echo e(__('Cancel')); ?></a>
                        </div>
                    </form>
                </div>
            </div>

            <?php endif; ?>
            <?php if($campaigns->isNotEmpty()): ?>
                <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                    <div class="p-5 space-y-4 border-t border-gray-100 dark:border-gray-800 sm:p-6">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                                <?php echo e(__('Uploaded campaigns')); ?>

                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?php echo e(__('Monitor import progress and errors in real time.')); ?>

                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-900/40 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3 text-left"><?php echo e(__('Campaign')); ?></th>
                                        <th class="px-4 py-3 text-left"><?php echo e(__('Description')); ?></th>
                                        <th class="px-4 py-3 text-left"><?php echo e(__('Status')); ?></th>
                                        <th class="px-4 py-3 text-left"><?php echo e(__('Progress')); ?></th>
                                        <th class="px-4 py-3 text-left"><?php echo e(__('Updated')); ?></th>
                                        <th class="px-4 py-3 text-left"><?php echo e(__('Action')); ?></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    <?php $__currentLoopData = $campaigns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $campaign): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $status = strtolower($campaign->import_status ?? 'pending');
                                            $total = (int) $campaign->total_rows;
                                            $imported = (int) $campaign->imported_rows;
                                            $percent = $total > 0 ? min(100, round(($imported / $total) * 100)) : 0;
                                            $statusLabel = \Illuminate\Support\Str::headline($campaign->import_status ?? 'pending');
                                            $statusClasses = match ($status) {
                                                'completed' => 'text-green-800 bg-green-100 dark:text-green-100 dark:bg-green-500/30 dark:border dark:border-green-400/60',
                                                'failed' => 'text-red-800 bg-red-100 dark:text-red-100 dark:bg-red-500/30 dark:border dark:border-red-400/60',
                                                'processing' => 'text-blue-800 bg-blue-100 dark:text-blue-100 dark:bg-blue-500/30 dark:border dark:border-blue-400/60',
                                                default => 'text-amber-800 bg-amber-100 dark:text-amber-100 dark:bg-amber-500/30 dark:border dark:border-amber-400/60',
                                            };
                                        ?>
                                        <tr
                                            data-campaign-row
                                            data-campaign-id="<?php echo e($campaign->id); ?>"
                                            data-status="<?php echo e($status); ?>"
                                            data-status-url="<?php echo e(route('admin.campaigns.status', $campaign)); ?>"
                                        >
                                            <td class="px-4 py-4 align-top">
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    <?php echo e($campaign->list_name); ?> <span class="text-gray-500 dark:text-gray-400">(<?php echo e($campaign->list_id); ?>)</span>
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    <?php echo e(__('Uploaded')); ?> <?php echo e(optional($campaign->created_at)->format('M j, Y H:i') ?? '—'); ?>

                                                </div>
                                            </td>
                                            <td class="px-4 py-4 align-top text-gray-700 dark:text-gray-300">
                                                <?php echo e($campaign->list_description); ?>

                                            </td>
                                            <td class="px-4 py-4 align-top">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold border <?php echo e($statusClasses); ?>"
                                                      data-status-text>
                                                    <?php echo e($statusLabel); ?>

                                                </span>
                                                <p class="mt-2 text-xs text-red-500 dark:text-red-300 <?php if(empty($campaign->import_error)): ?> hidden <?php endif; ?>"
                                                   data-status-error>
                                                    <?php echo e($campaign->import_error); ?>

                                                </p>
                                            </td>
                                            <td class="px-4 py-4 align-top text-sm text-gray-700 dark:text-gray-300">
                                                <div data-status-progress-text>
                                                    <?php echo e($imported); ?> / <?php echo e($total ?: '—'); ?>

                                                </div>
                                                <div class="mt-2 h-2 bg-gray-200 dark:bg-gray-800 rounded-full overflow-hidden">
                                                    <div
                                                        class="h-2 bg-blue-500 transition-all duration-300"
                                                        style="width: <?php echo e($percent); ?>%;"
                                                        data-status-progress-bar
                                                    ></div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 align-top text-xs text-gray-500 dark:text-gray-400">
                                                <?php echo e(optional($campaign->import_completed_at ?? $campaign->updated_at)->format('M j, Y H:i') ?? '—'); ?>

                                            </td>
                                            <td class="px-4 py-4 align-top">
                                                <div class="flex justify-center">
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
                                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('campaign.edit')): ?>
                                                        <?php if (isset($component)) { $__componentOriginald560e04bcb617ec3f965b99513409ac6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald560e04bcb617ec3f965b99513409ac6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.buttons.action-item','data' => ['href' => route('admin.campaigns.edit', $campaign),'icon' => 'pencil','label' => __('Edit')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('buttons.action-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.campaigns.edit', $campaign)),'icon' => 'pencil','label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Edit'))]); ?>
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
                                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('campaign.delete')): ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modals.confirm-delete','data' => ['id' => 'delete-campaign-'.e($campaign->id).'','title' => ''.e(__('Delete Campaign')).'','content' => ''.e(__('Are you sure you want to delete this campaign? This cannot be undone.')).'','formId' => 'delete-campaign-form-'.e($campaign->id).'','formAction' => ''.e(route('admin.campaigns.destroy', $campaign)).'','modalTrigger' => 'deleteModalOpen','cancelButtonText' => ''.e(__('No, cancel')).'','confirmButtonText' => ''.e(__('Yes, delete')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modals.confirm-delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'delete-campaign-'.e($campaign->id).'','title' => ''.e(__('Delete Campaign')).'','content' => ''.e(__('Are you sure you want to delete this campaign? This cannot be undone.')).'','formId' => 'delete-campaign-form-'.e($campaign->id).'','formAction' => ''.e(route('admin.campaigns.destroy', $campaign)).'','modalTrigger' => 'deleteModalOpen','cancelButtonText' => ''.e(__('No, cancel')).'','confirmButtonText' => ''.e(__('Yes, delete')).'']); ?>
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
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ACTIVE_STATUSES = ['pending', 'processing'];
    let pollRows = Array.from(document.querySelectorAll('[data-campaign-row]'))
        .filter((row) => ACTIVE_STATUSES.includes((row.dataset.status || '').toLowerCase()));

    if (!pollRows.length) {
        return;
    }

    const fetchStatus = async (row) => {
        const url = row.dataset.statusUrl;
        if (!url) {
            return;
        }

        try {
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            const data = await response.json();
            applyStatus(row, data);
        } catch (error) {
            const errorEl = row.querySelector('[data-status-error]');
            if (errorEl) {
                errorEl.textContent = error.message || '<?php echo e(__('Unable to fetch status')); ?>';
                errorEl.classList.remove('hidden');
            }
        }
    };

    const applyStatus = (row, payload) => {
        const status = (payload.status || payload.import_status || 'pending').toLowerCase();
        row.dataset.status = status;

        const statusText = row.querySelector('[data-status-text]');
        if (statusText) {
            const label = status.replace(/_/g, ' ');
            statusText.textContent = label.charAt(0).toUpperCase() + label.slice(1);
            statusText.classList.toggle('bg-green-100', status === 'completed');
            statusText.classList.toggle('text-green-800', status === 'completed');
            statusText.classList.toggle('bg-yellow-100', status === 'processing' || status === 'pending');
            statusText.classList.toggle('text-yellow-800', status === 'processing' || status === 'pending');
            statusText.classList.toggle('bg-red-100', status === 'failed');
            statusText.classList.toggle('text-red-800', status === 'failed');
        }

        const imported = payload.imported ?? payload.imported_rows ?? 0;
        const total = payload.total ?? payload.total_rows ?? 0;

        const progressText = row.querySelector('[data-status-progress-text]');
        if (progressText) {
            progressText.textContent = `${imported} / ${total || '—'}`;
        }

        const progressBar = row.querySelector('[data-status-progress-bar]');
        if (progressBar) {
            const percent = total > 0 ? Math.min(100, Math.round((imported / total) * 100)) : 0;
            progressBar.style.width = `${percent}%`;
        }

        const errorEl = row.querySelector('[data-status-error]');
        if (errorEl) {
            const errorMessage = payload.error || payload.import_error || '';
            if (errorMessage) {
                errorEl.textContent = errorMessage;
                errorEl.classList.remove('hidden');
            } else {
                errorEl.textContent = '';
                errorEl.classList.add('hidden');
            }
        }
    };

    const poll = () => {
        pollRows = pollRows.filter((row) => ACTIVE_STATUSES.includes((row.dataset.status || '').toLowerCase()));
        if (!pollRows.length) {
            return false;
        }
        pollRows.forEach(fetchStatus);
        return true;
    };

    poll();
    const interval = setInterval(() => {
        if (!poll()) {
            clearInterval(interval);
        }
    }, 5000);
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/Campaigns/index.blade.php ENDPATH**/ ?>