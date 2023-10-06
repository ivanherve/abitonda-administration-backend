<?php

namespace App\Views;


use Illuminate\Database\Eloquent\Model;

class VTransportCT2 extends Model
{
    protected $table = 'vt2costa';
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