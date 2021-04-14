<?php

namespace App\Http\Controllers;

use App\Models\Parents;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParentController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function addParentOfOneStudent(Request $request)
    {
        $firstname = $request->input('firstname');
        if (!$firstname) return $this->errorRes('Veuillez insérer un prénom', 404);

        $lastname = $request->input('lastname');
        if (!$lastname) return $this->errorRes('Veuillez insérer un nom de famille', 404);

        $telephone = $request->input('telephone');
        if (!$telephone) return $this->errorRes('Veuillez insérer un numéro de téléphone', 404);

        $email = $request->input('email');
        if (!$email) $email = "";

        $address = $request->input('address');
        if (!$address) return $this->errorRes('Veuillez insérer l\'adresse du domicile', 404);

        $linkChild = $request->input('linkChild');
        if (!$linkChild) $linkChild = "";

        $parent = Parents::create([
            'Lastname' => $lastname,
            'Firstname' => $firstname,
            'PhoneNumb' => $telephone,
            'Email' => $email,
            'Address' => $address,
            'LinkChild' => $linkChild
        ]);

        if (!$parent) return $this->errorRes('Un problème est survenu lors de la création', 500);
        $studentId = $request->input('StudentId');
        $student = Student::all()->where('StudentId', '=', $studentId)->first();
        if (!$student) return $this->errorRes('L\'élève est introuvable', 404);

        //return $this->debugRes($student);

        if (DB::insert('call add_link_parent_student(?,?)', [$student->StudentId, $parent->ParentId]))
            return $this->successRes("$firstname $lastname a bien été ajouté");
    }
}
