<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configuración por vehículo del color de la polilínea del historial
 * ("Colores del recorrido", estilo Wialon).
 *
 * - route_color_type: modo activo (trips | single | speed | sensor | schedule)
 * - route_color: hex del modo "único"
 * - route_speed_ranges: JSON [{"from":0,"to":40,"color":"#22d3ee"},...] (último to=null)
 * - route_sensor_id: reservado para el futuro modo sensor (sin lógica aún)
 * - route_schedule: JSON {"day_from":"06:00","day_to":"18:00","day_color":"#2563eb","night_color":"#000000"}
 */
class AddRouteColorToDevices extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('devices', 'route_color_type')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            $table->string('route_color_type', 10)
                ->default('trips')
                ->after('jimi_model')
                ->comment('Modo de color de la ruta del historial: trips|single|speed|sensor|schedule');
            $table->string('route_color', 10)
                ->nullable()
                ->default(null)
                ->after('route_color_type')
                ->comment('Color hex del modo single');
            $table->text('route_speed_ranges')
                ->nullable()
                ->after('route_color')
                ->comment('JSON de rangos de velocidad [{from,to,color},...]');
            $table->integer('route_sensor_id')
                ->nullable()
                ->default(null)
                ->after('route_speed_ranges')
                ->comment('Reservado: sensor con rangos de colores (modo sensor)');
            $table->text('route_schedule')
                ->nullable()
                ->after('route_sensor_id')
                ->comment('JSON horario {day_from,day_to,day_color,night_color}');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('devices', 'route_color_type')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            $columns = [];
            foreach (['route_color_type', 'route_color', 'route_speed_ranges', 'route_sensor_id', 'route_schedule'] as $column) {
                if (Schema::hasColumn('devices', $column)) {
                    $columns[] = $column;
                }
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
}
