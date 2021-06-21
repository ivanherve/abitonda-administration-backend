<?php

namespace App\Views;


use Illuminate\Database\Eloquent\Model;

class VComingBday extends Model
{
    protected $table = 'vcomingbday';
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
