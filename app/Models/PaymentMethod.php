<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'paymentmethod';
    protected $primaryKey = 'PaymentMethodId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'PaymentMethodId', 'Name'
    ];
}
