<?php

use App\Support\CampoMascaraPorRotulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mascara de CPF, CNPJ, CPF/CNPJ e CEP pelos rotulos dos campos de texto
 * (CampoMascaraPorRotulo). Corrige campos sem mascara e campos "CPF/CNPJ" com
 * mascara so de CPF, que cortava o CNPJ vindo do NEO em 11 digitos.
 *
 * So altera a coluna comportamento, so de campos TEXT, e so quando a mascara
 * atual esta ausente ou nao serve. Pode rodar mais de uma vez.
 */
class ApplyDocumentMasksByLabel extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('peticao_modelo_campos')) {
            return;
        }

        $byMask = [];
        foreach (DB::table('peticao_modelo_campos')->where('tipo', 'TEXT')->get(['id', 'tipo', 'rotulo', 'comportamento']) as $campo) {
            $mask = CampoMascaraPorRotulo::suggest($campo->tipo, $campo->rotulo, $campo->comportamento);
            if ($mask !== null) {
                $byMask[$mask][] = $campo->id;
            }
        }

        DB::transaction(function () use ($byMask) {
            foreach ($byMask as $mask => $ids) {
                foreach (array_chunk($ids, 500) as $chunk) {
                    // Sem tocar em updated_at, como os outros acertos de dados.
                    DB::table('peticao_modelo_campos')->whereIn('id', $chunk)->update(['comportamento' => $mask]);
                }
            }
        });
    }

    public function down()
    {
        // Nada a desfazer: as mascaras anteriores estavam ausentes ou erradas.
    }
}
