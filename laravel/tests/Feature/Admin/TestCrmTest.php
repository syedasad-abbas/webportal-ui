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
