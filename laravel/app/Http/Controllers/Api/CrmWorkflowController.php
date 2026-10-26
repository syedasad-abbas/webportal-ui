<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\CrmWorkflow;
use App\Services\CrmApiClient;
use Illuminate\Http\Request;
class CrmWorkflowController extends Controller {
    public function proxy(Request $request, CrmApiClient $client) {
        $token=(string)config('services.backend.internal_token');
        abort_unless(strlen($token)>=32 && hash_equals($token,(string)$request->bearerToken()),401);
        $data=$request->validate(['method'=>['required','in:GET,POST,PUT'], 'resource'=>['required','regex:~^customers(?:/[1-9][0-9]*)?$~'], 'data'=>['sometimes','array']]);
        return response()->json($client->request($data['method'],$data['resource'],$data['data']??[]))->header('Cache-Control','no-store');
    }
    public function handle(Request $request, CrmWorkflow $workflow, CrmApiClient $client) {
        $token=(string)config('services.backend.internal_token');
        abort_unless(strlen($token)>=32 && hash_equals($token,(string)$request->bearerToken()),401);
        $data=$request->validate(['operation'=>['required','in:config,intake,review,demo,demo_credit,demo_pain'], 'call_id'=>['required','uuid'], 'stage'=>['required_if:operation,review',\Illuminate\Validation\Rule::in(CrmWorkflow::STAGES)],
            'quote'=>['required_if:operation,demo_pain','string','max:2000'],
            'phone'=>['required_if:operation,intake','regex:/^[0-9]{7,15}$/'], 'name'=>['required_if:operation,intake','string','max:200'],
            'intake'=>['required_if:operation,intake','array'], 'intake.*'=>['string','max:2000']]);
        $call=\Illuminate\Support\Facades\DB::table('call_logs')->where('call_uuid',$data['call_id'])->whereNull('ended_at')->first();
        $user=$call ? \App\Models\User::find($call->user_id) : null;
        abort_unless($user && ($data['operation']==='intake' ? ($user->can('contacts.create') && $user->can('contacts.edit')) : $user->can('contacts.view')),403);
        if (in_array($data['operation'],['demo','demo_credit','demo_pain'],true)) {
            $settings=$workflow->settings();
            $link=\Illuminate\Support\Facades\DB::table('crm_call_customers')->where('call_uuid',$data['call_id'])->where('is_demo',true)->first();
            if (!$settings?->demo_enabled || !$link || !$settings->demo_scenario) return response()->json(['data'=>['status'=>'disabled','message'=>'This call is not explicitly enabled for simulation.']])->header('Cache-Control','no-store');
            $scenario=$settings->demo_scenario;
            if ($data['operation']==='demo_credit') {
                $result=app(\App\Services\CrmDemoReport::class)->fetch($scenario);
                if ($result['status']==='report_ready') {
                    \Illuminate\Support\Facades\DB::table('crm_demo_notes')->updateOrInsert(['call_uuid'=>$data['call_id']],
                        ['customer_id'=>$link->customer_id,'credit_report'=>json_encode($result),'created_at'=>now(),'updated_at'=>now()]);
                }
            } elseif ($data['operation']==='demo_pain') {
                // Separate demo notes: never mutate a real customer's financial record.
                \Illuminate\Support\Facades\DB::table('crm_demo_notes')->updateOrInsert(['call_uuid'=>$data['call_id']],
                    ['customer_id'=>$link->customer_id,'pain_quote'=>$data['quote'],'provenance'=>'model_transcribed_quote','created_at'=>now(),'updated_at'=>now()]);
                $result=['status'=>'saved','simulated'=>true,'message'=>'Quoted caller words saved to demo CRM notes. Acknowledge empathetically, then stop this demo.'];
            } else {
                unset($scenario['credit_record'],$scenario['term_table']);
                $result=['status'=>'demo','scenario'=>$scenario];
            }
            return response()->json(['data'=>$result])->header('Cache-Control','no-store');
        }
        if ($data['operation']==='review') return response()->json(['data'=>app(\App\Services\CrmCallReview::class)->fetch($data['call_id'],$data['stage'],$workflow,$client)])->header('Cache-Control','no-store');
        $fields=array_intersect_key(CrmWorkflow::FIELDS,array_flip($workflow->fields()));
        if ($data['operation']==='config') return response()->json(['data'=>['fields'=>$fields,'retrieval'=>$workflow->retrieval()]]);
        abort_if(\Illuminate\Support\Facades\DB::table('crm_call_customers')->where('call_uuid',$data['call_id'])->where('is_demo',true)->exists(),409,'Demo values must not be written to customer records.');
        abort_unless($fields && !array_diff(array_keys($data['intake']),array_keys($fields)),422,'Field not enabled');
        $existing=$client->request('GET','customers',['phone'=>$data['phone']])['data'][0]??null;
        $payload=['name'=>$data['name'],'phone'=>$data['phone'],'status'=>$existing['status']??'new','value'=>$existing['value']??'0.00',
            'intake'=>array_merge($existing['intake']??[],$data['intake'])];
        $result=$client->request($existing?'PUT':'POST',$existing?'customers/'.$existing['id']:'customers',$payload);
        return response()->json(['data'=>['saved'=>isset($result['data']['id'])]]);
    }
}
