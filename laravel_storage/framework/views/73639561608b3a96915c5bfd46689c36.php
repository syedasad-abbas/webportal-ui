<?php if(!empty(config('settings.google_tag_manager_script'))): ?>
    <?php echo config('settings.google_tag_manager_script'); ?>

<?php endif; ?>

<?php if(config('app.demo_mode', false)): ?>
    <script
        async
        src="https://www.googletagmanager.com/gtag/js?id=G-WWCRYQMHZ7"
    ></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() {
            dataLayer.push(arguments);
        }
        gtag("js", new Date());
        gtag("config", "G-WWCRYQMHZ7");
    </script>
<?php elseif(!empty(config('settings.google_analytics_script'))): ?>
    <?php echo config('settings.google_analytics_script'); ?>

<?php endif; ?>

<?php /**PATH /var/www/html/resources/views/backend/layouts/partials/integration-scripts.blade.php ENDPATH**/ ?>