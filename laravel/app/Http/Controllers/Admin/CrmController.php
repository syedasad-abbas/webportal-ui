<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CrmApiClient;
use Illuminate\Http\Request;

class CrmController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->can('contacts.view'), 403);
        return view('backend.pages.crm.index');
    }

    public function proxy(Request $request, CrmApiClient $client, string $resource)
    {
        $permission = match ($request->method()) {
            'POST' => 'contacts.create', 'PUT' => 'contacts.edit', 'DELETE' => 'contacts.delete', default => 'contacts.view',
        };
        abort_unless($request->user()?->can($permission), 403);
        abort_unless(preg_match('~^(customers(?:/[1-9][0-9]*)?|reports/summary)$~', $resource), 404);
        abort_if($resource === 'reports/summary' && !$request->isMethod('GET'), 405);
        $data = $request->only(['name', 'phone', 'email', 'company', 'status', 'value', 'notes', 'page']);
        if (isset($data['phone']) && is_string($data['phone'])) $data['phone'] = preg_replace('/\D/', '', $data['phone']);
        return response()->json($client->request($request->method(), $resource, $data))->header('Cache-Control', 'no-store');
    }
}
