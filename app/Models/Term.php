<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    // Nom explicite de la table si ce n'est pas 'terms'
    protected $table = 'Terms';

    // Clé primaire personnalisée
    protected $primaryKey = 'TermsId';

    // Pas de timestamps créés par défaut (à adapter selon ta table)
    public $timestamps = true;  // Vu que tu as CreatedAt, UpdatedAt en DB

    // Colonnes autorisées en assignation de masse
    protected $fillable = [
        'Code',
        'Description',
    ];

    // Relation inverse : un Term a plusieurs Fees
    public function fees()
    {
        return $this->hasMany(Fee::class, 'TermsId', 'TermsId');
    }
}
