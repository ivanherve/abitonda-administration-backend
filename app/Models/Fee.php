<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    protected $table = 'Fees';

    protected $primaryKey = 'FeesId';

    public $timestamps = true;

    protected $fillable = [
        'Name',
        'Amount',
        'TermsId',
        'IsDynamic',
        'MinClass',
        'IsActive',
    ];

    // Relation avec Term
    public function term()
    {
        return $this->belongsTo(Term::class, 'TermsId', 'TermsId');
    }

    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'FeeStudent',
            'FeeId',
            'StudentId'
        )->withTimestamps();
    }

    public function minClasse()
    {
        return $this->belongsTo(Classe::class, 'MinClasseId', 'ClasseId');
    }

}
