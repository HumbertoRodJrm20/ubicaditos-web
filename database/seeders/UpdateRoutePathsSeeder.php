<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Route;

class UpdateRoutePathsSeeder extends Seeder
{
    /**
     * Actualizar rutas existentes con path_coordinates detallados
     */
    public function run(): void
    {
        // Actualizar Ruta Centro - Monte Albán (OAX-106)
        $route106 = Route::where('code', 'OAX-106')->first();
        if ($route106) {
            $route106->path_coordinates = [
                ['lat' => 17.0654, 'lng' => -96.7256],
                ['lat' => 17.0648, 'lng' => -96.7268],
                ['lat' => 17.0642, 'lng' => -96.7276],
                ['lat' => 17.0638, 'lng' => -96.7254],
                ['lat' => 17.0625, 'lng' => -96.7289],
                ['lat' => 17.0610, 'lng' => -96.7320],
                ['lat' => 17.0590, 'lng' => -96.7380],
                ['lat' => 17.0560, 'lng' => -96.7450],
                ['lat' => 17.0523, 'lng' => -96.7589],
                ['lat' => 17.0540, 'lng' => -96.7620],
                ['lat' => 17.0580, 'lng' => -96.7650],
                ['lat' => 17.0620, 'lng' => -96.7680],
                ['lat' => 17.0678, 'lng' => -96.7712],
                ['lat' => 17.0650, 'lng' => -96.7690],
                ['lat' => 17.0580, 'lng' => -96.7670],
                ['lat' => 17.0520, 'lng' => -96.7665],
                ['lat' => 17.0480, 'lng' => -96.7670],
                ['lat' => 17.0432, 'lng' => -96.7678],
            ];
            $route106->save();
            $this->command->info('✓ Ruta OAX-106 actualizada');
        }

        // Actualizar Ruta Etla - Centro (OAX-107)
        $route107 = Route::where('code', 'OAX-107')->first();
        if ($route107) {
            $route107->path_coordinates = [
                ['lat' => 17.1989, 'lng' => -96.7845],
                ['lat' => 17.1920, 'lng' => -96.7820],
                ['lat' => 17.1850, 'lng' => -96.7800],
                ['lat' => 17.1780, 'lng' => -96.7780],
                ['lat' => 17.1700, 'lng' => -96.7760],
                ['lat' => 17.1567, 'lng' => -96.7734],
                ['lat' => 17.1450, 'lng' => -96.7710],
                ['lat' => 17.1350, 'lng' => -96.7680],
                ['lat' => 17.1234, 'lng' => -96.7623],
                ['lat' => 17.1120, 'lng' => -96.7580],
                ['lat' => 17.1000, 'lng' => -96.7520],
                ['lat' => 17.0856, 'lng' => -96.7445],
                ['lat' => 17.0800, 'lng' => -96.7420],
                ['lat' => 17.0729, 'lng' => -96.7398],
                ['lat' => 17.0690, 'lng' => -96.7340],
                ['lat' => 17.0670, 'lng' => -96.7290],
                ['lat' => 17.0654, 'lng' => -96.7256],
            ];
            $route107->save();
            $this->command->info('✓ Ruta OAX-107 actualizada');
        }

        // Actualizar Ruta Periférico Circuito (OAX-108)
        $route108 = Route::where('code', 'OAX-108')->first();
        if ($route108) {
            $route108->path_coordinates = [
                ['lat' => 17.0487, 'lng' => -96.7437],
                ['lat' => 17.0470, 'lng' => -96.7400],
                ['lat' => 17.0450, 'lng' => -96.7350],
                ['lat' => 17.0423, 'lng' => -96.7289],
                ['lat' => 17.0450, 'lng' => -96.7200],
                ['lat' => 17.0480, 'lng' => -96.7120],
                ['lat' => 17.0510, 'lng' => -96.7050],
                ['lat' => 17.0542, 'lng' => -96.7023],
                ['lat' => 17.0570, 'lng' => -96.6950],
                ['lat' => 17.0590, 'lng' => -96.6880],
                ['lat' => 17.0612, 'lng' => -96.6834],
                ['lat' => 17.0650, 'lng' => -96.6870],
                ['lat' => 17.0690, 'lng' => -96.6910],
                ['lat' => 17.0734, 'lng' => -96.6945],
                ['lat' => 17.0720, 'lng' => -96.7000],
                ['lat' => 17.0698, 'lng' => -96.7112],
                ['lat' => 17.0650, 'lng' => -96.7220],
                ['lat' => 17.0600, 'lng' => -96.7290],
                ['lat' => 17.0567, 'lng' => -96.7334],
                ['lat' => 17.0520, 'lng' => -96.7380],
                ['lat' => 17.0487, 'lng' => -96.7437],
            ];
            $route108->save();
            $this->command->info('✓ Ruta OAX-108 actualizada');
        }

        $this->command->info('');
        $this->command->info('✅ Todas las rutas antiguas han sido actualizadas con coordenadas detalladas');
        $this->command->info('Los autobuses ahora seguirán las calles correctamente');
    }
}
