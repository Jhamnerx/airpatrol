@extends('Frontend.Layouts.modal')

@section('modal_class', 'modal-lg')

@section('title')
    <i class="icon history" style="margin-right:6px"></i>
    {{ $device->name }} &mdash; Video histórico
@endsection

@section('body')
    <div id="tc-history-app">

        {{-- Controles --}}
        <div class="row" style="margin-bottom:12px;">
            <div class="col-sm-3">
                <label>Fecha</label>
                <input type="date" id="tc-date" class="form-control" value="{{ $date }}" max="{{ date('Y-m-d') }}">
            </div>
            <div class="col-sm-3">
                <label>Cámara</label>
                <select id="tc-channel" class="form-control">
                    @if ($playback)
                        @for ($i = 1; $i <= 4; $i++)
                            <option value="{{ $i }}" {{ $camera == $i ? 'selected' : '' }}>CH{{ $i }}</option>
                        @endfor
                    @else
                        <option value="1" {{ $camera == 1 ? 'selected' : '' }}>Frontal</option>
                        <option value="2" {{ $camera == 2 ? 'selected' : '' }}>Cabina</option>
                    @endif
                </select>
            </div>

            @if ($playback)
                <div class="col-sm-3">
                    <button id="tc-search-btn" class="btn btn-primary" style="margin-top:20px; width:100%;">
                        <i class="icon search"></i> Buscar
                    </button>
                </div>
                <div class="col-sm-3">
                    <button id="tc-stop-btn" class="btn btn-default" style="margin-top:20px; width:100%; display:none;">
                        <i class="icon stop"></i> Detener
                    </button>
                </div>
            @else
                <div class="col-sm-2">
                    <label>Hora</label>
                    <input type="time" id="tc-time" class="form-control" value="12:00" step="1">
                </div>
                <div class="col-sm-2">
                    <label>Duración</label>
                    <select id="tc-seconds" class="form-control">
                        <option value="15">15 s</option>
                        <option value="30">30 s</option>
                        <option value="60">60 s</option>
                    </select>
                </div>
                <div class="col-sm-2">
                    <button id="tc-clip-btn" class="btn btn-primary" style="margin-top:20px; width:100%;">
                        <i class="icon upload"></i> Pedir
                    </button>
                </div>
            @endif
        </div>

        @unless ($playback)
            <div class="alert alert-warning" style="padding:8px 12px;">
                Este modelo no permite reproducir desde la memoria del equipo. Se le pide un clip,
                el equipo lo sube al servidor y aparece abajo en unos minutos.
            </div>
        @endunless

        {{-- Status --}}
        <div id="tc-status" style="display:none; margin-bottom:10px;">
            <div class="alert alert-info" style="margin:0; padding:8px 12px;">
                <span id="tc-status-text">Consultando...</span>
            </div>
        </div>

        {{-- Player (solo JT1078) --}}
        @if ($playback)
            <div id="tc-player-wrapper" style="background:#000; display:none; border-radius:4px; overflow:hidden;">
                <video id="tc-video" controls playsinline
                    style="width:100%; max-height:390px; display:block; background:#000;"></video>
            </div>

            {{-- Lista de grabaciones en la SD --}}
            <div id="tc-list-wrapper" style="margin-top:12px; display:none;">
                <label>Grabaciones en la memoria del equipo</label>
                <div style="max-height:220px; overflow-y:auto; border:1px solid #ddd; border-radius:4px;">
                    <table class="table table-condensed table-hover" style="margin:0;">
                        <thead>
                            <tr>
                                <th>Cámara</th><th>Inicio</th><th>Fin</th><th>Tamaño</th><th style="width:110px;"></th>
                            </tr>
                        </thead>
                        <tbody id="tc-list-body"></tbody>
                    </table>
                </div>
                <p class="text-muted" style="margin-top:6px; font-size:12px;">
                    El video se transmite desde la memoria del equipo y no se almacena en el servidor.
                </p>
            </div>
        @endif

        {{-- Evidencias subidas (ambos modelos) --}}
        <div style="margin-top:12px;">
            <label>
                Archivos subidos por el equipo
                <button id="tc-media-refresh" class="btn btn-xs btn-default" style="margin-left:6px;">
                    <i class="icon refresh"></i> Actualizar
                </button>
            </label>
            <div id="tc-media-grid" style="display:flex; flex-wrap:wrap; gap:8px;"></div>
            <p id="tc-media-empty" class="text-muted" style="font-size:12px;">Sin archivos todavía.</p>
        </div>
    </div>
@endsection

@section('buttons')
    <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('global.cancel') }}</button>
@endsection

