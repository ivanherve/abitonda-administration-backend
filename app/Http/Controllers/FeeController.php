<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Fee;

class FeeController extends Controller
{
    public function index()
    {
        return Fee::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Name' => 'required|string',
            'Amount' => 'required|numeric|min:1',
            'Term' => 'required|in:T1,T2,T3',
            'IsActive' => 'sometimes|boolean'
        ]);

        $fee = Fee::create($validated);
        return response()->json($fee);
    }

    public function update(Request $request, $id)
    {
        $fee = Fee::findOrFail($id);

        $name = $request->input('Name');
        $amount = $request->input('Amount');
        $term = $request->input('Term');
        if (!$name) {
            $name = $fee->Name;
        }
        if (!$amount) {
            $amount = $fee->Amount;
        }
        if (!$term) {
            $term = $fee->Term;
        }
        $fee->fill([
            'Name' => $name,
            'Amount' => $amount,
            'Term' => $term,
            'IsActive' => $request->input('IsActive', $fee->IsActive)
        ]);
        return response()->json($fee);
    }

    public function destroy($id)
    {
        $fee = Fee::findOrFail($id);

        // Supprime les liens many-to-many
        $fee->students()->detach();

        return response()->json(['message' => 'Supprimé']);
    }

}
