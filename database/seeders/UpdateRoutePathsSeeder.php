<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Route;

class UpdateRoutePathsSeeder extends Seeder
{
    /**
     * Verifica que las rutas tengan un trazado real.
     *
     * Antes este seeder escribía coordenadas aproximadas a mano. Esas líneas
     * no seguían las calles y podían cruzar edificios, así que ahora sólo
     * avisa cuando falta el trazado.
     */
    public function run(): void
    {
        $missing = Route::all()
            ->filter(function (Route $route) {
                $path = is_array($route->path_coordinates) ? $route->path_coordinates : [];
                return count($path) < 2;
            });

        if ($missing->isEmpty()) {
            $this->command->info('✓ Todas las rutas tienen trazado guardado.');
            return;
        }

        $this->command->warn('Las siguientes rutas no tienen trazado sobre calles:');
        foreach ($missing as $route) {
            $this->command->line("  • {$route->code} - {$route->name}");
        }

        $this->command->info('');
        $this->command->info('Genera los trazados reales con:');
        $this->command->line('  php artisan routes:generate-paths --force');
    }
}
