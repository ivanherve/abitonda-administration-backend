<?php

namespace App\Views;


use Illuminate\Database\Eloquent\Model;

class VTransport extends Model
{
    protected $table = 'vtransport';
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