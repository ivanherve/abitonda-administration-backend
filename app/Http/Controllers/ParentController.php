<?php

namespace App\Http\Controllers;

use App\Models\Parents;
use App\Models\Student;
use Hamcrest\Arrays\IsArray;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function GuzzleHttp\json_decode;

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

    // GET /parents?familyId=3
    public function index(Request $request)
    {
        $familyId = $request->query('familyId');

        $query = Parents::query();

        if ($familyId) {
            $query->where('FamilyId', $familyId);
        }

        $parents = $query->get();

        return $this->successRes($parents);
    }

    public function addParentOfOneStudent(Request $request)
    {
        $parentSelected = $request->input('Name');

        if ($parentSelected) {
            $studentId = $request->input('StudentId');
            $student = Student::all()->where('StudentId', '=', $studentId)->first();
            if (!$student)
                return $this->errorRes('Cet étudiant est introuvable', 404);
            $parentId = $request->input('ParentId');
            $parent = Parents::all()->where('ParentId', '=', $parentId)->first();
            if (!$parent)
                return $this->errorRes('Ce parent est introuvble', 404);

            $findLink = DB::select('call find_link_parent_student(?,?)', [$student->StudentId, $parent->ParentId]);
            if ($findLink)
                return $this->errorRes('Ce lien existe déjà', 500);

            if (DB::insert('call add_link_parent_student(?,?)', [$student->StudentId, $parent->ParentId]))
                return $this->successRes("$parentSelected a bien été ajouté");
        }

        $firstname = $request->input('firstname');
        if (!$firstname)
            return $this->errorRes('Veuillez insérer un prénom', 404);

        $lastname = $request->input('lastname');
        if (!$lastname)
            return $this->errorRes('Veuillez insérer un nom de famille', 404);

        $telephone = $request->input('telephone');
        if (!$telephone)
            return $this->errorRes('Veuillez insérer un numéro de téléphone', 404);

        $email = $request->input('email');
        if (!$email)
            $email = "";

        $address = $request->input('address');
        if (!$address)
            $address = ''; //return $this->errorRes('Veuillez insérer l\'adresse du domicile', 404);

        $linkChild = $request->input('linkChild');
        if (!$linkChild)
            $linkChild = "";

        // 🟢 Langues
        $languages = $request->input('languages', []);

        $french = in_array('Français', $languages) ? 1 : 0;
        $english = in_array('Anglais', $languages) ? 1 : 0;
        $kinyarwanda = in_array('Kinyarwanda', $languages) ? 1 : 0;

        $parent = Parents::create([
            'Lastname' => $lastname,
            'Firstname' => $firstname,
            'PhoneNumb' => $telephone,
            'Email' => $email,
            'Address' => $address,
            'LinkChild' => $linkChild,
            'French' => $french,
            'English' => $english,
            'Kinyarwanda' => $kinyarwanda
        ]);

        if (!$parent)
            return $this->errorRes('Un problème est survenu lors de la création', 500);
        $studentId = $request->input('StudentId');
        $student = Student::all()->where('StudentId', '=', $studentId)->first();
        if (!$student)
            return $this->errorRes('L\'élève est introuvable', 404);

        //return $this->debugRes($student);

        if (DB::insert('call add_link_parent_student(?,?)', [$student->StudentId, $parent->ParentId]))
            return $this->successRes("$firstname $lastname a bien été ajouté");
    }
    public function getListParent(Request $request)
    {
        $parent = DB::table('vlistparent')->get();
        return $this->successRes($parent);
    }

    public function removeLinkParent(Request $request)
    {
        $studentId = $request->input('studentId');
        $parentId = $request->input('parentId');
        if (DB::delete("call del_link_parent_student(?,?)", [$studentId, $parentId]))
            return $this->successRes('Ce contact a bien été retiré');
    }

    public function editParent(Request $request)
    {
        $parentId = $request->input('ParentId');
        $parent = Parents::all()->where('ParentId', '=', $parentId)->first();
        if (!$parent)
            return $this->errorRes('Ce parent n\'existe pas dans le système', 404);

        $firstname = $request->input('Firstname');
        if (!$firstname)
            $firstname = $parent->Firstname;

        $lastname = $request->input('Lastname');
        if (!$lastname)
            $lastname = $parent->Lastname;

        $phoneNumb = $request->input('PhoneNumb');
        if (!$phoneNumb)
            $phoneNumb = $parent->PhoneNumb;

        $address = $request->input('Address');
        if (!$address)
            $address = $parent->Address;

        $email = $request->input('Email');
        if (!$email)
            $email = $parent->Email;

        $linkChild = $request->input('LinkChild');
        if (!$linkChild)
            $linkChild = $parent->LinkChild;

        // 🔹 Langues parlées
        $languages = $request->input('languages', []);
        if($languages) {
            $languages = json_decode($languages);
        } else {
            $languages = [];
        }
        $french = in_array('Français', $languages) ? 1 : 0;
        $english = in_array('Anglais', $languages) ? 1 : 0;
        $kinyarwanda = in_array('Kinyarwanda', $languages) ? 1 : 0;

        $parentUpdated = [
            'Lastname' => mb_strtoupper($lastname),
            'Firstname' => mb_strtoupper($firstname),
            'PhoneNumb' => mb_strtoupper($phoneNumb),
            'Email' => mb_strtoupper($email),
            'Address' => mb_strtoupper($address),
            'LinkChild' => mb_strtoupper($linkChild),
            'French' => $french,
            'English' => $english,
            'Kinyarwanda' => $kinyarwanda
        ];

        $parent->fill($parentUpdated)->save();

        return $this->successRes($parentUpdated);
    }
}
