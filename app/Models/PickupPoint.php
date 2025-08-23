<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickupPoint extends Model
{
    protected $table = 'pickup_point';
    protected $primaryKey = 'PickupId';
    public $timestamps = false;
    protected $fillable = [
        'LineId',
        'Name',
        'Latitude',
        'Longitude',
        'PositionOrder',
        'ArrivalGo',
        'ArrivalReturn'
    ];

    public function line()
    {
        return $this->belongsTo(BusLine::class, 'LineId', 'LineId');
    }

    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'student_pickup',   // table pivot
            'PickupId',    // clé étrangère vers pickup_point
            'StudentId' // clé étrangère vers student
        )->withPivot('DayOfWeek', 'DirectionId');
    }
}
