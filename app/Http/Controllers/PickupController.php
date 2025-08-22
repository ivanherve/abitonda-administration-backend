<?php

namespace App\Http\Controllers;

use App\Models\PickupPoint;
use Illuminate\Http\Request;

class PickupController extends Controller
{
    public function index()
    {
        $pickups = PickupPoint::with('line')->orderBy('Name')->get();
        return $this->successRes($pickups);
    }

    public function show($id)
    {
        $pickup = PickupPoint::with('line')->find($id);
        if (!$pickup) return $this->errorRes('Point de ramassage non trouvé', 404);
        return $this->successRes($pickup);
    }

    public function store(Request $request)
    {
        try {
            $pickup = PickupPoint::create($request->all());
            return $this->successRes($pickup, 201);
        } catch (\Exception $e) {
            return $this->errorRes($e->getMessage(), 400);
        }
    }

    public function update(Request $request, $id)
    {
        $pickup = PickupPoint::find($id);
        if (!$pickup) return $this->errorRes('Point de ramassage non trouvé', 404);

        $pickup->update($request->all());
        return $this->successRes($pickup);
    }

    public function destroy($id)
    {
        $pickup = PickupPoint::find($id);
        if (!$pickup) return $this->errorRes('Point de ramassage non trouvé', 404);

        $pickup->delete();
        return $this->successRes('Point de ramassage supprimé', 204);
    }
}
