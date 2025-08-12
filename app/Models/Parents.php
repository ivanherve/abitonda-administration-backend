<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Parents extends Model
{
    protected $table = 'parents';
    protected $primaryKey = 'ParentId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ParentId', 'Lastname', 'Firstname', 'PhoneNumb', 'Email', 'Address', 'LinkChild', 'FamilyId'
    ];

    // enfants parrainés par ce parent
    public function sponsoredChildren()
    {
        return $this->hasMany(Student::class, 'SponsoringParent', 'ParentId')
                    ->select('StudentId', 'Firstname', 'Lastname', 'Birthdate', 'SponsoringParent');
    }
}
