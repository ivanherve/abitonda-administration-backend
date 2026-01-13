<?php

namespace App\Http\Controllers;

use App\Models\BusLine;
use App\Models\PickupPoint;
use App\Models\Student;
use App\Models\StudentPickup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusController extends Controller
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
    public function index()
    {
        $lines = BusLine::with([
            'pickups.students:StudentId,Firstname,Lastname,ClasseId',
            'driver:EmployeeId,Firstname,Lastname',
            'assistant:EmployeeId,Firstname,Lastname',
            'pickups:PickupId,LineId,Name,Latitude,Longitude', // uniquement les colonnes nécessaires
            //'maxPlaces'
        ])->get();

        $lines = $lines->map(function ($line) {
            $students = $line->pickups
                ->flatMap(function ($pickup) {
                    return $pickup->students;
                })
                ->where('pivot.Registered', 1) // Filtrer les étudiants inscrits
                ->unique('StudentId');

            return [
                'LineId' => $line->LineId,
                'Name' => $line->Name,
                'nbStudents' => $students->count(),
                'driverName' => $line->driver ? $line->driver->Firstname . ' ' . $line->driver->Lastname : null,
                'assistantName' => $line->assistant ? $line->assistant->Firstname . ' ' . $line->assistant->Lastname : null,
                'maxPlaces' => $line->maxPlaces,
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
            'pickups.students.classe:ClasseId,Name',
            // 'maxPlaces'
        ])->find($id);

        if (!$line) {
            return $this->errorRes('Ligne non trouvée', 404);
        }

        $students = $line->pickups
            ->flatMap(function ($pickup) {
                return $pickup->students;
            })
            ->where('pivot.Registered', 1) // Filtrer les étudiants inscrits
            ->unique('StudentId')
            ->map(function ($student) {
                if ($student->Registered == 1) {
                return [
                    'StudentId' => $student->StudentId,
                    'Firstname' => $student->Firstname,
                    'Lastname' => $student->Lastname,
                    'Classe' => $student->classe->Name ?? null
                ];
                }
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
            'maxPlaces'
        ];

        return $this->successRes([
            'line' => $lineData,
            'students' => $students
        ]);
    }

    public function store(Request $request)
    {
        // return $this->debugRes($request->all());
        $name = $request->input('Name');
        if (empty($name)) {
            return $this->errorRes('Le nom est requis', 400);
        }
        $driverId = $request->input('DriverId');
        if (empty($driverId)) {
            return $this->errorRes('Le chauffeur est requis', 400);
        }
        $assistantId = $request->input('AssistantId');
        if (empty($assistantId)) {
            return $this->errorRes('L\'assistant est requis', 400);
        }
        $maxPlaces = $request->input('maxPlaces');
        if (empty($maxPlaces) || !is_numeric($maxPlaces) || $maxPlaces <= 0) {
            return $this->errorRes('Le nombre maximum de places doit être supérieur à 0', 400);
        }

        try {
            $line = BusLine::create([
                'Name' => $name,
                'DriverId' => $driverId,
                'AssistantId' => $assistantId
            ]);
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
        // return $this->debugRes([$request->all(), $line]);
        $name = $request->LineName;
        // On met à jour seulement si la valeur est envoyée
        if ($request->has('DriverId')) {
            $driverId = $request->input('DriverId');
        }

        if ($request->has('AssistantId')) {
            $assistantId = $request->input('AssistantId');
        }

        if ($request->has('maxPlaces')) {
            $maxPlaces = $request->input('maxPlaces');
            if (empty($maxPlaces) || !is_numeric($maxPlaces) || $maxPlaces <= 0) {
                return $this->errorRes('Le nombre maximum de places doit être supérieur à 0', 400);
            }
        }

        $data = ["Name" => $name];
        if (isset($driverId))
            $data['DriverId'] = $driverId;
        if (isset($assistantId))
            $data['AssistantId'] = $assistantId;
        if (isset($maxPlaces))
            $data['maxPlaces'] = $maxPlaces;

        $line->update($data);
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
            'pickups' => function ($q) use ($directionId) {
                $q->select('PickupId', 'LineId', 'Name', 'Latitude', 'Longitude', 'ArrivalGo', 'ArrivalReturn', 'ArrivalReturnHalfDay')
                    ->orderBy($directionId == 1 ? 'ArrivalGo' : 'ArrivalReturn', 'asc');
            }, // pickup_point
            'pickups.students:StudentId,Firstname,Lastname,ClasseId',             // students
            'pickups.students.classe:ClasseId,Name',                              // classe
            'driver:EmployeeId,Firstname,Lastname',                               // chauffeur
            'assistant:EmployeeId,Firstname,Lastname',                            // assistant
            // 'maxPlaces'
        ])->find($id);

        if (!$line) {
            return $this->errorRes('Ligne non trouvée', 404);
        }

        $pickups = $line->pickups->map(function ($pickup) use ($dayOfWeek, $directionId) {
            $studentsForDay = $pickup->students
                ->filter(function ($student) use ($dayOfWeek, $directionId) {
                    return 
                        $student->pivot->Registered == 1 &&
                        $student->pivot->DayOfWeek == $dayOfWeek &&
                        $student->pivot->DirectionId == $directionId;
                })
                // ->values();
                ->map(function ($student) use ($pickup, $directionId) { // <-- ajouter $pickup dans use
                    $daysOfWeek = DB::select("SELECT 
            S.StudentId,
            MAX(CASE WHEN T.DayOfWeek = 1 THEN T.PickupId END) AS Lundi,
            MAX(CASE WHEN T.DayOfWeek = 2 THEN T.PickupId END) AS Mardi,
            MAX(CASE WHEN T.DayOfWeek = 3 THEN T.PickupId END) AS Mercredi,
            MAX(CASE WHEN T.DayOfWeek = 4 THEN T.PickupId END) AS Jeudi,
            MAX(CASE WHEN T.DayOfWeek = 5 THEN T.PickupId END) AS Vendredi
        FROM students S
        LEFT JOIN student_pickup T ON S.StudentId = T.StudentId
        WHERE T.DayOfWeek is not null and T.DirectionId = $directionId and S.StudentId = $student->StudentId
        GROUP BY S.StudentId;");
                    return [
                        'StudentId' => $student->StudentId,
                        'Firstname' => $student->Firstname,
                        'Lastname' => $student->Lastname,
                        'Classe' => $student->classe->Name ?? null,
                        'PickupPoint' => $pickup->Name,
                        'DirectionId' => $directionId,
                        'DaysOfWeek' => $daysOfWeek[0] ?? (object) [
                            'Lundi' => null,
                            'Mardi' => null,
                            'Mercredi' => null,
                            'Jeudi' => null,
                            'Vendredi' => null
                        ]
                    ];
                })
                ->sortBy('Firstname')
                ->values();

            $data = [
                'PickupId' => $pickup->PickupId,
                'Name' => $pickup->Name,
                'Latitude' => $pickup->Latitude,
                'Longitude' => $pickup->Longitude,
                'students' => $studentsForDay,
                'nbStudents' => $studentsForDay->count()
            ];
            if($directionId == 1){
                $data['Arrival'] = $pickup->ArrivalGo;
            } elseif($directionId == 2){
                $data['Arrival'] = $pickup->ArrivalReturn;
            } else {
                $data['Arrival'] = $pickup->ArrivalReturnHalfDay;
            }
            
            return $data;
        });

        $students = $pickups->flatMap(function ($p) {
            return $p['students'];
        })->unique('StudentId')->values();

        $lineData = [
            'LineId' => $line->LineId,
            'Name' => $line->Name,
            'directionId' => $directionId,
            'pickups' => $pickups,
            'nbPickups' => $pickups->count(),
            'driverName' => $line->driver ? $line->driver->Firstname . ' ' . $line->driver->Lastname : null,
            'assistantName' => $line->assistant ? $line->assistant->Firstname . ' ' . $line->assistant->Lastname : null,
            'nbStudents' => $students->count(),
            'students' => $students,
            'maxPlaces' => $line->maxPlaces
        ];

        $lineData['pickups'] = $lineData['pickups']->filter(function ($pickup) {
            return $pickup['nbStudents'] > 0;
        })->values();

        return $this->successRes($lineData);
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
