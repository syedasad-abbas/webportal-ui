<?php

namespace Tests\Unit\Services;

use App\Services\VicidialService;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class VicidialServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('vicidial', [
            'enabled' => true,
            'api_url' => 'https://vici.example/agc/api.php',
            'agent_url' => 'https://vici.example/agc/vicidial.php',
            'api_user' => 'api-user', 'api_password' => 'api-private-password',
            'ws_url' => 'wss://vici.example/ws', 'sip_domain' => 'vici.example',
            'ice_servers' => [], 'dispositions' => ['SALE'],
            'agents' => [7 => ['agent_user' => 'agent7', 'sip_username' => 'phone7', 'sip_password' => 'phone-private-password', 'crm_agent_id' => 'crm7', 'crm_team_id' => 'team1']],
        ]);
        Http::preventStrayRequests();
    }

    public function test_api_commands_target_only_the_server_assigned_agent(): void
    {
        Http::fake(['*' => Http::response('SUCCESS: command sent')]);
        $service = app(VicidialService::class);
        foreach (['connect' => ['call_agent', 'CALL'], 'ready' => ['external_pause', 'RESUME'], 'pause' => ['external_pause', 'PAUSE'], 'hangup' => ['external_hangup', '1'], 'disposition' => ['external_status', 'SALE']] as $action => [$function, $value]) {
            $this->assertTrue($service->command(7, $action, 'SALE')['ok']);
            Http::assertSent(fn ($request) => $request->method() === 'POST'
                && $request->url() === 'https://vici.example/agc/api.php'
                && $request['agent_user'] === 'agent7'
                && $request['pass'] === 'api-private-password'
                && $request['function'] === $function && $request['value'] === $value);
        }
        Http::assertSentCount(5);
    }

    public function test_unmapped_users_cannot_control_an_agent(): void
    {
        Http::fake();
        try {
            app(VicidialService::class)->command(8, 'hangup');
            $this->fail('Expected forbidden');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        Http::assertNothingSent();
    }

    public function test_disposition_allowlist_is_enforced(): void
    {
        Http::fake();
        try {
            app(VicidialService::class)->command(7, 'disposition', 'UNCONFIGURED');
            $this->fail('Expected validation failure');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('value', $e->errors());
        }
        Http::assertNothingSent();
    }

    public function test_browser_receives_its_phone_credentials_but_never_api_credentials(): void
    {
        $config = app(VicidialService::class)->browserConfig(7);
        $this->assertSame('phone7', $config['username']);
        $this->assertStringNotContainsString('api-private-password', json_encode($config));
        $this->assertStringNotContainsString('api-user', json_encode($config));
    }

    public function test_plaintext_api_endpoint_is_rejected_before_sending_credentials(): void
    {
        Http::fake();
        config()->set('vicidial.api_url', 'http://vici.example/agc/api.php');
        try {
            app(VicidialService::class)->command(7, 'connect');
            $this->fail('Expected invalid URL');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('https', $e->getMessage());
        }
        Http::assertNothingSent();
    }

    public function test_error_bodies_are_not_forwarded_and_commands_are_not_retried(): void
    {
        Http::fake(['*' => Http::response('ERROR: api-private-password', 200)]);
        try {
            app(VicidialService::class)->command(7, 'hangup');
            $this->fail('Expected rejection');
        } catch (RuntimeException $e) {
            $this->assertStringNotContainsString('api-private-password', $e->getMessage());
        }
        Http::assertSentCount(1);
    }

    public function test_login_html_is_not_mistaken_for_success(): void
    {
        Http::fake(['*' => Http::response('<html>Login</html>')]);
        $this->expectException(RuntimeException::class);
        app(VicidialService::class)->command(7, 'ready');
    }

    public function test_lead_lookup_uses_configured_crm_ids_and_clears_finished_calls(): void
    {
        Http::fakeSequence()->push('SUCCESS: st_get_agent_active_lead lead found - agent7|15551234567|123|vendor|province|phrase|source')
            ->push('ERROR: no active lead found - agent7');
        $service = app(VicidialService::class);
        $this->assertSame(['phone' => '15551234567', 'id' => '123'], $service->command(7, 'lead')['lead']);
        $this->assertNull($service->command(7, 'lead')['lead']);
        Http::assertSent(fn ($request) => $request['value'] === 'crm7' && $request['vendor_id'] === 'team1');
    }

    public function test_controller_rejects_users_without_dial_permission(): void
    {
        $request = \Illuminate\Http\Request::create('/admin/vicidial/command', 'POST');
        $request->setUserResolver(fn () => new class {
            public function can($permission) { return false; }
        });
        $this->expectException(HttpException::class);
        app(\App\Http\Controllers\Admin\VicidialController::class)->command($request, app(VicidialService::class));
    }

    public function test_controller_preserves_forbidden_for_unmapped_user(): void
    {
        $request = \Illuminate\Http\Request::create('/admin/vicidial/command', 'POST', ['action' => 'hangup']);
        $request->setUserResolver(fn () => new class {
            public $id = 8;
            public function can($permission) { return true; }
        });
        try {
            app(\App\Http\Controllers\Admin\VicidialController::class)->command($request, app(VicidialService::class));
            $this->fail('Expected forbidden');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_disabled_integration_cannot_send_commands(): void
    {
        config()->set('vicidial.enabled', false);
        Http::fake();
        try {
            app(VicidialService::class)->command(7, 'connect');
            $this->fail('Expected disabled integration');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
        Http::assertNothingSent();
    }
    public function test_phone_registration_is_available_without_api_credentials(): void
    {
        config()->set('vicidial.api_password', '');
        config()->set('vicidial.api_url', '');
        $service = app(VicidialService::class);
        $this->assertFalse($service->apiReady());
        $this->assertSame('phone7', $service->browserConfig(7)['username']);
        Http::assertNothingSent();
    }

    public function test_missing_ca_bundle_prevents_api_request(): void
    {
        config()->set('vicidial.ca_bundle', '/nonexistent/vicidial-ca.pem');
        Http::fake();
        try {
            app(VicidialService::class)->command(7, 'pause');
            $this->fail('Expected missing CA error');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('CA certificate', $e->getMessage());
        }
        Http::assertNothingSent();
    }
}
