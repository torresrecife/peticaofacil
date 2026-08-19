<?php

namespace App\Services;

use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserAccountService
{
    public function create(array $data, string $plainPassword): User
    {
        $user = new User();

        return $this->update($user, $data, $plainPassword);
    }

    public function update(User $user, array $data, ?string $plainPassword = null): User
    {
        $user->nome_usu = $data['nome_usu'];
        $user->login_usu = $data['login_usu'];
        $user->email_usu = $data['email_usu'] ?? null;
        $user->nivel_usu = $data['nivel_usu'];
        $user->status_usu = $data['status_usu'];
        $user->id_setor = $data['id_setor'] ?: null;
        $user->id_cliente = $data['id_cliente'] ?? '0';
        $user->estados_usu = $data['estados_usu'] ?? $user->estados_usu;
        $user->comarca_usu = $data['comarca_usu'] ?? $user->comarca_usu;
        $user->name = $data['nome_usu'];
        $user->email = $data['email_usu'] ?? null;

        if ($plainPassword !== null && $plainPassword !== '') {
            $this->setPassword($user, $plainPassword);
        }

        $user->save();

        return $user;
    }

    public function touchAccess(User $user): User
    {
        $user->acesso_usu = now();
        $user->save();
        $this->linkImportedRecords($user);

        return $user;
    }

    public function linkImportedRecords(User $user): void
    {
        if (!$user->legacy_usuario_id) {
            return;
        }

        DB::table('user_model_favorites')->where('legacy_usuario_id', $user->legacy_usuario_id)->update(['user_id' => $user->id]);
        DB::table('peticoes')->where('legacy_usuario_id', $user->legacy_usuario_id)->update(['user_id' => $user->id]);
        DB::table('peticao_versoes')->where('legacy_usuario_id_snapshot', $user->legacy_usuario_id)->update(['user_id_snapshot' => $user->id]);
    }

    public function updatePassword(User $user, string $plainPassword): User
    {
        $this->setPassword($user, $plainPassword);
        $user->acesso_usu = now();
        $user->save();

        return $user;
    }

    public function verifyPassword(User $user, string $plainPassword): bool
    {
        $modernHash = $this->modernHash($user);

        if ($modernHash !== null) {
            if (!Hash::check($plainPassword, $modernHash)) {
                return false;
            }

            if (Hash::needsRehash($modernHash)
                || !hash_equals((string) $user->password, (string) $user->senha_usu)) {
                $this->setPassword($user, $plainPassword);
                $user->save();
            }

            return true;
        }

        $legacyHash = md5($plainPassword);
        $matches = hash_equals((string) $user->password, $legacyHash)
            || hash_equals((string) $user->senha_usu, $legacyHash);

        if (!$matches) {
            return false;
        }

        $this->setPassword($user, $plainPassword);
        $user->save();

        return true;
    }

    protected function modernHash(User $user): ?string
    {
        foreach ([$user->password, $user->senha_usu] as $hash) {
            if (is_string($hash) && preg_match('/^\$(2[ayb]|argon2(?:i|id))\$/', $hash)) {
                return $hash;
            }
        }

        return null;
    }

    protected function setPassword(User $user, string $plainPassword): void
    {
        $hash = Hash::make($plainPassword);
        $user->password = $hash;
        $user->senha_usu = $hash;
    }
}
