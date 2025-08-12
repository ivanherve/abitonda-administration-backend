<?php

namespace App\Views;

use App\Models\Classe;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Database\Eloquent\Model;

class VFees extends Model
{
    protected $table = 'VFees';

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