@section('scripts')
    @if ($playback)
        <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.17/dist/hls.min.js"></script>
    @endif
    <script>
        (function () {
            var PLAYBACK    = {{ $playback ? 'true' : 'false' }};
            var QUERY_URL   = '{{ route('traccar.history_query',   ['id' => $device->id]) }}';
            var LIST_URL    = '{{ route('traccar.history_list',    ['id' => $device->id]) }}';
            var PLAY_URL    = '{{ route('traccar.history_play',    ['id' => $device->id]) }}';
            var CONTROL_URL = '{{ route('traccar.history_control', ['id' => $device->id]) }}';
            var CLIP_URL    = '{{ route('traccar.history_clip',    ['id' => $device->id]) }}';
            var MEDIA_URL   = '{{ route('traccar.media',           ['id' => $device->id]) }}';
            var MEDIA_FILE  = '{{ route('traccar.media_file', ['id' => $device->id, 'filename' => '__FILE__']) }}';
            var CSRF        = '{{ csrf_token() }}';

            var POLL_INTERVAL = {{ (int) config('traccar.video.resource_poll_interval', 3) }} * 1000;
            var POLL_ATTEMPTS = {{ (int) config('traccar.video.resource_poll_attempts', 15) }};

            var ACTION_STOP = 2;

            var status  = document.getElementById('tc-status');
            var text    = document.getElementById('tc-status-text');
            var hls     = null;
            var polling = null;
            var playing = false;

            function setStatus(message, level) {
                if (!message) { status.style.display = 'none'; return; }
                status.style.display = '';
                status.firstElementChild.className = 'alert alert-' + (level || 'info');
                status.firstElementChild.style.cssText = 'margin:0; padding:8px 12px;';
                text.textContent = message;
            }

            function channel() {
                return parseInt($('#tc-channel').val(), 10) || 1;
            }

            function errorOf(xhr, fallback) {
                return (xhr.responseJSON || {}).error || fallback;
            }

            function formatSize(bytes) {
                if (!bytes) return '-';
                var mb = bytes / 1048576;
                return mb >= 1024 ? (mb / 1024).toFixed(1) + ' GB' : mb.toFixed(1) + ' MB';
            }

            function formatTime(iso) {
                if (!iso) return '-';
                var d = new Date(iso);
                return isNaN(d) ? iso : d.toLocaleString();
            }

            // ---------------------------------------------------------------
            // Evidencias subidas: común a ambos modelos
            // ---------------------------------------------------------------

            function loadMedia() {
                $.post(MEDIA_URL, { _token: CSRF, from: $('#tc-date').val() + ' 00:00:00' })
                    .done(function (data) {
                        var grid = $('#tc-media-grid').empty();
                        var items = data.media || [];

                        $('#tc-media-empty').toggle(items.length === 0);

                        items.forEach(function (item) {
                            var url = MEDIA_FILE.replace('__FILE__', encodeURIComponent(item.file));
                            var card = $('<div>').css({
                                width: '150px', border: '1px solid #ddd', borderRadius: '4px',
                                overflow: 'hidden', background: '#fafafa'
                            });

                            if (item.type === 'video') {
                                $('<video controls preload="metadata">')
                                    .attr('src', url)
                                    .css({ width: '100%', height: '90px', background: '#000' })
                                    .appendTo(card);
                            } else {
                                $('<a target="_blank">').attr('href', url).append(
                                    $('<img>').attr('src', url).css({ width: '100%', height: '90px', objectFit: 'cover' })
                                ).appendTo(card);
                            }

                            $('<div>').css({ padding: '4px 6px', fontSize: '11px' })
                                .append($('<div>').text(formatTime(item.time)))
                                .append(item.alarm ? $('<div class="text-danger">').text(item.alarm) : null)
                                .appendTo(card);

                            grid.append(card);
                        });
                    })
                    .fail(function (xhr) {
                        setStatus(errorOf(xhr, 'No se pudieron cargar los archivos.'), 'danger');
                    });
            }

            $('#tc-media-refresh').on('click', loadMedia);

            // ---------------------------------------------------------------
            // Concox (JC261/JC400): pedir clip, el equipo lo sube
            // ---------------------------------------------------------------

            if (!PLAYBACK) {
                $('#tc-clip-btn').on('click', function () {
                    var at = $('#tc-date').val() + ' ' + ($('#tc-time').val() || '12:00:00');

                    setStatus('Pidiendo el clip al equipo...', 'info');

                    $.post(CLIP_URL, {
                        _token: CSRF,
                        channel: channel(),
                        at: at,
                        seconds: $('#tc-seconds').val()
                    })
                        .done(function () {
                            setStatus('Solicitado. El equipo lo subirá en unos minutos; usa Actualizar.', 'success');
                        })
                        .fail(function (xhr) {
                            setStatus(errorOf(xhr, 'No se pudo pedir el clip.'), 'danger');
                        });
                });

                loadMedia();
                return;
            }

            // ---------------------------------------------------------------
            // JT1078 (JC450): listar la SD y reproducir en streaming
            // ---------------------------------------------------------------

            var video = document.getElementById('tc-video');

            function stopPolling() {
                if (polling) { clearTimeout(polling); polling = null; }
            }

            function destroyPlayer() {
                if (hls) { hls.destroy(); hls = null; }
            }

            function stopPlayback() {
                stopPolling();
                destroyPlayer();

                if (playing) {
                    playing = false;
                    $.post(CONTROL_URL, { _token: CSRF, channel: channel(), action: ACTION_STOP });
                }

                $('#tc-player-wrapper').hide();
                $('#tc-stop-btn').hide();
            }

            function attach(url) {
                destroyPlayer();
                $('#tc-player-wrapper').show();
                $('#tc-stop-btn').show();

                var retries = 0;

                if (!window.Hls || !Hls.isSupported()) {
                    if (video.canPlayType('application/vnd.apple.mpegurl')) {
                        video.src = url;
                        video.play().catch(function () {});
                        setStatus(null);
                        return;
                    }
                    setStatus('Este navegador no puede reproducir HLS.', 'danger');
                    return;
                }

                hls = new Hls();
                hls.loadSource(url);
                hls.attachMedia(video);

                hls.on(Hls.Events.MANIFEST_PARSED, function () {
                    setStatus(null);
                    video.play().catch(function () {});
                });

                // La playlist arranca vacía hasta el primer keyframe del equipo.
                hls.on(Hls.Events.ERROR, function (_, data) {
                    if (!data.fatal) return;

                    if (retries++ < 10) {
                        setStatus('Esperando al equipo... (' + retries + '/10)', 'info');
                        setTimeout(function () {
                            if (!hls) return;
                            if (data.type === Hls.ErrorTypes.NETWORK_ERROR) {
                                hls.startLoad();
                            } else {
                                hls.recoverMediaError();
                            }
                        }, 2000);
                        return;
                    }

                    setStatus('No se pudo recibir la reproducción.', 'danger');
                    stopPlayback();
                });
            }

            function play(from, to) {
                setStatus('Solicitando reproducción al equipo...', 'info');

                $.post(PLAY_URL, { _token: CSRF, channel: channel(), from: from, to: to })
                    .done(function (data) {
                        if (!data.url) {
                            setStatus('El equipo no devolvió una URL de reproducción.', 'danger');
                            return;
                        }
                        playing = true;
                        attach(data.url);
                    })
                    .fail(function (xhr) {
                        setStatus(errorOf(xhr, 'Error al reproducir.'), 'danger');
                    });
            }

            function renderList(resources) {
                var body = $('#tc-list-body').empty();

                if (!resources.length) {
                    $('#tc-list-wrapper').hide();
                    setStatus('El equipo no reportó grabaciones en ese rango.', 'warning');
                    return;
                }

                resources.forEach(function (item) {
                    var button = $('<button class="btn btn-xs btn-primary">Reproducir</button>')
                        .on('click', function () { play(item.start, item.end); });

                    $('<tr>')
                        .append($('<td>').text('CH' + item.channel))
                        .append($('<td>').text(formatTime(item.start)))
                        .append($('<td>').text(formatTime(item.end)))
                        .append($('<td>').text(formatSize(item.size)))
                        .append($('<td>').append(button))
                        .appendTo(body);
                });

                $('#tc-list-wrapper').show();
                setStatus(null);
            }

            // El 0x1205 llega asíncrono, así que se sondea hasta que aparece.
            function poll(attempt) {
                polling = setTimeout(function () {
                    $.post(LIST_URL, { _token: CSRF })
                        .done(function (data) {
                            if (data.resources) { renderList(data.resources); return; }

                            if (attempt >= POLL_ATTEMPTS) {
                                setStatus('El equipo no respondió con la lista de grabaciones.', 'warning');
                                return;
                            }

                            setStatus('Esperando la lista del equipo... (' + attempt + '/' + POLL_ATTEMPTS + ')');
                            poll(attempt + 1);
                        })
                        .fail(function (xhr) {
                            setStatus(errorOf(xhr, 'Error consultando la lista.'), 'danger');
                        });
                }, POLL_INTERVAL);
            }

            $('#tc-search-btn').on('click', function () {
                stopPlayback();
                $('#tc-list-wrapper').hide();

                var date = $('#tc-date').val();
                if (!date) { setStatus('Selecciona una fecha.', 'warning'); return; }

                setStatus('Pidiendo al equipo la lista de grabaciones...', 'info');

                $.post(QUERY_URL, {
                    _token: CSRF,
                    channel: channel(),
                    date: date,
                    from: date + ' 00:00:00',
                    to: date + ' 23:59:59'
                })
                    .done(function () { poll(1); })
                    .fail(function (xhr) {
                        setStatus(errorOf(xhr, 'Error al consultar.'), 'danger');
                    });
            });

            $('#tc-stop-btn').on('click', stopPlayback);

            $(document).one('hidden.bs.modal', stopPlayback);
            $(window).one('beforeunload', stopPlayback);

            loadMedia();
        })();
    </script>
@endsection
