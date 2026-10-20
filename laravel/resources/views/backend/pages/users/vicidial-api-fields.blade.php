<div class="sm:col-span-2">
    <h3 class="text-sm font-semibold text-gray-800 dark:text-white">{{ __('VICIdial API controls') }}</h3>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Use a VICIdial user with API access enabled. These credentials control the assigned agent and are separate from the phone registration credentials. If none are saved, the shared API account is used.') }}</p>
</div>
<div>
    <label for="vici_api_username" class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('VICIdial API Username') }}</label>
    <input type="text" id="vici_api_username" name="vici_api_username" maxlength="64" autocomplete="off"
        value="{{ old('vici_api_username', ($vicidialCredential ?? null)?->api_username ?? '') }}"
        class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
</div>
<div>
    <label for="vici_api_password" class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('VICIdial API Password') }}</label>
    <input type="password" id="vici_api_password" name="vici_api_password" maxlength="255" autocomplete="new-password" value=""
        placeholder="{{ __('Leave blank to keep a saved password') }}"
        class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
</div>
