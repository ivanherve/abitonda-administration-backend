<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentPickup extends Model
{
    protected $table = 'student_pickup';
    public $incrementing = false; // Clé primaire composite
    public $timestamps = false;
    protected $primaryKey = null; // car composite
    protected $fillable = ['StudentId', 'PickupId', 'LineId', 'DirectionId', 'DayOfWeek'];

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
