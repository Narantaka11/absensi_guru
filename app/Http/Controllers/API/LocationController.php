<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * GET /api/v1/locations
     * Daftar semua lokasi sekolah aktif.
     * Flutter gunakan ini untuk menampilkan pin di peta dan memilih lokasi check-in.
     */
    public function index(): JsonResponse
    {
        $locations = Location::active()
            ->orderBy('name')
            ->get()
            ->map(fn(Location $loc) => $this->formatLocation($loc));

        return $this->success('Daftar lokasi sekolah berhasil diambil.', [
            'locations' => $locations,
        ]);
    }

    /**
     * GET /api/v1/locations/{id}
     * Detail satu lokasi — untuk menampilkan geofence di peta.
     */
    public function show(Location $location): JsonResponse
    {
        if (!$location->is_active) {
            return $this->error('Lokasi tidak tersedia.', null, 404);
        }

        return $this->success('Detail lokasi berhasil diambil.', [
            'location' => $this->formatLocation($location),
        ]);
    }

    /**
     * GET /api/v1/locations/nearest?latitude=...&longitude=...
     * Temukan lokasi terdekat dari koordinat Flutter (untuk auto-select geofence).
     */
    public function nearest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $lat = (float) $validated['latitude'];
        $lng = (float) $validated['longitude'];

        $locations = Location::active()->get();

        if ($locations->isEmpty()) {
            return $this->success('Tidak ada lokasi sekolah yang terdaftar.', [
                'location' => null,
            ]);
        }

        /** @var Location $nearest */
        $nearest  = $locations->sortBy(fn(Location $l) => $l->distanceTo($lat, $lng))->first();
        $distance = (int) round($nearest->distanceTo($lat, $lng));
        $isWithin = $nearest->isWithinRadius($lat, $lng);

        return $this->success('Lokasi terdekat ditemukan.', [
            'location'         => $this->formatLocation($nearest),
            'distance_meters'  => $distance,
            'is_within_radius' => $isWithin,
        ]);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function formatLocation(Location $location): array
    {
        return [
            'id'             => $location->id,
            'name'           => $location->name,
            'address'        => $location->address,
            'latitude'       => $location->latitude,
            'longitude'      => $location->longitude,
            'radius_meters'  => $location->radius_meters,
        ];
    }

    private function success(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'errors'  => null,
        ], $status);
    }

    private function error(string $message, mixed $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => [],
            'errors'  => $errors,
        ], $status);
    }
}
