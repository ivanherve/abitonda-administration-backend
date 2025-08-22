<?php

namespace App\Http\Controllers;

use App\Models\BusLine;
use Illuminate\Http\Request;

class BusController extends Controller
{
    public function index()
    {
        $lines = BusLine::with('pickups')->get();
        return $this->successRes($lines);
    }

    public function show($id)
    {
        $line = BusLine::with('pickups')->find($id);
        if (!$line)
            return $this->errorRes('Ligne non trouvée', 404);
        return $this->successRes($line);
    }

    public function store(Request $request)
    {
        try {
            $line = BusLine::create($request->all());
            return $this->successRes($line, 201);
        } catch (\Exception $e) {
            return $this->errorRes($e->getMessage(), 400);
        }
    }

    public function update(Request $request, $id)
    {
        $line = BusLine::find($id);
        if (!$line)
            return $this->errorRes('Ligne non trouvée', 404);

        $line->update($request->all());
        return $this->successRes($line);
    }

    public function destroy($id)
    {
        $line = BusLine::find($id);
        if (!$line)
            return $this->errorRes('Ligne non trouvée', 404);

        $line->delete();
        return $this->successRes('Ligne supprimée', 204);
    }

    public function getBusLinesStudents($id)
    {
        $line = BusLine::find($id);
        if (!$line) {
            return $this->errorRes('Ligne non trouvée', 404);
        }

        // Récupérer le paramètre direction depuis la query string
        $directionParam = request()->query('direction', 'go'); // défaut = 'go'

        // Traduire 'go'/'return' en DirectionId selon ta table Direction
        // Par exemple, si DirectionId = 1 pour aller, 2 pour retour
        $directionId = $directionParam === 'go' ? 1 : 2;

        // Charger les affectations élèves pour cette ligne et cette direction
        $students = $line->studentPickups()
            ->where('DirectionId', $directionId)
            ->with(['student', 'pickupPoint'])
            ->get();

        // Formater les données pour le frontend
        $formatted = $students->map(function ($s) {
            return [
                'StudentId' => $s->student->StudentId,
                'name' => $s->student->Firstname . ' ' . $s->student->Lastname,
                'classe' => $s->student->Classe ?? 'N/A',
                'pickup_point' => [
                    'Name' => $s->pickupPoint->Name
                ],
                'LineId' => $s->LineId,
                'Direction' => $s->DirectionId,
            ];
        });

        return $this->successRes($formatted);
    }
}
