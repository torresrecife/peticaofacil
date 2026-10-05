<?php

namespace App\Policies;

use App\PeticaoNormalizada;
use App\User;

/**
 * Quem pode abrir, editar, restaurar e exportar uma peca salva:
 * o autor, o GER do mesmo setor do autor e qualquer ADM.
 *
 * A mesma regra, em forma de consulta, esta em
 * PeticaoNormalizada::scopeVisibleTo(). Mude as duas juntas.
 */
class PeticaoPolicy
{
    public function access(User $actor, PeticaoNormalizada $peticao)
    {
        if ($actor->nivel_usu === 'ADM') {
            return true;
        }

        if ($this->owns($actor, $peticao)) {
            return true;
        }

        if ($actor->nivel_usu !== 'GER' || (int) $actor->id_setor < 1) {
            return false;
        }

        $owner = $this->owner($peticao);

        return $owner !== null && (int) $owner->id_setor === (int) $actor->id_setor;
    }

    protected function owns(User $actor, PeticaoNormalizada $peticao)
    {
        if ((int) $peticao->user_id > 0) {
            return (int) $peticao->user_id === (int) $actor->id;
        }

        // Pecas importadas do legado cujo autor ainda nao foi vinculado.
        return (int) $peticao->legacy_usuario_id > 0
            && (int) $peticao->legacy_usuario_id === (int) $actor->legacy_usuario_id;
    }

    protected function owner(PeticaoNormalizada $peticao)
    {
        if ((int) $peticao->user_id > 0) {
            return $peticao->user;
        }

        if ((int) $peticao->legacy_usuario_id > 0) {
            return User::where('legacy_usuario_id', $peticao->legacy_usuario_id)->first();
        }

        return null;
    }
}
