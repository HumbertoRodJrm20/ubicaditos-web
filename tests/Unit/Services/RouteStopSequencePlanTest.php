<?php

namespace Tests\Unit\Services;

use App\Services\RouteStopSyncService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Planificación de la secuencia de una ruta con paradas de alta frecuencia
 * "ancladas". Notación: M<id> = parada principal, F<id> = de alta frecuencia.
 */
class RouteStopSequencePlanTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: array<int, int>, 2: string}>
     */
    public static function plans(): array
    {
        return [
            'sin cambios' => ['M1 F10 M2 F20', [1, 2], 'M1 F10 M2 F20'],
            'reordenar: el bloque viaja con su ancla' => ['M1 F10 M2 F20 M3', [3, 1, 2], 'M3 M1 F10 M2 F20'],
            'insertar una principal va después del bloque de su predecesora' => ['M1 F10 M2', [1, 9, 2], 'M1 F10 M9 M2'],
            'quitar una principal reasigna sus secundarias a la anterior' => ['M1 F10 M2 F20 M3 F30', [1, 3], 'M1 F10 F20 M3 F30'],
            'quitar la primera principal deja sus secundarias al inicio' => ['F5 M1 F10 M2', [2], 'F5 F10 M2'],
            'promover una secundaria a principal' => ['M1 F10 M2', [1, 10, 2], 'M1 M10 M2'],
            'secundarias posteriores a todas las principales siguen a la última' => ['M1 M2 M3 F10 F11', [1, 2, 3, 4], 'M1 M2 M3 F10 F11 M4'],
            'sin principales' => ['M1 F10 M2 F20', [], 'F10 F20'],
        ];
    }

    #[DataProvider('plans')]
    public function test_plan_keeps_frequent_stops_anchored(string $current, array $mainIds, string $expected): void
    {
        $plan = (new RouteStopSyncService())->planSequence($this->rows($current), $mainIds);

        $this->assertSame($expected, $this->render($plan));
    }

    public function test_plan_rejects_duplicated_main_stops(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new RouteStopSyncService())->planSequence($this->rows('M1 M2'), [1, 1]);
    }

    /**
     * @return array<int, array{stop_id: int, is_frequent: bool}>
     */
    private function rows(string $notation): array
    {
        $rows = [];

        foreach (preg_split('/\s+/', trim($notation)) as $token) {
            if ($token === '') {
                continue;
            }

            $rows[] = ['stop_id' => (int) substr($token, 1), 'is_frequent' => $token[0] === 'F'];
        }

        return $rows;
    }

    /**
     * @param array<int, array{stop_id: int, is_frequent: bool}> $plan
     */
    private function render(array $plan): string
    {
        return implode(' ', array_map(
            fn (array $row) => ($row['is_frequent'] ? 'F' : 'M') . $row['stop_id'],
            $plan
        ));
    }
}
