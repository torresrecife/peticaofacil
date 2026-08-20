<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;

class AuditUserPasswords extends Command
{
    protected $signature = 'users:password-audit {--details : List accounts that require attention}';

    protected $description = 'Report password hash migration status without changing user accounts';

    public function handle(): int
    {
        $counts = [
            'bcrypt' => 0,
            'md5' => 0,
            'mismatched' => 0,
            'missing' => 0,
            'unsupported' => 0,
        ];
        $details = [];

        User::query()
            ->select(['id', 'login_usu', 'email', 'password', 'senha_usu'])
            ->orderBy('id')
            ->chunk(500, function ($users) use (&$counts, &$details) {
                foreach ($users as $user) {
                    $category = $this->classify($user->password, $user->senha_usu);
                    $counts[$category]++;

                    if ($this->option('details') && !in_array($category, ['bcrypt', 'md5'], true)) {
                        $details[] = [
                            $user->id,
                            $user->login_usu ?: $user->email ?: '(not informed)',
                            $category,
                        ];
                    }
                }
            });

        $inconsistent = $counts['mismatched'] + $counts['missing'] + $counts['unsupported'];
        $total = array_sum($counts);

        $this->table(['Status', 'Accounts'], [
            ['Bcrypt (consistent)', $counts['bcrypt']],
            ['MD5 awaiting migration', $counts['md5']],
            ['Mismatched fields', $counts['mismatched']],
            ['Missing password hash', $counts['missing']],
            ['Unsupported hash', $counts['unsupported']],
            ['Total inconsistent', $inconsistent],
            ['Total accounts', $total],
        ]);

        if ($details) {
            $this->line('');
            $this->table(['User ID', 'Login/email', 'Problem'], $details);
        }

        $this->line('Read-only audit: no user account was changed.');

        return $inconsistent > 0 ? 1 : 0;
    }

    private function classify($password, $legacyPassword): string
    {
        $password = $this->normalize($password);
        $legacyPassword = $this->normalize($legacyPassword);

        if ($password === null && $legacyPassword === null) {
            return 'missing';
        }

        if ($password === null || $legacyPassword === null) {
            return 'mismatched';
        }

        if (!hash_equals($password, $legacyPassword)) {
            return 'mismatched';
        }

        if ($this->isModernHash($password)) {
            return 'bcrypt';
        }

        if (preg_match('/^[a-f0-9]{32}$/i', $password)) {
            return 'md5';
        }

        return 'unsupported';
    }

    private function normalize($hash): ?string
    {
        if (!is_string($hash) || trim($hash) === '') {
            return null;
        }

        return trim($hash);
    }

    private function isModernHash(string $hash): bool
    {
        return (bool) preg_match('/^\$(2[ayb]|argon2(?:i|id))\$/', $hash);
    }
}
