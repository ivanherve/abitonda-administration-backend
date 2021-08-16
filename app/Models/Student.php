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
        'StudentId', 'Lastname', 'Firstname', 'Birthdate', 'Canteen', 'Transport', 'Picture', 'ClasseId', 'Registered', 'NewStudent', 'Allergies', 'SectorId', 'Address'
    ];
}
