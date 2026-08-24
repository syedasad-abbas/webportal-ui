<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lead;
use Illuminate\Support\Facades\Gate;
class LeadsController extends Controller
{
    //
    /**
     * Display a listing of leads.
     */
    public function index()
    {
        Gate::authorize('leads.view');

        $leads = Lead::query()
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('patient_name', 'like', "%{$search}%")
                      ->orWhere('patient_phone', 'like', "%{$search}%")
                      ->orWhere('doctor_name', 'like', "%{$search}%")
                      ->orWhere('insurance', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20);

        return view('backend.pages.leads.index', compact('leads'));
    }

    /**
     * Show the form for creating a new lead.
     */
    public function create()
    {
        Gate::authorize('leads.create');

        return view('backend.pages.leads.create', [
            'breadcrumbs' => [
                'title' => __('Create New Lead'),
                'items' => [
                    ['label' => __('Dashboard'), 'url' => route('admin.dashboard')],
                    ['label' => __('Leads'), 'url' => route('admin.leads.index')],
                    ['label' => __('Create')],
                ]
            ]
        ]);
    }

    /**
     * Store a newly created lead.
     */
    public function store(Request $request)
    {
        Gate::authorize('leads.create');

        $validated = $request->validate([
            'date'                  => ['nullable', 'date'],
            'status'                => ['nullable', 'in:new,contacted,qualified,closed'],
            'patient_name'          => ['required', 'string', 'max:255'],
            'address'               => ['nullable', 'string', 'max:500'],
            'patient_phone'         => ['nullable', 'string', 'max:30'],
            'patient_dob'           => ['nullable', 'date'],
            'sizes'                 => ['nullable', 'string', 'max:100'],
            'insurance'             => ['nullable', 'string', 'max:255'],
            'member_id'             => ['nullable', 'string', 'max:100'],
            'secondary_member_id'   => ['nullable', 'string', 'max:100'],
            'products'              => ['nullable', 'string', 'max:500'],
            'doctor_name'           => ['nullable', 'string', 'max:255'],
            'doctor_npi'            => ['nullable', 'string', 'max:50'],
            'medications'           => ['nullable', 'string'],
            'treatments'            => ['nullable', 'string'],
            'dr_last_visit'         => ['nullable', 'date'],
        ]);

        Lead::create($validated);

        return redirect()
            ->route('admin.leads.index')
            ->with('success', __('Lead created successfully.'));
    }

    /**
     * Show the form for editing the specified lead.
     */
    public function edit(Lead $lead)
    {
        Gate::authorize('leads.edit');

        return view('backend.pages.leads.edit', [
            'lead' => $lead,
            'breadcrumbs' => [
                'title' => __('Edit Lead'),
                'items' => [
                    ['label' => __('Dashboard'), 'url' => route('admin.dashboard')],
                    ['label' => __('Leads'), 'url' => route('admin.leads.index')],
                    ['label' => __('Edit')],
                ]
            ]
        ]);
    }

    /**
     * Update the specified lead.
     */
    public function update(Request $request, Lead $lead)
    {
        Gate::authorize('leads.edit');

        $validated = $request->validate([
            'date'                  => ['nullable', 'date'],
            'status'                => ['nullable', 'in:new,contacted,qualified,closed'],
            'patient_name'          => ['required', 'string', 'max:255'],
            'address'               => ['nullable', 'string', 'max:500'],
            'patient_phone'         => ['nullable', 'string', 'max:30'],
            'patient_dob'           => ['nullable', 'date'],
            'sizes'                 => ['nullable', 'string', 'max:100'],
            'insurance'             => ['nullable', 'string', 'max:255'],
            'member_id'             => ['nullable', 'string', 'max:100'],
            'secondary_member_id'   => ['nullable', 'string', 'max:100'],
            'products'              => ['nullable', 'string', 'max:500'],
            'doctor_name'           => ['nullable', 'string', 'max:255'],
            'doctor_npi'            => ['nullable', 'string', 'max:50'],
            'medications'           => ['nullable', 'string'],
            'treatments'            => ['nullable', 'string'],
            'dr_last_visit'         => ['nullable', 'date'],
        ]);

        $lead->update($validated);

        return redirect()
            ->route('admin.leads.index')
            ->with('success', __('Lead updated successfully.'));
    }

    /**
     * Remove the specified lead from storage.
     */
    public function destroy(Lead $lead)
    {
        Gate::authorize('leads.delete');

        $lead->delete();

        return redirect()
            ->route('admin.leads.index')
            ->with('success', __('Lead deleted successfully.'));
    }

    /**
     * Bulk delete leads (optional - if you want to support bulk delete)
     */
    public function bulkDelete(Request $request)
    {
        Gate::authorize('leads.delete');

        $ids = $request->input('ids', []);

        if (!empty($ids)) {
            Lead::whereIn('id', $ids)->delete();
        }

        return redirect()
            ->route('admin.leads.index')
            ->with('success', __('Selected leads deleted successfully.'));
    }
}
