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
        \Illuminate\Support\Facades\Schema::shouldReceive('hasTable')->with('vicidial_credentials')->andReturn(false);
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

    public function test_customer_dial_uses_agent_api_and_validated_country_code(): void
    {
        Http::fake(['*' => Http::response('SUCCESS: external_dial function set')]);
        $this->assertTrue(app(VicidialService::class)->command(7, 'dial', '3001234567', ['phone_code' => '92'])['ok']);
        Http::assertSent(fn ($r) => $r['function'] === 'external_dial' && $r['agent_user'] === 'agent7'
            && $r['phone_code'] === '92' && $r['value'] === '3001234567' && $r['search'] === 'YES'
            && $r['preview'] === 'NO' && $r['focus'] === 'NO');
    }

    public function test_dial_cannot_inject_sip_destinations(): void
    {
        Http::fake();
        try {
            app(VicidialService::class)->command(7, 'dial', 'SIP/other', ['phone_code' => '92']);
            $this->fail('Expected validation error');
        } catch (ValidationException $e) {
            Http::assertNothingSent();
        }
    }

    public function test_dtmf_translates_hash_and_star_to_vicidial_encoding(): void
    {
        Http::fake(['*' => Http::response('SUCCESS: send_dtmf')]);
        app(VicidialService::class)->command(7, 'dtmf', '12*3#');
        Http::assertSent(fn ($r) => $r['function'] === 'send_dtmf' && $r['value'] === '12S3P');
    }

    public function test_conference_and_transfer_resolve_only_approved_destinations(): void
    {
        config()->set('vicidial.conference_targets', ['supervisor' => '8500']);
        Http::fake(['*' => Http::response('SUCCESS: transfer_conference')]);
        foreach (['conference' => 'DIAL_WITH_CUSTOMER', 'transfer' => 'BLIND_TRANSFER'] as $action => $value) {
            app(VicidialService::class)->command(7, $action, 'supervisor');
            Http::assertSent(fn ($r) => $r['function'] === 'transfer_conference' && $r['value'] === $value
                && $r['phone_number'] === '8500' && $r['dial_override'] === 'YES' && $r['agent_user'] === 'agent7');
        }
        Http::assertSentCount(2);
    }

    public function test_arbitrary_conference_number_is_rejected(): void
    {
        Http::fake();
        try {
            app(VicidialService::class)->command(7, 'conference', '8500');
            $this->fail('Expected allowlist rejection');
        } catch (ValidationException $e) {
            Http::assertNothingSent();
        }
    }

    public function test_customer_and_third_party_controls_never_hangup_both(): void
    {
        Http::fake(['*' => Http::response('SUCCESS: accepted')]);
        foreach (['park' => ['park_call', 'PARK_CUSTOMER'], 'unpark' => ['park_call', 'GRAB_CUSTOMER'],
            'end_conference' => ['transfer_conference', 'HANGUP_XFER'], 'logout' => ['logout', 'LOGOUT']] as $action => [$function, $value]) {
            app(VicidialService::class)->command(7, $action);
            Http::assertSent(fn ($r) => $r['function'] === $function && $r['value'] === $value);
        }
        Http::assertNotSent(fn ($r) => $r['value'] === 'HANGUP_BOTH');
    }

    public function test_queue_count_is_parsed_without_leaking_upstream_data(): void
    {
        Http::fake(['*' => Http::response('SUCCESS: calls_in_queue_count - 3')]);
        $result = app(VicidialService::class)->command(7, 'queue');
        $this->assertSame(3, $result['queuedCalls']);
        Http::assertSent(fn ($r) => $r['function'] === 'calls_in_queue_count' && $r['value'] === 'DISPLAY');
    }

    public function test_api_credential_failure_is_actionable_and_redacted(): void
    {
        Http::fake(['*' => Http::response('ERROR: Invalid Username/Password: |api-user|api-private-password|')]);
        try {
            app(VicidialService::class)->command(7, 'queue');
            $this->fail('Expected credential failure');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('rejected the API credentials', $e->getMessage());
            $this->assertStringNotContainsString('api-private-password', $e->getMessage());
        }
        Http::assertSentCount(1);
    }

    public function test_per_user_api_credentials_override_shared_account_without_changing_agent(): void
    {
        config()->set('vicidial.agents.7.api_username', 'specific-api');
        config()->set('vicidial.agents.7.api_password', 'specific-secret');
        Http::fake(['*' => Http::response('SUCCESS: accepted')]);
        $service = app(VicidialService::class);
        $this->assertTrue($service->apiReady(7));
        $service->command(7, 'pause');
        Http::assertSent(fn ($r) => $r['user'] === 'specific-api' && $r['pass'] === 'specific-secret'
            && $r['agent_user'] === 'agent7');
        $browser = json_encode($service->browserConfig(7));
        $this->assertStringNotContainsString('specific-api', $browser);
        $this->assertStringNotContainsString('specific-secret', $browser);
    }

    public function test_per_user_api_works_without_shared_credentials(): void
    {
        config()->set('vicidial.api_user', '');
        config()->set('vicidial.api_password', '');
        config()->set('vicidial.agents.7.api_username', 'specific-api');
        config()->set('vicidial.agents.7.api_password', 'specific-secret');
        $this->assertTrue(app(VicidialService::class)->apiReady(7));
        $this->assertFalse(app(VicidialService::class)->apiReady());
    }

    public function test_incomplete_per_user_api_pair_does_not_mix_with_shared_credentials(): void
    {
        config()->set('vicidial.agents.7.api_username', 'specific-api');
        Http::fake();
        $service = app(VicidialService::class);
        $this->assertFalse($service->apiReady(7));
        try {
            $service->command(7, 'pause');
            $this->fail('Expected incomplete credentials rejection');
        } catch (RuntimeException $e) {
            Http::assertNothingSent();
        }
    }

    public function test_phone_password_is_never_used_as_api_password(): void
    {
        config()->set('vicidial.api_user', '');
        config()->set('vicidial.api_password', '');
        $this->assertFalse(app(VicidialService::class)->apiReady(7));
    }

    public function test_new_api_password_is_encrypted_and_hidden_in_model_serialization(): void
    {
        $record = new \App\Models\VicidialCredential;
        $record->api_username = 'specific-api';
        $record->api_password = 'specific-secret';
        $this->assertNotSame('specific-secret', $record->getAttributes()['api_password']);
        $this->assertSame('specific-secret', $record->api_password);
        $this->assertArrayNotHasKey('api_password', $record->toArray());
        $this->assertArrayNotHasKey('api_username', $record->toArray());
    }
}
