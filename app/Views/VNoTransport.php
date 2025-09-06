<?php

namespace App\Views;


use Illuminate\Database\Eloquent\Model;

class VNoTransport extends Model
{
    protected $table = 'vnotransport';
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