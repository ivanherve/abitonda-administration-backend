<?php

namespace App\Http\Controllers;

use App\Models\StudentPickup;
use Illuminate\Http\Request;

class StudentPickupController extends Controller
{
    public function index()
    {
        $data = StudentPickup::with(['student', 'pickupPoint', 'busLine', 'direction'])->get();
        return $this->successRes($data);
    }

    public function store(Request $request)
    {
        try {
            $pickup = StudentPickup::create($request->all());
            return $this->successRes($pickup, 201);
        } catch (\Exception $e) {
            return $this->errorRes($e->getMessage(), 400);
        }
    }

    public function update(Request $request, $studentId, $directionId, $dayOfWeek)
    {
        $pickup = StudentPickup::where('StudentId', $studentId)
            ->where('DirectionId', $directionId)
            ->where('DayOfWeek', $dayOfWeek)
            ->first();

        if (!$pickup) return $this->errorRes('Affectation non trouvée', 404);

        $pickup->update($request->all());
        return $this->successRes($pickup);
    }

    public function destroy($studentId, $directionId, $dayOfWeek)
    {
        $pickup = StudentPickup::where('StudentId', $studentId)
            ->where('DirectionId', $directionId)
            ->where('DayOfWeek', $dayOfWeek)
            ->first();

        if (!$pickup) return $this->errorRes('Affectation non trouvée', 404);

        $pickup->delete();
        return $this->successRes('Affectation supprimée', 204);
    }
}
