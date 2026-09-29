<?php ($editing = isset($contact) && $contact); ?>

<?php $__env->startSection('title', ($editing ? __('Edit contact') : __('Add contact')) . ' | ' . config('app.name')); ?>

<?php $__env->startPush('styles'); ?>
    <?php echo $__env->make('backend.pages.dialer.nightwave-form-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('admin-content'); ?>
<div class="connectpro-communication-page min-h-full bg-[#06111f] text-white">
    <?php echo $__env->make('backend.pages.dialer.contacts-header', [
        'title' => $editing ? __('Edit contact') : __('Add contact'),
        'subtitle' => __('Customer record and ownership'),
        'showAddContact' => false,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="connectpro-admin-page connectpro-record-form-page p-4 md:p-6">
        <?php if (isset($component)) { $__componentOriginala357015bb12b9e55fb70db16bfbde37b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala357015bb12b9e55fb70db16bfbde37b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.messages','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('messages'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala357015bb12b9e55fb70db16bfbde37b)): ?>
<?php $attributes = $__attributesOriginala357015bb12b9e55fb70db16bfbde37b; ?>
<?php unset($__attributesOriginala357015bb12b9e55fb70db16bfbde37b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala357015bb12b9e55fb70db16bfbde37b)): ?>
<?php $component = $__componentOriginala357015bb12b9e55fb70db16bfbde37b; ?>
<?php unset($__componentOriginala357015bb12b9e55fb70db16bfbde37b); ?>
<?php endif; ?>

        <div class="connectpro-record-form-layout">
            <section class="connectpro-record-form-card">
                <div class="connectpro-record-form-body">
                    <h2 class="connectpro-record-form-heading"><?php echo e(__('Details')); ?></h2>

                    <form method="POST" action="<?php echo e($editing ? route('admin.contacts.update', $contact) : route('admin.contacts.store')); ?>" class="space-y-6">
                        <?php echo csrf_field(); ?>
                        <?php if($editing): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

                        <div class="connectpro-record-form-fields">
                            <div>
                                <label for="name" class="mb-2 block"><?php echo e(__('Name')); ?> *</label>
                                <input id="name" name="name" type="text" required maxlength="255" value="<?php echo e(old('name', $contact?->name)); ?>" placeholder="<?php echo e(__('Contact name')); ?>" class="h-11 px-4 text-sm">
                                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-red-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div>
                                <label for="company" class="mb-2 block"><?php echo e(__('Company')); ?></label>
                                <input id="company" name="company" type="text" maxlength="255" value="<?php echo e(old('company', $contact?->company)); ?>" placeholder="<?php echo e(__('Company')); ?>" class="h-11 px-4 text-sm">
                                <?php $__errorArgs = ['company'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-red-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div>
                                <label for="phone" class="mb-2 block"><?php echo e(__('Phone')); ?> *</label>
                                <input id="phone" name="phone" type="tel" required maxlength="40" value="<?php echo e(old('phone', $contact?->phone)); ?>" placeholder="<?php echo e(__('Phone number')); ?>" class="h-11 px-4 text-sm">
                                <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-red-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div>
                                <label for="email" class="mb-2 block"><?php echo e(__('Email')); ?></label>
                                <input id="email" name="email" type="email" maxlength="255" value="<?php echo e(old('email', $contact?->email)); ?>" placeholder="<?php echo e(__('contact@example.com')); ?>" class="h-11 px-4 text-sm">
                                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-red-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div>
                                <label for="avatar_url" class="mb-2 block"><?php echo e(__('Profile image URL')); ?></label>
                                <input id="avatar_url" name="avatar_url" type="url" maxlength="2048" value="<?php echo e(old('avatar_url', $contact?->avatar_url)); ?>" placeholder="https://example.com/avatar.jpg" class="h-11 px-4 text-sm">
                                <?php $__errorArgs = ['avatar_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-red-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="connectpro-record-form-actions">
                            <a href="<?php echo e($editing ? route('admin.contacts.show', $contact) : route('admin.contacts.index')); ?>" class="btn-default"><?php echo e(__('Cancel')); ?></a>
                            <button type="submit" class="btn-primary"><?php echo e($editing ? __('Save changes') : __('Add contact')); ?></button>
                        </div>
                    </form>
                </div>
            </section>

            <aside class="connectpro-record-form-context">
                <span class="connectpro-record-context-icon"><i class="bi bi-shield-check"></i></span>
                <h2><?php echo e(__('Context & permissions')); ?></h2>
                <p class="mt-4"><?php echo e(__('Changes are recorded in the contact activity history.')); ?></p>
                <p class="mt-2"><?php echo e(__('Your existing role permissions continue to control who can create or edit contacts.')); ?></p>
                <p class="mt-2"><?php echo e(__('Call notes, labels and flags are managed from the contact workspace.')); ?></p>
            </aside>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/dialer/contacts-form.blade.php ENDPATH**/ ?>