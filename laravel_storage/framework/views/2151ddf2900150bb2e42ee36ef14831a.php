<?php $__env->startSection('title'); ?>
    <?php echo e($breadcrumbs['title']); ?> | <?php echo e(config('app.name')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('admin-content'); ?>
<div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
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

    <?php echo ld_apply_filters('posts_show_after_breadcrumbs', '', $postType); ?>


    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5 flex justify-between items-center border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90"><?php echo e(__('Post Details')); ?></h3>
                <div class="flex gap-2">
                    <?php if(auth()->user()->can('post.edit')): ?>
                        <a href="<?php echo e(route('admin.posts.edit', [$postType, $post->id])); ?>" class="btn-primary">
                            <i class="bi bi-pencil mr-2"></i>
                            <?php echo e(__('Edit')); ?>

                        </a>
                    <?php endif; ?>
                    <a href="<?php echo e(route('admin.posts.index', $postType)); ?>" class="btn-default">
                        <i class="bi bi-arrow-left mr-2"></i>
                        <?php echo e(__('Back')); ?>

                    </a>
                </div>
            </div>
            
            <div class="px-5 py-4 sm:px-6 sm:py-5">
                <!-- Meta Information -->
                <div class="mb-6 flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-center">
                        <i class="bi bi-person mr-1"></i>
                        <?php echo e(__('Author:')); ?> <?php echo e($post->user->name); ?>

                    </div>
                    <div class="flex items-center">
                        <i class="bi bi-calendar mr-1"></i>
                        <?php echo e(__('Created:')); ?> <?php echo e($post->created_at->format('M d, Y h:i A')); ?>

                    </div>
                    <?php if($post->created_at != $post->updated_at): ?>
                        <div class="flex items-center">
                            <i class="bi bi-clock-history mr-1"></i>
                            <?php echo e(__('Updated:')); ?> <?php echo e($post->updated_at->format('M d, Y h:i A')); ?>

                        </div>
                    <?php endif; ?>
                    <div class="flex items-center">
                        <i class="bi bi-tag mr-1"></i>
                        <?php echo e(__('Status:')); ?> 
                        <span class="ml-1 inline-flex items-center justify-center px-2 py-1 text-xs font-medium 
                            <?php echo e($post->status === 'publish' ? 'text-green-800 bg-green-100 dark:bg-green-900/20 dark:text-green-400' : ''); ?>

                            <?php echo e($post->status === 'draft' ? 'text-gray-800 bg-gray-100 dark:bg-gray-700 dark:text-gray-300' : ''); ?>

                            <?php echo e($post->status === 'pending' ? 'text-orange-800 bg-orange-100 dark:bg-orange-900/20 dark:text-orange-400' : ''); ?>

                            <?php echo e($post->status === 'future' ? 'text-blue-800 bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400' : ''); ?>

                            <?php echo e($post->status === 'private' ? 'text-purple-800 bg-purple-100 dark:bg-purple-900/20 dark:text-purple-400' : ''); ?>

                            rounded-full">
                            <?php echo e(ucfirst($post->status)); ?>

                        </span>
                    </div>
                </div>

                <!-- Featured Image -->
                <?php if($post->featured_image): ?>
                    <div class="mb-6">
                        <img src="<?php echo e($post->featured_image); ?>" alt="<?php echo e($post->title); ?>" class="max-h-64 rounded-lg">
                    </div>
                <?php endif; ?>

                <!-- Excerpt -->
                <?php if($post->excerpt): ?>
                    <div class="mb-6">
                        <h4 class="text-lg font-medium text-gray-800 dark:text-white/90 mb-2"><?php echo e(__('Excerpt')); ?></h4>
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-gray-700 dark:text-gray-300">
                            <?php echo e($post->excerpt); ?>

                        </div>
                    </div>
                <?php endif; ?>

                <!-- Content -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 dark:text-white/90 mb-2"><?php echo e(__('Content')); ?></h4>
                    <div class="prose max-w-none dark:prose-invert prose-headings:font-medium prose-headings:text-gray-800 dark:prose-headings:text-white/90 prose-p:text-gray-700 dark:prose-p:text-gray-300">
                        <?php echo $post->content; ?>

                    </div>
                </div>

                <!-- Taxonomies -->
                <?php if($post->terms->count() > 0): ?>
                    <div class="mb-6">
                        <h4 class="text-lg font-medium text-gray-800 dark:text-white/90 mb-2"><?php echo e(__('Taxonomies')); ?></h4>
                        <div class="space-y-3">
                            <?php
                                $groupedTerms = $post->terms->groupBy('taxonomy');
                            ?>

                            <?php $__currentLoopData = $groupedTerms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $taxonomy => $terms): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div>
                                    <h5 class="text-sm font-medium text-gray-700 dark:text-gray-400 mb-1"><?php echo e(ucfirst($taxonomy)); ?></h5>
                                    <div class="flex flex-wrap gap-1">
                                        <?php $__currentLoopData = $terms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full dark:bg-gray-700 dark:text-white">
                                                <?php echo e($term->name); ?>

                                            </span>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backend.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/backend/pages/posts/show.blade.php ENDPATH**/ ?>