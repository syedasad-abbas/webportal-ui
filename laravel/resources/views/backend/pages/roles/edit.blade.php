@extends('backend.layouts.app')

@section('title')
    {{ $breadcrumbs['title'] }} | {{ config('app.name') }}
@endsection

@section('admin-content')

@php
    $isSuperadmin = isset($role) && $role->name === 'Superadmin';
@endphp

<div class="connectpro-admin-page p-4 mx-auto max-w-[var(--breakpoint-2xl)] md:p-6">
    <x-breadcrumbs :breadcrumbs="$breadcrumbs" />

    {!! ld_apply_filters('roles_edit_after_breadcrumbs', '') !!}

    @if ($isSuperadmin)
        <div class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200">
            {{ __('The Superadmin role is protected. Its name and permissions cannot be modified.') }}
        </div>
    @endif

    <form action="{{ $isSuperadmin ? '#' : route('admin.roles.update', $role->id) }}" method="POST">
        @method('PUT')
        @csrf
        <div class="space-y-8">
            <!-- Role Details Section -->
            <div class="rounded-lg border border-gray-200 bg-white shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                        {{ __('Role Details') }}
                    </h3>
                    @if (! $isSuperadmin)
                        <div class="flex gap-4">
                            <button type="submit" class="btn-primary">
                                {{ __('Save') }}
                            </button>
                            <a href="{{ route('admin.roles.index') }}" class="btn-default">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    @endif
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                {{ __('Role Name') }}
                            </label>
                            <input required autofocus name="name" value="{{ $role->name }}" type="text" placeholder="{{ __('Enter a Role Name') }}" class="mt-2 form-control" {{ $isSuperadmin ? 'disabled' : '' }}>
                            @if ($isSuperadmin)
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('This role name is locked.') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permissions Section -->
            <div class="rounded-lg border border-gray-200 bg-white shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                        {{ __('Permissions') }}
                    </h3>
                    @if ($isSuperadmin)
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('All permissions are assigned to this role and cannot be modified.') }}</p>
                    @endif
                </div>
                <div class="p-4">
                    <div class="mb-4">
                        <input type="checkbox" id="checkPermissionAll" class="mr-2 border-gray-400 dark:border-gray-500 rounded focus:ring-2 focus:ring-primary text-primary" {{ $roleService->roleHasPermissions($role, $all_permissions) ? 'checked' : '' }} {{ $isSuperadmin ? 'disabled' : '' }}>
                        <label for="checkPermissionAll" class="text-sm text-gray-800 dark:text-gray-200">
                            {{ __('Select All') }}
                        </label>
                    </div>
                    <hr class="mb-6">
                    @php $i = 1; @endphp
                    @foreach ($permission_groups as $group)
                    <div class="mb-6">
                        <div class="flex items-center mb-2">
                        <input type="checkbox" id="group{{ $i }}Management" class="mr-2 border-gray-400 dark:border-gray-500 rounded focus:ring-2 focus:ring-primary text-primary" {{ $roleService->roleHasPermissions($role, $roleService->getPermissionsByGroupName($group->name)) ? 'checked' : '' }} {{ $isSuperadmin ? 'disabled' : '' }}>
                        <label for="group{{ $i }}Management" class="capitalize text-sm font-medium text-gray-800 dark:text-gray-200">
                                {{ ucfirst($group->name) }}
                            </label>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 xl:grid-cols-6 gap-4" data-group="group{{ $i }}Management">
                            @php
                                $permissions = $roleService->getPermissionsByGroupName($group->name);
                            @endphp
                            @foreach ($permissions as $permission)
                            <div>
                                <input type="checkbox" id="checkPermission{{ $permission->id }}" name="permissions[]" value="{{ $permission->name }}" class="mr-2 border-gray-400 dark:border-gray-500 rounded focus:ring-2 focus:ring-primary text-primary"
                                       {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }} {{ $isSuperadmin ? 'disabled' : '' }}>
                                <label for="checkPermission{{ $permission->id }}" class="capitalize text-sm text-gray-800 dark:text-gray-200">
                                    {{ $permission->name }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @php $i++; @endphp
                    @endforeach
                </div>
            </div>

            @if (! $isSuperadmin)
                <!-- Action Buttons -->
                <div class="flex justify-start gap-4">
                    <button type="submit" class="btn-primary">
                        {{ __('Save') }}
                    </button>
                    <a href="{{ route('admin.roles.index') }}" class="btn-default">
                        {{ __('Cancel') }}
                    </a>
                </div>
            @endif
        </div>
    </form>
</div>
@endsection

@push('scripts')
    @include('backend.pages.roles.partials.scripts')
@endpush
