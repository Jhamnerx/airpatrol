@extends('Frontend.Layouts.modal')

@section('modal_class', 'modal-lg')

@section('title')
    <span class="icon play" style="margin-right:6px"></span>
    {{ $device->name }} &mdash; Video en vivo
@endsection

@section('body')
    <div id="tc-live-app">

        @if ($error)
            <div class="alert alert-danger">
                <strong>{{ trans('global.error') }}:</strong> {{ $error }}
            </div>
        @else
            <div class="row" style="margin-bottom:12px;">
                <div class="col-sm-4">
                    <label>Cámara</label>
                    <select id="tc-live-channel" class="form-control">
                        @for ($i = 1; $i <= 4; $i++)
                            <option value="{{ $i }}" {{ $camera == $i ? 'selected' : '' }}>CH{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-sm-3">
                    <button id="tc-live-reload" class="btn btn-primary" style="margin-top:20px; width:100%;">
                        <i class="icon refresh"></i> Cambiar
                    </button>
                </div>
            </div>

            <div id="tc-live-status" style="margin-bottom:10px;">
                <div class="alert alert-info" style="margin:0; padding:8px 12px;">
                    <span id="tc-live-status-text">Solicitando video al equipo...</span>
                </div>
            </div>

            <div style="background:#000; border-radius:4px; overflow:hidden;">
                <video id="tc-live-video" controls autoplay muted playsinline
                    style="width:100%; max-height:420px; display:block; background:#000;"></video>
            </div>

            <p class="text-muted" style="margin-top:8px; font-size:12px;">
                El equipo tarda unos segundos en empezar a transmitir. Sin audio: el decoder
                JT1078 todavía descarta la pista de sonido.
            </p>
        @endif
    </div>
@endsection

@section('buttons')
    <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('global.cancel') }}</button>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.17/dist/hls.min.js"></script>
    <script>
        (function () {
            var STREAM_URL = @json($streamUrl);
            var START_URL  = '{{ route('traccar.live_start', ['id' => $device->id]) }}';
            var STOP_URL   = '{{ route('traccar.live_stop', ['id' => $device->id]) }}';
            var CSRF       = '{{ csrf_token() }}';

            var video   = document.getElementById('tc-live-video');
            var status  = document.getElementById('tc-live-status');
            var text    = document.getElementById('tc-live-status-text');
            var hls     = null;
            var channel = {{ (int) $camera }};
            var retries = 0;

            if (!video || !STREAM_URL) {
                return;
            }

            function setStatus(message, level) {
                if (!message) {
                    status.style.display = 'none';
                    return;
                }
                status.style.display = '';
                status.firstElementChild.className = 'alert alert-' + (level || 'info');
                status.firstElementChild.style.cssText = 'margin:0; padding:8px 12px;';
                text.textContent = message;
            }

            function destroy() {
                if (hls) {
                    hls.destroy();
                    hls = null;
                }
            }

            // La playlist queda vacía hasta que llega el primer keyframe, así
            // que un error inicial es normal: se reintenta en vez de rendirse.
            function attach(url) {
                destroy();
                retries = 0;

                if (!window.Hls || !Hls.isSupported()) {
                    if (video.canPlayType('application/vnd.apple.mpegurl')) {
                        video.src = url;
                        setStatus(null);
                        return;
                    }
                    setStatus('Este navegador no puede reproducir HLS.', 'danger');
                    return;
                }

                hls = new Hls({ liveSyncDurationCount: 2, lowLatencyMode: true });
                hls.loadSource(url);
                hls.attachMedia(video);

                hls.on(Hls.Events.MANIFEST_PARSED, function () {
                    setStatus(null);
                    video.play().catch(function () {});
                });

                hls.on(Hls.Events.ERROR, function (_, data) {
                    if (!data.fatal) {
                        return;
                    }
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
                    setStatus('No se pudo recibir el video del equipo.', 'danger');
                    destroy();
                });
            }

            function stop() {
                destroy();
                $.post(STOP_URL, { _token: CSRF, channel: channel });
            }

            $('#tc-live-reload').on('click', function () {
                var next = parseInt($('#tc-live-channel').val(), 10) || 1;
                stop();
                channel = next;
                setStatus('Solicitando cámara CH' + channel + '...', 'info');

                $.post(START_URL, { _token: CSRF, channel: channel })
                    .done(function (data) {
                        if (data.url) {
                            attach(data.url);
                        } else {
                            setStatus('El servidor no devolvió una URL de video.', 'danger');
                        }
                    })
                    .fail(function (xhr) {
                        setStatus((xhr.responseJSON || {}).error || 'No se pudo iniciar la cámara.', 'danger');
                    });
            });

            attach(STREAM_URL);

            // Cortar la transmisión al cerrar el modal: si no, el equipo sigue
            // subiendo video y gastando datos.
            $(document).one('hidden.bs.modal', stop);
            $(window).one('beforeunload', stop);
        })();
    </script>
@endsection
