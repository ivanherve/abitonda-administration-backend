<?php

namespace App\Views;


use Illuminate\Database\Eloquent\Model;

class VKinderGarden extends Model
{
    protected $table = 'vkindergardensite';
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