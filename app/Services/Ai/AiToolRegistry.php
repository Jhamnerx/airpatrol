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
        Tools\BuscarGuiaTool::class,
        Tools\AbrirPantallaTool::class,
    ];

    /** @var AbstractTool[] */
    private $tools = [];

    public function __construct(User $user)
    {
        foreach (self::TOOLS as $class) {
            /** @var AbstractTool $tool */
            $tool = new $class();
            $tool->setUser($user);
            $this->tools[$tool->name()] = $tool;
        }
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
