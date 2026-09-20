<?php

namespace Tests\Feature;

use App\User;
use App\Services\UserAccountService;
use Tests\TestCase;

class TemporaryPasswordTest extends TestCase
{
    private function issue()
    {
        $user = factory(User::class)->create();
        $service = app(UserAccountService::class);
        $service->update($user, [
            'nome_usu' => $user->nome_usu, 'login_usu' => $user->login_usu,
            'nivel_usu' => 'USU', 'status_usu' => 'ATI', 'id_setor' => null,
        ], 'temporary-secret-123');
        return $user;
    }

    public function test_temporary_login_requires_new_password_and_cannot_be_reused()
    {
        $user = $this->issue();
        $this->post('/login', ['username' => $user->login_usu, 'password' => 'temporary-secret-123'])
            ->assertRedirect('/primeiro-acesso');
        $this->get('/painel')->assertRedirect('/primeiro-acesso');
        foreach (['123456', 'temporary-secret-123', 'Abcdefgh1!x', 'abcdefghij1!', 'ABCDEFGHIJ1!', 'Abcdefghijk!', 'Abcdefghij12', 'Abcdefghij1 '] as $invalid) {
            $this->post('/primeiro-acesso', ['password' => $invalid, 'password_confirmation' => $invalid])
                ->assertSessionHasErrors('password');
        }
        $this->post('/primeiro-acesso', ['password' => 'Abcdefghij1!', 'password_confirmation' => 'Abcdefghij1!'])
            ->assertRedirect('/painel');
        $user->refresh();
        $this->assertFalse($user->requiresInitialPasswordChange());
        $this->assertNull($user->temporary_password_expires_at);
        $this->assertFalse(app(UserAccountService::class)->verifyPassword($user, 'temporary-secret-123'));
        $this->post('/primeiro-acesso', ['password' => 'Outra frase exclusiva 2026', 'password_confirmation' => 'Outra frase exclusiva 2026'])
            ->assertStatus(403);
    }

    public function test_expired_temporary_password_is_rejected_even_after_login()
    {
        $user = $this->issue();
        $user->temporary_password_expires_at = now()->subMinute();
        $user->save();
        $this->post('/login', ['username' => $user->login_usu, 'password' => 'temporary-secret-123'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->actingAs($user)->get('/primeiro-acesso')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_password_change_invalidates_session_with_old_password_hash()
    {
        $user = $this->issue();
        $oldHash = $user->password;
        app(UserAccountService::class)->updatePassword($user, 'Nova frase exclusiva 2026');
        $this->actingAs($user)->withSession(['password_hash' => $oldHash])->get('/painel')
            ->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_attempts_are_limited()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => 'unknown', 'password' => 'wrong']);
        }
        $this->postJson('/login', ['username' => 'unknown', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_admin_reissue_displays_secret_without_storing_it_in_session_and_invalidates_old_password()
    {
        $user = $this->issue();
        $admin = factory(User::class)->create(['nivel_usu' => 'ADM']);
        $data = [
            'nome_usu' => $user->nome_usu, 'login_usu' => $user->login_usu,
            'nivel_usu' => 'USU', 'status_usu' => 'ATI', 'id_setor' => null,
            'reset_password' => 1,
        ];
        $response = $this->actingAs($admin)->put('/admin/usuarios/' . $user->id, $data)->assertOk();
        $secret = $response->viewData('temporaryPassword');
        $this->assertSame(24, strlen($secret));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString($secret, json_encode(session()->all()));
        $user->refresh();
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->temporary_password_expires_at->isFuture());
        $service = app(UserAccountService::class);
        $this->assertTrue($service->verifyPassword($user, $secret));
        $this->assertFalse($service->verifyPassword($user, 'temporary-secret-123'));
        $data['reset_password'] = 0;
        $hash = $user->password;
        $this->put('/admin/usuarios/' . $user->id, $data)->assertRedirect();
        $this->assertSame($hash, $user->fresh()->password);
    }
}
