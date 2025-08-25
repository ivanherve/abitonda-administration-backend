<?php

namespace App\Http\Controllers;

use App\Models\BusLine;
use App\Models\PickupPoint;
use App\Models\Student;
use App\Models\StudentPickup;
use Illuminate\Http\Request;

class BusController extends Controller
{
    public function index()
    {
        $lines = BusLine::with('pickups.students')->get();

        // Ajouter le nombre d'élèves uniques par ligne
        $lines = $lines->map(function ($line) {
            $students = $line->pickups
                ->flatMap(function ($pickup) {
                    return $pickup->students;
                })
                ->unique('StudentId');

            return collect($line)->put('nbStudents', $students->count());
        });

        return $this->successRes($lines);
    }

    public function show($id)
    {
        $line = BusLine::with('pickups.students')->find($id);

        if (!$line) {
            return $this->errorRes('Ligne non trouvée', 404);
        }

        $students = $line->pickups
            ->flatMap(function ($pickup) {
                return $pickup->students;
            })
            ->unique('StudentId');

        $line = collect($line)->put('nbStudents', $students->count());

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
        // Récupérer les paramètres depuis la query string
        $date = request()->query('date', date('Y-m-d')); // par défaut aujourd'hui
        $directionId = request()->query('directionId', 1); // par défaut aller

        // Numéro du jour de la semaine (1 = lundi, 7 = dimanche)
        $dayOfWeek = date('N', strtotime($date));

        // Charger la ligne avec pickups et élèves
        $line = BusLine::with('pickups.students')->find($id);

        if (!$line) {
            return $this->errorRes('Ligne non trouvée', 404);
        }

        // Filtrer les élèves par jour et direction
        $pickups = $line->pickups->map(function ($pickup) use ($dayOfWeek, $directionId) {
            $studentsForDay = $pickup->students
                ->filter(function ($student) use ($dayOfWeek, $directionId) {
                    return $student->pivot->DayOfWeek == $dayOfWeek &&
                        $student->pivot->DirectionId == $directionId;
                })
                ->map(function ($student) use ($pickup) {
                    return collect($student)
                        ->except(['Picture'])
                        ->put('Classe', isset($student->classe) ? $student->classe->Name : null) // version sans ?->
                        ->put('PickupPoint', $pickup->Name);
                })
                ->values();

            return [
                'PickupId' => $pickup->PickupId,
                'Name' => $pickup->Name,
                'Location' => $pickup->Location,
                'Arrival' => $directionId == 1 ? $pickup->ArrivalGo : $pickup->ArrivalReturn,
                'students' => $studentsForDay,
            ];
        });

        // Tous les élèves uniques pour la ligne, le jour et la direction
        $students = $pickups
            ->flatMap(function ($pickup) {
                return $pickup['students'];
            })
            ->unique('StudentId')
            ->values();

        $lineData = collect($line)->except(['pickups']);
        $lineData = $lineData->put('pickups', $pickups);

        $data = [
            'line' => $lineData,
            'students' => $students,
            'nbStudents' => $students->count(),
        ];

        return $this->successRes($data);
    }
}
