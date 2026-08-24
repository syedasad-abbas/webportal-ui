<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\InboundDid;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InboundDidController extends Controller
{
    private function requirePermission(string $permission): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->hasAnyRole(['Admin', 'Superadmin']) || $user->can($permission)), 403);
    }

    private function normalizeDid(?string $did): string
    {
        return preg_replace('/\D+/', '', (string) $did) ?? '';
    }

    public function index(Request $request)
    {
        $this->requirePermission('carrier.view');

        $query = InboundDid::query()->with('carrier')->latest();
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($builder) use ($search) {
                $builder->where('did', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%")
                    ->orWhereHas('carrier', fn ($carrier) => $carrier->where('name', 'like', "%{$search}%"));
            });
        }

        return view('backend.pages.carrier.inbound-dids.index', [
            'dids' => $query->paginate(20)->withQueryString(),
            'breadcrumbs' => [
                'title' => __('Inbound DIDs'),
                'items' => [[
                    'label' => __('Carrier'),
                    'url' => route('admin.carrier.index'),
                ]],
            ],
        ]);
    }

    public function create()
    {
        $this->requirePermission('carrier.create');

        return view('backend.pages.carrier.inbound-dids.create', [
            'carriers' => Carrier::query()->orderBy('name')->get(['id', 'name']),
            'breadcrumbs' => [
                'title' => __('Add Inbound DID'),
                'items' => [[
                    'label' => __('Inbound DIDs'),
                    'url' => route('admin.carrier.inbound-dids.index'),
                ]],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->requirePermission('carrier.create');
        $request->merge(['did' => $this->normalizeDid($request->input('did'))]);

        $data = $request->validate([
            'carrier_id' => ['required', 'integer', 'exists:carriers,id'],
            'did' => ['required', 'digits_between:3,20', 'unique:inbound_dids,did'],
            'label' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        InboundDid::create($data);

        return redirect()->route('admin.carrier.inbound-dids.index')
            ->with('status', 'Inbound DID added. It is now available to the inbound call router.');
    }

    public function edit(InboundDid $inboundDid)
    {
        $this->requirePermission('carrier.edit');

        return view('backend.pages.carrier.inbound-dids.edit', [
            'inboundDid' => $inboundDid,
            'carriers' => Carrier::query()->orderBy('name')->get(['id', 'name']),
            'breadcrumbs' => [
                'title' => __('Edit Inbound DID'),
                'items' => [[
                    'label' => __('Inbound DIDs'),
                    'url' => route('admin.carrier.inbound-dids.index'),
                ]],
            ],
        ]);
    }

    public function update(Request $request, InboundDid $inboundDid)
    {
        $this->requirePermission('carrier.edit');
        $request->merge(['did' => $this->normalizeDid($request->input('did'))]);

        $data = $request->validate([
            'carrier_id' => ['required', 'integer', 'exists:carriers,id'],
            'did' => ['required', 'digits_between:3,20', Rule::unique('inbound_dids', 'did')->ignore($inboundDid->id)],
            'label' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $inboundDid->update($data);

        return redirect()->route('admin.carrier.inbound-dids.index')
            ->with('status', 'Inbound DID updated.');
    }

    public function destroy(InboundDid $inboundDid)
    {
        $this->requirePermission('carrier.delete');
        $inboundDid->delete();

        return redirect()->route('admin.carrier.inbound-dids.index')
            ->with('status', 'Inbound DID deleted. Calls to it will now be rejected.');
    }
}
