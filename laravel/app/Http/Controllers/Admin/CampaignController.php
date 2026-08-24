<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Jobs\ImportCampaignLeads;
use Illuminate\Support\Facades\Storage;

class CampaignController extends Controller
{
    public function __construct()
{
    $this->middleware('can:campaign.add')->only(['index','store','status']);
}
    // Campaign list page
    public function index()
    {
        $breadcrumbs = [
            'title' => __('Campaigns'),
            'items' => [
                ['label' => __('Dashboard'), 'url' => route('admin.dashboard')],
            ],
        ];

        $campaigns = Campaign::latest()->get();

        return view('backend.pages.Campaigns.index', compact('breadcrumbs', 'campaigns'));
    }

    // ✅ NEW: status endpoint for polling in UI
    public function status(Campaign $campaign)
    {
        return response()->json([
            'status' => $campaign->import_status,
            'imported' => $campaign->imported_rows,
            'total' => $campaign->total_rows,
            'error' => $campaign->import_error,
        ]);
    }

    // Store campaign + queue import job
    public function store(Request $request)
    {
        $request->validate([
            'list_id' => 'required|string',
            'list_name' => 'required|string',
            'list_description' => 'required|string',
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        // Upload file
        $filePath = $request->file('file')->store('campaigns', 'public');

        // ✅ Save campaign with import fields
        $campaign = Campaign::create([
            'list_id' => $request->list_id,
            'list_name' => $request->list_name,
            'list_description' => $request->list_description,
            'file_path' => $filePath,

            // these columns must exist in campaigns table
            'import_status' => 'processing',
            'import_started_at' => now(),
            'import_completed_at' => null,
            'total_rows' => 0,
            'imported_rows' => 0,
            'import_error' => null,
        ]);

        // ✅ Dispatch background job to parse file and insert leads
        ImportCampaignLeads::dispatch($campaign->id);

        return redirect()
            ->route('admin.campaigns.index')
            ->with('success', 'Campaign uploaded. Processing started… You can continue working.');
    }

    public function edit(Campaign $campaign)
    {
        $breadcrumbs = [
            'title' => __('Edit Campaign'),
            'items' => [
                ['label' => __('Dashboard'), 'url' => route('admin.dashboard')],
                ['label' => __('Campaigns'), 'url' => route('admin.campaigns.index')],
            ],
        ];

        return view('backend.pages.Campaigns.edit', compact('breadcrumbs', 'campaign'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $request->validate([
            'list_id' => 'required|string',
            'list_name' => 'required|string',
            'list_description' => 'required|string',
            'file' => 'nullable|mimes:xlsx,xls,csv|max:10240',
        ]);

        $payload = [
            'list_id' => $request->list_id,
            'list_name' => $request->list_name,
            'list_description' => $request->list_description,
        ];

        $shouldReimport = false;

        if ($request->hasFile('file')) {
            if ($campaign->file_path) {
                Storage::disk('public')->delete($campaign->file_path);
            }
            $payload['file_path'] = $request->file('file')->store('campaigns', 'public');
            $payload = array_merge($payload, [
                'import_status' => 'processing',
                'import_started_at' => now(),
                'import_completed_at' => null,
                'total_rows' => 0,
                'imported_rows' => 0,
                'import_error' => null,
            ]);
            $shouldReimport = true;
        }

        $campaign->update($payload);

        if ($shouldReimport) {
            ImportCampaignLeads::dispatch($campaign->id);
        }

        return redirect()
            ->route('admin.campaigns.index')
            ->with('success', __('Campaign updated successfully.'));
    }

    public function destroy(Campaign $campaign)
    {
        if ($campaign->file_path) {
            Storage::disk('public')->delete($campaign->file_path);
        }

        $campaign->delete();

        return redirect()
            ->route('admin.campaigns.index')
            ->with('success', __('Campaign deleted successfully.'));
    }
}
