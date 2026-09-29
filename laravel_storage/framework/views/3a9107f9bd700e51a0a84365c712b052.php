<?php echo ld_apply_filters('inside_post_form_start', ''); ?>


<input type="hidden" name="post_id" value="<?php echo e($post->id ?? ''); ?>" data-post-id="<?php echo e($post->id ?? ''); ?>">
<input type="hidden" name="post_type" value="<?php echo e($postType); ?>" data-post-type="<?php echo e($postType); ?>">

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Main Content Area -->
    <div class="lg:col-span-3 space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="p-5 space-y-4 sm:p-6">
                <!-- Title and Slug with Alpine.js -->
                <div x-data="slugGenerator('<?php echo e(old('title', $post->title ?? '')); ?>', '<?php echo e(old('slug', $post->slug ?? '')); ?>')">
                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-400"><?php echo e(__('Title')); ?></label>
                        <input type="text" name="title" id="title" required x-model="title" maxlength="255"
                            class="form-control">
                    </div>
                    <?php echo ld_apply_filters('post_form_after_title', ''); ?>


                    <!-- Compact Slug UI -->
                    <div class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400">
                        <span class="mr-1"><?php echo e(__('Permalink')); ?>:</span>
                        <span class="flex-1 truncate" x-show="!showSlugEdit">
                            <span class="text-gray-400"><?php echo e(url('/')); ?>/</span><span class="font-medium text-primary" x-text="slug || '<?php echo e(__('auto-generated')); ?>'"></span>
                        </span>
                        <div class="flex-1" x-show="showSlugEdit">
                            <input type="text" name="slug" id="slug" x-model="slug" maxlength="200"
                                class="h-7 w-full rounded border border-gray-300 bg-transparent px-2 py-1 text-xs text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                placeholder="<?php echo e(__('Leave empty to auto-generate')); ?>">
                        </div>
                        <div class="ml-2 flex space-x-1">
                            <!-- Edit/Save Button -->
                            <button type="button" @click="toggleSlugEdit()" class="text-xs text-primary hover:underline">
                                <span x-show="!showSlugEdit"><?php echo e(__('Edit')); ?></span>
                                <span x-show="showSlugEdit"><?php echo e(__('OK')); ?></span>
                            </button>
                            <!-- Generate Button -->
                            <button type="button" @click="generateSlug()" class="text-xs text-primary hover:underline ml-2">
                                <?php echo e(__('Generate')); ?>

                            </button>
                        </div>
                    </div>
                    <?php echo ld_apply_filters('post_form_after_slug', ''); ?>

                </div>

                <?php if($postTypeModel->supports_editor): ?>
                <div class="mt-1">
                    <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-400"><?php echo e(__('Content')); ?></label>
                    <textarea name="content" id="content" rows="10"><?php echo old('content', $post->content ?? ''); ?></textarea>
                </div>
                <?php endif; ?>
                <?php echo ld_apply_filters('post_form_after_content', ''); ?>


                <?php if($postTypeModel->supports_excerpt): ?>
                <div class="mt-1">
                    <label for="excerpt" class="block text-sm font-medium text-gray-700 dark:text-gray-400"><?php echo e(__('Excerpt')); ?></label>
                    <textarea name="excerpt" id="excerpt" rows="3"
                        class="w-full rounded-lg border border-gray-300 bg-transparent p-4 text-sm text-gray-800 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"><?php echo e(old('excerpt', $post->excerpt ?? '')); ?></textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?php echo e(__('A short summary of the content')); ?>. <?php echo e(__('Leave empty to auto-generate from content')); ?></p>
                </div>
                <?php endif; ?>
                <?php echo ld_apply_filters('post_form_after_excerpt', ''); ?>


                <?php if($postTypeModel->supports_thumbnail): ?>
                <div class="mt-1">
                    <label for="featured_image" class="block text-sm font-medium text-gray-700 dark:text-gray-400"><?php echo e(__('Featured Image')); ?></label>
                    <?php if(isset($post) && $post->featured_image): ?>
                        <div class="mb-4">
                            <img src="<?php echo e($post->featured_image); ?>" alt="<?php echo e($post->title); ?>" class="max-h-48 rounded-lg">
                            <div class="mt-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="remove_featured_image" id="remove_featured_image" class="mr-2">
                                    <span class="text-sm text-gray-700 dark:text-gray-400"><?php echo e(__('Remove featured image')); ?></span>
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="featured_image" id="featured_image" accept="image/*"
                        class="focus:border-ring-brand-300 cursor-pointer focus:file:ring-brand-300 w-full overflow-hidden rounded-lg border border-gray-300 bg-transparent text-sm text-gray-500 transition-colors file:mr-5 file:border-collapse file:cursor-pointer file:rounded-l-lg file:border-0 file:border-r file:border-solid file:border-gray-200 file:bg-gray-50 file:py-3 file:px-4 file:text-sm file:text-gray-700 placeholder:text-gray-400 hover:file:bg-gray-100 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:file:border-gray-800 dark:file:bg-white/[0.03] dark:file:text-gray-400 px-4">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?php echo e(__('Select an image to represent this post')); ?></p>
                </div>
                <?php endif; ?>
                <?php echo ld_apply_filters('post_form_after_featured_image', ''); ?>

            </div>
        </div>

        <?php if (isset($component)) { $__componentOriginal255ddd15bfd27a77e432d61f1380e674 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal255ddd15bfd27a77e432d61f1380e674 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.advanced-fields','data' => ['postMeta' => isset($post) ? $post->getAllMeta() : []]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('advanced-fields'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['post-meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(isset($post) ? $post->getAllMeta() : [])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal255ddd15bfd27a77e432d61f1380e674)): ?>
