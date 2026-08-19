<?php

namespace App\Services;

use App\User;
use Illuminate\Support\Facades\DB;

class UserAccountService
{
    public function create(array $data, ?string $passwordHash = null): User
    {
        $user = new User();

        return $this->update($user, $data, $passwordHash ?: md5($data['password']));
    }

    public function update(User $user, array $data, ?string $passwordHash = null): User
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

        if ($passwordHash) {
            $user->password = $passwordHash;
            $user->senha_usu = $passwordHash;
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
        $hash = md5($plainPassword);
        $user->password = $hash;
        $user->senha_usu = $hash;
        $user->acesso_usu = now();
        $user->save();

        return $user;
    }
}
