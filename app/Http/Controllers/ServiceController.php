<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Models\Plane;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function planeIndex(Plane $plane): Response
    {
        $services = $plane->services()
            ->orderByDesc('started')
            ->get()
            ->map(fn (Service $s) => $this->serialize($s));

        return Inertia::render('admin/flota/servisi/index', [
            'plane' => $this->serializePlane($plane),
            'services' => $services,
        ]);
    }

    public function planeCreate(Plane $plane): Response
    {
        return Inertia::render('admin/flota/servisi/novi', [
            'plane' => $this->serializePlane($plane),
        ]);
    }

    public function planeStore(StoreServiceRequest $request, Plane $plane): RedirectResponse
    {
        $data = $request->validated();
        $data['plane_id'] = $plane->id;
        $data['admin_id'] = $request->user()->id;

        Service::create($data);

        // When admin schedules a service, plane is taken off the line.
        // Only flip to in_service if we're not already finished (a service can be created retroactively as 'finished').
        if ($data['status'] !== 'finished' && $plane->status !== 'in_service') {
            $plane->update(['status' => 'in_service']);
        }

        return redirect()
            ->route('admin.flota.servisi.index', $plane)
            ->with('success', 'Servis uspešno zakazan.');
    }

    public function show(Service $service): Response
    {
        $service->load(['plane', 'admin.user']);

        return Inertia::render('admin/servisi/show', [
            'service' => array_merge($this->serialize($service), [
                'plane' => $this->serializePlane($service->plane),
                'admin_name' => $service->admin?->user?->name,
            ]),
        ]);
    }

    public function complete(Service $service): RedirectResponse
    {
        $service->update([
            'status' => 'finished',
            'ended' => $service->ended ?? now(),
        ]);

        // Return the plane to service-ready state if it was in_service for this service.
        if ($service->plane->status === 'in_service') {
            $service->plane->update(['status' => 'in_garage']);
        }

        return back()->with('success', 'Servis označen kao završen.');
    }

    private function serialize(Service $service): array
    {
        return [
            'id' => $service->id,
            'plane_id' => $service->plane_id,
            'started' => $service->started?->toIso8601String(),
            'ended' => $service->ended?->toIso8601String(),
            'status' => $service->status,
            'description' => $service->description,
            'price' => (float) $service->price,
            'service_center' => $service->service_center,
        ];
    }

    private function serializePlane(Plane $plane): array
    {
        return [
            'id' => $plane->id,
            'reg_number' => $plane->reg_number,
            'model' => $plane->model,
            'status' => $plane->status,
            'model_year' => $plane->model_year,
            'total_mileage' => $plane->total_mileage,
            'repair_service_interval' => $plane->repair_service_interval,
        ];
    }
}
