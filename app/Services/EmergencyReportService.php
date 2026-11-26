<?php

namespace App\Services;

use App\Models\Bus;
use App\Models\EmergencyReport;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * RF-19: crea reportes de emergencia y aplica el límite anti-spam acordado.
 */
class EmergencyReportService
{
    public const MAX_PER_HOUR = 5;

    public const DECAY_SECONDS = 3600;

    public static function throttleKey(User $user): string
    {
        return 'emergency-report:'.$user->id;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function report(User $reporter, array $data): EmergencyReport
    {
        $this->ensureWithinLimit($reporter);

        $bus = $this->resolveBus($reporter, $data);
        $latitude = $data['latitude'] ?? ($reporter->role === 'driver' ? $bus?->current_latitude : null);
        $longitude = $data['longitude'] ?? ($reporter->role === 'driver' ? $bus?->current_longitude : null);

        $emergency = EmergencyReport::create([
            'reporter_id' => $reporter->id,
            'bus_id' => $bus?->id,
            'type' => $data['type'],
            'description' => trim($data['description']),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'status' => 'pending',
        ]);

        if ($bus !== null) {
            $bus->update(['status' => 'emergency']);
        }

        return $emergency;
    }

    private function ensureWithinLimit(User $user): void
    {
        $key = self::throttleKey($user);

        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_HOUR)) {
            throw new TooManyRequestsHttpException(
                self::DECAY_SECONDS,
                'Has enviado demasiados reportes de emergencia. Intenta de nuevo en una hora.'
            );
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveBus(User $reporter, array $data): ?Bus
    {
        if (! empty($data['bus_id'])) {
            return Bus::find($data['bus_id']);
        }

        // El flujo heredado del conductor sigue usando su unidad asignada.
        return $reporter->role === 'driver' ? $reporter->drivingBus : null;
    }
}
