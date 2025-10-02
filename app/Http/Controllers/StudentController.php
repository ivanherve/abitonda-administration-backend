<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Family;
use App\Models\PickupPoint;
use App\Models\Student;
use App\Models\Parents;
use App\Models\StudentPickup;
use App\Views\VComingBday;
use App\Views\VNeighborhood;
use App\Views\VNoTransport;
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
use Illuminate\Support\Facades\Log;

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

    private function transformFeeWithBirthday($fee, $birthdate)
    {
        if ($fee->Name !== 'Anniversaire') {
            return [
                'FeesId' => $fee->FeesId,
                'Name' => $fee->Name,
                'Amount' => $fee->Amount,
                'IsDynamic' => $fee->IsDynamic,
                'Term' => optional($fee->term)->Code,
            ];
        }

        $birthMonth = $this->getAnniversaireMonthName($birthdate);
        $birthTerm = $this->getBirthTerm($birthdate);

        return [
            'FeesId' => $fee->FeesId,
            'Name' => "Anniversaire ({$birthMonth})",
            'Amount' => $fee->Amount,
            'IsDynamic' => $fee->IsDynamic,
            'Term' => $birthTerm,
        ];
    }

    private function getBirthTerm(string $dateString): ?string
    {
        $month = (int) date('n', strtotime($dateString));
        if (in_array($month, [7, 8, 9, 10, 11, 12]))
            return 'T1'; // juil à déc
        if ($month >= 1 && $month <= 3)
            return 'T2'; // janv à mars
        if ($month >= 4 && $month <= 6)
            return 'T3'; // avr à juin
        return null;
    }

    private function getAnniversaireMonthName(string $dateString): string
    {
        $months = [
            1 => 'janvier',
            2 => 'février',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
            6 => 'juin',
            7 => 'juillet',
            8 => 'août',
            9 => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'décembre'
        ];

        $month = (int) date('n', strtotime($dateString));
        if (in_array($month, [7, 8]))
            return 'septembre'; // juillet/août => septembre

        return $months[$month] ?? 'septembre';
    }

    private function getSiblingsData($studentId)
    {
        $student = VStudents::findOrFail($studentId);

        if (empty($student->FamilyId)) {
            return [
                'siblings' => collect(),
                'studentPosition' => [
                    'StudentId' => $student->StudentId,
                    'PositionIndex' => 1,
                    'PositionLabel' => 'Enfant unique',
                    'ReductionPourcentage' => 0,
                ],
            ];
        }

        $familyChildren = VStudents::with(['fees.term'])
            ->where('FamilyId', $student->FamilyId)
            ->orderBy('Birthdate')
            ->get();

        $familyChildren = $familyChildren->map(function ($child, $index) {
            $position = $index + 1;

            // Remplacement de match par switch
            switch ($index) {
                case 0:
                    $positionLabel = 'Aîné(e)';
                    break;
                case 1:
                    $positionLabel = 'Deuxième enfant';
                    break;
                case 2:
                    $positionLabel = 'Troisième enfant';
                    break;
                default:
                    $positionLabel = $position . 'e enfant';
                    break;
            }

            $reduction = 0;
            switch ($index) {
                case 1:
                    $reduction = 10;
                    break;
                case 2:
                    $reduction = 15;
                    break;
                default:
                    if ($index >= 3) {
                        $reduction = 20;
                    }
                    break;
            }

            $child->PositionIndex = $position;
            $child->PositionLabel = $positionLabel;
            $child->ReductionPourcentage = $reduction;

            $child->fees = $child->fees->map(function ($fee) use ($child) {
                return $this->transformFeeWithBirthday($fee, $child->Birthdate);
            });

            return $child;
        });

        $siblings = $familyChildren->filter(function ($c) use ($student) {
            return $c->StudentId !== $student->StudentId;
        })->values();

        $studentPosition = $familyChildren->firstWhere('StudentId', $student->StudentId);

        return [
            'siblings' => $siblings,
            'studentPosition' => [
                'StudentId' => $studentPosition->StudentId,
                'PositionIndex' => $studentPosition->PositionIndex,
                'PositionLabel' => $studentPosition->PositionLabel,
                'ReductionPourcentage' => $studentPosition->ReductionPourcentage,
            ],
        ];
    }

    public function getStudents()
    {
        $students = VStudents::with(['fees.term'])->get();

        $transformed = $students->map(function ($student) {
            $student->fees = $student->fees->map(function ($fee) use ($student) {
                return $this->transformFeeWithBirthday($fee, $student->Birthdate);
            });
            return $student;
        });

        return $this->successRes($transformed);
    }

    public function getTenStudents(Request $request)
    {
        $limit = $request->get('limit', 10);

        $students = VStudents::with(['fees.term'])->paginate($limit);

        $transformedCollection = $students->getCollection()->map(function ($student) {
            $student->fees = $student->fees->map(function ($fee) use ($student) {
                return $this->transformFeeWithBirthday($fee, $student->Birthdate);
            });
            return $student;
        });

        $students->setCollection($transformedCollection);

        return $this->successRes($students);
    }

    public function searchStudent(Request $request)
    {
        $name = $request->get('name');

        $students = VStudents::with(['fees.term'])
            ->where('Firstname', 'like', "%" . $name . "%")
            ->get();

        $transformed = $students->map(function ($student) {
            $student->fees = $student->fees->map(function ($fee) use ($student) {
                return $this->transformFeeWithBirthday($fee, $student->Birthdate);
            });
            return $student;
        });

        return $this->successRes($transformed);
    }

    public function getSiblings($studentId)
    {
        $data = $this->getSiblingsData($studentId);
        return $this->successRes($data);
    }

    public function getStudentParents(Request $request)
    {
        $studentId = $request->get('studentid');

        if (!$studentId) {
            $parents = Parents::all();
            return $this->successRes($parents);
        }

        $student = DB::table('students')
            ->select('StudentId', 'FamilyId', 'Birthdate')
            ->where('StudentId', $studentId)
            ->first();

        if (!$student) {
            return $this->errorRes("Élève introuvable", 404);
        }

        // Appeler getSiblings pour récupérer la réduction de position
        $siblingsData = $this->getSiblingsData($studentId);
        // On récupère la réduction de position (extraction depuis la réponse)
        $studentPositionReduction = 0;
        if (isset($siblingsData['studentPosition']['ReductionPourcentage'])) {
            $studentPositionReduction = $siblingsData['studentPosition']['ReductionPourcentage'];
        }

        $cadet = DB::table('students')
            ->where('FamilyId', $student->FamilyId)
            ->orderByDesc('Birthdate')
            ->first();

        $isCadet = $cadet && $cadet->StudentId == $student->StudentId;

        $parents = DB::select('call get_student_parents(?)', [$studentId]);

        foreach ($parents as &$parent) {
            $sponsored = DB::table('students')
                ->select('StudentId', 'Firstname', 'Lastname', 'Birthdate')
                ->where('SponsoringParent', $parent->ParentId)
                ->get();

            $parent->SponsoredChildren = $sponsored;

            $sponsorshipReduction = 0;
            if ($isCadet && $sponsored->count() > 0) {
                $sponsorshipReduction = $sponsored->count() * 5;
            }

            // Ajout de la réduction de position + réduction par parrainage
            $parent->ReductionPourcentage = $studentPositionReduction + $sponsorshipReduction;
        }

        return $this->successRes($parents);
    }

    public function addStudent(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $firstname = $request->input('Firstname');
            if (!$firstname)
                return $this->errorRes('Veuillez insérer un prénom', 404);
            $lastname = $request->input('Lastname');
            if (!$lastname)
                return $this->errorRes('Veuillez insérer un nom de famille', 404);
            $urubuto = $request->input('Urubuto');
            if (!$urubuto)
                return $this->errorRes('Veuillez insérer le code Urubuto de l\'enfant', 404);
            $birthdate = $request->input('Birthdate');
            if (!$birthdate)
                return $this->errorRes('Veuillez insérer une date de naissance', 404);

            $canteen = filter_var($request->input('Canteen'), FILTER_VALIDATE_BOOLEAN);
            $transport = filter_var($request->input('Transport'), FILTER_VALIDATE_BOOLEAN);
            $rulesSigned = filter_var($request->input('rulesSigned'), FILTER_VALIDATE_BOOLEAN);
            $registrationFileFilled = filter_var($request->input('registrationFileFilled'), FILTER_VALIDATE_BOOLEAN);
            $vaccinsFile = filter_var($request->input('vaccinsFile'), FILTER_VALIDATE_BOOLEAN);
            $paid = filter_var($request->input('paid'), FILTER_VALIDATE_BOOLEAN);
            $address = $request->input('address');
            $pointDeRamassage = $request->input('pointDeRamassage');

            $classe = $request->input('Classe');
            if (!$classe)
                return $this->errorRes('Veuillez insérer une classe', 404);

            $classe = Classe::where('Name', $classe)->pluck('ClasseId')->first();
            if (!$classe)
                return $this->errorRes('Cette classe est introuvable', 404);

            $neighborhood = $request->input('neighborhoodSelected');
            if (!$neighborhood)
                return $this->errorRes('Veuillez séléctionner un quartier', 404);

            $sector = VNeighborhood::where('Neighborhood', $neighborhood)->pluck('SectorId')->first();

            $studentToCreate = [
                'Lastname' => strtoupper($lastname),
                'Firstname' => strtoupper($firstname),
                'Urubuto' => $urubuto,
                'Birthdate' => $birthdate,
                'Canteen' => $canteen,
                'Transport' => $transport,
                'ClasseId' => $classe,
                'Picture' => $request->input('Picture'),
                'SectorId' => $sector,
                'Address' => $address,
                'InternalRulesSigned' => $rulesSigned,
                'RegistrationFileFilled' => $registrationFileFilled,
                'VaccinsFile' => $vaccinsFile,
                'Paid' => $paid,
                'PointDeRamassage' => $pointDeRamassage
            ];

            // ✅ tout est dans la transaction
            $newStudent = Student::create($studentToCreate);

            // 🔗 assigner la famille
            $this->assignFamily($newStudent, $request);

            return $this->successRes($newStudent);
        });
    }

    private function assignFamily(Student $student, Request $request)
    {
        $familyId = $request->input('FamilyId');   // id existant ou vide
        $siblingId = $request->input('SiblingId');

        if ($siblingId) {
            $sibling = Student::find($siblingId);
        } else {
            return $this->errorRes("Aucun frère ou soeur n'a été sélectionné", 404);
        }

        if ($familyId) {
            // rattacher l’élève existant
            $student->update(['family_id' => $familyId]);

        } else {
            // créer une nouvelle famille
            $newFamily = Family::create([]);

            // rattacher le nouvel élève
            $student->update(['family_id' => $newFamily->id]);

            // ⚡ rattacher aussi le sibling
            $sibling->update(['family_id' => $newFamily->id]);

            // rattacher les parents du sibling
            $parents = $sibling->parents;  // via relation Eloquent
            foreach ($parents as $p) {
                $p->update(['family_id' => $newFamily->id]);
            }
        }
    }

    public function getFamilyFromSibling($siblingId)
    {
        // ✅ Récupérer l'élève sibling
        $sibling = Student::find($siblingId);

        if (!$sibling) {
            return $this->errorRes("Élève introuvable", 404);
        }

        // ✅ Vérifier si le sibling a déjà un FamilyId
        $familyId = $sibling->FamilyId;

        if ($familyId) {
            // Si FamilyId existe → récupérer frères/soeurs + parents
            $siblings = Student::where('FamilyId', $familyId)
                ->where('StudentId', '!=', $siblingId) // exclure le sibling lui-même si besoin
                ->get();

            $parents = Parents::where('FamilyId', $familyId)->get();

            return $this->successRes([
                'familyId' => $familyId,
                'sibling' => $sibling,
                'siblings' => $siblings,
                'parents' => $parents
            ]);
        } else {
            // Pas de FamilyId → on renvoie uniquement les parents liés au sibling
            $parents = $sibling->parents;

            return $this->successRes([
                'familyId' => null,
                'sibling' => $sibling,
                'siblings' => [],
                'parents' => $parents
            ]);
        }
    }

    public function getRegistrationIncomplete()
    {
        $students = VRegistrationIncomplete::all();
        if (!$students)
            return $this->errorRes('Aucun enfant sur cette liste, tout le monde est en ordre', 404);

        return $this->successRes($students);
    }

    public function getStudentsPicture(Request $request)
    {
        $studentId = $request->input('studentId');
        if (!$studentId)
            return $this->errorRes('De quel élève s\'agit-il ?', 404);
        $picture = DB::select("call get_students_picture(?)", [$studentId]);

        return $this->successRes($picture);
    }

    public function addStudentCSV(Request $request)
    {
        $file = $request->file('csv');
        if (!$file)
            return $this->errorRes('Veuillez insérer un fichier', 404);
        if ($file->getClientOriginalExtension() != 'csv')
            return $this->errorRes('Veuillez uniquement importer des fichiers .csv', 401);

        $studentList = $this->csvToArray($file->getRealPath());
        /**/
        $sList = [];

        foreach ($studentList as $k => $v) {
            if (!$v['NOMS'])
                return $this->errorRes('Veuillez vérifier la ligne ' . ($k + 1) . ". Il manque un nom.", 404);
            if (!$v['PRENOMS'])
                return $this->errorRes('Veuillez vérifier la ligne ' . ($k + 1) . ". Il manque un prénom.", 404);
            if (!$v['DATE DE NAISSANCE'])
                return $this->errorRes('Veuillez vérifier la ligne ' . ($k + 1) . ". Il manque une date de naissance.", 404);
            if (!$v['CANTINE'])
                return $this->errorRes('Veuillez vérifier la ligne ' . ($k + 1) . ". Est-ce que l'enfant mange à l'école ?", 404);
            if (!$v['TRANSPORT'])
                return $this->errorRes('Veuillez vérifier la ligne ' . ($k + 1) . ". Est-ce que l'enfant est transporté ?", 404);
            if (!$v['CLASSE'])
                return $this->errorRes('Veuillez vérifier la ligne ' . ($k + 1) . ". Il manque la classe.", 404);
            $classe = Classe::all()->where('Name', '=', str_replace("\t", '', $v['CLASSE']))->first();
            if (!$classe)
                return $this->errorRes("Veuillez vérifier " . strtoupper($v['PRENOMS']) . " " . strtoupper($v['NOMS']) . ". La classe de " . $v['Classe'] . " est introuvable", 404);
            $existingStudentFirstname = Student::all()->where('Firstname', '=', $v['PRENOMS'])->first();
            if ($existingStudentFirstname) {
                $existingStudentLastname = Student::all()->where('Lastname', '=', $v['NOMS'])->first();
                if ($existingStudentLastname)
                    return $this->errorRes($v['PRENOMS'] . ' ' . $v['NOMS'] . ' existe déjà dans le système', 404);
            } else
                Student::create([
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

    public function getTransportList(Request $request)
    {
        $classe = $request->input('classe');

        if (empty($classe)) {
            $students = VTransport::all();
        } else {
            $students = VTransport::where('Classe', '=', $classe)->get();
        }

        return $this->successRes($students);
    }
    public function getNoTransportList(Request $request)
    {
        $classe = $request->input('classe');
        if (empty($classe)) {
            $students = VNoTransport::all();
        } else {
            $students = VNoTransport::where('Classe', '=', $classe)->get();
        }

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
        if (!$classeId)
            return $this->errorRes("De quelle classe s'agit-il ?", 404);
        $students = DB::select("call get_birthday_list_per_classe(?);", [$classeId]);

        return $this->successRes($students);
    }

    public function getPresenceListPerClasse(Request $request)
    {
        $classe = $request->get('classe');
        if (!$classe)
            return $this->errorRes("De quelle classe s'agit-il ?", 404);
        $students = DB::select("call get_presence_per_classe(?);", [$classe]);

        return $this->successRes($students);
    }

    public function editStudent(Request $request)
    {
        $studentId = $request->input('studentId');
        if (!$studentId)
            return $this->errorRes('De quel élève s\'agit-il ?', 404);

        $student = Student::all()->where('StudentId', '=', $studentId)->first();
        $vstudent = VStudents::all()->where('StudentId', '=', $studentId)->first();

        $firstname = $request->input('firstname');
        if (!$firstname)
            $firstname = $student->Firstname;
        $lastname = $request->input('lastname');
        if (!$lastname)
            $lastname = $student->Lastname;
        $birthdate = $request->input('birthdate');
        if (!$birthdate)
            $birthdate = $student->Birthdate;
        $classe = $request->input('classe');
        $classeId = 0;
        if (!$classe)
            $classeId = $student->ClasseId;
        $urubuto = $request->input('Urubuto');
        if (!$urubuto)
            $urubuto = $student->Urubuto;
        $allergies = $request->input('allergies');
        if (!$allergies)
            $allergies = $student->allergies;
        $canteen = filter_var($request->input('Canteen'), FILTER_VALIDATE_BOOLEAN);
        $transport = filter_var($request->input('Transport'), FILTER_VALIDATE_BOOLEAN);
        $registered = filter_var($request->input('Registered'), FILTER_VALIDATE_BOOLEAN);
        $picture = $request->input('Picture');
        if (!$picture)
            $picture = $student->Picture;
        $address = $request->input('address');
        if (!$address)
            $address = $student->Address;
        $newStudent = filter_var($request->input('newStudent'), FILTER_VALIDATE_BOOLEAN);
        $pointDeRamassage = $request->input('pointDeRamassage');
        if (!$pointDeRamassage)
            $pointDeRamassage = $student->PointDeRamassage;

        $rulesSigned = filter_var($request->input('rulesSigned'), FILTER_VALIDATE_BOOLEAN);
        $registrationFileFilled = filter_var($request->input('registrationFileFilled'), FILTER_VALIDATE_BOOLEAN);
        $vaccinsFile = filter_var($request->input('vaccinsFile'), FILTER_VALIDATE_BOOLEAN);
        $paid = filter_var($request->input('paid'), FILTER_VALIDATE_BOOLEAN);
        $sexe = filter_var($request->input('Sexe'), FILTER_VALIDATE_BOOLEAN);

        $classe = Classe::all()->where('Name', '=', $classe)->first();
        if ($classe)
            $classeId = $classe->ClasseId;

        $neighborhood = $request->input('neighborhood');
        if (!$neighborhood)
            $neighborhood = $vstudent->Neighborhood;
        $sector = VNeighborhood::all()->where('Neighborhood', '=', $neighborhood)->first();
        if (!$sector) {
            //return $this->debugRes($student->SectorId);
            if (!$student->SectorId)
                return $this->errorRes('Ce secteur n\'existe pas ou est introuvable dans le système', 404);
            else
                $sector = VNeighborhood::all()->where('SectorId', '=', $student->SectorId)->first();
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
            'Paid' => $paid,
            'Sexe' => $sexe,
            'PointDeRamassage' => $pointDeRamassage
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

    public function getStudentPerClasse(Request $request)
    {
        $classe = $request->get('classe');
        if (!$classe)
            return $this->errorRes('De quelle classe s\'agit-il ?', 404);
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
        if (!$students)
            return $this->errorRes('Il n\'existe pas d\'élève dans le système', 404);

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
        if (!$students)
            return $this->errorRes('Il n\'existe pas d\'élève dans le système', 404);

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

    public function getCanteen()
    {
        $students = VSoras::where('Canteen', true)->get();
        if (!$students)
            return $this->errorRes("Aucun élève n'est à la cantine", 404);
        else
            return $this->successRes($students);
    }

    /**
     * Met à jour les points de ramassage et horaires d'un élève
     */

    public function updateTransport(Request $request)
    {
        $studentId = $request->input('studentId');
        $settings = $request->input('settings');

        if (!$studentId || !is_array($settings)) {
            return $this->debugRes([
                'studentId' => $studentId,
                'settings' => $settings,
                'isArray' => is_array($settings)
            ]);
        }

        $updatedPickups = [];

        try {
            foreach ($settings as $setting) {
                $day = $setting['day'] ?? null;
                $goPointName = $setting['goPoint'] ?? null;
                $returnPointName = $setting['returnPoint'] ?? null;
                $returnPointHalfDayName = $setting['returnPointHalfDay'] ?? null; // 👈 nouveau

                if (!$day)
                    continue;

                $goPickup = null;
                $returnPickup = null;
                $returnHalfDayPickup = null;

                // 🚍 Aller
                if ($goPointName) {
                    $goPickup = PickupPoint::where('Name', $goPointName)->first();
                    if ($goPickup) {
                        $updated = StudentPickup::where('StudentId', $studentId)
                            ->where('DayOfWeek', $day)
                            ->where('DirectionId', 1)
                            ->update(['PickupId' => $goPickup->PickupId]);

                        if ($updated === 0) {
                            StudentPickup::create([
                                'StudentId' => $studentId,
                                'DayOfWeek' => $day,
                                'DirectionId' => 1,
                                'PickupId' => $goPickup->PickupId
                            ]);
                        }
                    }
                }

                // 🏠 Retour normal
                if ($returnPointName) {
                    $returnPickup = PickupPoint::where('Name', $returnPointName)->first();
                    if ($returnPickup) {
                        $updated = StudentPickup::where('StudentId', $studentId)
                            ->where('DayOfWeek', $day)
                            ->where('DirectionId', 2)
                            ->update(['PickupId' => $returnPickup->PickupId]);

                        if ($updated === 0) {
                            StudentPickup::create([
                                'StudentId' => $studentId,
                                'DayOfWeek' => $day,
                                'DirectionId' => 2,
                                'PickupId' => $returnPickup->PickupId
                            ]);
                        }
                    }
                }

                // 🕒 Retour demi-journée (uniquement vendredi → on suppose que "Friday" = 5 ou "vendredi")
                if ($day == 5 && $returnPointHalfDayName) { // 👈 selon ton format DayOfWeek
                    $returnHalfDayPickup = PickupPoint::where('Name', $returnPointHalfDayName)->first();
                    if ($returnHalfDayPickup) {
                        $updated = StudentPickup::where('StudentId', $studentId)
                            ->where('DayOfWeek', $day)
                            ->where('DirectionId', 3) // 👈 nouvelle direction
                            ->update(['PickupId' => $returnHalfDayPickup->PickupId]);

                        if ($updated === 0) {
                            StudentPickup::create([
                                'StudentId' => $studentId,
                                'DayOfWeek' => $day,
                                'DirectionId' => 3,
                                'PickupId' => $returnHalfDayPickup->PickupId
                            ]);
                        }
                    }
                }

                // Log
                $updatedPickups[] = [
                    'day' => $day,
                    'student_id' => $studentId,
                    'go_point_name' => $goPointName,
                    'return_point_name' => $returnPointName,
                    'return_point_half_day_name' => $returnPointHalfDayName,
                    'GoPickup' => $goPickup,
                    'ReturnPickup' => $returnPickup,
                    'ReturnHalfDayPickup' => $returnHalfDayPickup,
                    'message' => 'Mise à jour réussie',
                ];
            }

            return $this->successRes($updatedPickups);

        } catch (\Exception $e) {
            return $this->errorRes('Erreur lors de la mise à jour : ' . $e->getMessage(), 500);
        }
    }

    public function getStudentPickups($id, Request $request)
    {
        // $date = $request->query('date'); // peut être null si non fourni
        $directionId = $request->query('directionId'); // peut être null

        $dayOfWeek = $request->query('day');

        $student = Student::with(['pickupPoints.line'])
            ->find($id);

        if (!$student) {
            return $this->errorRes("Élève non trouvé", 404);
        }

        // On utilise un filter avec une fonction anonyme "ancienne syntaxe"
        $pickups = $student->pickupPoints
            ->filter(function ($pickup) use ($dayOfWeek, $directionId) {
                $validDay = !$dayOfWeek || $pickup->pivot->DayOfWeek == $dayOfWeek;
                $validDirection = !$directionId || $pickup->pivot->DirectionId == $directionId;
                return $validDay && $validDirection;
            })
            ->values();

        $goPickups = [];
        $returnPickups = [];

        foreach ($pickups as $pickup) {
            // Choisir le tableau cible en fonction de la direction
            if ($pickup->pivot->DirectionId == 1) {
                $directionArray = &$goPickups;
            } else {
                $directionArray = &$returnPickups;
            }

            $pickupId = $pickup->PickupId;

            if (!isset($directionArray[$pickupId])) {
                // Première fois qu’on rencontre cet arrêt
                $directionArray[$pickupId] = [
                    'id' => $pickup->PickupId,
                    'name' => $pickup->Name,
                    'line' => $pickup->line,
                    'days' => [],
                    'latitude' => $pickup->Latitude,
                    'longitude' => $pickup->Longitude
                ];
            }

            // Ajouter le jour correspondant
            $directionArray[$pickupId]['days'][] = $pickup->pivot->DayOfWeek;
        }

        $pickups = [
            'goPickups' => array_values($goPickups),
            'returnPickups' => array_values($returnPickups),
        ];

        return $this->successRes($pickups);
    }

    public function unsetPickupPoint(Request $request)
    {
        $studentId = $request->input('studentId');
        $day = $request->input('day');
        $direction = $request->input('directionId');
        $pickupId = $request->input('pickupId');

        $data = [
            'studentId' => $studentId,
            'day' => $day,
            'direction' => $direction,
            'pickupId' => $pickupId
        ];

        $pickupsToDelete = [];
        foreach ($data['day'] as $key => $value) {
            if ($value) {
                $pickupsToDelete[] = [
                    'studentId' => $studentId,
                    'day' => $value,
                    'direction' => $direction,
                    'pickupId' => $pickupId
                ];
            }
        }

        // Supprimer les points de ramassage
        foreach ($pickupsToDelete as $pickup) {
            StudentPickup::where('StudentId', $pickup['studentId'])
                ->where('DayOfWeek', $pickup['day'])
                ->where('DirectionId', $pickup['direction'])
                ->where('PickupId', $pickup['pickupId'])
                ->delete();
        }

        return $this->errorRes('Point(s) de ramassage supprimé(s) avec succès', 200);
    }

    public function getGoogleMyMapsCoordinates($id)
    {
        $map = DB::select("call google_my_maps_per_line($id)");
        if (!$map)
            return $this->errorRes("Aucune donnée trouvée pour cette ligne", 404);
        return $this->successRes($map);
    }

}
