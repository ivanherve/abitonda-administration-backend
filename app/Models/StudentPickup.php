<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class StudentPickup extends Pivot
{
    protected $table = 'student_pickup';
    public $incrementing = false; // car clé composite
    public $timestamps = false;

    protected $fillable = [
        'StudentId',
        'PickupId',
        'LineId',
        'DirectionId',
        'DayOfWeek'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'StudentId', 'StudentId');
    }

    public function pickupPoint()
    {
        return $this->belongsTo(PickupPoint::class, 'PickupId', 'PickupId');
    }

    public function busLine()
    {
        return $this->belongsTo(BusLine::class, 'LineId', 'LineId');
    }

    public function direction()
    {
        return $this->belongsTo(Direction::class, 'DirectionId', 'DirectionId');
    }
}
