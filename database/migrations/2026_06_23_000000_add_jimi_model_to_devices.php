<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega la columna `jimi_model` a la tabla `devices` para almacenar el modelo
 * del equipo Jimi (mcType, ej: "JC400", "JC450", "JC261").
 *
 * Es necesario para saber el protocolo del DVR y traducir correctamente el
 * número de canal de cámara:
 *   - Concox (JC261, JC400, ...):       los canales empiezan en 0
 *   - JT808/1078 (JC371, JC181, JC182,
 *                 JC450, JC451, ...):   los canales empiezan en 1
 *
 * Se rellena de forma oportunista en jimi:sync-positions (la respuesta de
 * jimi.user.device.location.list incluye mcType para cada dispositivo).
 */
class AddJimiModelToDevices extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('jimi_model', 50)
                ->nullable()
                ->default(null)
                ->after('jimi_type')
                ->comment('Modelo Jimi (mcType): JC400, JC450, JC261, ... usado para mapear el canal de cámara');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('jimi_model');
        });
    }
}
