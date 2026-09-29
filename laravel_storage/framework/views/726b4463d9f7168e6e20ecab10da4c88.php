<?php $__env->startSection('title'); ?>
    <?php echo e($breadcrumbs['title']); ?> | <?php echo e(config('app.name')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    <?php echo $__env->make('backend.pages.dialer.nightwave-form-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('admin-content'); ?>
<?php
    abort_unless(auth()->check() && auth()->user()->can('carrier.edit'), 403);
?>
<div class="connectpro-admin-page connectpro-record-form-page p-4 md:p-6">
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

    <?php echo ld_apply_filters('carriers_edit_after_breadcrumbs', '', $carrier); ?>


    <div class="connectpro-record-form-layout">
        <div class="connectpro-record-form-card rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="connectpro-record-form-body p-5 space-y-6 border-t border-gray-100 dark:border-gray-800 sm:p-6"
                 x-data="{ registrationRequired: <?php echo e(old('registrationRequired', !empty($carrier['registration_required'])) ? 'true' : 'false'); ?> }">
                <h2 class="connectpro-record-form-heading"><?php echo e(__('Details')); ?></h2>
                <form method="POST" action="<?php echo e(route('admin.carrier.update', $carrier['id'])); ?>" class="space-y-6">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>

                    <div class="connectpro-record-form-fields grid grid-cols-1 gap-6 sm:grid-cols-2">
                        
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                <?php echo e(__('Name')); ?> *
                            </label>
                            <input type="text" name="name" id="name" required
                                   value="<?php echo e(old('name', $carrier['name'] ?? '')); ?>"
                                   class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        </div>

                        
                        <div>
                            <label for="callerId" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                <?php echo e(__('Default Caller ID')); ?>

                            </label>
                            <input type="text" name="callerId" id="callerId"
                                   value="<?php echo e(old('callerId', $carrier['default_caller_id'] ?? '')); ?>"
                                   class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        </div>

                        
                        <div class="sm:col-span-2">
                            <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-400">
                                <input type="hidden" name="callerIdRequired" value="0">
                                <input type="checkbox" name="callerIdRequired" value="1"
                                       <?php echo e(old('callerIdRequired', !empty($carrier['caller_id_required']) ? '1' : '0') === '1' ? 'checked' : ''); ?>

                                       class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <span><?php echo e(__('Requires Caller ID')); ?></span>
                            </label>
                        </div>

                        
                        <div>
                            <label for="prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                <?php echo e(__('Prefix')); ?> (<?php echo e(__('optional')); ?>)
                            </label>
                            <input type="text" name="prefix" id="prefix"
                                   value="<?php echo e(old('prefix', $carrier['prefixes'][0]['prefix'] ?? '')); ?>"
                                   placeholder="<?php echo e(__('e.g. 100')); ?>"
                                   class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        </div>

                        
                        <div>
                            <label for="transport" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                <?php echo e(__('Transport')); ?>

                            </label>
                            <?php ($selectedTransport = old('transport', $carrier['transport'] ?? 'udp')); ?>
                            <select name="transport" id="transport"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value="udp" <?php echo e($selectedTransport === 'udp' ? 'selected' : ''); ?>>UDP</option>
                                <option value="tcp" <?php echo e($selectedTransport === 'tcp' ? 'selected' : ''); ?>>TCP</option>
                                <option value="tls" <?php echo e($selectedTransport === 'tls' ? 'selected' : ''); ?>>TLS</option>
                            </select>
                        </div>

                        
                        <div>
                            <label for="outboundProxy" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                <?php echo e(__('Outbound Proxy')); ?> (<?php echo e(__('optional')); ?>)
                            </label>
                            <input type="text" name="outboundProxy" id="outboundProxy"
                                   value="<?php echo e(old('outboundProxy', $carrier['outbound_proxy'] ?? '')); ?>"
                                   placeholder="<?php echo e(__('proxy.provider.com:5060')); ?>"
                                   class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        </div>

                        
                        <div>
                            <label for="sipDomain" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                <?php echo e(__('Domain / IP')); ?> *
                            </label>
                            <input type="text" name="sipDomain" id="sipDomain" required
                                   value="<?php echo e(old('sipDomain', $carrier['sip_domain'] ?? '')); ?>"
                                   class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        </div>

                        
                        <div>
                            <label for="sipPort" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                <?php echo e(__('Port')); ?> *
                            </label>
                            <input type="number" name="sipPort" id="sipPort" required min="1" max="65535"
                                   value="<?php echo e(old('sipPort', $carrier['sip_port'] ?? 5062)); ?>"
                                   class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        </div>

                        
                        <div class="sm:col-span-2">
                            <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-400">
                                <input type="checkbox" name="registrationRequired" value="1"
                                       x-model="registrationRequired"
                                       <?php echo e(old('registrationRequired', !empty($carrier['registration_required']) ? '1' : '') ? 'checked' : ''); ?>

                                       class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <span><?php echo e(__('Requires Registration')); ?></span>
                            </label>
                        </div>

                        
                        <div class="sm:col-span-2" x-show="registrationRequired" x-cloak>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label for="registrationUsername" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        <?php echo e(__('Registration Username')); ?>

                                    </label>
                                    <input type="text" name="registrationUsername" id="registrationUsername"
                                           value="<?php echo e(old('registrationUsername', $carrier['registration_username'] ?? '')); ?>"
                                           class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                                </div>
                                <div>
                                    <label for="registrationPassword" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        <?php echo e(__('Registration Password')); ?>

                                    </label>
                                    <input type="password" name="registrationPassword" id="registrationPassword"
                                           value="<?php echo e(old('registrationPassword')); ?>"
                                           placeholder="<?php echo e(__('Leave blank to keep current')); ?>"
                                           class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="connectpro-record-form-actions mt-6 flex justify-start gap-4">
                        <button type="submit" class="btn-primary"><?php echo e(__('Save')); ?></button>
                        <a href="<?php echo e(route('admin.carrier.index')); ?>" class="btn-default"><?php echo e(__('Cancel')); ?></a>
                    </div>
                </form>

            </div>
        </div>
        <aside class="connectpro-record-form-context">
            <span class="connectpro-record-context-icon"><i class="bi bi-router"></i></span>
            <h2><?php echo e(__('Context & permissions')); ?></h2>
            <p class="mt-4"><?php echo e(__('Carrier changes affect routing for calls assigned to this provider.')); ?></p>
            <p class="mt-2"><?php echo e(__('Registration credentials retain their current value when the password is left blank.')); ?></p>
            <p class="mt-2"><?php echo e(__('All existing transport, proxy and caller ID fields are preserved.')); ?></p>
        </aside>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/carrier/edit.blade.php ENDPATH**/ ?>