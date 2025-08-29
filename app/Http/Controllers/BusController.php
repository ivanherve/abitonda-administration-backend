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
        $lines = BusLine::with([
            'pickups.students:StudentId,Firstname,Lastname,ClasseId',
            'driver:EmployeeId,Firstname,Lastname',
            'assistant:EmployeeId,Firstname,Lastname',
            'pickups:PickupId,LineId,Name,Latitude,Longitude' // uniquement les colonnes nécessaires
        ])->get();

        $lines = $lines->map(function ($line) {
            $students = $line->pickups
                ->flatMap(function ($pickup) {
                    return $pickup->students;
                })
                ->unique('StudentId');

            return [
                'LineId' => $line->LineId,
                'Name' => $line->Name,
                'nbStudents' => $students->count(),
                'driverName' => $line->driver ? $line->driver->Firstname . ' ' . $line->driver->Lastname : null,
                'assistantName' => $line->assistant ? $line->assistant->Firstname . ' ' . $line->assistant->Lastname : null,
            ];
        });

        return $this->successRes($lines);
    }

    public function show($id)
    {
        $line = BusLine::with([
            'pickups.students:StudentId,Firstname,Lastname,ClasseId',
            'pickups:PickupId,LineId,Name',
            'driver:EmployeeId,Firstname,Lastname',
            'assistant:EmployeeId,Firstname,Lastname',
            'pickups.students.classe:PickupId,Name'
        ])->find($id);

        if (!$line) {
            return $this->errorRes('Ligne non trouvée', 404);
        }

        $students = $line->pickups
            ->flatMap(function ($pickup) {
                return $pickup->students;
            })
            ->unique('StudentId')
            ->map(function ($student) {
                return [
                    'StudentId' => $student->StudentId,
                    'Firstname' => $student->Firstname,
                    'Lastname' => $student->Lastname,
                    'Classe' => $student->classe->Name ?? null
                ];
            })
            ->values();

        $lineData = [
            'LineId' => $line->LineId,
            'Name' => $line->Name,
            'nbStudents' => $students->count(),
            'driverName' => $line->driver ? $line->driver->Firstname . ' ' . $line->driver->Lastname : null,
            'assistantName' => $line->assistant ? $line->assistant->Firstname . ' ' . $line->assistant->Lastname : null,
            'pickups' => $line->pickups->map(function ($pickup) {
                return [
                    'PickupId' => $pickup->PickupId,
                    'Name' => $pickup->Name,
                    'Latitude' => $pickup->Latitude,
                    'Longitude' => $pickup->Longitude,
                ];
            }),
        ];

        return $this->successRes([
            'line' => $lineData,
            'students' => $students
        ]);
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
        $date = request()->query('date', date('Y-m-d'));
        $directionId = request()->query('directionId', 1);
        $dayOfWeek = date('N', strtotime($date));

        $line = BusLine::with([
            'pickups:PickupId,LineId,Name,Latitude,Longitude,ArrivalGo,ArrivalReturn', // pickup_point
            'pickups.students:StudentId,Firstname,Lastname,ClasseId',             // students
            'pickups.students.classe:ClasseId,Name',                              // classe
            'driver:EmployeeId,Firstname,Lastname',                               // chauffeur
            'assistant:EmployeeId,Firstname,Lastname'                             // assistant
        ])->find($id);

        if (!$line) {
            return $this->errorRes('Ligne non trouvée', 404);
        }

        $pickups = $line->pickups->map(function ($pickup) use ($dayOfWeek, $directionId) {
            $studentsForDay = $pickup->students
                ->filter(function ($student) use ($dayOfWeek, $directionId) {
                    return $student->pivot->DayOfWeek == $dayOfWeek &&
                        $student->pivot->DirectionId == $directionId;
                })
                ->map(function ($student) use ($pickup) { // <-- ajouter $pickup dans use
                    return [
                        'StudentId' => $student->StudentId,
                        'Firstname' => $student->Firstname,
                        'Lastname' => $student->Lastname,
                        'Classe' => $student->classe->Name ?? null,
                        'PickupPoint' => $pickup->Name
                    ];
                })
                ->values();

            return [
                'PickupId' => $pickup->PickupId,
                'Name' => $pickup->Name,
                'Latitude' => $pickup->Latitude,
                'Longitude' => $pickup->Longitude,
                'Arrival' => $directionId == 1 ? $pickup->ArrivalGo : $pickup->ArrivalReturn,
                'students' => $studentsForDay,
            ];
        });

        $students = $pickups->flatMap(function ($p) {
            return $p['students'];
        })->unique('StudentId')->values();

        $lineData = [
            'LineId' => $line->LineId,
            'Name' => $line->Name,
            'pickups' => $pickups,
            'driverName' => $line->driver ? $line->driver->Firstname . ' ' . $line->driver->Lastname : null,
            'assistantName' => $line->assistant ? $line->assistant->Firstname . ' ' . $line->assistant->Lastname : null,
        ];

        return $this->successRes([
            'line' => $lineData,
            'students' => $students,
            'nbStudents' => $students->count(),
        ]);
    }

    public function updateTeam(Request $request)
    {
        $lineId = $request->input('LineId');
        if (!$lineId)
            return $this->errorRes("Veuillez insérer un bus", 404);
        $line = BusLine::find($lineId);
        if (!$line) {
            return $this->errorRes('Ligne non trouvée', 404);
        }

        // On met à jour seulement si la valeur est envoyée
        if ($request->has('DriverId')) {
            $line->DriverId = $request->input('DriverId');
        }

        if ($request->has('AssistantId')) {
            $line->AssistantId = $request->input('AssistantId');
        }

        $line->save();

        return $this->successRes(
            BusLine::with(['driver', 'assistant'])->find($lineId)
        );
    }

}
