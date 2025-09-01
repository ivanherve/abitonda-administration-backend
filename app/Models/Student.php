<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'students';
    protected $primaryKey = 'StudentId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'StudentId',
        'Lastname',
        'Urubuto',
        'Firstname',
        'Sexe',
        'Birthdate',
        'Canteen',
        'Transport',
        'Picture',
        'ClasseId',
        'Registered',
        'NewStudent',
        'Allergies',
        'SectorId',
        'Address',
        'InternalRulesSigned',
        'RegistrationFileFilled',
        'VaccinsFile',
        'Paid',
        'Guitar',
        'Piscine',
        'Danse',
        'FamilyId',
        'PointDeRamassage'
    ];

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'ClasseId', 'ClasseId');
    }

    public function fees()
    {
        return $this->belongsToMany(Fee::class, 'FeeStudent', 'StudentId', 'FeeId')
            ->withPivot([])
            ->withTimestamps(false); // 👈 Désactive les timestamps
    }

    // relation vers le parent qui parraine cet élève
    public function sponsoringParent()
    {
        return $this->belongsTo(parent::class, 'SponsoringParent', 'ParentId');
    }

    public function pickupPoints()
    {
        return $this->belongsToMany(
            PickupPoint::class,
            'student_pickup',
            'StudentId',
            'PickupId'
        )->withPivot('DayOfWeek', 'DirectionId');
    }

    public function parents()
    {
        return $this->belongsToMany(
            Parents::class,
            'parent_student',
            'StudentId',
            'ParentId'
        );
    }

}
