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
        $parentSelected = $request->input('Name');

        if ($parentSelected) {
            $studentId = $request->input('StudentId');
            $student = Student::all()->where('StudentId', '=', $studentId)->first();
            if (!$student) return $this->errorRes('Cet étudiant est introuvable', 404);
            $parentId = $request->input('ParentId');
            $parent = Parents::all()->where('ParentId', '=', $parentId)->first();
            if (!$parent) return $this->errorRes('Ce parent est introuvble', 404);

            $findLink = DB::select('call find_link_parent_student(?,?)', [$student->StudentId, $parent->ParentId]);
            if ($findLink) return $this->errorRes('Ce lien existe déjà', 500);

            if (DB::insert('call add_link_parent_student(?,?)', [$student->StudentId, $parent->ParentId]))
                return $this->successRes("$parentSelected a bien été ajouté");
        }

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

    public function getListParent(Request $request)
    {
        $parent = DB::table('vlistparent')->get();
        return $this->successRes($parent);
    }
}
