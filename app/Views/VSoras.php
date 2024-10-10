<?php

namespace App\Views;


use Illuminate\Database\Eloquent\Model;

class VSoras extends Model
{
    protected $table = 'vsoras';
    protected $primaryKey = 'StudentId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'StudentId', 'Lastname', 'Firstname', 'Birthdate', 'Classe', 'Urubuto', 'Paid', 'Canteen'
    ];
}