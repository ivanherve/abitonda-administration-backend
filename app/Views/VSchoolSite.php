<?php

namespace App\Views;


use Illuminate\Database\Eloquent\Model;

class VSchoolSite extends Model
{
    protected $table = 'vschoolsite';
    protected $primaryKey = 'StudentId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'StudentId', 'Lastname', 'Firstname', 'Birthdate', 'Classe'
    ];
}