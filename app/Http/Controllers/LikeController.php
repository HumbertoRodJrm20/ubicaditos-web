<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Stop;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function toggleRoute(Request $request, Route $route)
    {
        return $this->toggle($request, $route);
    }

    public function toggleStop(Request $request, Stop $stop)
    {
        return $this->toggle($request, $stop);
    }

    /**
     * Alterna el like del usuario autenticado sobre una ruta o parada.
     */
    private function toggle(Request $request, Route|Stop $likeable)
    {
        $user = $request->user();

        $existing = $likeable->likes()
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            $likeable->likes()->create(['user_id' => $user->id]);
            $liked = true;
        }

        $count = $likeable->likes()->count();

        if ($request->expectsJson()) {
            return response()->json([
                'liked' => $liked,
                'count' => $count,
            ]);
        }

        return back()->with(
            'success',
            $liked ? 'Agregado a tus me gusta.' : 'Eliminado de tus me gusta.'
        );
    }
}
