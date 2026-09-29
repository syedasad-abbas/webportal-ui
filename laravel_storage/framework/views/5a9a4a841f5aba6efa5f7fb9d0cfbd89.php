<!doctype html>
<html lang="en" class="antialiased">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', config('app.name')); ?></title>

    <script>
        (() => {
            const savedTheme = localStorage.getItem('darkMode');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', savedTheme === null ? prefersDark : savedTheme === 'true');
        })();
    </script>

    <link rel="icon" href="<?php echo e(config('settings.site_favicon') ?? asset('favicon.ico')); ?>" type="image/x-icon">

    <?php echo $__env->make('backend.layouts.partials.theme-colors', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->yieldContent('before_vite_build'); ?>

    <?php if(app()->environment('local')): ?>
        <?php echo app('Illuminate\Foundation\Vite')->reactRefresh(); ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/js/app.js', 'resources/css/app.css']); ?>
    <?php else: ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/js/app.js', 'resources/css/app.css'], 'build'); ?>
    <?php endif; ?>
    <?php echo $__env->yieldPushContent('styles'); ?>
    <?php echo $__env->yieldContent('before_head'); ?>

    <?php if(!empty(config('settings.global_custom_css'))): ?>
    <style>
        <?php echo config('settings.global_custom_css'); ?>

    </style>
    <?php endif; ?>

    <?php echo $__env->make('backend.layouts.partials.integration-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    
    <?php echo ld_apply_filters('admin_head', ''); ?>
</head>

<body x-data="{ 
    page: 'ecommerce', 
    loaded: true, 
    darkMode: document.documentElement.classList.contains('dark'),
    stickyMenu: false, 
    sidebarToggle: $persist(false), 
    scrollTop: false,
    toggleTheme() {
        this.darkMode = !this.darkMode;
    },
    applyTheme(value) {
        document.documentElement.classList.toggle('dark', value);
        localStorage.setItem('darkMode', JSON.stringify(value));
    }
}" 
x-init="
    applyTheme(darkMode);
    $watch('darkMode', value => applyTheme(value));
    $watch('sidebarToggle', value => localStorage.setItem('sidebarToggle', JSON.stringify(value)))
" 
:class="darkMode ? 'bg-gray-900' : 'bg-gray-50'">
    <!-- Preloader -->
    <div x-show="loaded" x-init="window.addEventListener('load', () => { setTimeout(() => loaded = false, 250) })"
        class="fixed left-0 top-0 z-999999 flex h-screen w-screen items-center justify-center bg-white dark:bg-[#070b14]">
        <div class="h-16 w-16 animate-spin rounded-full border-4 border-solid border-brand-500 border-t-transparent">
        </div>
    </div>
    <!-- End Preloader -->
    <!-- Page Wrapper -->
    <div class="flex h-screen overflow-hidden">
        <?php echo $__env->make('backend.layouts.partials.sidebar-logo', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <!-- Content Area -->
        <div class="relative flex min-w-0 flex-1 flex-col overflow-x-auto overflow-y-auto bg-gray-50 dark:bg-[#0b1120]">
            <!-- Small Device Overlay -->
            <div @click="sidebarToggle = false" :class="sidebarToggle ? 'block lg:hidden' : 'hidden'"
                class="fixed w-full h-screen z-9 bg-gray-900/50"></div>
            <!-- End Small Device Overlay -->

            <?php if (! (request()->routeIs('admin.dialer.*') || request()->routeIs('admin.contacts.*'))): ?>
                <?php echo $__env->make('backend.layouts.partials.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>

            <!-- Main Content -->
            <main class="min-h-0 flex-1">
                <?php echo $__env->yieldContent('admin-content'); ?>
            </main>
            <!-- End Main Content -->
        </div>
    </div>

    <?php echo ld_apply_filters('admin_footer_before', ''); ?>


    <?php echo $__env->yieldPushContent('scripts'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize sidebar state from localStorage if it exists
            if (window.Alpine) {
                const sidebarState = localStorage.getItem('sidebarToggle');
                if (sidebarState !== null) {
                    document.addEventListener('alpine:initialized', () => {
                        // Ensure the Alpine.js instance is ready
                        setTimeout(() => {
                            const alpineData = document.querySelector('body').__x;
                            if (alpineData && typeof alpineData.$data !== 'undefined') {
                                alpineData.$data.sidebarToggle = JSON.parse(sidebarState);
                            }
                        }, 0);
                    });
                }
            }
        });
    </script>
    
    <?php if(!empty(config('settings.global_custom_js'))): ?>
    <script>
        <?php echo config('settings.global_custom_js'); ?>

    </script>
    <?php endif; ?>

    <!-- Global drawer handling script -->
    <script>
        // Define the global drawer opener function
        window.openDrawer = function(drawerId) {
            console.log('Opening drawer:', drawerId);
            
            // Method 1: Try using the LaraDrawers registry if available
            if (window.LaraDrawers && window.LaraDrawers[drawerId]) {
                console.log('Opening drawer via registry');
                window.LaraDrawers[drawerId].open = true;
                return;
            }
            
            // Method 2: Try using Alpine.js directly
            const drawerEl = document.querySelector(`[data-drawer-id="${drawerId}"]`);
            if (drawerEl && window.Alpine) {
                console.log('Opening drawer via Alpine');
                try {
                    const alpineInstance = Alpine.getComponent(drawerEl);
                    if (alpineInstance) {
                        alpineInstance.open = true;
                        return;
                    }
                } catch (e) {
                    console.error('Alpine error:', e);
                }
            }
            
            // Method 3: Dispatch a custom event as fallback
            console.log('Opening drawer via event dispatch');
            window.dispatchEvent(new CustomEvent('open-drawer-' + drawerId));
        };
        
        // Initialize all drawer triggers on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-drawer-trigger]').forEach(function(element) {
                element.addEventListener('click', function(e) {
                    const drawerId = this.getAttribute('data-drawer-trigger');
                    if (drawerId) {
                        e.preventDefault();
                        window.openDrawer(drawerId);
                        return false;
                    }
                });
            });
        });
    </script>
    
    <?php if (isset($component)) { $__componentOriginal704196272d5e2debce23ffdbf1a3fb23 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal704196272d5e2debce23ffdbf1a3fb23 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.toast-notifications','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('toast-notifications'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal704196272d5e2debce23ffdbf1a3fb23)): ?>
