<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'invoice';
    protected $primaryKey = 'InvoiceId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'InvoiceId', 'Title', 'Amount', 'DatePayment', 'Description', 'BillPicture', 'PaymentMethodId'
    ];
}
