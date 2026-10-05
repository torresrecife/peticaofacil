<?php

namespace Tests\Feature;

use App\Support\FileManagerToken;
use App\User;
use Tests\TestCase;

class FileManagerAccessTest extends TestCase
{
    private function tokenCookie($response)
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === FileManagerToken::COOKIE) {
                return $cookie;
            }
        }

        return null;
    }

    public function test_logged_in_user_receives_a_valid_file_manager_token()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->get('/painel');
        $response->assertOk();

        $cookie = $this->tokenCookie($response);
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame((int) $user->id, FileManagerToken::verify($cookie->getValue(), config('app.key')));
    }

    public function test_guest_receives_no_file_manager_token()
    {
        $response = $this->get('/login');
        $response->assertOk();

        $this->assertNull($this->tokenCookie($response));
    }

    public function test_user_pending_password_change_receives_no_file_manager_token()
    {
        $user = factory(User::class)->create(['acesso_usu' => null]);

        $response = $this->actingAs($user)->get('/primeiro-acesso');
        $response->assertOk();

        $this->assertNull($this->tokenCookie($response));
    }

    public function test_logout_clears_the_file_manager_token()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->call('POST', '/logout', [], [
            FileManagerToken::COOKIE => 'valor-anterior',
        ]);

        $response->assertRedirect('/login');
        $response->assertCookieExpired(FileManagerToken::COOKIE);
    }

    public function test_ckfinder_connector_no_longer_authenticates_unconditionally()
    {
        $config = file_get_contents(public_path('ckfinder/config.php'));

        $this->assertStringContainsString('FileManagerToken::verify', $config);
        $this->assertSame(0, preg_match('/authentication\'\]\s*=\s*function\s*\(\)\s*\{\s*return true;/', $config));
    }
}
