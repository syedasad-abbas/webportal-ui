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
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="p-5 space-y-6 border-t border-gray-100 dark:border-gray-800 sm:p-6">
                    <form action="<?php echo e(route('admin.leads.update', $lead->id)); ?>" method="POST" class="space-y-6">
                        <?php echo method_field('PUT'); ?>
                        <?php echo csrf_field(); ?>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">

                            
                            <div>
                                <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Date')); ?>

                                </label>
                                <input
                                    type="date"
                                    name="date"
                                    id="date"
                                    value="<?php echo e(old('date', $lead->date?->format('Y-m-d') ?? '')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >
                            </div>

                            
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Status')); ?>

                                </label>
                                <select name="status" id="status" 
                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="new" <?php echo e(old('status', $lead->status) == 'new' ? 'selected' : ''); ?>><?php echo e(__('New')); ?></option>
                                    <option value="contacted" <?php echo e(old('status', $lead->status) == 'contacted' ? 'selected' : ''); ?>><?php echo e(__('Contacted')); ?></option>
                                    <option value="qualified" <?php echo e(old('status', $lead->status) == 'qualified' ? 'selected' : ''); ?>><?php echo e(__('Qualified')); ?></option>
                                    <option value="closed" <?php echo e(old('status', $lead->status) == 'closed' ? 'selected' : ''); ?>><?php echo e(__('Closed')); ?></option>
                                </select>
                            </div>

                            
                            <div>
                                <label for="patient_name" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Patient Name')); ?>

                                </label>
                                <input
                                    type="text"
                                    name="patient_name"
                                    id="patient_name"
                                    required
                                    value="<?php echo e(old('patient_name', $lead->patient_name ?? '')); ?>"
                                    placeholder="<?php echo e(__('Full name of patient')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Address')); ?>

                                </label>
                                <input
                                    type="text"
                                    name="address"
                                    id="address"
                                    value="<?php echo e(old('address', $lead->address ?? '')); ?>"
                                    placeholder="<?php echo e(__('Patient address')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="patient_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Patient Phone')); ?>

                                </label>
                                <input
                                    type="tel"
                                    name="patient_phone"
                                    id="patient_phone"
                                    value="<?php echo e(old('patient_phone', $lead->patient_phone ?? '')); ?>"
                                    placeholder="<?php echo e(__('+1 (555) 123-4567')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="patient_dob" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Patient DOB')); ?>

                                </label>
                                <input
                                    type="date"
                                    name="patient_dob"
                                    id="patient_dob"
                                    value="<?php echo e(old('patient_dob', $lead->patient_dob?->format('Y-m-d') ?? '')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >
                            </div>

                            
                            <div>
                                <label for="sizes" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Sizes')); ?>

                                </label>
                                <input
                                    type="text"
                                    name="sizes"
                                    id="sizes"
                                    value="<?php echo e(old('sizes', $lead->sizes ?? '')); ?>"
                                    placeholder="<?php echo e(__('e.g. 34D, 40R')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="insurance" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Insurance')); ?>

                                </label>
                                <input
                                    type="text"
                                    name="insurance"
                                    id="insurance"
                                    value="<?php echo e(old('insurance', $lead->insurance ?? '')); ?>"
                                    placeholder="<?php echo e(__('Primary Insurance')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="member_id" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Member ID')); ?>

                                </label>
                                <input
                                    type="text"
                                    name="member_id"
                                    id="member_id"
                                    value="<?php echo e(old('member_id', $lead->member_id ?? '')); ?>"
                                    placeholder="<?php echo e(__('Primary Member ID')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="secondary_member_id" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Member ID (Secondary Insurance)')); ?>

                                </label>
                                <input
                                    type="text"
                                    name="secondary_member_id"
                                    id="secondary_member_id"
                                    value="<?php echo e(old('secondary_member_id', $lead->secondary_member_id ?? '')); ?>"
                                    placeholder="<?php echo e(__('Secondary Member ID')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="products" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Products')); ?>

                                </label>
                                <input
                                    type="text"
                                    name="products"
                                    id="products"
                                    value="<?php echo e(old('products', $lead->products ?? '')); ?>"
                                    placeholder="<?php echo e(__('e.g. Breast Pump, Walker')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="doctor_name" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Doctor Name')); ?>

                                </label>
                                <input
                                    type="text"
                                    name="doctor_name"
                                    id="doctor_name"
                                    value="<?php echo e(old('doctor_name', $lead->doctor_name ?? '')); ?>"
                                    placeholder="<?php echo e(__('Referring Doctor')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="doctor_npi" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__("Doctor's NPI")); ?>

                                </label>
                                <input
                                    type="text"
                                    name="doctor_npi"
                                    id="doctor_npi"
                                    value="<?php echo e(old('doctor_npi', $lead->doctor_npi ?? '')); ?>"
                                    placeholder="<?php echo e(__('10-digit NPI number')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            
                            <div>
                                <label for="medications" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Medications')); ?>

                                </label>
                                <textarea
                                    name="medications"
                                    id="medications"
                                    rows="2"
                                    placeholder="<?php echo e(__('Current medications')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                ><?php echo e(old('medications', $lead->medications ?? '')); ?></textarea>
                            </div>

                            
                            <div>
                                <label for="treatments" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Treatments')); ?>

                                </label>
                                <textarea
                                    name="treatments"
                                    id="treatments"
                                    rows="2"
                                    placeholder="<?php echo e(__('Recommended treatments / procedures')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                ><?php echo e(old('treatments', $lead->treatments ?? '')); ?></textarea>
                            </div>

                            
                            <div>
                                <label for="dr_last_visit" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <?php echo e(__('Doctor Last Visit')); ?>

                                </label>
                                <input
                                    type="date"
                                    name="dr_last_visit"
                                    id="dr_last_visit"
                                    value="<?php echo e(old('dr_last_visit', $lead->dr_last_visit?->format('Y-m-d') ?? '')); ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >
                            </div>

                        </div>

                        <div class="mt-6 flex justify-start gap-4">
                            <button type="submit" class="btn-primary"><?php echo e(__('Update Lead')); ?></button>
                            <a href="<?php echo e(route('admin.leads.index')); ?>" class="btn-default"><?php echo e(__('Cancel')); ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/leads/edit.blade.php ENDPATH**/ ?>