<?php $attributes = $__attributesOriginal704196272d5e2debce23ffdbf1a3fb23; ?>
<?php unset($__attributesOriginal704196272d5e2debce23ffdbf1a3fb23); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal704196272d5e2debce23ffdbf1a3fb23)): ?>
<?php $component = $__componentOriginal704196272d5e2debce23ffdbf1a3fb23; ?>
<?php unset($__componentOriginal704196272d5e2debce23ffdbf1a3fb23); ?>
<?php endif; ?>
    <!-- Modal Toggle Script -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Open modals
        document.querySelectorAll("[data-modal-target]").forEach(button => {
            button.addEventListener("click", () => {
                const targetId = button.getAttribute("data-modal-target");
                const modal = document.getElementById(targetId);
                if (modal) modal.classList.remove("hidden");
            });
        });

        // Close modals by button
        document.querySelectorAll(".modal-close").forEach(button => {
            button.addEventListener("click", () => {
                button.closest(".modal").classList.add("hidden");
            });
        });

        // Close modal on outside click
        document.querySelectorAll(".modal").forEach(modal => {
            modal.addEventListener("click", function (e) {
                if (e.target === modal) {
                    modal.classList.add("hidden");
                }
            });
        });
    });

    //inde.blade.php for agent_logs
      document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".datepicker-single").forEach(function (input) {
            if (!input._flatpickr && window.flatpickr) {
                flatpickr(input, {
                    mode: "single",
                    dateFormat: "M j, Y",
                    defaultDate: new Date() // ✅ sets today's date by default
                });
            }
        });
  

     // ✅ DataTable init
       const agentLogsTable = document.getElementById('agentLogsTable');
       if (agentLogsTable && window.$ && $.fn && $.fn.DataTable) {
       $('#agentLogsTable').DataTable({
    paging: false,
    ordering: true,
    info: true,
    scrollX: true,
    autoWidth: false,
    fixedColumns: {
        left: 2 // Freeze Agent ID and Name
    },
    createdRow: function (row, data, dataIndex) {
        // Tooltip on hover with agent name
        row.title = row.querySelector('td:nth-child(2)')?.textContent.trim() ?? '';

        // Hover effect
        row.classList.add('hover:bg-gray-100', 'dark:hover:bg-gray-700');

        // Zebra striping
        if (dataIndex % 2 === 0) {
            // Even row
            row.classList.add('bg-white', 'dark:bg-gray-800');
        } else {
            // Odd row
            row.classList.add('bg-gray-50', 'dark:bg-gray-900');
        }
    },
    headerCallback: function (thead) {
        thead.querySelectorAll('th').forEach(th => {
            th.classList.add('bg-white', 'dark:bg-gray-800');
        });
    }
});
}
});
</script>

    <?php echo ld_apply_filters('admin_footer_after', ''); ?>

</body>
</html>
<?php /**PATH /var/www/html/resources/views/backend/layouts/app.blade.php ENDPATH**/ ?>