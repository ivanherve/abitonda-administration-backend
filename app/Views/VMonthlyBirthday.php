<?php

namespace App\Views;


use Illuminate\Database\Eloquent\Model;

class VMonthlyBirthday extends Model
{
    protected $table = 'vmonthlybirthday';
    protected $primaryKey = 'StudentId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'StudentId', 'Lastname', 'Firstname', 'Birthdate', 'Classe', 'Age'
    ];
}
