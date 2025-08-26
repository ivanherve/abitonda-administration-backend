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
        $lines = BusLine::with(['pickups.students', 'driver', 'assistant'])->get();

        $lines = $lines->map(function ($line) {
            $students = $line->pickups
                ->flatMap(function ($pickup) {
                    return $pickup->students;
                })
                ->unique('StudentId');

            return collect($line)->put('nbStudents', $students->count())
                ->put('driverName', $line->driver ? $line->driver->Firstname . ' ' . $line->driver->Lastname : null)
                ->put('assistantName', $line->assistant ? $line->assistant->Firstname . ' ' . $line->assistant->Lastname : null); // nom du chauffeur
        });

        return $this->successRes($lines);
    }

    public function show($id)
    {
        $line = BusLine::with(['pickups.students', 'driver', 'assistant'])->find($id);

        if (!$line) {
            return $this->errorRes('Ligne non trouvée', 404);
        }

        $students = $line->pickups
            ->flatMap(function ($pickup) {
                return $pickup->students;
            })
            ->unique('StudentId');

        $line = collect($line)->put('nbStudents', $students->count())
            ->put('driverName', $line->driver ? $line->driver->Name : null);

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
        $directionId = request()->query('directionId', 1); // par défaut Aller

        // Numéro du jour de la semaine (1 = lundi, 7 = dimanche)
        $dayOfWeek = date('N', strtotime($date));

        // Charger la ligne avec ses pickups, étudiants et le chauffeur
        $line = BusLine::with(['pickups.students', 'driver', 'assistant'])->find($id);

        // Vérifier si la ligne existe
        if (!$line) {
            return $this->errorRes('Ligne non trouvée', 404);
        }

        // Filtrer les élèves pour chaque point de ramassage selon le jour et la direction
        $pickups = $line->pickups->map(function ($pickup) use ($dayOfWeek, $directionId) {
            $studentsForDay = $pickup->students
                ->filter(function ($student) use ($dayOfWeek, $directionId) {
                    // Garder uniquement les étudiants correspondant au jour et à la direction
                    return $student->pivot->DayOfWeek == $dayOfWeek &&
                        $student->pivot->DirectionId == $directionId;
                })
                ->map(function ($student) use ($pickup) {
                    // Créer une collection de l'étudiant sans l'image
                    // Ajouter le nom de la classe et le nom du point de ramassage
                    return collect($student)
                        ->except(['Picture'])
                        ->put('Classe', isset($student->classe) ? $student->classe->Name : null)
                        ->put('PickupPoint', $pickup->Name);
                })
                ->values(); // réindexer le tableau des étudiants

            return [
                'PickupId' => $pickup->PickupId,
                'Name' => $pickup->Name,
                'Location' => $pickup->Location,
                'Arrival' => $directionId == 1 ? $pickup->ArrivalGo : $pickup->ArrivalReturn,
                'students' => $studentsForDay,
            ];
        });

        // Tous les étudiants uniques pour cette ligne, le jour et la direction
        $students = $pickups
            ->flatMap(function ($pickup) {
                return $pickup['students'];
            })
            ->unique('StudentId')
            ->values();

        // Créer une collection pour la ligne en excluant les pickups originaux
        $lineData = collect($line)->except(['pickups']);
        // Ajouter les points de ramassage filtrés
        $lineData = $lineData->put('pickups', $pickups)
            ->put('driverName', $line->driver ? $line->driver->Firstname . ' ' . $line->driver->Lastname : null) // nom du chauffeur
            ->put('assistantName', $line->assistant ? $line->assistant->Firstname . ' ' . $line->assistant->Lastname : null); // nom du chauffeur

        // Préparer les données finales à retourner
        $data = [
            'line' => $lineData,
            'students' => $students,
            'nbStudents' => $students->count(),
        ];

        // Retourner la réponse au format JSON avec succès
        return $this->successRes($data);
    }
    public function updateTeam(Request $request)
    {
        $lineId = $request->input('LineId');
        if(!$lineId) return $this->errorRes("Veuillez insérer un bus", 404);
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
