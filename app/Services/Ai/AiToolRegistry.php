<?php

namespace App\Services\Ai;

use App\Services\Ai\Tools;
use App\Services\Ai\Tools\AbstractTool;
use Tobuli\Entities\User;

class AiToolRegistry
{
    /** Orden fijo: cambiarlo invalida el cache de prompts. */
    const TOOLS = [
        Tools\BuscarUnidadesTool::class,
        Tools\EstadoUnidadesTool::class,
        Tools\UbicacionActualTool::class,
        Tools\DiagnosticoOfflineTool::class,
        Tools\ExcesosVelocidadTool::class,
        Tools\ParadasTool::class,
        Tools\RecorridosParadasTool::class,
        Tools\ResumenGeneralTool::class,
        Tools\OperacionDiariaTool::class,
        Tools\AlertasEventosTool::class,
        Tools\GeocercasTool::class,
        Tools\CombustibleTool::class,
        Tools\HorasMotorTool::class,
        Tools\OdometroTool::class,
        Tools\ConductoresTool::class,
        Tools\MantenimientoTool::class,
        Tools\VencimientosTool::class,
        Tools\CompararPeriodosTool::class,
        Tools\ExportarReporteTool::class,
        Tools\ExportarRecorridoTool::class,
        Tools\VerEnMapaTool::class,
        Tools\BuscarGuiaTool::class,
        Tools\AbrirPantallaTool::class,
    ];

    /**
     * Acciones que cambian datos: solo con el permiso ai_assistant (edit) y
     * siempre con confirmacion del usuario.
     */
    const ACTION_TOOLS = [
        Tools\Actions\ProgramarReporteTool::class,
        Tools\Actions\CrearAlertaTool::class,
        Tools\Actions\EnviarComandoTool::class,
        Tools\Actions\RenombrarUnidadTool::class,
        Tools\Actions\RenovarLicenciaTool::class,
    ];

    /** @var AbstractTool[] */
    private $tools = [];

    public function __construct(User $user)
    {
        $classes = self::TOOLS;

        if (self::canAct($user)) {
            $classes = array_merge($classes, self::ACTION_TOOLS);
        }

        foreach ($classes as $class) {
            /** @var AbstractTool $tool */
            $tool = new $class();
            $tool->setUser($user);
            $this->tools[$tool->name()] = $tool;
        }
    }

    public static function canAct(User $user): bool
    {
        return (bool) $user->perm('ai_assistant', 'edit');
    }

    public function definitions(): array
    {
        return array_values(array_map(function (AbstractTool $tool) {
            return $tool->definition();
        }, $this->tools));
    }

    public function get(string $name): ?AbstractTool
    {
        return $this->tools[$name] ?? null;
    }
}
