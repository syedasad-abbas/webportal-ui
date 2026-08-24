@extends('backend.layouts.app')

@section('title')
    {{ $breadcrumbs['title'] }} | {{ config('app.name') }}
@endsection

@section('admin-content')
    <div class="p-4 mx-auto max-w-7xl md:p-6">
        <x-breadcrumbs :breadcrumbs="$breadcrumbs" />

        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="p-5 space-y-6 border-t border-gray-100 dark:border-gray-800 sm:p-6">
                    <form action="{{ route('admin.leads.store') }}" method="POST" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">

                            {{-- Date --}}
                            <div>
                                <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Date') }}
                                </label>
                                <input
                                    type="date"
                                    name="date"
                                    id="date"
                                    value="{{ old('date') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >
                            </div>

                            {{-- Status --}}
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Status') }}
                                </label>
                                <select name="status" id="status" 
                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="">{{ __('Select Status') }}</option>
                                    <option value="new" {{ old('status') == 'new' ? 'selected' : '' }}>{{ __('New') }}</option>
                                    <option value="contacted" {{ old('status') == 'contacted' ? 'selected' : '' }}>{{ __('Contacted') }}</option>
                                    <option value="qualified" {{ old('status') == 'qualified' ? 'selected' : '' }}>{{ __('Qualified') }}</option>
                                    <option value="closed" {{ old('status') == 'closed' ? 'selected' : '' }}>{{ __('Closed') }}</option>
                                </select>
                            </div>

                            {{-- Patient Name --}}
                            <div>
                                <label for="patient_name" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Patient Name') }}
                                </label>
                                <input
                                    type="text"
                                    name="patient_name"
                                    id="patient_name"
                                    required
                                    value="{{ old('patient_name') }}"
                                    placeholder="{{ __('Full name of patient') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            {{-- Address --}}
                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Address') }}
                                </label>
                                <input
                                    type="text"
                                    name="address"
                                    id="address"
                                    value="{{ old('address') }}"
                                    placeholder="{{ __('Patient address') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            {{-- Patient Phone --}}
                            <div>
                                <label for="patient_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Patient Phone') }}
                                </label>
                                <input
                                    type="tel"
                                    name="patient_phone"
                                    id="patient_phone"
                                    value="{{ old('patient_phone') }}"
                                    placeholder="{{ __('+1 (555) 123-4567') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            {{-- Patient DOB --}}
                            <div>
                                <label for="patient_dob" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Patient DOB') }}
                                </label>
                                <input
                                    type="date"
                                    name="patient_dob"
                                    id="patient_dob"
                                    value="{{ old('patient_dob') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >
                            </div>

                            {{-- Sizes --}}
                            <div>
                                <label for="sizes" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Sizes') }}
                                </label>
                                <input
                                    type="text"
                                    name="sizes"
                                    id="sizes"
                                    value="{{ old('sizes') }}"
                                    placeholder="{{ __('e.g. 34D, 40R') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            {{-- Insurance --}}
                            <div>
                                <label for="insurance" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Insurance') }}
                                </label>
                                <input
                                    type="text"
                                    name="insurance"
                                    id="insurance"
                                    value="{{ old('insurance') }}"
                                    placeholder="{{ __('Primary Insurance') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            {{-- Member ID (Primary) --}}
                            <div>
                                <label for="member_id" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Member ID') }}
                                </label>
                                <input
                                    type="text"
                                    name="member_id"
                                    id="member_id"
                                    value="{{ old('member_id') }}"
                                    placeholder="{{ __('Primary Member ID') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            {{-- Member ID for Secondary Insurance --}}
                            <div>
                                <label for="secondary_member_id" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Member ID (Secondary Insurance)') }}
                                </label>
                                <input
                                    type="text"
                                    name="secondary_member_id"
                                    id="secondary_member_id"
                                    value="{{ old('secondary_member_id') }}"
                                    placeholder="{{ __('Secondary Member ID') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            {{-- Products --}}
                            <div>
                                <label for="products" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Products') }}
                                </label>
                                <input
                                    type="text"
                                    name="products"
                                    id="products"
                                    value="{{ old('products') }}"
                                    placeholder="{{ __('e.g. Breast Pump, Walker') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            {{-- Doctor Name --}}
                            <div>
                                <label for="doctor_name" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Doctor Name') }}
                                </label>
                                <input
                                    type="text"
                                    name="doctor_name"
                                    id="doctor_name"
                                    value="{{ old('doctor_name') }}"
                                    placeholder="{{ __('Referring Doctor') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            {{-- Doctor's NPI --}}
                            <div>
                                <label for="doctor_npi" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __("Doctor's NPI") }}
                                </label>
                                <input
                                    type="text"
                                    name="doctor_npi"
                                    id="doctor_npi"
                                    value="{{ old('doctor_npi') }}"
                                    placeholder="{{ __('10-digit NPI number') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            {{-- Medications --}}
                            <div>
                                <label for="medications" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Medications') }}
                                </label>
                                <textarea
                                    name="medications"
                                    id="medications"
                                    rows="2"
                                    placeholder="{{ __('Current medications') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >{{ old('medications') }}</textarea>
                            </div>

                            {{-- Treatments --}}
                            <div>
                                <label for="treatments" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Treatments') }}
                                </label>
                                <textarea
                                    name="treatments"
                                    id="treatments"
                                    rows="2"
                                    placeholder="{{ __('Recommended treatments / procedures') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >{{ old('treatments') }}</textarea>
                            </div>

                            {{-- Dr Last Visit --}}
                            <div>
                                <label for="dr_last_visit" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Doctor Last Visit') }}
                                </label>
                                <input
                                    type="date"
                                    name="dr_last_visit"
                                    id="dr_last_visit"
                                    value="{{ old('dr_last_visit') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >
                            </div>

                        </div>

                        <div class="mt-6 flex justify-start gap-4">
                            <button type="submit" class="btn-primary">{{ __('Create Lead') }}</button>
                            <a href="{{ route('admin.leads.index') }}" class="btn-default">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection