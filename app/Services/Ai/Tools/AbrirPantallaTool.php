<?php

namespace App\Services\Ai\Tools;

use App\Services\Ai\ToolException;

/**
 * No consulta datos: agrega un boton "Ir a ..." en la respuesta. El panel
 * solo ejecuta pantallas de una lista fija (no se envia JS desde el servidor).
 */
class AbrirPantallaTool extends AbstractTool
{
    /** clave => [etiqueta, permiso requerido] */
    const SCREENS = [
        'unidades'       => ['Vehiculos', null],
        'eventos'        => ['Eventos', 'events'],
        'historial'      => ['Historial', 'history'],
        'reportes'       => ['Reportes', 'reports'],
        'alertas'        => ['Alertas', 'alerts'],
        'geocercas'      => ['Geocercas', 'geofences'],
        'rutas'          => ['Rutas', 'routes'],
        'pois'           => ['POI', 'poi'],
        'enviar_comando' => ['Enviar comando', 'send_command'],
        'compartir'      => ['Compartir', 'sharing'],
    ];

    public function name(): string
    {
        return 'abrir_pantalla';
    }

    public function description(): string
    {
        return 'Agrega a tu respuesta un boton "Ir a <pantalla>" que el usuario puede pulsar. Usalo al explicar '
            . 'como hacer algo o cuando el usuario quiera ver el detalle. Pantallas: '
            . implode(', ', array_keys(self::SCREENS)) . '.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'pantalla' => ['type' => 'string', 'enum' => array_keys(self::SCREENS)],
            ],
            'required' => ['pantalla'],
            'additionalProperties' => false,
        ];
    }

    public function label(array $input): string
    {
        return '';
    }

    protected function run(array $input): array
    {
        [$label, $perm] = self::SCREENS[$input['pantalla']];

        if ($perm && !$this->hasPerm($perm)) {
            throw new ToolException("El usuario no tiene permiso para la pantalla $label.");
        }

        $this->actions[] = [
            'type'   => 'screen',
            'screen' => $input['pantalla'],
            'label'  => 'Ir a ' . $label,
        ];

        return ['boton_agregado' => 'Ir a ' . $label];
    }

    private function hasPerm(string $perm): bool
    {
        try {
            return (bool) $this->user->perm($perm, 'view');
        } catch (\Throwable $e) {
            return true;
        }
    }
}
