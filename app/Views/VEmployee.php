<?php

namespace App\Views;


use Illuminate\Database\Eloquent\Model;

class VEmployee extends Model
{
    protected $table = 'vemployee';
    protected $primaryKey = 'EmployeeId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'EmployeeId', 'Firstname', 'Lastname', 'Email', 'Bank', 'BankAccount', 'NbRSSB', 'NbDays', 'isEmployed'
    ];
}
