<?php

namespace App\Views;


use Illuminate\Database\Eloquent\Model;

class VRegistrationIncomplete extends Model
{
    protected $table = 'vregistrationincompletewithphonenumb';
    protected $primaryKey = 'StudentId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'StudentId', 'Firstname', 'Lastname', 'Classe', 'ROI', 'Fiche', 'Vaccin', 'Photo', 'Parent', 'PhoneNumb'
    ];
}