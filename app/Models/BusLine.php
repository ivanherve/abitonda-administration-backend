<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusLine extends Model
{
    protected $table = 'bus_line';
    protected $primaryKey = 'LineId';
    public $timestamps = false;
    protected $fillable = ['Name', 'DriverId', 'AssistantId', 'maxPlaces'];

    public function pickups()
    {
        return $this->hasMany(PickupPoint::class, 'LineId', 'LineId');
    }
    public function driver()
    {
        return $this->belongsTo(Employee::class, 'DriverId', 'EmployeeId');
    }

    public function assistant()
    {
        return $this->belongsTo(Employee::class, 'AssistantId', 'EmployeeId');
    }
}
