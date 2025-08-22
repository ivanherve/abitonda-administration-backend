<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Direction extends Model
{
    protected $table = 'direction';
    protected $primaryKey = 'DirectionId';
    public $timestamps = false;
    protected $fillable = ['Code', 'Label'];

    public function studentPickups()
    {
        return $this->hasMany(StudentPickup::class, 'DirectionId', 'DirectionId');
    }
}
