<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Canales de broadcasting
|--------------------------------------------------------------------------
|
| Canal privado "buses": cualquier usuario autenticado (pasajero, conductor
| o administrador) puede recibir las posiciones de la flota, igual que con
| los endpoints JSON /api/*.
|
*/

Broadcast::channel('buses', function ($user) {
    return $user !== null;
});
