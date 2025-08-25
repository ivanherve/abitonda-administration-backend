<?php

namespace App\Http\Controllers;

use App\Models\PickupPoint;
use Illuminate\Http\Request;

class PickupController extends Controller
{
    public function index(Request $request)
    {
        $directionId = $request->query('directionId', 1);
        $date = $request->query('date', date('Y-m-d'));
        $dayOfWeek = date('N', strtotime($date)); // 1=lundi ... 7=dimanche

        if ($directionId == 2) {
            $query = PickupPoint::with(['line', 'students'])->orderBy('ArrivalReturn');
        } else {
            $query = PickupPoint::with(['line', 'students'])->orderBy('ArrivalGo');
        }

        if ($request->has('lineId')) {
            $query->where('LineId', $request->query('lineId'));
        }

        $pickups = $query->orderBy('Name', 'asc')->get();

        $pickups = $pickups->map(function ($pickup) use ($dayOfWeek, $directionId) {
            // filtrer les élèves selon jour + direction
            $students = $pickup->students->filter(function ($student) use ($dayOfWeek, $directionId) {
                return $student->pivot->DayOfWeek == $dayOfWeek &&
                    $student->pivot->DirectionId == $directionId;
            });

            return collect($pickup)->put('nbStudents', $students->unique('StudentId')->count());
        });

        return $this->successRes($pickups);
    }

    public function show($id, Request $request)
    {
        $directionId = $request->query('directionId', 1);
        $date = $request->query('date', date('Y-m-d'));
        $dayOfWeek = date('N', strtotime($date));

        $pickups = PickupPoint::with(['line', 'students'])
            ->where('LineId', $id)
            ->orderBy('Name', 'asc')
            ->get();

        if ($pickups->isEmpty()) {
            return $this->errorRes('Aucun point de ramassage trouvé', 404);
        }

        $pickups = $pickups->map(function ($pickup) use ($dayOfWeek, $directionId) {
            $students = $pickup->students->filter(function ($student) use ($dayOfWeek, $directionId) {
                return $student->pivot->DayOfWeek == $dayOfWeek &&
                    $student->pivot->DirectionId == $directionId;
            });

            return collect($pickup)->put('nbStudents', $students->unique('StudentId')->count());
        });

        return $this->successRes($pickups);
    }

    public function store(Request $request)
    {
        try {
            $name = $request->input('Name');
            $latitude = $request->input('Latitude');
            $longitude = $request->input('Longitude');
            $lineId = $request->input('LineId');
            $arrivalGo = $request->input('ArrivalGo');
            $arrivalReturn = $request->input('ArrivalReturn');

            // Vérifier que les champs obligatoires sont présents
            if (!$name)
                return $this->errorRes("Attribut manquant: Name", 400);
            if (!$lineId)
                return $this->errorRes("Attribut manquant: LineId", 400);
            if (!$arrivalGo)
                return $this->errorRes("Attribut manquant: ArrivalGo", 400);
            if (!$arrivalReturn)
                return $this->errorRes("Attribut manquant: ArrivalReturn", 400);

            $data = [
                'Name' => $name,
                'Latitude' => $latitude,
                'Longitude' => $longitude,
                'LineId' => $lineId,
                'ArrivalGo' => $arrivalGo,
                'ArrivalReturn' => $arrivalReturn,
            ];

            // return $this->debugRes($data);

            $pickup = PickupPoint::create($data);

            return $this->successRes($pickup, 201);

        } catch (\Exception $e) {
            return $this->errorRes($e->getMessage(), 400);
        }
    }

    public function update(Request $request, $id)
    {
        $pickup = PickupPoint::find($id);
        if (!$pickup)
            return $this->errorRes('Point de ramassage non trouvé', 404);

        $pickup->update($request->all());
        return $this->successRes($pickup);
    }

    public function destroy($id)
    {
        $pickup = PickupPoint::find($id);
        if (!$pickup)
            return $this->errorRes('Point de ramassage non trouvé', 404);

        $pickup->delete();
        return $this->successRes('Point de ramassage supprimé', 204);
    }
}
