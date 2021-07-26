<?php

namespace App\Views;


use Illuminate\Database\Eloquent\Model;

class VClasse extends Model
{
    protected $table = 'vclasses';
    protected $primaryKey = 'ClasseId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ClasseId', 'Name', 'enabled', 'Teacher'
    ];
}
