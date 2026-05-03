<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega la columna `jimi_type` a la tabla `devices` para identificar
 * qué dispositivos se gestionan mediante la API de Jimi IoT / Tracksolid.
 *
 * Valores:
 *   null    → No es un dispositivo Jimi (comportamiento actual)
 *   'gps'   → GPS básico gestionado por Jimi
 *   'video' → GPS con capacidades de video/media (cámara embebida)
 */
class AddJimiTypeToDevices extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('jimi_type', 20)
                ->nullable()
                ->default(null)
                ->after('authentication')
                ->index()
                ->comment('null=no jimi, gps=GPS básico, video=GPS con video');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex(['jimi_type']);
            $table->dropColumn('jimi_type');
        });
    }
}
