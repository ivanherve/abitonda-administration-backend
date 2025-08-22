<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusLine extends Model
{
    protected $table = 'bus_line';
    protected $primaryKey = 'LineId';
    public $timestamps = false;
    protected $fillable = ['Name', 'DriverId', 'AssistantId'];

    public function pickups()
    {
        return $this->hasMany(PickupPoint::class, 'LineId', 'LineId');
    }

    public function studentPickups()
    {
        return $this->hasMany(StudentPickup::class, 'LineId', 'LineId');
    }
}
