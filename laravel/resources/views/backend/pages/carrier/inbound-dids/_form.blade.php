@php
    $editing = isset($inboundDid);
    $selectedCarrier = old('carrier_id', $editing ? $inboundDid->carrier_id : '');
    $active = old('is_active', $editing ? (int) $inboundDid->is_active : 1);
    $inputClass = 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
@endphp

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <label for="carrier_id" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ __('Carrier') }} *
        </label>
        <select id="carrier_id" name="carrier_id" required class="{{ $inputClass }}">
            <option value="">{{ __('Select a carrier') }}</option>
            @foreach($carriers as $carrier)
                <option value="{{ $carrier->id }}" {{ (string) $selectedCarrier === (string) $carrier->id ? 'selected' : '' }}>
                    {{ $carrier->name }}
                </option>
            @endforeach
        </select>
        @error('carrier_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="did" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ __('Inbound DID') }} *
        </label>
        <input id="did" name="did" type="tel" required inputmode="tel"
               value="{{ old('did', $editing ? $inboundDid->did : '') }}"
               placeholder="{{ __('e.g. +15551234567') }}" class="{{ $inputClass }}">
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            {{ __('Enter the number delivered by your carrier. Formatting and a leading + are accepted; it is saved as digits.') }}
        </p>
        @error('did') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="label" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ __('Label') }}
        </label>
        <input id="label" name="label" type="text" maxlength="255"
               value="{{ old('label', $editing ? $inboundDid->label : '') }}"
               placeholder="{{ __('e.g. Main sales line') }}" class="{{ $inputClass }}">
        @error('label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center pt-6">
        <input type="hidden" name="is_active" value="0">
        <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-400">
            <input type="checkbox" name="is_active" value="1" {{ (int) $active === 1 ? 'checked' : '' }}
                   class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700">
            <span>{{ __('Active — accept inbound calls to this DID') }}</span>
        </label>
    </div>
</div>

<div class="mt-6 flex gap-4">
    <button type="submit" class="btn-primary">
        {{ $editing ? __('Save DID') : __('Add DID') }}
    </button>
    <a href="{{ route('admin.carrier.inbound-dids.index') }}" class="btn-default">{{ __('Cancel') }}</a>
</div>
