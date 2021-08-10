<?php

namespace App\Views;


use Illuminate\Database\Eloquent\Model;

class VInvoice extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'vinvoices';
    protected $primaryKey = 'InvoiceId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'InvoiceId', 'Title', 'Amount', 'DatePayment', 'Description', 'BillPicture', 'PaymentMethod', 'created_at', 'updated_at'
    ];
}
