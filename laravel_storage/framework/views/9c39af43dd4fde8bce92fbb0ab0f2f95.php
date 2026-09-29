<?php $__env->startSection('title', __('Call History') . ' | ' . config('app.name')); ?>

<?php $__env->startSection('admin-content'); ?>
<div class="connectpro-communication-page min-h-full bg-[#06111f] text-white">
    <?php echo $__env->make('backend.pages.dialer.contacts-header', ['title' => __('Call History'), 'subtitle' => __('Review recent inbound and outbound conversations')], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="mx-auto max-w-[1180px] p-4 sm:p-6">
        <section class="overflow-hidden rounded-2xl border border-[#294158] bg-[#091827] shadow-2xl shadow-black/20">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#294158] p-4 sm:px-5">
                <div><h2 class="text-lg font-semibold"><?php echo e(__('Recent Calls')); ?></h2><p class="mt-1 text-xs text-slate-400"><?php echo e(__('All call activity from your connected lines')); ?></p></div>
                <span class="rounded-full border border-[#365068] px-3 py-1 text-xs text-slate-300"><?php echo e($calls->total()); ?> <?php echo e(__('calls')); ?></span>
            </div>
            <div class="divide-y divide-[#1e3347]">
                <?php $__empty_1 = true; $__currentLoopData = $calls; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $call): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $contact = $call->getRelation('matchedContact');
                        $number = $call->direction === 'inbound' ? $call->caller_id : $call->destination;
                        $isMissed = in_array(strtolower((string) $call->status), ['failed', 'missed', 'declined', 'busy', 'no_answer']);
                    ?>
                    <article class="grid items-center gap-3 px-4 py-4 transition hover:bg-white/[.025] sm:grid-cols-[48px_minmax(160px,1fr)_140px_110px_120px_auto] sm:px-5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full <?php echo e($isMissed ? 'bg-red-500/10 text-red-400' : 'bg-blue-500/10 text-blue-400'); ?>"><i class="bi <?php echo e($call->direction === 'inbound' ? 'bi-telephone-inbound' : 'bi-telephone-outbound'); ?> text-lg"></i></span>
                        <div class="min-w-0"><p class="truncate font-semibold"><?php echo e($contact?->name ?: $number ?: __('Unknown caller')); ?></p><p class="truncate text-xs text-slate-400"><?php echo e($contact?->company ?: $number); ?></p></div>
                        <span class="text-sm capitalize text-slate-300"><?php echo e(str_replace('_', ' ', $call->direction ?: 'outbound')); ?></span>
                        <span class="text-sm <?php echo e($isMissed ? 'text-red-400' : 'text-emerald-400'); ?>"><?php echo e(ucfirst(str_replace('_', ' ', (string) $call->status))); ?></span>
                        <span class="text-sm text-slate-400"><?php echo e(gmdate('i:s', max(0, (int) $call->duration_seconds))); ?></span>
                        <div class="flex items-center justify-end gap-2"><span class="hidden text-xs text-slate-500 xl:inline"><?php echo e($call->created_at?->format('M j, g:i A')); ?></span><a href="<?php echo e(route('admin.dialer.index', ['destination' => $number])); ?>" class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-600 text-white hover:bg-emerald-500"><i class="bi bi-telephone-fill"></i></a></div>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="px-6 py-16 text-center text-sm text-slate-400"><?php echo e(__('No calls have been recorded yet.')); ?></div>
                <?php endif; ?>
            </div>
        </section>
        <div class="connectpro-pagination mt-4"><?php echo e($calls->links()); ?></div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/dialer/contacts-call-history.blade.php ENDPATH**/ ?>