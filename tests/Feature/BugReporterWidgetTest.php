<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;

class BugReporterWidgetTest extends TestCase
{
    public function test_logged_user_gets_bug_reporter_script_with_identity_when_configured()
    {
        config(['services.bug_reporter' => [
            'script' => 'http://kanban.test/widget/bug-reporter.js',
            'token' => 'token-de-teste',
        ]]);
        $user = factory(User::class)->create([
            'nivel_usu' => 'USU',
            'nome_usu' => 'Maria "Teste"',
            'login_usu' => 'maria.teste',
            'acesso_usu' => now(),
        ]);

        $page = $this->actingAs($user)->get('/painel');

        $page->assertStatus(200);
        $page->assertSee('src="http://kanban.test/widget/bug-reporter.js"', false);
        $page->assertSee('data-token="token-de-teste"', false);
        $page->assertSee('data-user-name="Maria &quot;Teste&quot;"', false);
        $page->assertSee('data-user-login="maria.teste"', false);
        $page->assertSee('data-user-level="USU"', false);
    }

    public function test_no_bug_reporter_script_when_not_configured_or_logged_out()
    {
        config(['services.bug_reporter' => ['script' => null, 'token' => null]]);
        $user = factory(User::class)->create(['nivel_usu' => 'ADM', 'acesso_usu' => now()]);

        $this->actingAs($user)->get('/painel')->assertStatus(200)->assertDontSee('bug-reporter.js');

        config(['services.bug_reporter' => ['script' => 'http://kanban.test/widget/bug-reporter.js', 'token' => 'x']]);
        auth()->logout();
        $this->get('/login')->assertDontSee('bug-reporter.js');
    }
}
