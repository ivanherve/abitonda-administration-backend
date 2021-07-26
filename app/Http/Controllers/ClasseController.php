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

        return $this->successRes([$oneClass]);
    }
}
