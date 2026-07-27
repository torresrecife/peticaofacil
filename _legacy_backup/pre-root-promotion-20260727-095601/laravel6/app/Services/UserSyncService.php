<?php

namespace App\Services;

use App\LegacyUser;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserSyncService
{
    public function syncFromLegacy(LegacyUser $legacyUser): User
    {
        $user = User::updateOrCreate(
            ['legacy_usuario_id' => $legacyUser->id_usu],
            $this->mapLegacyToApp($legacyUser)
        );

        $this->syncInternalReferences($user);

        return $user;
    }

    public function syncAll(): int
    {
        $count = 0;

        LegacyUser::orderBy('id_usu')->chunk(200, function ($users) use (&$count) {
            foreach ($users as $legacyUser) {
                $this->syncFromLegacy($legacyUser);
                $count++;
            }
        });

        return $count;
    }

    public function create(array $data, ?string $passwordHash = null): User
    {
        return DB::transaction(function () use ($data, $passwordHash) {
            $legacyUser = new LegacyUser();
            $this->fillLegacyUser($legacyUser, $data, $passwordHash ?: md5($data['password']));
            $legacyUser->data_cad = now();
            $legacyUser->save();

            return $this->syncFromLegacy($legacyUser);
        });
    }

    public function update(User $user, array $data, ?string $passwordHash = null): User
    {
        if (!$user->legacy_usuario_id) {
            return $this->updateAppOnly($user, $data, $passwordHash);
        }

        return DB::transaction(function () use ($user, $data, $passwordHash) {
            $legacyUser = $this->resolveLegacyUser($user);

            $this->fillLegacyUser($legacyUser, $data, $passwordHash);
            $legacyUser->save();

            return tap($this->syncFromLegacy($legacyUser), function ($synced) use ($user) {
                $user->forceFill($synced->getAttributes())->syncOriginal();
            });
        });
    }

    public function touchAccess(User $user): User
    {
        $legacyUser = $user->legacy_usuario_id ? LegacyUser::find($user->legacy_usuario_id) : null;

        if (!$legacyUser) {
            $user->acesso_usu = now();
            $user->save();

            $this->syncInternalReferences($user);

            return $user;
        }

        $legacyUser->acesso_usu = now();
        $legacyUser->save();

        return tap($this->syncFromLegacy($legacyUser), function ($synced) use ($user) {
            $user->forceFill($synced->getAttributes())->syncOriginal();
        });
    }

    public function syncInternalReferences(User $user): void
    {
        if (!$user->legacy_usuario_id) {
            return;
        }

        DB::table('user_model_favorites')
            ->where('legacy_usuario_id', $user->legacy_usuario_id)
            ->update(['user_id' => $user->id]);

        DB::table('peticoes')
            ->where('legacy_usuario_id', $user->legacy_usuario_id)
            ->update(['user_id' => $user->id]);

        DB::table('peticao_versoes')
            ->where('legacy_usuario_id_snapshot', $user->legacy_usuario_id)
            ->update(['user_id_snapshot' => $user->id]);
    }

    public function updatePassword(User $user, string $plainPassword): User
    {
        $legacyUser = $user->legacy_usuario_id ? LegacyUser::find($user->legacy_usuario_id) : null;

        if (!$legacyUser) {
            $hash = md5($plainPassword);
            $user->password = $hash;
            $user->senha_usu = $hash;
            $user->acesso_usu = now();
            $user->save();

            return $user;
        }

        return $this->update($user, $user->only([
            'nome_usu',
            'login_usu',
            'email_usu',
            'nivel_usu',
            'status_usu',
            'id_setor',
            'id_cliente',
            'estados_usu',
            'comarca_usu',
        ]), md5($plainPassword));
    }

    protected function updateAppOnly(User $user, array $data, ?string $passwordHash = null): User
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

    protected function resolveLegacyUser(User $user): LegacyUser
    {
        $legacyUser = LegacyUser::find($user->legacy_usuario_id);

        if ($legacyUser) {
            return $legacyUser;
        }

        $legacyUser = new LegacyUser();
        $legacyUser->data_cad = $user->data_cad ?: now();

        return $legacyUser;
    }

    protected function fillLegacyUser(LegacyUser $legacyUser, array $data, ?string $passwordHash = null): void
    {
        $legacyUser->nome_usu = $data['nome_usu'];
        $legacyUser->login_usu = $data['login_usu'];
        $legacyUser->email_usu = $data['email_usu'] ?? null;
        $legacyUser->nivel_usu = $data['nivel_usu'];
        $legacyUser->status_usu = $data['status_usu'];
        $legacyUser->id_setor = $data['id_setor'] ?: null;
        $legacyUser->id_cliente = isset($data['id_cliente'])
            ? ($data['id_cliente'] ?: '0')
            : (!empty($data['cliente_ids']) ? implode(',', $data['cliente_ids']) : '0');
        $legacyUser->estados_usu = $data['estados_usu'] ?? $legacyUser->estados_usu;
        $legacyUser->comarca_usu = $data['comarca_usu'] ?? $legacyUser->comarca_usu;

        if ($passwordHash) {
            $legacyUser->senha_usu = $passwordHash;
        }
    }

    protected function mapLegacyToApp(LegacyUser $legacyUser): array
    {
        $passwordHash = $legacyUser->senha_usu ?: md5('__sem_senha_legada__');

        return [
            'name' => $legacyUser->nome_usu,
            'email' => $legacyUser->email_usu,
            'password' => $passwordHash,
            'nome_usu' => $legacyUser->nome_usu,
            'login_usu' => $legacyUser->login_usu,
            'senha_usu' => $passwordHash,
            'email_usu' => $legacyUser->email_usu,
            'nivel_usu' => $legacyUser->nivel_usu,
            'acesso_usu' => $this->normalizeDate($legacyUser->acesso_usu),
            'data_cad' => $this->normalizeDate($legacyUser->data_cad),
            'id_setor' => $legacyUser->id_setor,
            'id_cliente' => $legacyUser->id_cliente ?: '0',
            'status_usu' => $legacyUser->status_usu,
            'estados_usu' => $legacyUser->estados_usu,
            'comarca_usu' => $legacyUser->comarca_usu,
        ];
    }

    protected function normalizeDate($value)
    {
        if (empty($value) || $value === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            $date = $value instanceof Carbon ? $value : Carbon::parse($value);

            return $date->year < 1900 ? null : $date;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
