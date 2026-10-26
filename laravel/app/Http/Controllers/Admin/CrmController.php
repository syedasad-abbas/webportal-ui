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

    public function activeCalls(Request $request) {
        abort_unless($request->user()?->can('contacts.view'),403);
        $calls=\Illuminate\Support\Facades\DB::table('call_logs')->whereNull('ended_at')->whereNotNull('connected_at')
            ->when(!$request->user()->can('crm_config.edit'),fn($q)=>$q->where('user_id',$request->user()->id))
            ->orderByDesc('id')->limit(30)->get(['call_uuid']);
        return response()->json(['data'=>$calls])->header('Cache-Control','no-store');
    }
    public function verifyCall(Request $request, CrmApiClient $client) {
        abort_unless($request->user()?->can('contacts.view'),403);
        $data=$request->validate(['call_uuid'=>['required','uuid'],'customer_id'=>['required','integer','min:1'],'verified'=>['required','accepted'],'is_demo'=>['sometimes','boolean']]);
        $call=\Illuminate\Support\Facades\DB::table('call_logs')->where('call_uuid',$data['call_uuid'])->whereNull('ended_at')->whereNotNull('connected_at')->first();
        abort_unless($call && ((int)$call->user_id===(int)$request->user()->id || $request->user()->can('crm_config.edit')),403);
        abort_if($request->boolean('is_demo') && !$request->user()->can('crm_config.edit'),403);
        $client->request('GET','customers/'.$data['customer_id']);
        \Illuminate\Support\Facades\DB::table('crm_call_customers')->updateOrInsert(['call_uuid'=>$data['call_uuid']],['customer_id'=>$data['customer_id'],'verified_by'=>$request->user()->id,'verified_at'=>now(), 'is_demo'=>$request->boolean('is_demo')]);
        return response()->json(['data'=>['linked'=>true]]);
    }

    public function proxy(Request $request, CrmApiClient $client, string $resource)
    {
        $permission = match ($request->method()) {
            'POST' => 'contacts.create', 'PUT' => 'contacts.edit', 'DELETE' => 'contacts.delete', default => 'contacts.view',
        };
        abort_unless($request->user()?->can($permission), 403);
        abort_unless(preg_match('~^(customers(?:/[1-9][0-9]*)?|reports/summary)$~', $resource), 404);
        abort_if($resource === 'reports/summary' && !$request->isMethod('GET'), 405);
        $data = $request->only(['name', 'phone', 'email', 'company', 'status', 'value', 'notes', 'intake', 'review', 'page']);
        if (isset($data['phone']) && is_string($data['phone'])) $data['phone'] = preg_replace('/\D/', '', $data['phone']);
        return response()->json($client->request($request->method(), $resource, $data))->header('Cache-Control', 'no-store');
    }
}
