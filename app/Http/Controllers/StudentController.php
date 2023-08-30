<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Student;
use App\Models\Parents;
use App\Views\VComingBday;
use App\Views\VNeighborhood;
use App\Views\VNumberStudentPerNeighborhood;
use App\Views\VPastBday;
use App\Views\VRegistrationIncomplete;
use App\Views\VSoras;
use App\Views\VStudents;
use App\Views\VTransport;
use App\Views\VSchoolSite;
use App\Views\VKinderGarden;
use App\Views\VMonthlyBirthday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
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

    public function getStudents()
    {
        $students = VStudents::all();

        return $this->successRes($students);
    }

    public function getTenStudents(Request $request)
    {
        $students = DB::table('vstudents')->orderBy('Firstname', 'asc')->paginate($request->get('limit'));

        return $this->successRes($students);
    }

    public function searchStudent(Request $request)
    {
        $students = DB::table('vstudents')->where('Firstname', 'like', "%" . $request->get('name') . "%")->get();

        return $this->successRes($students);
    }

    public function addStudent(Request $request)
    {
        $firstname = $request->input('Firstname');
        if (!$firstname) return $this->errorRes('Veuillez insérer un prénom', 404);
        $lastname = $request->input('Lastname');
        if (!$lastname) return $this->errorRes('Veuillez insérer un nom de famille', 404);
        $urubuto = $request->input('Urubuto');
        if (!$urubuto) return $this->errorRes('Veuillez insérer le code Urubuto de l\'enfant',404);
        $birthdate = $request->input('Birthdate');
        if (!$birthdate) return $this->errorRes('Veuillez insérer une date de naissance', 404);
        $canteen = filter_var($request->input('Canteen'), FILTER_VALIDATE_BOOLEAN);
        $transport = filter_var($request->input('Transport'), FILTER_VALIDATE_BOOLEAN);
        $rulesSigned = filter_var($request->input('rulesSigned'), FILTER_VALIDATE_BOOLEAN);
        $registrationFileFilled = filter_var($request->input('registrationFileFilled'), FILTER_VALIDATE_BOOLEAN);
        $vaccinsFile = filter_var($request->input('vaccinsFile'), FILTER_VALIDATE_BOOLEAN);
        $piano = filter_var($request->input('piano'), FILTER_VALIDATE_BOOLEAN);
        $guitar = filter_var($request->input('guitar'), FILTER_VALIDATE_BOOLEAN);
        $danse = filter_var($request->input('danse'), FILTER_VALIDATE_BOOLEAN);
        $swimmingpool = filter_var($request->input('swimmingpool'), FILTER_VALIDATE_BOOLEAN);
        $address = $request->input('address');
        
        $classe = $request->input('Classe');
        if (!$classe) return $this->errorRes('Veuillez insérer une classe', 404);
        $picture = $request->input('Picture');
        //if (!$picture) return $this->errorRes('Veuillez insérer une photo', 404);

        $classe = Classe::all()->where('Name', '=', $classe)->pluck('ClasseId')->first();
        if (!$classe) return $this->errorRes('Cette classe est introuvable', 404);

        $neighborhood = $request->input('neighborhoodSelected');
        if (!$neighborhood) return $this->errorRes('Veuillez séléctionner un quartier', 404);

        $sector = VNeighborhood::all()->where('Neighborhood', '=', $neighborhood)->pluck('SectorId')->first();
        
        $studentToCreate = [
            'Lastname' => strtoupper($lastname),
            'Firstname' => strtoupper($firstname),
            'Urubuto' => $urubuto,
            'Birthdate' => $birthdate,
            'Canteen' => $canteen,
            'Transport' => $transport,
            'ClasseId' => $classe,
            'Picture' => $picture,
            'SectorId' => $sector,
            'Address' => $address,
            'InternalRulesSigned' => $rulesSigned,
            'RegistrationFileFilled' => $registrationFileFilled,
            'VaccinsFile' => $vaccinsFile,
            'Piano' => $piano,
            'Guitar' => $guitar,
            'Piscine' => $swimmingpool,
            'Danse' => $danse
        ];
        #return $this->debugRes($studentToCreate);
        $newStudent = Student::create($studentToCreate);

        if($newStudent){
            return $this->successRes($newStudent);            
        }
        
    }

    public function getRegistrationIncomplete()
    {
        $students = VRegistrationIncomplete::all();
        if (!$students) return $this->errorRes('Aucun enfant sur cette liste, tout le monde est en ordre', 404);

        return $this->successRes($students);
    }

    public function getStudentsPicture(Request $request)
    {
        $studentId = $request->input('studentId');
        if (!$studentId) return $this->errorRes('De quel élève s\'agit-il ?', 404);
        $picture = DB::select("call get_students_picture(?)", [$studentId]);

        return $this->successRes($picture);
    }

    public function addStudentCSV(Request $request)
    {
        $file = $request->file('csv');
        if (!$file) return $this->errorRes('Veuillez insérer un fichier', 404);
        if ($file->getClientOriginalExtension() != 'csv') return $this->errorRes('Veuillez uniquement importer des fichiers .csv', 401);

        $studentList = $this->csvToArray($file->getRealPath());
        /**/
        $sList = [];

        foreach ($studentList as $k => $v) {
            if (!$v['NOMS']) return $this->errorRes('Veuillez vérifier la ligne ' . ($k + 1) . ". Il manque un nom.", 404);
            if (!$v['PRENOMS']) return $this->errorRes('Veuillez vérifier la ligne ' . ($k + 1) . ". Il manque un prénom.", 404);
            if (!$v['DATE DE NAISSANCE']) return $this->errorRes('Veuillez vérifier la ligne ' . ($k + 1) . ". Il manque une date de naissance.", 404);
            if (!$v['CANTINE']) return $this->errorRes('Veuillez vérifier la ligne ' . ($k + 1) . ". Est-ce que l'enfant mange à l'école ?", 404);
            if (!$v['TRANSPORT']) return $this->errorRes('Veuillez vérifier la ligne ' . ($k + 1) . ". Est-ce que l'enfant est transporté ?", 404);
            if (!$v['CLASSE']) return $this->errorRes('Veuillez vérifier la ligne ' . ($k + 1) . ". Il manque la classe.", 404);
            $classe = Classe::all()->where('Name', '=', str_replace("\t", '', $v['CLASSE']))->first();
            if (!$classe) return $this->errorRes("Veuillez vérifier " . strtoupper($v['PRENOMS']) . " " . strtoupper($v['NOMS']) . ". La classe de " . $v['Classe'] . " est introuvable", 404);
            $existingStudentFirstname = Student::all()->where('Firstname', '=', $v['PRENOMS'])->first();
            if ($existingStudentFirstname) {
                $existingStudentLastname = Student::all()->where('Lastname', '=', $v['NOMS'])->first();
                if ($existingStudentLastname) return $this->errorRes($v['PRENOMS'] . ' ' . $v['NOMS'] . ' existe déjà dans le système', 404);
            } else Student::create([
                'Firstname' => strtoupper(str_replace("\t", '', $v['PRENOMS'])),
                'Lastname' => strtoupper(str_replace("\t", "", $v['NOMS'])),
                'Birthdate' => date('Y-m-d', strtotime(str_replace('/', '-', str_replace("\t", '', $v['DATE DE NAISSANCE'])))),
                'Canteen' => str_replace("\t", '', $v['CANTINE']) == 'Oui',
                'Transport' => str_replace("\t", '', $v['TRANSPORT']) == 'Oui',
                'ClasseId' => $classe->ClasseId,
            ]);
        }

        //return $this->debugRes($sList);

        return $this->successRes('la liste a bien été importé');
    }

    public function getSorasList()
    {
        $students = VSoras::all();

        return $this->successRes($students);
    }

    public function getMonthlyBirthday()
    {
        $students = VMonthlyBirthday::all();

        return $this->successRes($students);
    }

    public function getTransportList()
    {
        $students = VTransport::all();

        return $this->successRes($students);
    }

    public function getSchoolSiteList()
    {
        $students = VSchoolSite::all();

        return $this->successRes($students);
    }

    public function getKinderGardenSite()
    {
        $students = VKinderGarden::all();

        return $this->successRes($students);
    }

    public function getBirthdayListPerClass(Request $request)
    {
        $classeId = $request->get('cI');
        if (!$classeId) return $this->errorRes("De quelle classe s'agit-il ?", 404);
        $students = DB::select("call get_birthday_list_per_classe(?);", [$classeId]);

        return $this->successRes($students);
    }

    public function getPresenceListPerClasse(Request $request)
    {
        $classe = $request->get('classe');
        if (!$classe) return $this->errorRes("De quelle classe s'agit-il ?", 404);
        $students = DB::select("call get_presence_per_classe(?);", [$classe]);

        return $this->successRes($students);
    }

    public function editStudent(Request $request)
    {
        $studentId = $request->input('studentId');
        if (!$studentId) return $this->errorRes('De quel élève s\'agit-il ?', 404);

        $student = Student::all()->where('StudentId', '=', $studentId)->first();
        $vstudent = VStudents::all()->where('StudentId', '=', $studentId)->first();

        $firstname = $request->input('firstname');
        if (!$firstname) $firstname = $student->Firstname;
        $lastname = $request->input('lastname');
        if (!$lastname) $lastname = $student->Lastname;
        $birthdate = $request->input('birthdate');
        if (!$birthdate) $birthdate = $student->Birthdate;
        $classe = $request->input('classe');
        $classeId = 0;
        if (!$classe) $classeId = $student->ClasseId;
        $urubuto = $request->input('Urubuto');
        if (!$urubuto) $urubuto = $student->Urubuto;
        $allergies = $request->input('allergies');
        if (!$allergies) $allergies = $student->allergies;
        $canteen = filter_var($request->input('Canteen'), FILTER_VALIDATE_BOOLEAN);
        $transport = filter_var($request->input('Transport'), FILTER_VALIDATE_BOOLEAN);
        $registered = filter_var($request->input('Registered'), FILTER_VALIDATE_BOOLEAN);
        $picture = $request->input('Picture');
        if (!$picture) $picture = $student->Picture;
        $address = $request->input('address');
        if (!$address) $address = $student->Address;
        $newStudent = filter_var($request->input('newStudent'), FILTER_VALIDATE_BOOLEAN);

        $rulesSigned = filter_var($request->input('rulesSigned'), FILTER_VALIDATE_BOOLEAN);
        $registrationFileFilled = filter_var($request->input('registrationFileFilled'), FILTER_VALIDATE_BOOLEAN);
        $vaccinsFile = filter_var($request->input('vaccinsFile'), FILTER_VALIDATE_BOOLEAN);
        $piano = filter_var($request->input('piano'), FILTER_VALIDATE_BOOLEAN);
        $guitar = filter_var($request->input('guitar'), FILTER_VALIDATE_BOOLEAN);
        $swimmingpool = filter_var($request->input('swimmingpool'), FILTER_VALIDATE_BOOLEAN);
        $danse = filter_var($request->input('danse'), FILTER_VALIDATE_BOOLEAN);
        $sexe = filter_var($request->input('Sexe'), FILTER_VALIDATE_BOOLEAN);

        $classe = Classe::all()->where('Name', '=', $classe)->first();
        if ($classe) $classeId = $classe->ClasseId;

        $neighborhood = $request->input('neighborhood');
        if (!$neighborhood) $neighborhood = $vstudent->Neighborhood;
        $sector = VNeighborhood::all()->where('Neighborhood', '=', $neighborhood)->first();
        if (!$sector) {
            //return $this->debugRes($student->SectorId);
            if (!$student->SectorId) return $this->errorRes('Ce secteur n\'existe pas ou est introuvable dans le système', 404);
            else $sector = VNeighborhood::all()->where('SectorId', '=', $student->SectorId)->first();
        }

        $sectorId = $sector->SectorId;

        //return $this->debugRes($rulesSigned);

        $data = [
            'Lastname' => strtoupper($lastname),
            'Firstname' => strtoupper($firstname),
            'Birthdate' => $birthdate,
            'Urubuto' => $urubuto,
            'Canteen' => ($canteen != $student->Canteen) && $canteen,
            'Transport' => ($transport != $student->Transport) && $transport,
            'Picture' => $picture,
            'ClasseId' => $classeId,
            'Registered' => $registered,
            'Allergies' => $allergies,
            'SectorId' => $sectorId,
            'Address' => $address,
            'NewStudent' => $newStudent,
            'InternalRulesSigned' => $rulesSigned,
            'RegistrationFileFilled' => $registrationFileFilled,
            'VaccinsFile' => $vaccinsFile,
            'Piano' => $piano,
            'Guitar' => $guitar,
            'Piscine' => $swimmingpool,
            'Danse' => $danse,
            'Sexe' => $sexe,
        ];
/*         
        return $this->debugRes([
            '$data' => $data,
            '$registered' => $registered,
            'Canteen' => $canteen,
            'Transport' => $transport,
            'Sexe' => $sexe,
            'Urubuto' => $urubuto,
        ]);
*/
        $student->fill($data)->save();

        return $this->successRes('Mis à jour réussi!'); 
    }

    public function getStudentParents(Request $request)
    {
        $parents = Parents::all();
        $studentId = $request->get('studentid');
        if ($studentId) {
            $parents = DB::select('call get_student_parents(?)', [$studentId]);
            return $this->successRes($parents);
        }
        return $this->successRes($parents);
    }

    public function getStudentPerClasse(Request $request)
    {
        $classe = $request->get('classe');
        if (!$classe) return $this->errorRes('De quelle classe s\'agit-il ?', 404);
        $classe = strtoupper($classe);

        $classeId = Classe::all()->where('Name', '=', $classe)->pluck('ClasseId')->first();
        //return $this->debugRes([$classeId, $classe]);

        $classe = DB::select('call get_students_per_classe(?);', [$classeId]);
        return $this->successRes($classe);
    }

    public function getNeighborhoods()
    {
        $neighborhood = VNeighborhood::all();
        return $this->successRes($neighborhood);
    }

    public function getNumberStudentPerNeighborhood()
    {
        $studentPerNeighborhood = VNumberStudentPerNeighborhood::all();
        return $this->successRes($studentPerNeighborhood);
    }

    public function getNumberStudentPerSector()
    {
        $studentPerSector = DB::select("SELECT District, sum(NbStudents) as NbStudents
        FROM vnumberstudentperneighborhood
        group by district;");
        return $this->successRes($studentPerSector);
    }

    public function getComingBday()
    {
        $comingbday = VComingBday::all();
        return $this->successRes($comingbday);
    }

    public function getPastBday()
    {
        $pastbday = VPastBday::all();
        return $this->successRes($pastbday);
    }

    public function PassToNextClass()
    {
        $students = Student::all();
        if (!$students) return $this->errorRes('Il n\'existe pas d\'élève dans le système', 404);

        $arr = [];

        foreach ($students as $key => $value) {
            //array_push($arr,['ClasseId' => ($value->ClasseId + 2)]);
            $value->fill(['ClasseId' => ($value->ClasseId + 1), 'NewStudent' => 0])->save();
        }

        return $this->debugRes($students);
    }

    public function BackToPreviousClass()
    {
        $students = Student::all();
        if (!$students) return $this->errorRes('Il n\'existe pas d\'élève dans le système', 404);

        $arr = [];

        foreach ($students as $key => $value) {
            //array_push($arr,['ClasseId' => ($value->ClasseId + 2)]);
            $value->fill(['ClasseId' => ($value->ClasseId - 1), 'NewStudent' => 0])->save();
        }

        return $this->debugRes($students);
    }

    public function getNewStudents()
    {
        $students = VSoras::all()->where('NewStudent');
        $arr = [];
        //if(!$students) return $this->errorRes('Il n\'y a pas de nouveaux', 404);
        foreach ($students as $key => $value) {
            //return $this->debugRes([$key, $value]);
            array_push($arr, $value);
        }
        return $this->successRes($arr);
    }
}
