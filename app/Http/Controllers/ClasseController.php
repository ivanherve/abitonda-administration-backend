<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use Illuminate\Http\Request;

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
        $classes = Classe::all();

        $oneClass = $request->get('classe');

        if(!$oneClass) return $this->successRes($classes);

        $oneClass = Classe::all()->where('Classe','=',$oneClass)->first();

        return $this->successRes([$oneClass]);
    }
}
