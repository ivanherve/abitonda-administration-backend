<?php

namespace App\Views;


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
        'StudentId', 'Lastname', 
        'Sexe', 'Firstname', 
        'Birthdate', 
        'Canteen', 'Transport', 
        'Picture', 'Classe', 
        'Urubuto', 'FamilyId', 'PointDeRamassage'
    ];
}