<?php $attributes = $__attributesOriginal255ddd15bfd27a77e432d61f1380e674; ?>
<?php unset($__attributesOriginal255ddd15bfd27a77e432d61f1380e674); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal255ddd15bfd27a77e432d61f1380e674)): ?>
<?php $component = $__componentOriginal255ddd15bfd27a77e432d61f1380e674; ?>
<?php unset($__componentOriginal255ddd15bfd27a77e432d61f1380e674); ?>
<?php endif; ?>
    </div>

    <!-- Sidebar Area -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Status and Visibility -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="px-4 py-3 sm:px-6 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-base font-medium text-gray-800 dark:text-white"><?php echo e(__('Status & Visibility')); ?></h3>
            </div>
            <div class="p-3 space-y-2 sm:p-4">
                <!-- Status with Combobox -->
                <?php
                    $statusOptions = ld_apply_filters('post_status_options', [
                        ['value' => 'draft', 'label' => __('Draft')],
                        ['value' => 'publish', 'label' => __('Published')],
                        ['value' => 'pending', 'label' => __('Pending Review')],
                        ['value' => 'future', 'label' => __('Scheduled')],
                        ['value' => 'private', 'label' => __('Private')],
                    ]);
                    $currentStatus = old('status', $post->status ?? 'draft');
                ?>

                <?php if (isset($component)) { $__componentOriginald9e3f05661d2b31b6e566c65f0284ef2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald9e3f05661d2b31b6e566c65f0284ef2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inputs.combobox','data' => ['name' => 'status','label' => ''.e(__('Status')).'','options' => $statusOptions,'selected' => $currentStatus,'multiple' => false,'searchable' => false,'xModel' => 'status']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inputs.combobox'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'status','label' => ''.e(__('Status')).'','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statusOptions),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($currentStatus),'multiple' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'searchable' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'x-model' => 'status']); ?>
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

                <?php echo ld_apply_filters('post_form_after_status', ''); ?>


                <!-- Publish Date (for scheduled posts) -->
                <div x-data="{ 
                        showSchedule: <?php echo e(isset($post) && (old('status', $post->status) === 'future' || $post->published_at) ? 'true' : 'false'); ?>,
                        status: '<?php echo e(old('status', $post->status ?? 'draft')); ?>',
                        init() {
                            this.$watch('status', value => {
                                if (value === 'future') {
                                    this.showSchedule = true;
                                }
                            });
                        }
                    }">
                    <div class="mb-2">
                        <input type="checkbox" id="schedule_post" name="schedule_post" x-model="showSchedule" 
                            x-on:change="if(showSchedule && status !== 'future') status = 'future'; $dispatch('input', status)" class="mr-2">
                        <label for="schedule_post" class="text-sm font-medium text-gray-700 dark:text-gray-400"><?php echo e(__('Schedule this post')); ?></label>
                    </div>
                    <div x-show="showSchedule" class="mt-2">
                        <?php if (isset($component)) { $__componentOriginal9655d79619a96419e7ba3403c9c64d1e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9655d79619a96419e7ba3403c9c64d1e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inputs.datetime-picker','data' => ['id' => 'published_at','name' => 'published_at','label' => __('Publish Date'),'value' => old('published_at', isset($post) && $post->published_at ? $post->published_at->format('Y-m-d H:i') : now()->addDay()->format('Y-m-d H:i')),'minDate' => now()->format('Y-m-d'),'helpText' => __('Schedule when this post should be published')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inputs.datetime-picker'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'published_at','name' => 'published_at','label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Publish Date')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('published_at', isset($post) && $post->published_at ? $post->published_at->format('Y-m-d H:i') : now()->addDay()->format('Y-m-d H:i'))),'min-date' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(now()->format('Y-m-d')),'help-text' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Schedule when this post should be published'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9655d79619a96419e7ba3403c9c64d1e)): ?>
