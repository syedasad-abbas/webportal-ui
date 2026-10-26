<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\CrmController;
use App\Models\TestCrmCustomer;
use App\Services\CrmApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TestCrmTest extends TestCase
{
    private string $token = 'test-token-123456789012345678901234567890';
    private string $base = '/api/test-crm/v1';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'crm.test_enabled' => true, 'crm.test_token' => $this->token,
            'crm.base_url' => 'https://crm.example/api/v1', 'crm.token' => $this->token,
            'cache.default' => 'array', 'session.driver' => 'array']);
        \Illuminate\Support\Facades\DB::purge('sqlite');
        (require database_path('migrations/2026_10_23_000001_create_test_crm_customers_table.php'))->up();
        (require database_path('migrations/2026_10_24_000001_create_crm_workflow_settings.php'))->up();
        (require database_path('migrations/2026_10_24_000003_add_crm_call_retrieval.php'))->up();
        (require database_path('migrations/2026_10_26_000001_add_crm_demo_scenario.php'))->up();
        $this->withHeaders(['Authorization' => 'Bearer '.$this->token]);
        Http::preventStrayRequests();
    }

    private function customer(array $overrides = []): array
    {
        return array_merge(['name'=>'Test Customer', 'phone'=>'15559876543', 'email'=>'test@example.com',
            'company'=>'Lab', 'status'=>'new', 'value'=>'12.50', 'notes'=>'Test notes'], $overrides);
    }

    public function test_customer_lifecycle_phone_lookup_and_reports(): void
    {
        $id = $this->postJson($this->base.'/customers', $this->customer())->assertCreated()->json('data.id');
        $this->getJson($this->base.'/customers?phone=15559876543')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson($this->base.'/customers?phone=15550000000')->assertJsonCount(0, 'data');
        $this->putJson($this->base.'/customers/'.$id, $this->customer(['status'=>'won', 'notes'=>'Updated']))->assertOk()->assertJsonPath('data.notes', 'Updated');
        $this->getJson($this->base.'/customers/'.$id)->assertOk()->assertJsonPath('data.status', 'won');
        $this->getJson($this->base.'/reports/summary')->assertOk()->assertJsonPath('data.total_customers', 1)->assertJsonPath('data.open_leads', 0)->assertJsonPath('data.won_revenue', '12.5');
        $this->deleteJson($this->base.'/customers/'.$id)->assertNoContent();
        $this->getJson($this->base.'/customers/'.$id)->assertNotFound();
        $this->getJson($this->base.'/reports/summary')->assertJsonPath('data.total_customers', 0);
    }

    public function test_appointments_count_saved_details_not_general_mentions(): void
    {
        foreach (['Appointment Date: 2026-11-01 10:00 | Doctor: Test', 'appointment date: 2026-11-02', 'Asked about an Appointment', null] as $i => $notes) {
            TestCrmCustomer::create($this->customer(['phone'=>'1555987654'.$i, 'notes'=>$notes]));
        }
        $this->getJson($this->base.'/reports/summary')->assertOk()->assertJsonPath('data.appointments_booked', 2);
    }

    public function test_clarity_intake_is_structured_and_rejects_unknown_or_negative_values(): void
    {
        $this->postJson($this->base.'/customers', $this->customer(['intake'=>['monthly_income'=>'4000','loan_amount'=>'5000','pain_statement'=>'My exact words']]))->assertCreated()->assertJsonPath('data.intake.pain_statement','My exact words');
        $this->getJson($this->base.'/reports/summary')->assertOk()->assertJsonPath('data.intakes_recorded',1)->assertJsonPath('data.requested_amount_total',5000);
        $this->postJson($this->base.'/customers', $this->customer(['phone'=>'15550000001','intake'=>['account_number'=>'123']]))->assertUnprocessable();
        $this->postJson($this->base.'/customers', $this->customer(['phone'=>'15550000002','intake'=>['monthly_income'=>'-5']]))->assertUnprocessable();
    }

    public function test_workflow_settings_persist_and_token_is_not_serialized(): void
    {
        $settings=\App\Models\CrmWorkflowSetting::create(['id'=>1,'fields'=>['monthly_income'],'cards'=>[['key'=>'intakes_recorded','label'=>'Intakes','enabled'=>true]],'api_token'=>$this->token,'api_url'=>'https://crm.example/api/v1']);
        $workflow=new \App\Services\CrmWorkflow;
        $this->assertSame(['monthly_income'],$workflow->fields());
        $this->assertSame('Intakes',$workflow->cards()[0]['label']);
        $this->assertArrayNotHasKey('api_token',$settings->toArray());
        $this->assertNotSame($this->token,\Illuminate\Support\Facades\DB::table('crm_workflow_settings')->value('api_token'));
    }

    public function test_workflow_ui_checks_permission_and_saves_cards(): void
    {
        $controller=new \App\Http\Controllers\Admin\CrmConfigController;
        $request=Request::create('/admin/crm-config/dashboard','PUT',['cards'=>[['key'=>'intakes_recorded','label'=>'Completed intake','enabled'=>1]]]);
        $request->setUserResolver(fn()=>new class { public function can($p) {return $p==='crm_dashboard.configure';} });
        $controller->updateDashboard($request);
        $this->assertSame('Completed intake',(new \App\Services\CrmWorkflow)->cards()[0]['label']);
        $request->setUserResolver(fn()=>new class {public function can($p) {return false;} });
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->updateDashboard($request);
    }

    public function test_ui_configuration_saves_enabled_fields_and_retrieval_rules(): void
    {
        $request=Request::create('/admin/crm-config','PUT',['fields'=>['monthly_income'], 'api_url'=>'https://crm.example/api/v1', 'retrieval'=>[['source'=>'review.credit_score','label'=>'Score','enabled'=>1,'read_aloud'=>1,'stage'=>'debt_review','missing'=>'handoff']]]);
        $request->setUserResolver(fn()=>new class {public function can($p) {return $p==='crm_config.edit';} });
        (new \App\Http\Controllers\Admin\CrmConfigController)->update($request);
        $this->assertSame(['monthly_income'],(new \App\Services\CrmWorkflow)->fields());
        $this->assertSame('review.credit_score',\App\Models\CrmWorkflowSetting::find(1)->retrieval[0]['source']);
        $request=Request::create('/admin/crm-config','PUT',['fields'=>['ssn'], 'api_url'=>'https://crm.example/api/v1']);
        $request->setUserResolver(fn()=>new class {public function can($p) {return true;} });
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new \App\Http\Controllers\Admin\CrmConfigController)->update($request);
    }

    public function test_intake_form_preserves_backend_connection_without_api_fields(): void
    {
        \App\Models\CrmWorkflowSetting::create(['id'=>1,'api_url'=>'https://crm.example/api/v1','api_token'=>$this->token]);
        $request=Request::create('/admin/crm-config','PUT',['fields'=>['monthly_income']]);
        $request->setUserResolver(fn()=>new class {public function can($p) {return $p==='crm_config.edit';} });
        $controller=new \App\Http\Controllers\Admin\CrmConfigController;
        $controller->update($request);
        $this->assertSame(['url'=>'https://crm.example/api/v1','token'=>$this->token],(new \App\Services\CrmWorkflow)->connection());
        $request->merge(['api_url'=>'https://other.example','api_token'=>str_repeat('x',32)]);
        $controller->update($request);
        $this->assertSame(['url'=>'https://crm.example/api/v1','token'=>$this->token],(new \App\Services\CrmWorkflow)->connection());
    }

    public function test_customer_review_data_is_validated_and_saved_separately(): void
    {
        $this->postJson($this->base.'/customers',$this->customer(['review'=>['credit_score'=>700,'accounts'=>[['creditor'=>'Test lender','balance'=>1200]]]]))->assertCreated()->assertJsonPath('data.review.credit_score',700);
        $this->postJson($this->base.'/customers',$this->customer(['phone'=>'15550000001','review'=>['bank_account'=>'secret']]))->assertUnprocessable();
        $this->postJson($this->base.'/customers',$this->customer(['phone'=>'15550000002','review'=>['credit_score'=>999]]))->assertUnprocessable();
    }

    public function test_review_requires_active_link_and_projects_only_configured_customer_fields(): void
    {
        \Illuminate\Support\Facades\Schema::create('call_logs',function($t){$t->string('call_uuid');$t->timestamp('ended_at')->nullable();});
        $uuid='12345678-1234-4234-8234-123456789abc';
        \Illuminate\Support\Facades\DB::table('call_logs')->insert(['call_uuid'=>$uuid]);
        $workflow=new \App\Services\CrmWorkflow; $client=new CrmApiClient; $service=new \App\Services\CrmCallReview;
        $this->assertSame('verification_required',$service->fetch($uuid,'debt_review',$workflow,$client)['status']);
        Http::assertNothingSent();
        $rules=[];
        foreach (['credit_score'=>true,'program_cost'=>false,'debts_total'=>true] as $key=>$aloud) $rules[]=['source'=>'review.'.$key,'label'=>$key,'enabled'=>true,'read_aloud'=>$aloud,'stage'=>'debt_review','missing'=>'handoff'];
        \App\Models\CrmWorkflowSetting::create(['id'=>1,'retrieval'=>$rules]);
        \Illuminate\Support\Facades\DB::table('crm_call_customers')->insert(['call_uuid'=>$uuid,'customer_id'=>42,'verified_by'=>1,'verified_at'=>now()]);
        Http::fake(['crm.example/api/v1/customers/42'=>Http::response(['data'=>['id'=>42,'review'=>['credit_score'=>700,'program_cost'=>999,'unapproved'=>'secret']]])]);
        $result=$service->fetch($uuid,'debt_review',$workflow,$client);
        $this->assertCount(1,$result['values']); $this->assertSame('700',$result['values'][0]['value']);
        $this->assertSame(['program_cost'],$result['internal_only']);
        $this->assertSame('debts_total',$result['missing'][0]['label']);
        $this->assertSame('not_configured',$service->fetch($uuid,'plan_review',$workflow,$client)['status']);
        \Illuminate\Support\Facades\DB::table('call_logs')->update(['ended_at'=>now()]);
        $this->assertSame('verification_required',$service->fetch($uuid,'debt_review',$workflow,$client)['status']);
        Http::assertSentCount(1);
    }

    public function test_account_projection_excludes_unapproved_nested_fields(): void
    {
        $rule=['source'=>'review.accounts','label'=>'Accounts','enabled'=>true,'read_aloud'=>true,'missing'=>'handoff'];
        $result=(new \App\Services\CrmCallReview)->project(['review'=>['accounts'=>[['creditor'=>'Test lender','balance'=>0,'account_number'=>'secret']]]],[$rule]);
        $this->assertSame([['creditor'=>'Test lender','balance'=>'0']],$result['values'][0]['value']);
    }

    public function test_demo_template_is_editable_and_persists_only_when_explicitly_saved(): void
    {
        $this->assertNull((new \App\Services\CrmWorkflow)->settings());
        $scenario=json_decode(file_get_contents(public_path('demo/clarity-scenario.json')),true);
        $scenario['values']['monthly_payment']='333';
        $request=Request::create('/admin/crm-config','PUT',['demo_enabled'=>1,'demo_json'=>json_encode($scenario)]);
        $request->setUserResolver(fn()=>new class {public function can($p){return $p==='crm_config.edit';}});
        (new \App\Http\Controllers\Admin\CrmConfigController)->update($request);
        $saved=\App\Models\CrmWorkflowSetting::find(1);
        $this->assertTrue($saved->demo_enabled);
        $this->assertSame('333',$saved->demo_scenario['values']['monthly_payment']);
        $this->assertCount(13,$saved->demo_scenario['steps']);
    }

    public function test_intro_demo_validates_persists_and_fetches_configured_report(): void
    {
        $scenario=json_decode(file_get_contents(public_path('demo/clarity-intro-scenario.json')),true);
        $request=Request::create('/admin/crm-config','PUT',['demo_enabled'=>1,'demo_json'=>json_encode($scenario)]);
        $request->setUserResolver(fn()=>new class {public function can($p){return $p==='crm_config.edit';}});
        (new \App\Http\Controllers\Admin\CrmConfigController)->update($request);
        $saved=\App\Models\CrmWorkflowSetting::find(1)->demo_scenario;
        $report=(new \App\Services\CrmDemoReport)->fetch($saved);
        $this->assertSame('report_ready',$report['status']);
        $this->assertTrue($report['simulated']);
        $this->assertEquals(20000,$report['debts_total']);
        $this->assertSame(2,$report['accounts_count']);
        $this->assertSame(48,$report['enrollment']['term_months']);
        $saved['credit_record']['accounts']=[['creditor'=>'Edited demo creditor','balance'=>5000]];
        $this->assertSame(24,(new \App\Services\CrmDemoReport)->fetch($saved)['enrollment']['term_months']);
        $saved['credit_record']['accounts'][0]['balance']=90000;
        $this->assertSame('unavailable',(new \App\Services\CrmDemoReport)->fetch($saved)['status']);
        Http::assertNothingSent();
    }

    public function test_demo_http_operations_require_active_explicit_demo_and_store_quote_separately(): void
    {
        (require database_path('migrations/2026_10_26_000002_create_crm_demo_notes.php'))->up();
        \Illuminate\Support\Facades\Schema::create('users',function($t){$t->id();$t->string('name');});
        \Illuminate\Support\Facades\DB::table('users')->insert(['id'=>1,'name'=>'Demo owner']);
        require_once database_path('migrations/2020_07_24_184706_create_permission_tables.php');
        (new \CreatePermissionTables)->up();
        \Illuminate\Support\Facades\Gate::before(fn($user,$ability)=>$ability==='contacts.view' ? true : null);
        \Illuminate\Support\Facades\Schema::create('call_logs',function($t){$t->string('call_uuid');$t->unsignedBigInteger('user_id');$t->timestamp('ended_at')->nullable();});
        $uuid='12345678-1234-4234-8234-123456789abc';
        \Illuminate\Support\Facades\DB::table('call_logs')->insert(['call_uuid'=>$uuid,'user_id'=>1]);
        config(['services.backend.internal_token'=>$this->token]);
        $scenario=json_decode(file_get_contents(public_path('demo/clarity-intro-scenario.json')),true);
        \App\Models\CrmWorkflowSetting::create(['id'=>1,'demo_enabled'=>true,'demo_scenario'=>$scenario]);
        $url='/api/internal/crm-workflow';
        $this->postJson($url,['operation'=>'demo_credit','call_id'=>$uuid])->assertOk()->assertJsonPath('data.status','disabled');
        \Illuminate\Support\Facades\DB::table('crm_call_customers')->insert(['call_uuid'=>$uuid,'customer_id'=>42,'verified_by'=>1,'verified_at'=>now(),'is_demo'=>true]);
        $this->postJson($url,['operation'=>'demo','call_id'=>$uuid])->assertOk()->assertJsonMissingPath('data.scenario.credit_record');
        $this->postJson($url,['operation'=>'demo_credit','call_id'=>$uuid])->assertOk()->assertJsonPath('data.status','report_ready')->assertJsonPath('data.accounts_count',2);
        $this->assertSame(48,json_decode(\Illuminate\Support\Facades\DB::table('crm_demo_notes')->value('credit_report'),true)['enrollment']['term_months']);
        $quote='I lost my job.  Then my car broke down.';
        $this->postJson($url,['operation'=>'demo_pain','call_id'=>$uuid,'quote'=>$quote])->assertOk()->assertJsonPath('data.status','saved');
        $this->assertSame($quote,\Illuminate\Support\Facades\DB::table('crm_demo_notes')->value('pain_quote'));
        $this->assertSame(0,TestCrmCustomer::count());
        \Illuminate\Support\Facades\DB::table('call_logs')->update(['ended_at'=>now()]);
        $this->postJson($url,['operation'=>'demo_credit','call_id'=>$uuid])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_demo_cannot_enable_without_a_valid_scenario(): void
    {
        $request=Request::create('/admin/crm-config','PUT',['demo_enabled'=>1]);
        $request->setUserResolver(fn()=>new class {public function can($p){return true;}});
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new \App\Http\Controllers\Admin\CrmConfigController)->update($request);
    }

    public function test_internal_workflow_rejects_missing_token(): void
    {
        config(['services.backend.internal_token'=>str_repeat('s',32)]);
        $this->postJson('/api/internal/crm-workflow',['operation'=>'config','call_id'=>'12345678-1234-4234-8234-123456789abc'])->assertUnauthorized();
    }

    public function test_validation_and_duplicate_phone(): void
    {
        $this->postJson($this->base.'/customers', $this->customer())->assertCreated();
        $this->postJson($this->base.'/customers', $this->customer())->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->postJson($this->base.'/customers', $this->customer(['phone'=>'bad','status'=>'other','value'=>-1,'email'=>'bad']))->assertUnprocessable()->assertJsonValidationErrors(['phone','status','value','email']);
        $this->getJson($this->base.'/customers?phone=bad')->assertUnprocessable();
    }

    public function test_disabled_missing_and_wrong_token_are_rejected(): void
    {
        $this->withHeaders(['Authorization'=>'Bearer wrong'])->getJson($this->base.'/customers')->assertUnauthorized();
        $this->withHeaders(['Authorization'=>''])->getJson($this->base.'/customers')->assertUnauthorized();
        config(['crm.test_token'=>'']);
        $this->getJson($this->base.'/customers')->assertUnauthorized();
        config(['crm.test_enabled'=>false]);
        $this->getJson($this->base.'/customers')->assertNotFound();
    }

    public function test_pagination_is_bounded(): void
    {
        for ($i=0; $i<21; $i++) TestCrmCustomer::create($this->customer(['phone'=>'1555987'.str_pad((string)$i,4,'0',STR_PAD_LEFT)]));
        $this->getJson($this->base.'/customers')->assertOk()->assertJsonCount(20,'data')->assertJsonPath('last_page',2);
        $this->getJson($this->base.'/customers?page=2')->assertJsonCount(1,'data');
    }

    public function test_adapter_sends_token_and_filters_to_configured_api(): void
    {
        Http::fake(['crm.example/*'=>Http::response(['data'=>[$this->customer()]],200)]);
        $result = (new CrmApiClient)->request('GET','customers',['phone'=>'15559876543']);
        $this->assertSame('Test Customer',$result['data'][0]['name']);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization','Bearer '.$this->token) && $request['phone']==='15559876543');
    }

    public function test_adapter_rejects_unconfigured_url_and_redirects(): void
    {
        Http::fake(['*'=>Http::response('',302,['Location'=>'https://other.example'])]);
        try { (new CrmApiClient)->request('GET','customers'); $this->fail('Redirect accepted'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(502,$e->getStatusCode()); }
        config(['crm.base_url'=>'http://external.example/api']);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        (new CrmApiClient)->request('GET','customers');
    }

    public function test_adapter_maps_validation_and_does_not_retry_writes(): void
    {
        Http::fake(['*'=>Http::response(['errors'=>['phone'=>['Already exists']]],422)]);
        try { (new CrmApiClient)->request('POST','customers',$this->customer()); $this->fail('Accepted invalid input'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertSame(['Already exists'],$e->errors()['phone']); }
        Http::assertSentCount(1);
    }

    public function test_proxy_denies_users_without_contact_permission(): void
    {
        $request = Request::create('/admin/crm/api/customers','GET');
        $request->setUserResolver(fn () => new class { public function can($permission) { return false; } });
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        (new CrmController)->proxy($request,new CrmApiClient,'customers');
    }

    public function test_proxy_normalizes_dialer_phone_and_returns_crm_data(): void
    {
        Http::fake(['*'=>Http::response(['data'=>[$this->customer()]],200)]);
        $request = Request::create('/admin/crm/api/customers','GET',['phone'=>'+1 (555) 987-6543']);
        $request->setUserResolver(fn () => new class { public function can($permission) { return $permission==='contacts.view'; } });
        $response = (new CrmController)->proxy($request,new CrmApiClient,'customers');
        $this->assertSame('Test Customer',$response->getData(true)['data'][0]['name']);
        Http::assertSent(fn ($request) => $request['phone']==='15559876543');
    }
}
