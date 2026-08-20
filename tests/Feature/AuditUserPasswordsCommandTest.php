<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditUserPasswordsCommandTest extends TestCase
{
    public function test_it_reports_password_migration_status_without_changing_accounts(): void
    {
        $bcrypt = Hash::make('modern-secret');
        $md5 = md5('legacy-secret');

        $modernUser = $this->createUser('modern', $bcrypt, $bcrypt);
        $legacyUser = $this->createUser('legacy', $md5, $md5);
        $mismatchedUser = $this->createUser('mismatched', $bcrypt, $md5);
        $missingUser = $this->createUser('missing', null, null);
        $unsupportedUser = $this->createUser('unsupported', 'plain-text', 'plain-text');

        $before = User::query()->orderBy('id')->get(['id', 'password', 'senha_usu'])->toArray();

        $this->artisan('users:password-audit', ['--details' => true])
            ->expectsOutput('Read-only audit: no user account was changed.')
            ->assertExitCode(1);

        $after = User::query()->orderBy('id')->get(['id', 'password', 'senha_usu'])->toArray();

        $this->assertSame($before, $after);
        $this->assertSame(5, User::count());
        $this->assertNotNull($modernUser->id);
        $this->assertNotNull($legacyUser->id);
        $this->assertNotNull($mismatchedUser->id);
        $this->assertNotNull($missingUser->id);
        $this->assertNotNull($unsupportedUser->id);
    }

    public function test_it_succeeds_when_all_passwords_are_consistent(): void
    {
        $bcrypt = Hash::make('modern-secret');
        $md5 = md5('legacy-secret');

        $this->createUser('modern', $bcrypt, $bcrypt);
        $this->createUser('legacy', $md5, $md5);

        $this->artisan('users:password-audit')
            ->expectsOutput('Read-only audit: no user account was changed.')
            ->assertExitCode(0);
    }

    private function createUser(string $login, $password, $legacyPassword): User
    {
        return User::create([
            'name' => $login,
            'login_usu' => $login,
            'password' => $password,
            'senha_usu' => $legacyPassword,
            'nivel_usu' => 'USU',
            'status_usu' => 'ATI',
        ]);
    }
}
