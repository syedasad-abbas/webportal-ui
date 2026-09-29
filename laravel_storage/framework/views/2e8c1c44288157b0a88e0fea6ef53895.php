<?php $__env->startSection('title', __('Activity Logs') . ' | ' . config('app.name')); ?>

<?php $__env->startSection('admin-content'); ?>
<div class="connectpro-communication-page min-h-full bg-[#06111f] text-white">
    <?php echo $__env->make('backend.pages.dialer.contacts-header', ['title' => __('Activity Logs'), 'subtitle' => __('Contact updates, notes, labels and follow-up activity')], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="mx-auto max-w-[1100px] p-4 sm:p-6">
        <section class="rounded-2xl border border-[#294158] bg-[#091827] p-4 shadow-2xl shadow-black/20 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#294158] pb-4">
                <div><h2 class="text-lg font-semibold"><?php echo e(__('Recent customer activity')); ?></h2><p class="mt-1 text-xs text-slate-400"><?php echo e(__('A shared timeline across your contact book')); ?></p></div>
                <span class="rounded-full border border-blue-500/30 bg-blue-500/10 px-3 py-1 text-xs font-semibold text-blue-300"><?php echo e($activities->total()); ?> <?php echo e(__('events')); ?></span>
            </div>
            <div class="relative mt-5 space-y-1 before:absolute before:bottom-6 before:left-5 before:top-6 before:w-px before:bg-[#294158]">
                <?php $__empty_1 = true; $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $icons = ['comment_added' => ['bi-chat-left-text', 'bg-violet-600'], 'contact_created' => ['bi-person-plus', 'bg-blue-600'], 'contact_updated' => ['bi-pencil', 'bg-cyan-600'], 'label_added' => ['bi-tag', 'bg-emerald-600'], 'label_removed' => ['bi-tag', 'bg-slate-600'], 'flag_added' => ['bi-flag-fill', 'bg-amber-500'], 'flag_removed' => ['bi-flag', 'bg-slate-600']];
                        [$icon, $color] = $icons[$activity->action] ?? ['bi-activity', 'bg-blue-600'];
                    ?>
                    <article class="relative flex gap-4 rounded-xl px-1 py-4 transition hover:bg-white/[.025] sm:px-3">
                        <span class="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-4 border-[#091827] <?php echo e($color); ?> text-white"><i class="bi <?php echo e($icon); ?>"></i></span>
                        <div class="min-w-0 flex-1 border-b border-[#1d3246] pb-4">
                            <div class="flex flex-wrap items-start justify-between gap-2"><div><a href="<?php echo e($activity->contact ? route('admin.contacts.show', $activity->contact) : '#'); ?>" class="font-semibold text-white hover:text-blue-400"><?php echo e($activity->contact?->name ?? __('Deleted contact')); ?></a><span class="mx-2 text-slate-600">•</span><span class="text-sm text-slate-400"><?php echo e($activity->contact?->company); ?></span></div><time class="text-xs text-slate-500"><?php echo e($activity->created_at?->diffForHumans()); ?></time></div>
                            <p class="mt-1 text-sm text-slate-300"><?php echo e($activity->description); ?></p>
                            <p class="mt-1 text-[11px] text-slate-500"><?php echo e(__('By')); ?> <?php echo e($activity->user?->external_name ?: $activity->user?->email ?: __('System')); ?> · <?php echo e($activity->created_at?->format('M j, Y \a\t g:i A')); ?></p>
                        </div>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="py-16 text-center text-sm text-slate-400"><?php echo e(__('No contact activity has been recorded yet.')); ?></div>
                <?php endif; ?>
            </div>
        </section>
        <div class="connectpro-pagination mt-4"><?php echo e($activities->links()); ?></div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/dialer/contacts-activity.blade.php ENDPATH**/ ?>