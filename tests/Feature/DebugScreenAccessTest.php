<?php

namespace Tests\Feature;

use App\Support\DebugAccess;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DebugScreenAccessTest extends TestCase
{
    const SECRET = 'detalhe-interno-do-erro';

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_teste/erro', function () {
            throw new \RuntimeException(self::SECRET);
        });
    }

    public function test_request_from_the_machine_itself_still_sees_the_debug_screen()
    {
        config(['app.debug' => true]);

        $response = $this->get('/_teste/erro');

        $response->assertStatus(500);
        $this->assertStringContainsString(self::SECRET, $response->getContent());
        $this->assertTrue(config('app.debug'));
    }

    public function test_request_from_the_network_gets_the_plain_error_page()
    {
        config(['app.debug' => true]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.2.50'])->get('/_teste/erro');

        $response->assertStatus(500);
        $this->assertStringNotContainsString(self::SECRET, $response->getContent());
        $this->assertStringNotContainsString('DebugScreenAccessTest', $response->getContent());
        $this->assertFalse(config('app.debug'));
    }

    public function test_json_error_from_the_network_has_no_trace()
    {
        config(['app.debug' => true]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.2.50'])->getJson('/_teste/erro');

        $response->assertStatus(500);
        $this->assertSame(['message' => 'Server Error'], $response->json());
    }

    public function test_method_not_allowed_from_the_network_does_not_show_the_stack()
    {
        config(['app.debug' => true]);

        $local = $this->get('/logout');
        $local->assertStatus(405);
        $this->assertStringContainsString('RouteCollection', $local->getContent());

        $remote = $this->withServerVariables(['REMOTE_ADDR' => '192.168.2.50'])->get('/logout');
        $remote->assertStatus(405);
        $this->assertStringNotContainsString('RouteCollection', $remote->getContent());
    }

    public function test_visitor_behind_a_proxy_on_the_same_machine_is_not_local()
    {
        config(['app.debug' => true]);

        // O proxy entrega a requisicao por 127.0.0.1; o cabecalho mostra que ha alguem atras dele.
        $response = $this->withHeaders(['X-Forwarded-For' => '192.168.2.50'])->get('/_teste/erro');

        $response->assertStatus(500);
        $this->assertStringNotContainsString(self::SECRET, $response->getContent());
    }

    public function test_address_listed_in_app_debug_ips_sees_the_debug_screen()
    {
        config(['app.debug' => true, 'app.debug_ips' => ['192.168.2.50']]);

        $allowed = $this->withServerVariables(['REMOTE_ADDR' => '192.168.2.50'])->get('/_teste/erro');
        $this->assertStringContainsString(self::SECRET, $allowed->getContent());

        $other = $this->withServerVariables(['REMOTE_ADDR' => '192.168.2.51'])->get('/_teste/erro');
        $this->assertStringNotContainsString(self::SECRET, $other->getContent());
    }

    public function test_debug_turned_off_stays_off_for_everyone()
    {
        config(['app.debug' => false]);

        $response = $this->get('/_teste/erro');

        $response->assertStatus(500);
        $this->assertStringNotContainsString(self::SECRET, $response->getContent());
        $this->assertFalse(config('app.debug'));
    }

    public function test_only_loopback_addresses_count_as_the_machine_itself()
    {
        foreach (['127.0.0.1', '127.10.0.9', '::1', '::ffff:127.0.0.1', ' 127.0.0.1 '] as $address) {
            $this->assertTrue(DebugAccess::isLoopback($address), $address);
        }

        foreach (['192.168.2.92', '10.0.0.1', '1270.0.0.1', '127.exemplo.com', '::ffff:192.168.2.92', '', null] as $address) {
            $this->assertFalse(DebugAccess::isLoopback($address), var_export($address, true));
        }
    }
}
