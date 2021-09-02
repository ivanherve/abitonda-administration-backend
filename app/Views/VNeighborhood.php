<?php

namespace App\Views;


use Illuminate\Database\Eloquent\Model;

class VNeighborhood extends Model
{
    protected $table = 'vneighborhood';
    protected $primaryKey = 'SectorId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'SectorId', 'DistrictId', 'Neighborhood'
    ];
}
