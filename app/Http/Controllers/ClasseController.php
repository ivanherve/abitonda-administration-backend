<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Views\VClasse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClasseController extends Controller
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

    public function getClasses(Request $request)
    {
        $classes = VClasse::all();

        $oneClass = $request->get('classe');

        if (!$oneClass) return $this->successRes($classes);

        $oneClass = VClasse::all()->where('Name', '=', $oneClass)->first();

        $oneClass['assistants'] = DB::select("call get_assistants(?)", [$oneClass->ClasseId]);

        return $this->successRes($oneClass);
    }

    public function AddTeacher(Request $request)
    {
        $employeeId = $request->input('employeeId');
        $classeId = $request->input('classeId');
        if (DB::insert("call add_teacher(?,?)", [$employeeId, $classeId]))
            return $this->successRes("L'enseignant a bien été retiré");
        else return $this->errorRes("L'enseignant n'a pas pu être ajouté", 500);
    }

    public function RemoveTeacher(Request $request)
    {
        $employeeId = $request->input('employeeId');
        $classeId = $request->input('classeId');
        if (DB::delete("call remove_teacher_from_classe(?,?)", [$employeeId, $classeId]))
            return $this->successRes("L'enseignant a bien été retiré");
        else return $this->errorRes("L'enseignant n'a pas pu être retiré", 500);
    }

    public function AddAssistant(Request $request)
    {
        $employeeId = $request->input('employeeId');
        $classeId = $request->input('classeId');
        if (DB::insert("call add_assistant(?,?)", [$classeId, $employeeId]))
            return $this->successRes("L'enseignant a bien été retiré");
        else return $this->errorRes("L'enseignant n'a pas pu être ajouté", 500);
    }

    public function RemoveAssistant(Request $request)
    {
        $employeeId = $request->input('employeeId');
        $classeId = $request->input('classeId');
        if (DB::delete("call remove_assistant_from_classe(?,?)", [$classeId, $employeeId]))
            return $this->successRes("L'assistant a bien été retiré");
        //else return $this->errorRes("L'assistant n'a pas pu être retiré", 500);
    }

    public function getListContactPerClasse(Request $request)
    {
        $classe = $request->get('classe');
        $contacts = DB::select("call list_contact_per_classe(?)", [$classe]);

        return $this->successRes($contacts);
    }
}