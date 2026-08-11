// Reconstruye public/assets/js/app.js replicando la tarea `gulp script-app` del
// gulpfile.js: concat puro (sin minificar) de la lista scripts.app (gulpfile.js
// líneas 74-118) con separador '\n' (default de gulp-concat).
//
// Necesario porque npm/gulp no funcionan en el entorno de desarrollo actual:
// node_modules no está instalado y node-sass@7 no compila con Node >= 18
// (el gulpfile carga node-sass al inicio incluso para tareas sin sass).
//
// Uso: node scripts/build-app-bundle.js
// Si se añade/quita un archivo del bundle en gulpfile.js, actualizar la lista aquí.
const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..');

const files = [
  'resources/assets/js/lib/moment.js',
  'resources/assets/js/lib/moment-timezone.js',
  'resources/assets/js/lib/es6-promise.min.js',

  'resources/assets/js/lib/leaflet/leaflet.1.0.3.js',
  'resources/assets/js/lib/leaflet/leaflet.polylineDecorator.js',
  'resources/assets/js/lib/leaflet/leaflet.markerCluster.js',
  'resources/assets/js/lib/leaflet/leaflet.draw.js',
  'resources/assets/js/lib/leaflet/leaflet.editable.js',
  'resources/assets/js/lib/leaflet/leaflet.ruler.js',
  'resources/assets/js/lib/leaflet/marker.rotate.js',
  'resources/assets/js/lib/leaflet/Leaflet.Marker.SlideTo.js',
  'resources/assets/js/lib/leaflet/leaflet.bing.min.js',
  'resources/assets/js/lib/leaflet/Leaflet.GoogleMutant.js',
  'resources/assets/js/lib/leaflet/Yandex.js',
  'resources/assets/js/lib/leaflet/leaflet.circle.topolygon-min.js',

  'resources/assets/js/controller/listview.js',
  'resources/assets/js/controller/historyGraph.js',
  'resources/assets/js/controller/historyPlayer.js',
  'resources/assets/js/controller/history.js',
  'resources/assets/js/controller/devices.js',
  'resources/assets/js/controller/pois.js',
  'resources/assets/js/controller/geofences.js',
  'resources/assets/js/controller/routes.js',
  'resources/assets/js/controller/alerts.js',
  'resources/assets/js/controller/events.js',
  'resources/assets/js/controller/app.js',
  'resources/assets/js/controller/notifications.js',
  'resources/assets/js/controller/commands.js',
  'resources/assets/js/controller/deviceMedia.js',

  'resources/assets/js/model/device.js',
  'resources/assets/js/model/alert.js',
  'resources/assets/js/model/poi.js',
  'resources/assets/js/model/geofence.js',
  'resources/assets/js/model/route.js',
  'resources/assets/js/model/event.js',
  'resources/assets/js/model/MapTiles.js',
  'resources/assets/js/lib/socket.io.js',

  'resources/assets/js/plugins/chat.js',
  'resources/assets/js/controller/dashboard.js',
];

const missing = files.filter((f) => !fs.existsSync(path.join(ROOT, f)));
if (missing.length) {
  console.error('MISSING FILES:\n' + missing.join('\n'));
  process.exit(1);
}

const out = files.map((f) => fs.readFileSync(path.join(ROOT, f), 'utf8')).join('\n');
fs.writeFileSync(path.join(ROOT, 'public/assets/js/app.js'), out);
console.log('files:', files.length, 'bytes:', Buffer.byteLength(out, 'utf8'));
