<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlaneRequest;
use App\Models\Plane;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlaneController extends Controller
{
    public function index(): Response
    {
        $planes = Plane::orderBy('reg_number')
            ->get()
            ->map(fn (Plane $p) => $this->serialize($p));

        return Inertia::render('admin/flota/index', [
            'planes' => $planes,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/flota/novi');
    }

    public function store(StorePlaneRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['admin_id'] = $request->user()->id;
        $data['status'] ??= 'in_garage';
        $data['total_mileage'] ??= 0;

        Plane::create($data);

        return redirect()->route('admin.flota.index')
            ->with('success', 'Avion uspešno dodat u flotu.');
    }

    private function serialize(Plane $plane): array
    {
        return [
            'id' => $plane->id,
            'reg_number' => $plane->reg_number,
            'model' => $plane->model,
            'capacity' => $plane->capacity,
            'luxury_level' => $plane->luxury_level,
            'range_km' => $plane->range_km,
            'max_speed' => $plane->max_speed,
            'repair_service_interval' => $plane->repair_service_interval,
            'model_year' => $plane->model_year,
            'status' => $plane->status,
            'commissioned_at' => $plane->commissioned_at?->toDateString(),
            'total_mileage' => $plane->total_mileage,
        ];
    }
}
