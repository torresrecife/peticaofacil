<?php

namespace App\Support;

class PeticaoModeloRuntime
{
    public $id;
    public $legacy_tipo_id;
    public $tipo_id;
    public $tipo_nome;
    public $nome_pre;
    public $nome_pos;
    public $cod_cabec;
    public $cod_rodap;
    public $tipo_stt;
    public $tipo_arq;
    public $id_db;
    public $id_cliente;
    public $id_setor;
    public $campos;
    public $paragrafos;
    public $setor;
    public $cliente;
    public $servidor;
    public $servidorLegacy;
    public $is_normalized;
    public $source;

    public function __construct(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
    }
}
