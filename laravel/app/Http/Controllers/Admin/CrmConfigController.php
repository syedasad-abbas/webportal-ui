<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\CrmWorkflowSetting;
use App\Services\CrmWorkflow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class CrmConfigController extends Controller {
    public function index(Request $request, CrmWorkflow $workflow) {
        abort_unless($request->user()?->can('crm_config.view'),403);
        return view('backend.pages.crm-config.index', ['fields'=>CrmWorkflow::FIELDS, 'selected'=>$workflow->fields(), 'retrieval'=>$workflow->retrieval(), 'sources'=>$workflow->sources(), 'demoEnabled'=>$workflow->settings()?->demo_enabled??false, 'demoScenario'=>$workflow->settings()?->demo_scenario, 'demoNotes'=>\Illuminate\Support\Facades\Schema::hasTable('crm_demo_notes') ? \Illuminate\Support\Facades\DB::table('crm_demo_notes')->latest('updated_at')->limit(10)->get() : collect()]);
    }
    public function update(Request $request) {
        abort_unless($request->user()?->can('crm_config.edit'),403);
        if ($request->filled('demo_json')) {
            $decoded=json_decode($request->input('demo_json'),true);
            if (!is_array($decoded)) throw \Illuminate\Validation\ValidationException::withMessages(['demo_json'=>'Enter a valid demo scenario JSON object.']);
            $request->merge(['demo_scenario'=>$decoded]);
        }
        $data=$request->validate([
            'demo_json'=>['nullable','string','max:30000'], 'demo_enabled'=>['sometimes','boolean'],
            'demo_scenario'=>['required_if:demo_enabled,1','array:values,steps,credit_record,term_table'],
            'demo_scenario.values'=>['required_with:demo_scenario','array','max:60'],
            'demo_scenario.values.*'=>['string','max:2000'],
            'demo_scenario.steps'=>['required_with:demo_scenario','array','min:1','max:30'],
            'demo_scenario.steps.*'=>['array:id,instructions,result,effect'],
            'demo_scenario.steps.*.id'=>['required','string','regex:/^[a-z][a-z0-9_]{0,39}$/','distinct'],
            'demo_scenario.steps.*.instructions'=>['required','string','max:2000'],
            'demo_scenario.steps.*.result'=>['required','string','max:1000'],
            'demo_scenario.steps.*.effect'=>['sometimes',Rule::in(['credit_lookup','pain_quote'])],
            'demo_scenario.credit_record'=>['sometimes','array:id,accounts,credit_score'],
            'demo_scenario.credit_record.id'=>['required_with:demo_scenario.credit_record','string','max:80'],
            'demo_scenario.credit_record.credit_score'=>['required_with:demo_scenario.credit_record','integer','between:300,850'],
            'demo_scenario.credit_record.accounts'=>['required_with:demo_scenario.credit_record','array','min:1','max:30'],
            'demo_scenario.credit_record.accounts.*'=>['array:creditor,balance'],
            'demo_scenario.credit_record.accounts.*.creditor'=>['required','string','max:100'],
            'demo_scenario.credit_record.accounts.*.balance'=>['required','numeric','between:0,10000000'],
            'demo_scenario.term_table'=>['required_with:demo_scenario.credit_record','array','min:1','max:20'],
            'demo_scenario.term_table.*'=>['array:min_debt,max_debt,plan_account,base_plan,term_months'],
            'demo_scenario.term_table.*.min_debt'=>['required','numeric','min:0'],
            'demo_scenario.term_table.*.max_debt'=>['required','numeric','gte:demo_scenario.term_table.*.min_debt'],
            'demo_scenario.term_table.*.plan_account'=>['required','string','max:100'],
            'demo_scenario.term_table.*.base_plan'=>['required','string','max:100'],
            'demo_scenario.term_table.*.term_months'=>['required','integer','between:1,120'],
            'fields'=>['sometimes','array'], 'fields.*'=>['string','distinct',Rule::in(array_keys(CrmWorkflow::FIELDS))],
            'retrieval'=>['sometimes','array','max:14'],
            'retrieval.*.source'=>['required','distinct',Rule::in(array_keys((new CrmWorkflow)->sources()))],
            'retrieval.*.label'=>['required','string','max:80'],
            'retrieval.*.enabled'=>['required','boolean'],
            'retrieval.*.read_aloud'=>['required','boolean'],
            'retrieval.*.stage'=>['required',Rule::in(CrmWorkflow::STAGES)],
            'retrieval.*.missing'=>['required',Rule::in(['clarify','handoff'])],
        ]);
        $s=CrmWorkflowSetting::firstOrNew(['id'=>1]);
        $s->fields=$data['fields']??[]; $s->retrieval=array_map(fn($r)=>['source'=>$r['source'],'label'=>$r['label'],'enabled'=>(bool)$r['enabled'],'read_aloud'=>(bool)$r['read_aloud'],'stage'=>$r['stage'],'missing'=>$r['missing']],$data['retrieval']??[]);
        if ($request->has('demo_enabled')) $s->demo_enabled=(bool)$data['demo_enabled'];
        if (isset($data['demo_scenario'])) $s->demo_scenario=$data['demo_scenario'];
        $s->save();
        return back()->with('success','CRM configuration saved. New AI calls use these fields.');
    }
    public function dashboard(Request $request, CrmWorkflow $workflow) {
        abort_unless($request->user()?->can('crm_dashboard.configure'),403);
        $saved=collect($workflow->cards())->keyBy('key');
        $cards=array_map(fn($key)=>$saved->get($key,['key'=>$key,'label'=>ucwords(str_replace('_',' ',$key)),'enabled'=>false]),CrmWorkflow::METRICS);
        return view('backend.pages.crm-config.dashboard',['dashboardCards'=>$cards]);
    }
    public function updateDashboard(Request $request) {
        abort_unless($request->user()?->can('crm_dashboard.configure'),403);
        $data=$request->validate(['cards'=>['required','array','max:7'], 'cards.*.key'=>['required','distinct',Rule::in(CrmWorkflow::METRICS)],
            'cards.*.label'=>['required','string','max:80'], 'cards.*.enabled'=>['required','boolean']]);
        $cards=array_map(fn($c)=>['key'=>$c['key'],'label'=>$c['label'],'enabled'=>(bool)$c['enabled']],$data['cards']);
        CrmWorkflowSetting::updateOrCreate(['id'=>1],['cards'=>$cards]);
        return back()->with('success','Dashboard cards saved.');
    }
}
