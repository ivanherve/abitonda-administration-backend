<?php

namespace App\Views;


use Illuminate\Database\Eloquent\Model;

class VTransportCT1 extends Model
{
    protected $table = 'vt1costa';
    protected $primaryKey = 'SId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'SId', 'Lastname', 'Firstname', 'Ligne', 'Classe', 'Chauffeur', 'Tour'
    ];
}