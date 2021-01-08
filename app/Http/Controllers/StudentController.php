<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Student;
use App\Models\Parents;
use App\Views\VStudents;
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
        $students = DB::table('vstudents')->paginate($request->get('limit'));

        return $this->successRes($students);
    }

    public function addStudent(Request $request)
    {
        $firstname = $request->input('Firstname');
        if (!$firstname) return $this->errorRes('Veuillez insérer un prénom', 404);
        $lastname = $request->input('Lastname');
        if (!$lastname) return $this->errorRes('Veuillez insérer un nom de famille', 404);
        $birthdate = $request->input('Birthdate');
        if (!$birthdate) return $this->errorRes('Veuillez insérer une date de naissance', 404);
        $canteen = $request->input('Canteen');
        $transport = $request->input('Transport');
        $classe = $request->input('Classe');
        if (!$classe) return $this->errorRes('Veuillez insérer une classe', 404);
        $picture = $request->input('Picture');
        if (!$picture) return $this->errorRes('Veuillez insérer une photo', 404);

        $classe = Classe::all()->where('Classe', '=', $classe)->pluck('ClasseId')->first();
        if (!$classe) return $this->errorRes('Cette classe est introuvable', 404);

        //return $this->debugRes($classe);

        $newStudent = Student::create([
            'Lastname' => strtoupper($lastname),
            'Firstname' => strtoupper($firstname),
            'Birthdate' => $birthdate,
            'Canteen' => $canteen,
            'Transport' => $transport,
            'ClasseId' => $classe,
            'Picture' => $picture
        ]);

        return $this->successRes($newStudent);
    }

    public function getParents(Request $request)
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

        $classeId = Classe::all()->where('Classe', '=', $classe)->pluck('ClasseId')->first();
        //return $this->debugRes($classeId);

        $classe = DB::select('call get_students_per_classe(?);', [$classeId]);
        return $this->successRes($classe);
    }
}