<?php $attributes = $__attributesOriginal9655d79619a96419e7ba3403c9c64d1e; ?>
<?php unset($__attributesOriginal9655d79619a96419e7ba3403c9c64d1e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9655d79619a96419e7ba3403c9c64d1e)): ?>
<?php $component = $__componentOriginal9655d79619a96419e7ba3403c9c64d1e; ?>
<?php unset($__componentOriginal9655d79619a96419e7ba3403c9c64d1e); ?>
<?php endif; ?>
                    </div>
                </div>
                <?php echo ld_apply_filters('post_form_after_publish_date', ''); ?>


                <div class="flex justify-between gap-4 mt-3">
                    <button type="submit" class="btn-primary"><?php echo e(isset($post) ? __('Update') : __('Save')); ?></button>
                    <a href="<?php echo e(route('admin.posts.index', $postType)); ?>" class="btn-default"><?php echo e(__('Cancel')); ?></a>
                </div>
                <?php echo ld_apply_filters('post_form_after_submit_buttons', ''); ?>

            </div>
        </div>

        <?php if($postTypeModel->hierarchical): ?>
        <!-- Parent -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="px-4 py-3 sm:px-6 sm:py-3 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-base font-medium text-gray-800 dark:text-white"><?php echo e(__('Parent')); ?></h3>
            </div>
            <div class="p-3 space-y-2 sm:p-4">
                <?php
                    $parentOptions = [['value' => '', 'label' => __('None')]];
                    foreach($parentPosts as $id => $title) {
                        $parentOptions[] = [
                            'value' => $id,
                            'label' => $title
                        ];
                    }
                ?>
                
                <?php if (isset($component)) { $__componentOriginald9e3f05661d2b31b6e566c65f0284ef2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald9e3f05661d2b31b6e566c65f0284ef2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inputs.combobox','data' => ['name' => 'parent_id','label' => __('Parent {$postTypeModel->label_singular}'),'placeholder' => __('Select Parent'),'options' => $parentOptions,'selected' => old('parent_id', $post->parent_id ?? ''),'searchable' => false]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inputs.combobox'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'parent_id','label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Parent {$postTypeModel->label_singular}')),'placeholder' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Select Parent')),'options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($parentOptions),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('parent_id', $post->parent_id ?? '')),'searchable' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false)]); ?>
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
        </div>
        <?php endif; ?>
        <?php echo ld_apply_filters('post_form_after_content_parent', ''); ?>


        <!-- Taxonomies -->
        <?php if(!empty($taxonomies)): ?>
            <?php $__currentLoopData = $taxonomies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $taxonomy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo $__env->make('backend.pages.posts.partials.post-taxonomy-chooser', [
                    'taxonomy' => $taxonomy,
                    'post_type' => $postType,
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
    </div>
</div>

<?php echo ld_apply_filters('inside_post_form_end', ''); ?>

<?php /**PATH /var/www/html/resources/views/backend/pages/posts/partials/form.blade.php ENDPATH**/ ?>