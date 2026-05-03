<?php

namespace App\Jobs;

use App\Console\PositionsStack;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Tobuli\Services\Jimi\JimiException;
use Tobuli\Services\Jimi\JimiGpsService;

/**
 * Job para sincronizar la posición GPS de un dispositivo Jimi específico.
 *
 * Alternativa al comando SyncJimiPositions cuando se necesita procesar
 * un dispositivo individual de forma asíncrona (ej: ante un evento webhook
 * o una actualización manual desde el panel).
 *
 * ⚠ NO escribe directamente en DB. Usa PositionsStack::add() → Redis
 *   → PositionsWriter daemon → traccar DB → eventos del sistema.
 *
 * Uso:
 *   ProcessJimiPosition::dispatch('868120145233604');
 *
 *   // Con queue específica:
 *   ProcessJimiPosition::dispatch($imei)->onQueue('jimi');
 *
 * @see App\Console\Commands\SyncJimiPositions  Para sincronización masiva
 * @see App\Console\PositionsStack              Para inserción directa sin Job
 */
class ProcessJimiPosition implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Intentos máximos antes de marcar como fallido */
    public int $tries = 3;

    /** @var int Segundos de espera entre reintentos */
    public int $backoff = 30;

    protected string $imei;

    /**
     * @param string $imei  IMEI del dispositivo a sincronizar
     */
    public function __construct(string $imei)
    {
        $this->imei = $imei;
        $this->onQueue('jimi');
    }

    public function handle(JimiGpsService $gpsService): void
    {
        try {
            $locations = $gpsService->getLocationsByImeis([$this->imei]);
        } catch (JimiException $e) {
            Log::error('[Jimi] ProcessJimiPosition: error al obtener posición', [
                'imei'  => $this->imei,
                'error' => $e->getMessage(),
                'code'  => $e->getCode(),
            ]);

            throw $e; // Deja que el sistema de colas maneje los reintentos
        }

        if (empty($locations)) {
            Log::debug('[Jimi] ProcessJimiPosition: sin posiciones para IMEI', ['imei' => $this->imei]);
            return;
        }

        $stack = new PositionsStack();

        foreach ($locations as $location) {
            $data = $gpsService->mapToPositionData($location);

            if (empty($data['imei']) || $data['fixTime'] <= 0) {
                continue;
            }

            $stack->add($data);

            Log::debug('[Jimi] Posición encolada', [
                'imei'    => $data['imei'],
                'fixTime' => $data['fixTime'],
                'lat'     => $data['latitude'],
                'lng'     => $data['longitude'],
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[Jimi] ProcessJimiPosition: Job fallido definitivamente', [
            'imei'  => $this->imei,
            'error' => $exception->getMessage(),
        ]);
    }
}
