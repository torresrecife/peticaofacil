<?php

namespace App\Policies;

use App\User;

class UserPolicy
{
    public function create(User $actor)
    {
        return $actor->nivel_usu === 'ADM'
            || ($actor->nivel_usu === 'GER' && (int) $actor->id_setor > 0
                && count($this->managedClientIds($actor)) > 0);
    }

    public function update(User $actor, User $target)
    {
        if ($actor->nivel_usu === 'ADM') {
            return true;
        }

        $targetClients = array_map('trim', explode(',', (string) $target->id_cliente));

        return $actor->nivel_usu === 'GER'
            && $actor->id !== $target->id
            && $target->nivel_usu === 'USU'
            && (int) $actor->id_setor > 0
            && (int) $actor->id_setor === (int) $target->id_setor
            && count(array_diff($targetClients, $this->managedClientIds($actor))) === 0;
    }

    public function managedClientIds(User $actor)
    {
        // Empty/zero means unrestricted legacy access, never a management delegation.
        return array_values(array_filter($actor->client_ids, function ($id) {
            return ctype_digit((string) $id) && (int) $id > 0;
        }));
    }
}
