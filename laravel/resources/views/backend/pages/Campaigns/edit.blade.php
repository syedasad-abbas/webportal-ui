@extends('backend.layouts.app')

@section('title')
    {{ $breadcrumbs['title'] }} | {{ config('app.name') }}
@endsection

@section('admin-content')
    <div class="p-4 mx-auto max-w-4xl md:p-6">
        <x-breadcrumbs :breadcrumbs="$breadcrumbs" />

        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="p-5 space-y-6 border-t border-gray-100 dark:border-gray-800 sm:p-6">
                <form action="{{ route('admin.campaigns.update', $campaign) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="list_id" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                {{ __('List ID') }}
                            </label>
                            <input
                                type="text"
                                name="list_id"
                                id="list_id"
                                required
                                value="{{ old('list_id', $campaign->list_id) }}"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            >
                        </div>

                        <div>
                            <label for="list_name" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                {{ __('List Name') }}
                            </label>
                            <input
                                type="text"
                                name="list_name"
                                id="list_name"
                                required
                                value="{{ old('list_name', $campaign->list_name) }}"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            >
                        </div>

                        <div class="sm:col-span-2">
                            <label for="list_description" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                {{ __('List Description') }}
                            </label>
                            <input
                                type="text"
                                name="list_description"
                                id="list_description"
                                required
                                value="{{ old('list_description', $campaign->list_description) }}"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            >
                        </div>

                        <div class="sm:col-span-2">
                            <label for="file" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                {{ __('Replace File (optional)') }}
                            </label>
                            <input
                                type="file"
                                name="file"
                                id="file"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            >
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Uploading a new file will restart the import for this campaign.') }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-start gap-4">
                        <button type="submit" class="btn-primary">{{ __('Save Changes') }}</button>
                        <a href="{{ route('admin.campaigns.index') }}" class="btn-default">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
