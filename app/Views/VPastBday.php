<?php

namespace App\Views;


use Illuminate\Database\Eloquent\Model;

class VPastBday extends Model
{
    protected $table = 'vpastbday';
    protected $primaryKey = 'StudentId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'StudentId', 'Firstname', 'Lastname', 'BirthDay', 'age'
    ];
}
