<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\BusStopSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * HU-012: suscripciones in-app por bus/parada. No hay canal externo ni se
 * expone la identidad de otros pasajeros.
 */
class NotificationSubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $subscriptions = $request->user()
            ->busStopSubscriptions()
            ->with(['bus:id,license_plate', 'stop:id,name'])
            ->latest('id')
            ->get()
            ->map(fn (BusStopSubscription $subscription) => [
                'id' => $subscription->id,
                'bus_id' => $subscription->bus_id,
                'license_plate' => $subscription->bus?->license_plate,
                'stop_id' => $subscription->stop_id,
                'stop_name' => $subscription->stop?->name,
            ]);

        return response()->json(['subscriptions' => $subscriptions]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bus_id' => ['required', 'integer', Rule::exists('buses', 'id')],
            'stop_id' => ['required', 'integer', Rule::exists('stops', 'id')],
        ]);

        $subscription = $request->user()->busStopSubscriptions()->firstOrCreate([
            'bus_id' => $validated['bus_id'],
            'stop_id' => $validated['stop_id'],
        ]);

        return response()->json([
            'subscribed' => true,
            'id' => $subscription->id,
            'bus_id' => $subscription->bus_id,
            'stop_id' => $subscription->stop_id,
        ]);
    }

    public function unsubscribe(Request $request, int $subscription): JsonResponse
    {
        $existing = BusStopSubscription::find($subscription);

        if ($existing !== null) {
            abort_unless($existing->user_id === $request->user()->id, 403);
            $existing->delete();
        }

        // Borrar dos veces es inofensivo: el cliente puede reintentar sin errores.
        return response()->json(['subscribed' => false]);
    }
}
