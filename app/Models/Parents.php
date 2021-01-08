<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Parents extends Model
{
    protected $table = 'parents';
    protected $primaryKey = 'ParentId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ParentId', 'Lastname', 'Firstname', 'Telephone', 'Email', 'Address', 'Profession'
    ];
}
