<?php

namespace App\Views;


use Illuminate\Database\Eloquent\Model;

class VBank extends Model
{
    protected $table = 'vbank';
    protected $primaryKey = 'BankId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'BankId', 'Name'
    ];
}
