<?php
/**
 * Activa el tema AirPatrol.
 *
 * Uso:
 *   php scripts/activate-airpatrol-theme.php            -> setea el tema global
 *   php scripts/activate-airpatrol-theme.php --users    -> además migra usuarios
 *                                                          con override 'light-blue'
 *
 * Solo migra usuarios cuyo override personal sea 'light-blue' (el default
 * antiguo); respeta a quien haya elegido deliberadamente otro tema.
 */

$base = dirname(__DIR__);
require $base.'/bootstrap/autoload.php';
$app = require_once $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'Tema global actual: '.var_export(settings('main_settings.template_color'), true).PHP_EOL;
settings('main_settings.template_color', 'airpatrol');
echo 'Tema global nuevo:  '.var_export(settings('main_settings.template_color'), true).PHP_EOL;

if (in_array('--users', $argv ?? [], true)) {
    $users = DB::table('users')->where('settings', 'like', '%template_color%')->get(['id', 'settings']);
    $changed = 0;

    foreach ($users as $u) {
        $s = json_decode($u->settings, true);

        if (($s['appearance']['template_color'] ?? null) === 'light-blue') {
            $s['appearance']['template_color'] = 'airpatrol';
            DB::table('users')->where('id', $u->id)->update(['settings' => json_encode($s)]);
            $changed++;
        }
    }

    echo "Usuarios migrados de light-blue a airpatrol: {$changed}".PHP_EOL;
}
