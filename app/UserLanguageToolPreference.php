<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserLanguageToolPreference extends Model
{
    protected $table = 'user_languagetool_preferences';

    protected $fillable = [
        'user_id',
        'entry_type',
        'token',
        'rule_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
