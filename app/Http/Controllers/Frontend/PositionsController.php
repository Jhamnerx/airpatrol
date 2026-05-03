<?php

namespace App\Http\Controllers\Frontend;

use App\Console\PositionsStack;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;

class PositionsController extends Controller
{

    /**
     * Inserta una posición GPS en la pila Redis para procesamiento asíncrono.
     *
     * Endpoint HTTP: POST|GET /api/insert_position  (sin autenticación, sin CSRF)
     * Controlador:   ApiController@PositionsController#insert
     *
     * PARÁMETROS REQUERIDOS (Request o llamada directa a PositionsStack):
     *   - uniqueId  (string)  IMEI del dispositivo GPS
     *   - fixTime   (string)  Fecha/hora de la posición, parseable por strtotime()  ej: "2026-04-17 14:00:00"
     *   - latitude  (float)   Latitud en grados decimales
     *   - longitude (float)   Longitud en grados decimales
     *   - speed     (float)   Velocidad en km/h
     *   - altitude  (float)   Altitud en metros
     *   - course    (float)   Rumbo/dirección en grados (0-360)
     *   - protocol  (string)  Protocolo del tracker, ej: "osmand", "h02", etc.
     *
     * PARÁMETROS OPCIONALES:
     *   - valid      (bool)   Indica si la posición es válida (señal GPS correcta)
     *   - attributes (array)  Atributos adicionales del dispositivo (ignition, batteryLevel, etc.)
     *
     * USO INTERNO DESDE UN JOB (evita el HTTP, escribe directo en Redis):
     *
     *   use App\Console\PositionsStack;
     *
     *   (new PositionsStack())->add([
     *       'imei'       => '868120145233604',
     *       'fixTime'    => strtotime('2026-04-17 14:00:00') * 1000,
     *       'valid'      => true,
     *       'latitude'   => 19.432608,
     *       'longitude'  => -99.133209,
     *       'speed'      => 0,
     *       'altitude'   => 2240,
     *       'course'     => 0,
     *       'protocol'   => 'osmand',
     *       'attributes' => ['ignition' => false, 'batteryLevel' => 85],
     *   ]);
     *
     * FLUJO INTERNO:
     *   insert() → PositionsStack::add() → Redis lPush("positions.{imei}", json)
     *   → PositionsWriter (daemon) consume la pila → guarda en traccar DB → dispara eventos
     *
     * RESPUESTAS HTTP (solo aplica si se llama vía HTTP):
     *   200  sin body  → posición encolada correctamente
     *   400  { status: 0, message: "Missing params: ..." } → falta algún campo requerido
     */
    public function insert()
    {
        $input = Request::all();

        $error = null;
        $required = ['uniqueId' => '', 'fixTime' => '', 'latitude' => '', 'longitude' => '', 'speed' => '', 'altitude' => '', 'course' => '', 'protocol' => ''];

        foreach ($required as $field => $value) {
            if (!isset($input[$field]))
                $error .= $field . ', ';
        }

        if (!is_null($error))
            return Response::make(json_encode(['status' => 0, 'message' => 'Missing params: ' . substr($error, 0, -2)]), 400);

        $data = [
            'fixTime'    => strtotime($input['date']) * 1000,
            'valid'      => $input['valid'],
            'imei'       => $input['uniqueId'],
            'latitude'   => $input['latitude'],
            'longitude'  => $input['longitude'],
            'attributes' => empty($input['attributes']) ? [] : $input['attributes'],
            'speed'      => $input['speed'],
            'altitude'   => $input['altitude'],
            'course'     => $input['course'],
            'protocol'   => $input['protocol'],
        ];

        (new PositionsStack())->add($data);
    }
}
