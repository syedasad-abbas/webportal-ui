<?php
use App\Services\ThemeColorService;

$primaryColor = config('settings.theme_primary_color', '#635bff');
$secondaryColor = config('settings.theme_secondary_color', '#1f2937');

$primaryPalette = ThemeColorService::generateColorPalette($primaryColor);
?>

<style>
    :root {
        /* Base colors */
        --color-primary: <?php echo e($primaryColor); ?>;
        --color-secondary: <?php echo e($secondaryColor); ?>;
        
        /* Brand color palette */
        --color-brand-50: <?php echo e($primaryPalette[50]); ?>;
        --color-brand-100: <?php echo e($primaryPalette[100]); ?>;
        --color-brand-200: <?php echo e($primaryPalette[200]); ?>;
        --color-brand-300: <?php echo e($primaryPalette[300]); ?>;
        --color-brand-400: <?php echo e($primaryPalette[400]); ?>;
        --color-brand-500: <?php echo e($primaryColor); ?>;
        --color-brand-600: <?php echo e($primaryPalette[600]); ?>;
        --color-brand-700: <?php echo e($primaryPalette[700]); ?>;
        --color-brand-800: <?php echo e($primaryPalette[800]); ?>;
        --color-brand-900: <?php echo e($primaryPalette[900]); ?>;
    }
</style>
<?php /**PATH /var/www/html/resources/views/backend/layouts/partials/theme-colors.blade.php ENDPATH**/ ?>