<?php

namespace App\Views;


use App\Models\Fee;
use Illuminate\Database\Eloquent\Model;

class VStudents extends Model
{
    protected $table = 'vstudents';
    protected $primaryKey = 'StudentId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'StudentId',
        'Lastname',
        'Sexe',
        'Firstname',
        'Birthdate',
        'Canteen',
        'Transport',
        'Picture',
        'Classe',
        'Urubuto',
        'FamilyId',
        'PointDeRamassage'
    ];

    public function fees()
    {
        return $this->belongsToMany(
            VFees::class,
            'FeeStudent',
            'StudentId',
            'FeeId'
        )->withTimestamps();
    }
}
