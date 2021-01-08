<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    protected $table = 'token';
    protected $primaryKey = 'TokenId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'UserId', 'Api_token', 'TokenId'
    ];

    public function user() {
        $this->belongsTo(User::class, 'UserId');
    }
}