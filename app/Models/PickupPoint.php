<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickupPoint extends Model
{
    protected $table = 'pickup_point';
    protected $primaryKey = 'PickupId';
    public $timestamps = false;
    protected $fillable = ['LineId', 'Name', 'Latitude', 'Longitude', 'PositionOrder'];

    public function line()
    {
        return $this->belongsTo(BusLine::class, 'LineId', 'LineId');
    }

    public function studentPickups()
    {
        return $this->hasMany(StudentPickup::class, 'PickupId', 'PickupId');
    }
}
