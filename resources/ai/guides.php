<?php

/*
|--------------------------------------------------------------------------
| Guias de uso de la plataforma para AirBot
|--------------------------------------------------------------------------
| Cada guia: titulo, palabras clave, pasos, errores comunes, pantalla que
| abre el boton "Ir a..." (clave de AbrirPantallaTool) y video opcional.
| Revisar y ajustar con soporte: son el manual que AirBot usa al responder.
*/

return [

    'ubicacion' => [
        'titulo'   => 'Ver la ubicacion de una unidad',
        'palabras' => 'ubicacion donde esta mapa localizar seguir vehiculo unidad',
        'pasos'    => [
            'Abre la pestana Vehiculos en el menu lateral.',
            'Busca la unidad por nombre o placa en el buscador de la lista.',
            'Haz clic en la unidad: el mapa se centra en ella y muestra su ultima posicion, velocidad y hora.',
            'Para seguirla en vivo, deja seleccionada la unidad; el mapa se actualiza solo.',
        ],
        'errores'  => [
            'Si la unidad aparece en gris o sin posicion, puede estar offline o vencida: preguntame "por que esta offline".',
        ],
        'pantalla' => 'unidades',
    ],

    'historial' => [
        'titulo'   => 'Ver el recorrido (historial) de una unidad',
        'palabras' => 'historial recorrido ruta trayecto donde estuvo reproducir replay',
        'pasos'    => [
            'Abre la pestana Historial en el menu lateral.',
            'Elige la unidad y el rango de fechas y horas.',
            'Pulsa Mostrar: veras el recorrido en el mapa con paradas y eventos.',
            'Usa el reproductor para ver el recorrido paso a paso y exportarlo si lo necesitas.',
        ],
        'errores'  => [
            'Si no aparece nada, revisa que el rango de fechas sea correcto y que la unidad haya reportado ese dia.',
        ],
        'pantalla' => 'historial',
    ],

    'reportes' => [
        'titulo'   => 'Generar un reporte',
        'palabras' => 'reporte informe exportar pdf excel xls generar descargar programar',
        'pasos'    => [
            'Abre Reportes desde el menu lateral o desde Herramientas > Reportes.',
            'Elige el tipo de reporte (por ejemplo Exceso de velocidad, Paradas, Informacion general).',
            'Selecciona las unidades y el periodo.',
            'Elige el formato: HTML para verlo en pantalla, PDF o XLS para descargarlo.',
            'Pulsa Generar. Para recibirlo por correo cada dia o semana, guardalo con envio programado.',
        ],
        'errores'  => [
            'Periodos muy largos con muchas unidades tardan mas: divide el periodo si es necesario.',
        ],
        'pantalla' => 'reportes',
    ],

    'alertas' => [
        'titulo'   => 'Crear o editar una alerta',
        'palabras' => 'alerta notificacion aviso avisar crear configurar exceso geocerca sos',
        'pasos'    => [
            'Abre Alertas desde el menu lateral o desde Herramientas > Alertas.',
            'Pulsa Anadir nuevo.',
            'Ponle un nombre y elige las unidades.',
            'Elige el tipo de alerta (exceso de velocidad, entrada/salida de geocerca, parada prolongada, encendido, etc.) y sus parametros.',
            'Elige como avisarte (notificacion, correo, SMS) y guarda.',
        ],
        'errores'  => [
            'Si no llegan avisos, revisa que la alerta este activa, que incluya la unidad y que tengas las notificaciones habilitadas.',
        ],
        'pantalla' => 'alertas',
    ],

    'eventos' => [
        'titulo'   => 'Consultar eventos y alertas recibidas',
        'palabras' => 'eventos alertas recibidas consultar ver lista historial de alertas',
        'pasos'    => [
            'Abre la pestana Eventos en el menu lateral.',
            'Veras la lista de eventos recientes con unidad, hora y descripcion.',
            'Haz clic en un evento para verlo en el mapa.',
            'Para un periodo o tipo especifico, genera el reporte de eventos o preguntame directamente.',
        ],
        'errores'  => [
            'Si no aparecen eventos, verifica que haya alertas activas configuradas para esas unidades.',
        ],
        'pantalla' => 'eventos',
    ],

    'geocercas' => [
        'titulo'   => 'Crear una geocerca',
        'palabras' => 'geocerca zona perimetro area crear dibujar poligono circulo',
        'pasos'    => [
            'Abre Geocercas desde el menu lateral o desde Herramientas > Geocercas.',
            'Pulsa Anadir nuevo.',
            'Dibuja la zona en el mapa (poligono o circulo) haciendo clic en los puntos del borde.',
            'Ponle nombre, grupo y color, y guarda.',
            'Para recibir avisos de entrada o salida, crea una alerta de geocerca.',
        ],
        'errores'  => [
            'Si la geocerca no detecta entradas, revisa que este asociada a una alerta y a las unidades correctas.',
        ],
        'pantalla' => 'geocercas',
    ],

    'rutas' => [
        'titulo'   => 'Crear una ruta',
        'palabras' => 'ruta camino trayecto planificado linea desvio',
        'pasos'    => [
            'Abre Herramientas > Rutas.',
            'Pulsa Anadir nuevo y dibuja la ruta en el mapa.',
            'Ponle nombre y guarda.',
            'Crea una alerta de desvio de ruta si quieres que te avise cuando una unidad salga de ella.',
        ],
        'errores'  => [],
        'pantalla' => 'rutas',
    ],

    'pois' => [
        'titulo'   => 'Crear puntos de interes (POI)',
        'palabras' => 'poi punto de interes marcador cliente lugar',
        'pasos'    => [
            'Abre Herramientas > POI.',
            'Pulsa Anadir nuevo y haz clic en el mapa donde va el punto.',
            'Ponle nombre, icono y guarda.',
        ],
        'errores'  => [],
        'pantalla' => 'pois',
    ],

    'comandos' => [
        'titulo'   => 'Enviar un comando a la unidad (cortar motor, reiniciar...)',
        'palabras' => 'comando cortar motor apagar bloquear desbloquear reiniciar enviar gprs sms',
        'pasos'    => [
            'Abre Comando en el menu lateral o Herramientas > Enviar comando.',
            'Elige la unidad.',
            'Elige el comando (por ejemplo corte o restablecimiento de motor) y si se envia por GPRS o SMS.',
            'Pulsa Enviar y confirma. El resultado aparece en el registro de comandos.',
        ],
        'errores'  => [
            'Si el comando no llega, la unidad puede estar offline: se enviara cuando vuelva a conectarse (GPRS).',
            'Corta el motor solo con el vehiculo detenido o a baja velocidad.',
        ],
        'pantalla' => 'enviar_comando',
    ],

    'compartir' => [
        'titulo'   => 'Compartir la ubicacion de una unidad',
        'palabras' => 'compartir enlace link ubicacion en vivo cliente tercero',
        'pasos'    => [
            'Abre Compartir en el menu lateral.',
            'Crea un nuevo enlace, elige las unidades y por cuanto tiempo estara activo.',
            'Copia el enlace y envialo: quien lo abra vera la ubicacion sin necesitar usuario.',
        ],
        'errores'  => ['Desactiva el enlace cuando ya no lo necesites.'],
        'pantalla' => 'compartir',
    ],

    'medir' => [
        'titulo'   => 'Medir distancias o buscar una direccion en el mapa',
        'palabras' => 'regla medir distancia buscar direccion coordenadas punto',
        'pasos'    => [
            'Para medir: Herramientas > Regla y haz clic en los puntos del mapa.',
            'Para ubicar una direccion: Herramientas > Buscar Direccion.',
            'Para ubicar coordenadas: Herramientas > Mostrar punto.',
        ],
        'errores'  => [],
        'pantalla' => 'unidades',
    ],

    'mantenimiento' => [
        'titulo'   => 'Programar mantenimientos (servicios)',
        'palabras' => 'mantenimiento servicio cambio de aceite revision kilometraje recordatorio',
        'pasos'    => [
            'Abre Mantenimiento en el menu superior.',
            'Agrega un servicio a la unidad indicando cada cuantos km, horas de motor o dias se repite.',
            'La plataforma te avisara cuando el servicio este proximo o vencido.',
        ],
        'errores'  => [],
        'pantalla' => null,
    ],

    'cuenta' => [
        'titulo'   => 'Cambiar contrasena, idioma o unidades de medida',
        'palabras' => 'contrasena password idioma zona horaria unidades km millas cuenta perfil configuracion',
        'pasos'    => [
            'Abre el menu de usuario arriba a la derecha.',
            'Mi cuenta > Cambiar contrasena para cambiar la contrasena.',
            'En Configuracion puedes cambiar idioma, zona horaria y unidades (km o millas).',
        ],
        'errores'  => [],
        'pantalla' => null,
    ],

    'offline' => [
        'titulo'   => 'Mi unidad aparece offline o no reporta',
        'palabras' => 'offline no reporta sin senal desconectado no aparece no actualiza gris',
        'pasos'    => [
            'Revisa si la unidad esta vencida o desactivada (aparece en la lista de Vehiculos).',
            'Verifica que el vehiculo tenga energia y que el chip tenga saldo y senal.',
            'Si sigue sin reportar, contacta a soporte con el nombre o IMEI de la unidad.',
        ],
        'errores'  => ['Puedo diagnosticarla por ti: dime el nombre o la placa de la unidad.'],
        'pantalla' => 'unidades',
    ],

    'airbot' => [
        'titulo'   => 'Que puede hacer AirBot',
        'palabras' => 'ayuda que puedes hacer funciones asistente airbot ejemplos',
        'pasos'    => [
            'Responder sobre tu flota: excesos de velocidad, paradas, km, ralenti, eventos y alertas en un periodo.',
            'Decirte donde esta una unidad, como esta tu flota ahora y por que una unidad esta offline.',
            'Explicarte como usar cada parte de la plataforma y llevarte a la pantalla correcta.',
            'Ejemplos: "Excesos de 80 km/h de la semana pasada", "Que unidad paro mas ayer", "Donde esta ABC-123".',
        ],
        'errores'  => [],
        'pantalla' => null,
    ],
];
