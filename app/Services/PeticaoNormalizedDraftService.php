<?php

namespace App\Services;

use App\PeticaoModelo;
use App\PeticaoNormalizada;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PeticaoNormalizedDraftService
{
    public function createFromPreview(PeticaoModelo $modelo, array $payload)
    {
        return DB::transaction(function () use ($modelo, $payload) {
            $peticao = PeticaoNormalizada::create([
                'legacy_peca_id' => null,
                'modelo_id' => $modelo->id,
                'user_id' => Auth::id(),
                'codigo_externo' => $payload['codigo_processo'] ?? null,
                'nome_arquivo' => $modelo->nome,
                'cliente_referencia' => $payload['nome_cli'],
                'conteudo_html' => $payload['content'],
                'campos_resolvidos' => $payload['resolved_fields'] ?? null,
                'gerado_em' => now(),
                'salvo_em' => now(),
            ]);

            app(PeticaoNormalizedStorageService::class)->createVersionSnapshot($peticao, 'draft');

            return $peticao;
        });
    }
}
