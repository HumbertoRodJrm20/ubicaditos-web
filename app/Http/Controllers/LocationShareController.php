<?php

namespace App\Http\Controllers;

use App\Models\LocationShare;
use Illuminate\Http\Request;

class LocationShareController extends Controller
{
    /**
     * Inicia (o renueva) la compartición de ubicación del pasajero.
     */
    public function start(Request $request)
    {
        $coordinates = $this->validateCoordinates($request);
        $share = LocationShare::startFor($request->user(), $coordinates);

        if ($request->expectsJson()) {
            return response()->json([
                'active' => true,
                'id' => $share->id,
                'token' => $share->token,
                'url' => $share->publicUrl(),
                'latitude' => (float) $share->latitude,
                'longitude' => (float) $share->longitude,
                'accuracy' => $share->accuracy,
                'expires_at' => $share->expires_at?->toIso8601String(),
            ]);
        }

        return back()->with('success', 'Tu ubicación se está compartiendo.');
    }

    /**
     * Actualiza la última posición del enlace activo.
     */
    public function update(Request $request, LocationShare $share)
    {
        $this->ensureOwner($share);

        $share->updatePosition($this->validateCoordinates($request));

        return response()->json([
            'active' => true,
            'token' => $share->token,
            'latitude' => (float) $share->fresh()->latitude,
            'longitude' => (float) $share->fresh()->longitude,
            'accuracy' => $share->fresh()->accuracy,
            'expires_at' => $share->expires_at?->toIso8601String(),
        ]);
    }

    /**
     * Detiene la compartición de inmediato.
     */
    public function stop(Request $request, LocationShare $share)
    {
        $this->ensureOwner($share);
        $share->revoke();

        if ($request->expectsJson()) {
            return response()->json([
                'active' => false,
                'token' => $share->token,
            ]);
        }

        return back()->with('success', 'Dejaste de compartir tu ubicación.');
    }

    /**
     * Devuelve el enlace activo del usuario (o null) para rehidratar la UI.
     */
    public function current(Request $request)
    {
        $share = $request->user()
            ->locationShares()
            ->active()
            ->latest('id')
            ->first();

        return response()->json([
            'active' => $share !== null,
            'share' => $share?->toPublicArray(),
        ]);
    }

    /**
     * Página pública del enlace compartido.
     */
    public function show(string $token)
    {
        $share = $this->findActiveShare($token);

        return view('share.show', [
            'token' => $token,
            'share' => $share,
        ]);
    }

    /**
     * Endpoint público que consulta la página cada pocos segundos.
     */
    public function data(string $token)
    {
        return response()->json($this->findActiveShare($token)->toPublicArray());
    }

    private function findActiveShare(string $token): LocationShare
    {
        $share = LocationShare::where('token', $token)->first();

        abort_unless($share !== null && $share->isActive(), 404);

        return $share;
    }

    private function ensureOwner(LocationShare $share): void
    {
        abort_unless($share->user_id === auth()->id(), 403);
    }

    /**
     * @return array{latitude: float, longitude: float, accuracy: int|null}
     */
    private function validateCoordinates(Request $request): array
    {
        return $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ], [
            'latitude.required' => 'Necesitamos tu latitud para compartir la ubicación.',
            'latitude.numeric' => 'La latitud debe ser un número válido.',
            'latitude.between' => 'La latitud está fuera de rango.',
            'longitude.required' => 'Necesitamos tu longitud para compartir la ubicación.',
            'longitude.numeric' => 'La longitud debe ser un número válido.',
            'longitude.between' => 'La longitud está fuera de rango.',
            'accuracy.integer' => 'La precisión debe ser un número entero de metros.',
        ]);
    }
}
