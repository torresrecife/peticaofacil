<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PeticaoNormalizada extends Model
{
    protected $table = 'peticoes';

    protected $fillable = [
        'legacy_peca_id',
        'modelo_id',
        'user_id',
        'legacy_usuario_id',
        'codigo_externo',
        'nome_arquivo',
        'cliente_referencia',
        'conteudo_html',
        'campos_resolvidos',
        'gerado_em',
        'salvo_em',
    ];

    protected $casts = [
        'campos_resolvidos' => 'array',
        'gerado_em' => 'datetime',
        'salvo_em' => 'datetime',
    ];

    public function getDisplayTitleAttribute()
    {
        if (data_get($this->campos_resolvidos, 'origem') === 'avulsa' && !empty($this->nome_arquivo)) {
            return $this->nome_arquivo;
        }

        if ($this->relationLoaded('modelo') && $this->modelo) {
            return $this->modelo->nome;
        }

        if ($this->modelo) {
            return $this->modelo->nome;
        }

        return $this->nome_arquivo ?: 'Peticao avulsa';
    }

    public function getDisplayReferenceAttribute()
    {
        if (!empty($this->cliente_referencia)) {
            return $this->cliente_referencia;
        }

        return data_get($this->campos_resolvidos, 'parte_contraria', '-');
    }

    public function getOriginLabelAttribute()
    {
        if (data_get($this->campos_resolvidos, 'origem') === 'avulsa') {
            return 'Avulsa';
        }

        return 'Modelo';
    }

    /**
     * Pecas que o usuario pode abrir: as proprias, as dos autores do seu
     * setor quando ele e GER, e todas quando ele e ADM.
     *
     * E a forma de consulta de App\Policies\PeticaoPolicy::access().
     * Mude as duas juntas.
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->nivel_usu === 'ADM') {
            return $query;
        }

        $legacyUserId = (int) $user->legacy_usuario_id;
        $sectorId = $user->nivel_usu === 'GER' ? (int) $user->id_setor : 0;

        return $query->where(function ($visible) use ($user, $legacyUserId, $sectorId) {
            $visible->where('user_id', $user->id);

            if ($legacyUserId > 0) {
                $visible->orWhere(function ($legacy) use ($legacyUserId) {
                    $legacy->whereNull('user_id')->where('legacy_usuario_id', $legacyUserId);
                });
            }

            if ($sectorId > 0) {
                $visible->orWhereIn('user_id', function ($users) use ($sectorId) {
                    $users->select('id')->from('users')->where('id_setor', $sectorId);
                });
                $visible->orWhere(function ($legacy) use ($sectorId) {
                    $legacy->whereNull('user_id')->whereIn('legacy_usuario_id', function ($users) use ($sectorId) {
                        $users->select('legacy_usuario_id')->from('users')
                            ->where('id_setor', $sectorId)
                            ->whereNotNull('legacy_usuario_id');
                    });
                });
            }
        });
    }

    public function modelo()
    {
        return $this->belongsTo(PeticaoModelo::class, 'modelo_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function versoes()
    {
        return $this->hasMany(PeticaoVersao::class, 'peticao_id')->orderByDesc('versao_numero');
    }
}
