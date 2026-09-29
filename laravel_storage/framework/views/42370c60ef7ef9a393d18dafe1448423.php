<?php $__env->startSection('title'); ?>
    <?php echo e($breadcrumbs['title']); ?> | <?php echo e(config('app.name')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    <?php echo $__env->make('backend.pages.dialer.nightwave-form-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('admin-content'); ?>
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

        <?php echo ld_apply_filters('users_after_breadcrumbs', ''); ?>


        <div class="connectpro-record-form-layout">
            <div class="connectpro-record-form-card rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="connectpro-record-form-body p-5 space-y-6 border-t border-gray-100 dark:border-gray-800 sm:p-6">
                    <h2 class="connectpro-record-form-heading"><?php echo e(__('Details')); ?></h2>
                    <form action="<?php echo e(route('admin.users.store')); ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <?php echo csrf_field(); ?>

                        <div class="connectpro-record-form-fields grid grid-cols-1 gap-6 sm:grid-cols-2">

                            
                            <div>
                                <label for="external_name" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('External Name')); ?>

                                </label>
                                <input
                                    type="text"
                                    name="external_name"
                                    id="external_name"
                                    required
                                    value="<?php echo e(old('external_name')); ?>"
                                    placeholder="<?php echo e(__('Displayed to others')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="internal_name" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Internal Name')); ?>

                                </label>
                                <input
                                    type="text"
                                    name="internal_name"
                                    id="internal_name"
                                    required
                                    value="<?php echo e(old('internal_name')); ?>"
                                    placeholder="<?php echo e(__('For internal reference')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Email')); ?>

                                </label>
                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    required
                                    value="<?php echo e(old('email')); ?>"
                                    placeholder="<?php echo e(__('user@example.com')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Password')); ?>

                                </label>
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    required
                                    placeholder="<?php echo e(__('Temporary password')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Confirm Password')); ?>

                                </label>
                                <input
                                    type="password"
                                    name="password_confirmation"
                                    id="password_confirmation"
                                    required
                                    placeholder="<?php echo e(__('Re-enter password')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="carrierId" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Carrier')); ?>

                                </label>
                                <select
                                    name="carrierId"
                                    id="carrierId"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >
                                    <option value=""><?php echo e(__('Default')); ?></option>
                                    <?php $__currentLoopData = $carriers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $carrier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($carrier['id']); ?>" <?php echo e(old('carrierId') == $carrier['id'] ? 'selected' : ''); ?>>
                                            <?php echo e($carrier['name']); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>

                            
                            <div>
                                <label for="sip_username" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('SIP Username')); ?>

                                </label>
                                <input
                                    type="text"
                                    name="sip_username"
                                    id="sip_username"
                                    value="<?php echo e(old('sip_username')); ?>"
                                    placeholder="<?php echo e(__('e.g. 1001')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="sip_password" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('SIP Password')); ?>

                                </label>
                                <input
                                    type="password"
                                    name="sip_password"
                                    id="sip_password"
                                    value="<?php echo e(old('sip_password')); ?>"
                                    placeholder="<?php echo e(__('SIP secret')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <?php if (isset($component)) { $__componentOriginald9e3f05661d2b31b6e566c65f0284ef2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald9e3f05661d2b31b6e566c65f0284ef2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inputs.combobox','data' => ['name' => 'roles[]','label' => ''.e(__('Assign Roles')).'','placeholder' => ''.e(__('Select Roles')).'','options' => collect($roles)->map(fn($name, $id) => ['value' => $name, 'label' => ucfirst($name)])->values()->toArray(),'selected' => old('roles', []),'multiple' => true,'searchable' => false]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inputs.combobox'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'roles[]','label' => ''.e(__('Assign Roles')).'','placeholder' => ''.e(__('Select Roles')).'','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(collect($roles)->map(fn($name, $id) => ['value' => $name, 'label' => ucfirst($name)])->values()->toArray()),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('roles', [])),'multiple' => true,'searchable' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald9e3f05661d2b31b6e566c65f0284ef2)): ?>
<?php $attributes = $__attributesOriginald9e3f05661d2b31b6e566c65f0284ef2; ?>
<?php unset($__attributesOriginald9e3f05661d2b31b6e566c65f0284ef2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald9e3f05661d2b31b6e566c65f0284ef2)): ?>
<?php $component = $__componentOriginald9e3f05661d2b31b6e566c65f0284ef2; ?>
<?php unset($__componentOriginald9e3f05661d2b31b6e566c65f0284ef2); ?>
<?php endif; ?>
                            </div>

                            
                            <div>
                                <label for="is_active" class="block text-sm font-medium text-gray-700 dark:text-gray-400"><?php echo e(__('Status')); ?></label>
                                <select name="is_active" id="is_active" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="1" <?php echo e(old('is_active', '1') == '1' ? 'selected' : ''); ?>><?php echo e(__('Enabled')); ?></option>
                                    <option value="0" <?php echo e(old('is_active') == '0' ? 'selected' : ''); ?>><?php echo e(__('Disabled')); ?></option>
                                </select>
                            </div>

                        </div>

                        <div class="connectpro-record-form-actions mt-6 flex justify-start gap-4">
                            <button type="submit" class="btn-primary"><?php echo e(__('Create user')); ?></button>
                            <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn-default"><?php echo e(__('Cancel')); ?></a>
                        </div>
                    </form>
                </div>
            </div>
            <aside class="connectpro-record-form-context">
                <span class="connectpro-record-context-icon"><i class="bi bi-shield-check"></i></span>
                <h2><?php echo e(__('Context & permissions')); ?></h2>
                <p class="mt-4"><?php echo e(__('User access is controlled by assigned roles and status.')); ?></p>
                <p class="mt-2"><?php echo e(__('SIP credentials connect this user to the calling service.')); ?></p>
                <p class="mt-2"><?php echo e(__('Sensitive account changes remain subject to your existing permissions.')); ?></p>
            </aside>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/users/create.blade.php ENDPATH**/ ?>