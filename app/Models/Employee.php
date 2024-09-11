<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employee';
    protected $primaryKey = 'EmployeeId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'EmployeeId', 'Firstname', 'Lastname', 'Email', 'BankId', 'BankAccount', 'NbRSSB', 'NbDays', 'isEmployed'
    ];
}
