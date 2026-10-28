<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Configuracao geral do sistema (chave => valor).
 *
 * - modelo.cabecalho_padrao: cabecalho que todo modelo novo recebe (criado na
 *   tela ou pela importacao do Word com IA);
 * - modelo.rodape_padrao: idem para o rodape (vazio = sem rodape).
 */
class Configuracao extends Model
{
    const CABECALHO_MODELO = 'modelo.cabecalho_padrao';
    const RODAPE_MODELO = 'modelo.rodape_padrao';

    /** Cabecalho institucional do escritorio, valor inicial da configuracao. */
    const CABECALHO_PADRAO_INICIAL = '<div style="text-align:right">'
        . '<table border="0" style="border:0;height:100px;width:100%;padding:0;margin:0;"><tbody><tr>'
        . '<td style="border:0;vertical-align:middle;"><p><img alt="" src="/peticaofacil/img/userfiles/fabiotorres/images/cabecalho.jpg" style="float:left;" /></p></td>'
        . '<td style="border:0;vertical-align:middle;">'
        . '<p style="text-align:right;margin-bottom:0;font-family:Tahoma,Geneva,sans-serif;font-size:9px">Rua Djalma Farias, 159, Torre&atilde;o - Recife - PE, CEP: 52.030-195</p>'
        . '<p style="text-align:right;margin-bottom:0;font-family:Tahoma,Geneva,sans-serif;font-size:9px">Fone: 55 (81) 3222.2159 | www.brunovanderlei.adv.br</p>'
        . '<p style="text-align:right;margin-bottom:0;font-family:Tahoma,Geneva,sans-serif;font-size:9px">E-mail: contato@bvaa.adv.br</p>'
        . '</td></tr></tbody></table></div>';

    protected $table = 'configuracoes';

    protected $fillable = ['chave', 'valor'];

    public static function valor($chave, $padrao = null)
    {
        if (!Schema::hasTable('configuracoes')) {
            return $padrao;
        }
        $registro = static::where('chave', $chave)->first();

        return $registro ? $registro->valor : $padrao;
    }

    public static function gravar($chave, $valor)
    {
        static::updateOrCreate(['chave' => $chave], ['valor' => $valor]);
    }

    public static function cabecalhoModelo()
    {
        $valor = trim((string) static::valor(self::CABECALHO_MODELO, self::CABECALHO_PADRAO_INICIAL));

        return $valor !== '' ? $valor : null;
    }

    public static function rodapeModelo()
    {
        $valor = trim((string) static::valor(self::RODAPE_MODELO, ''));

        return $valor !== '' ? $valor : null;
    }
}
