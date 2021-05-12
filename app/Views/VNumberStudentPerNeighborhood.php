<?php

namespace App\Views;


use Illuminate\Database\Eloquent\Model;

class VNumberStudentPerNeighborhood extends Model
{
    protected $table = 'vnumberstudentperneighborhood';
    protected $primaryKey = 'SectorId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'SectorId', 'Sector', 'District', 'NbStudents'
    ];
}
