<header class="connectpro-page-header border-b border-[#20364c] bg-[#071526]/95 backdrop-blur-xl">
    <!-- Top bar navigation tabs -->
    <nav class="connectpro-reference-nav hidden items-center gap-2 px-4 py-3 lg:flex" aria-label="<?php echo e(__('Contacts navigation')); ?>">
        <a href="<?php echo e(route('admin.contacts.index')); ?>" class="connectpro-reference-nav-active"><?php echo e(__('Contacts')); ?></a>
        <a href="<?php echo e(route('admin.dialer.index')); ?>"><?php echo e(__('Dialpad')); ?></a>
        <a href="<?php echo e(route('admin.contacts.call-history')); ?>"><?php echo e(__('History')); ?></a>
        <a href="<?php echo e(route('admin.contacts.activity')); ?>"><?php echo e(__('Activity')); ?></a>
        <a href="#"><?php echo e(__('Reports')); ?></a>
    </nav>
</header>
<?php /**PATH /var/www/html/resources/views/backend/pages/dialer/contacts-header.blade.php ENDPATH**/ ?>