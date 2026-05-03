@extends('Frontend.Layouts.modal')

@section('modal_class', 'modal-lg')

@section('title')
    <i class="icon history" style="margin-right:6px"></i>
    {{ $device->name }} &mdash; Video histórico
@endsection

@section('body')
    <div id="jimi-history-app">

        {{-- Controles --}}
        <div class="row" style="margin-bottom:12px;">
            <div class="col-sm-4">
                <label>Fecha</label>
                <input type="date" id="jimi-date" class="form-control" value="{{ $date }}" max="{{ date('Y-m-d') }}">
            </div>
            <div class="col-sm-3">
                <label>Canal</label>
                <select id="jimi-channel" class="form-control">
                    <option value="1" {{ $channel == 1 ? 'selected' : '' }}>CH1</option>
                    <option value="2" {{ $channel == 2 ? 'selected' : '' }}>CH2</option>
                    <option value="3" {{ $channel == 3 ? 'selected' : '' }}>CH3</option>
                    <option value="4" {{ $channel == 4 ? 'selected' : '' }}>CH4</option>
                </select>
            </div>
            <div class="col-sm-3">
                <button id="jimi-search-btn" class="btn btn-primary" style="margin-top:20px; width:100%;">
                    <i class="icon search"></i> Buscar
                </button>
            </div>
            <div class="col-sm-2">
                <button id="jimi-stop-btn" class="btn btn-default" style="margin-top:20px; width:100%; display:none;"
                    title="Detener">
                    <i class="icon stop"></i>
                </button>
            </div>
        </div>

        {{-- Status --}}
        <div id="jimi-status" style="display:none; margin-bottom:10px;">
            <div class="alert alert-info" style="margin:0; padding:8px 12px;">
                <i class="icon refresh jimi-spin"></i>
                <span id="jimi-status-text">Consultando...</span>
            </div>
        </div>

        {{-- Player --}}
        <div id="jimi-player-wrapper"
            style="background:#0d1117; position:relative; display:none; border-radius:4px; overflow:hidden;">
            <video id="jimi-video" controls style="width:100%; max-height:390px; display:block; background:#000;"></video>
            <div id="jimi-video-loading"
                style="display:none; position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.6); align-items:center; justify-content:center; color:#fff;">
                <div style="text-align:center;">
                    <i class="icon refresh jimi-spin" style="font-size:22px;"></i>
                    <div style="margin-top:6px; font-size:12px;">Cargando video...</div>
                </div>
            </div>
            <div id="jimi-segment-info"
                style="display:none; position:absolute; top:6px; left:8px; background:rgba(0,0,0,.6); color:#fff; font-size:11px; padding:2px 8px; border-radius:3px;">
            </div>
        </div>

        {{-- Timeline --}}
        <div id="jimi-timeline-wrapper" style="display:none; margin-top:10px;">
            <div style="display:flex; align-items:center; margin-bottom:3px;">
                <span style="font-size:11px; color:#888;">Grabaciones del día</span>
                <span id="jimi-playback-time" style="font-size:11px; color:#aaa; margin-left:auto;"></span>
            </div>
            <div id="jimi-hour-labels"
                style="position:relative; height:16px; margin-left:38px; margin-bottom:2px; font-size:10px; color:#888;">
            </div>
            <div id="jimi-channels-container" style="border:1px solid #dee2e6; border-radius:4px; overflow:hidden;"></div>
            <div style="font-size:10px; color:#aaa; margin-top:3px; text-align:right;">Haz clic en un segmento para
                reproducirlo</div>
        </div>

        {{-- Sin resultados --}}
        <div id="jimi-no-results" style="display:none;">
            <div class="alert alert-warning" style="margin-top:10px;">No se encontraron grabaciones para la fecha y canal
                seleccionados.</div>
        </div>

        {{-- Error --}}
        <div id="jimi-error" style="display:none;">
            <div class="alert alert-danger" style="margin-top:10px;"><strong>Error:</strong> <span
                    id="jimi-error-text"></span></div>
        </div>

    </div>

    <style>
        .jimi-spin {
            animation: jimi-rotation 1s linear infinite;
        }

        @keyframes jimi-rotation {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .jimi-ch-row {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
        }

        .jimi-ch-row:last-child {
            border-bottom: none;
        }

        .jimi-ch-label {
            width: 38px;
            font-size: 11px;
            font-weight: 700;
            color: #555;
            text-align: center;
            flex-shrink: 0;
            padding: 4px 0;
        }

        .jimi-ch-track {
            flex: 1;
            position: relative;
            height: 24px;
            background: #f3f4f6;
            cursor: default;
        }

        .jimi-seg {
            position: absolute;
            top: 3px;
            bottom: 3px;
            background: #3b82f6;
            border-radius: 2px;
            cursor: pointer;
            min-width: 2px;
            transition: opacity .15s;
        }

        .jimi-seg:hover {
            opacity: .75;
        }

        .jimi-seg.active {
            background: #16a34a;
        }

        .jimi-cursor {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #f59e0b;
            pointer-events: none;
            z-index: 5;
        }
    </style>
@endsection

@section('buttons')
    <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('global.cancel') }}</button>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flv.js@1.6.2/dist/flv.min.js"></script>
    <script>
        (function() {
            var CMD_URL = '{{ route('jimi.history_list_cmd', ['id' => $device->id]) }}';
            var LIST_URL = '{{ route('jimi.history_list', ['id' => $device->id]) }}';
            var STREAM_URL = '{{ route('jimi.history_stream_url', ['id' => $device->id]) }}';
            var CLOSE_URL = '{{ route('jimi.history_close', ['id' => $device->id]) }}';
            var CSRF = '{{ csrf_token() }}';
            var DAY_SECS = 86400;

            var flvPlayer = null;
            var currentAppId = null;
            var currentCh = null;
            var pollTimer = null;
            var pollCount = 0;
            var cursorTimer = null;
            var allSegments = {};

            /* ─── UI helpers ─── */
            function showStatus(msg) {
                $('#jimi-status-text').text(msg);
                $('#jimi-status').show();
            }

            function hideStatus() {
                $('#jimi-status').hide();
            }

            function showError(msg) {
                $('#jimi-error-text').text(msg);
                $('#jimi-error').show();
            }

            function hideError() {
                $('#jimi-error').hide();
            }

            function setSearching(on) {
                $('#jimi-search-btn').prop('disabled', on)
                    .html(on ? '<i class="icon refresh jimi-spin"></i> Buscando...' :
                        '<i class="icon search"></i> Buscar');
            }

            /* ─── Player ─── */
            function destroyPlayer() {
                clearInterval(cursorTimer);
                if (flvPlayer) {
                    try {
                        flvPlayer.pause();
                    } catch (e) {}
                    try {
                        flvPlayer.unload();
                    } catch (e) {}
                    try {
                        flvPlayer.detachMediaElement();
                    } catch (e) {}
                    try {
                        flvPlayer.destroy();
                    } catch (e) {}
                    flvPlayer = null;
                }
                $('#jimi-video-loading').hide();
            }

            function closeStream(appId, ch) {
                if (!appId) return;
                navigator.sendBeacon(CLOSE_URL, new URLSearchParams({
                    _token: CSRF,
                    channel: ch,
                    appId: appId
                }));
            }

            function playSegment(seg, ch, appId) {
                destroyPlayer();
                hideError();
                $('#jimi-segment-info').text(seg.beginTime + ' → ' + seg.endTime).show();
                $('#jimi-video-loading').css('display', 'flex').show();
                $('#jimi-player-wrapper').show();
                $('#jimi-stop-btn').show();
                $('.jimi-seg').removeClass('active');
                $('[data-begin="' + seg.beginTime + '"][data-ch="' + ch + '"]').addClass('active');

                $.post(STREAM_URL, {
                        _token: CSRF,
                        channel: ch,
                        appId: appId,
                        beginTime: seg.beginTime || '',
                        endTime: seg.endTime || '',
                        fileNameList: seg.fileName || '',
                    })
                    .done(function(r) {
                        if (r.error) {
                            $('#jimi-video-loading').hide();
                            showError(r.error);
                            return;
                        }
                        currentAppId = r.appId;
                        currentCh = ch;

                        if (!flvjs.isSupported()) {
                            $('#jimi-video-loading').hide();
                            showError('Su navegador no soporta FLV WebSocket. Use Chrome o Edge.');
                            return;
                        }

                        var vid = document.getElementById('jimi-video');
                        flvPlayer = flvjs.createPlayer({
                            type: 'flv',
                            url: r.url,
                            isLive: false
                        });
                        flvPlayer.attachMediaElement(vid);
                        flvPlayer.load();
                        flvPlayer.play();
                        flvPlayer.on(flvjs.Events.ERROR, function() {
                            $('#jimi-video-loading').hide();
                            showError('Error al reproducir el stream.');
                        });
                        flvPlayer.on(flvjs.Events.MEDIA_INFO, function() {
                            $('#jimi-video-loading').hide();
                        });

                        startCursor(seg, ch);
                    })
                    .fail(function(xhr) {
                        $('#jimi-video-loading').hide();
                        showError((xhr.responseJSON || {}).error || 'Error del servidor.');
                    });
            }

            /* ─── Cursor ─── */
            function timeToSecs(str) {
                var t = (str || '').split(' ');
                var p = (t.length > 1 ? t[1] : t[0]).split(':');
                return +p[0] * 3600 + +p[1] * 60 + +(p[2] || 0);
            }

            function secToHMS(s) {
                s = Math.floor(s);
                var h = Math.floor(s / 3600),
                    m = Math.floor((s % 3600) / 60),
                    ss = s % 60;
                return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m + ':' + (ss < 10 ? '0' : '') + ss;
            }

            function startCursor(seg, ch) {
                var track = $('#track-ch-' + ch);
                if (!track.length) return;
                var cursor = track.find('.jimi-cursor');
                if (!cursor.length) {
                    cursor = $('<div class="jimi-cursor">');
                    track.append(cursor);
                }
                var startSec = timeToSecs(seg.beginTime),
                    endSec = timeToSecs(seg.endTime);

                clearInterval(cursorTimer);
                cursorTimer = setInterval(function() {
                    if (!flvPlayer) {
                        clearInterval(cursorTimer);
                        return;
                    }
                    var sec = Math.min(startSec + (flvPlayer.currentTime || 0), endSec);
                    cursor.css('left', (sec / DAY_SECS * 100).toFixed(3) + '%');
                    $('#jimi-playback-time').text(secToHMS(sec));
                }, 500);
            }

            /* ─── Timeline render ─── */
            function renderTimeline(segs) {
                var container = $('#jimi-channels-container').empty();

                /* Etiquetas de horas */
                var labelsDiv = $('#jimi-hour-labels').empty();
                for (var h = 0; h <= 24; h += 3) {
                    $('<span>').text(h < 10 ? '0' + h + ':00' : h + ':00').css({
                        position: 'absolute',
                        left: (h / 24 * 100).toFixed(2) + '%',
                        transform: 'translateX(-50%)'
                    }).appendTo(labelsDiv);
                }

                var channels = Object.keys(segs).sort(function(a, b) {
                    return +a - +b;
                });
                channels.forEach(function(ch) {
                    var row = $('<div class="jimi-ch-row">');
                    var label = $('<div class="jimi-ch-label">').text('CH' + ch);
                    var track = $('<div class="jimi-ch-track">').attr('id', 'track-ch-' + ch);

                    segs[ch].forEach(function(seg) {
                        var s = timeToSecs(seg.beginTime),
                            e = timeToSecs(seg.endTime);
                        var pctL = (s / DAY_SECS * 100).toFixed(3) + '%';
                        var pctW = (Math.max(e - s, 0) / DAY_SECS * 100).toFixed(3) + '%';
                        $('<div class="jimi-seg">')
                            .css({
                                left: pctL,
                                width: pctW
                            })
                            .attr({
                                'data-begin': seg.beginTime,
                                'data-end': seg.endTime,
                                'data-ch': ch,
                                title: seg.beginTime + ' → ' + seg.endTime
                            })
                            .on('click', (function(s2, ch2) {
                                return function() {
                                    playSegment(s2, +ch2, currentAppId);
                                };
                            })(seg, ch))
                            .appendTo(track);
                    });

                    row.append(label).append(track);
                    container.append(row);
                });

                $('#jimi-timeline-wrapper').show();
            }

            /* ─── Polling ─── */
            function startPolling(instructionId, appId) {
                pollCount = 0;
                clearInterval(pollTimer);
                pollTimer = setInterval(function() {
                    pollCount++;
                    showStatus('Esperando respuesta del dispositivo... (' + pollCount + '/20)');

                    $.post(LIST_URL, {
                            _token: CSRF,
                            instructionId: instructionId
                        })
                        .done(function(r) {
                            if (r.pending) {
                                if (pollCount >= 20) {
                                    clearInterval(pollTimer);
                                    setSearching(false);
                                    hideStatus();
                                    showError('El dispositivo no respondió a tiempo. Intenta de nuevo.');
                                }
                                return;
                            }
                            clearInterval(pollTimer);
                            setSearching(false);
                            hideStatus();
                            if (r.error) {
                                showError(r.error);
                                return;
                            }

                            allSegments = {};
                            (r.segments || []).forEach(function(seg) {
                                var ch = seg.channel || $('#jimi-channel').val();
                                if (!allSegments[ch]) allSegments[ch] = [];
                                allSegments[ch].push(seg);
                            });

                            if (!Object.keys(allSegments).length) {
                                $('#jimi-no-results').show();
                                return;
                            }
                            currentAppId = appId;
                            renderTimeline(allSegments);
                        })
                        .fail(function(xhr) {
                            clearInterval(pollTimer);
                            setSearching(false);
                            hideStatus();
                            showError((xhr.responseJSON || {}).error || 'Error del servidor.');
                        });
                }, 1000);
            }

            /* ─── Botón Buscar ─── */
            $('#jimi-search-btn').on('click', function() {
                var date = $('#jimi-date').val(),
                    ch = parseInt($('#jimi-channel').val());
                if (!date) {
                    showError('Selecciona una fecha.');
                    return;
                }

                hideError();
                destroyPlayer();
                clearInterval(pollTimer);
                $('#jimi-player-wrapper').hide();
                $('#jimi-timeline-wrapper').hide();
                $('#jimi-no-results').hide();
                $('#jimi-stop-btn').hide();
                allSegments = {};
                setSearching(true);
                showStatus('Enviando comando al dispositivo...');

                $.post(CMD_URL, {
                        _token: CSRF,
                        channel: ch,
                        date: date
                    })
                    .done(function(r) {
                        if (r.error) {
                            setSearching(false);
                            hideStatus();
                            showError(r.error);
                            return;
                        }
                        startPolling(r.instructionId, r.appId);
                    })
                    .fail(function(xhr) {
                        setSearching(false);
                        hideStatus();
                        showError((xhr.responseJSON || {}).error || 'Error del servidor.');
                    });
            });

            /* ─── Botón Detener ─── */
            $('#jimi-stop-btn').on('click', function() {
                if (currentAppId) closeStream(currentAppId, currentCh);
                destroyPlayer();
                $(this).hide();
                $('.jimi-seg').removeClass('active');
                $('#jimi-player-wrapper').hide();
                $('#jimi-segment-info').hide();
                $('#jimi-playback-time').text('');
            });

            /* ─── Cerrar modal ─── */
            $(document).off('hidden.bs.modal.jimiHistory').on('hidden.bs.modal.jimiHistory', function() {
                clearInterval(pollTimer);
                clearInterval(cursorTimer);
                if (currentAppId) closeStream(currentAppId, currentCh);
                destroyPlayer();
            });
        })();
    </script>
@endsection
