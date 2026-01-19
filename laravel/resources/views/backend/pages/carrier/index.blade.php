@extends('backend.layouts.app')

@section('title')
    {{ $breadcrumbs['title'] }} | {{ config('app.name') }}
@endsection

@section('admin-content')
@php
  $isAdmin = auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Superadmin']);
@endphp

<div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
    <x-breadcrumbs :breadcrumbs="$breadcrumbs">
        <x-slot name="title_after">
            <div class="flex items-center gap-2">
            </div>
        </x-slot>
    </x-breadcrumbs>

    {!! ld_apply_filters('carrier_after_breadcrumbs', '') !!}

    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5 flex gap-3 flex-col md:flex-row md:justify-between md:items-center">
                <div>
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90 hidden md:block">
                        {{ __('Configured carrier') }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 hidden md:block">
                        {{ __('Monitor registration health and manage routing rules.') }}
                    </p>
                </div>

                @include('backend.partials.search-form', [
                    'placeholder' => __('Search by name or domain'),
                ])
            </div>

            <div class="space-y-3 border-t border-gray-100 dark:border-gray-800 overflow-x-auto overflow-y-visible">
                <table class="w-full dark:text-gray-400">
                    <thead class="bg-light text-capitalize">
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">{{ __('Name') }}</th>
                            <th class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">{{ __('Default Caller ID') }}</th>
                            <th class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">{{ __('Domain / Port') }}</th>
                            <th class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">{{ __('Transport') }}</th>

                            {{-- ✅ NEW: Outbound Proxy --}}
                            <th class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">{{ __('Outbound Proxy') }}</th>

                            <th class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">{{ __('Registration') }}</th>
                            <th class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-left px-5">{{ __('Prefixes') }}</th>

                            @if($isAdmin)
                                <th class="p-2 bg-gray-50 dark:bg-gray-800 dark:text-white text-right px-5">{{ __('Actions') }}</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($carrier as $carrierItem)
                            @php
                                $status = $carrierItem['registration_status'] ?? null;
                                $state = $status['state'] ?? null;

                                $chipClass = 'text-gray-800 bg-gray-100 dark:bg-gray-700 dark:text-gray-300';
                                if ($state === 'success') $chipClass = 'text-green-800 bg-green-100 dark:bg-green-900/20 dark:text-green-400';
                                if ($state === 'error') $chipClass = 'text-red-800 bg-red-100 dark:bg-red-900/20 dark:text-red-400';

                                $statusLabel = $status['label'] ?? (!empty($carrierItem['registration_required']) ? __('Pending') : __('Not required'));
                                $domain = $carrierItem['sip_domain'] ?? '—';
                                $port = !empty($carrierItem['sip_port']) ? ':'.$carrierItem['sip_port'] : '';
                                $transport = strtoupper($carrierItem['transport'] ?? 'udp');

                                // ✅ NEW: Outbound Proxy (display)
                                $outboundProxy = $carrierItem['outbound_proxy'] ?? '—';
                            @endphp

                            <tr class="{{ $loop->last ? '' : 'border-b border-gray-100 dark:border-gray-800' }}">
                                <td class="px-5 py-4 sm:px-6">
                                    {{ $carrierItem['name'] ?? '—' }}
                                </td>

                                <td class="px-5 py-4 sm:px-6">
                                    {{ $carrierItem['default_caller_id'] ?? '—' }}
                                </td>

                                <td class="px-5 py-4 sm:px-6">
                                    {{ $domain }}{{ $port }}
                                </td>

                                <td class="px-5 py-4 sm:px-6">
                                    <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full dark:bg-gray-800 dark:text-white">
                                        {{ $transport }}
                                    </span>
                                </td>

                                {{-- ✅ NEW: Outbound Proxy cell --}}
                                <td class="px-5 py-4 sm:px-6">
                                    {{ $outboundProxy }}
                                </td>

                                <td class="px-5 py-4 sm:px-6">
                                    <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium rounded-full {{ $chipClass }}">
                                        {{ $statusLabel }}
                                    </span>

                                    @if(!empty($status['detail']))
                                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $status['detail'] }}
                                        </div>
                                    @endif

                                    @if(!empty($carrierItem['registration_username']))
                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $carrierItem['registration_username'] }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-5 py-4 sm:px-6">
                                    @if(!empty($carrierItem['prefixes']))
                                        <div class="flex flex-col gap-2">
                                            @foreach($carrierItem['prefixes'] as $prefix)
                                                <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full dark:bg-gray-800 dark:text-white">
                                                    {{ $prefix['prefix'] ?: '—' }} · {{ $prefix['callerId'] ?? '—' }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full dark:bg-gray-800 dark:text-white">
                                            —
                                        </span>
                                    @endif
                                </td>

                                @if($isAdmin)
                                    <td class="px-5 py-4 sm:px-6 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.carrier.edit', $carrierItem['id']) }}" class="btn-default">
                                                {{ __('Edit') }}
                                            </a>

                                            <form method="POST"
                                                  action="{{ route('admin.carrier.destroy', $carrierItem['id']) }}"
                                                  onsubmit="return confirm('Delete this carrier?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-danger">
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                @endif

                            </tr>
                        @empty
                            <tr>
                                {{-- ✅ colspan increased by 1 because we added a column --}}
                                <td colspan="{{ $isAdmin ? 8 : 7 }}" class="text-center py-6">
                                    <p class="text-gray-500 dark:text-gray-400">{{ __('No carrier found') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if($carrier instanceof \Illuminate\Contracts\Pagination\Paginator)
                    <div class="my-4 px-4 sm:px-6">
                        {{ $carrier->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
