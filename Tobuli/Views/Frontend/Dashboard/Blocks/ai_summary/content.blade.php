<div class="airbot-dash" data-url="{{ $url }}">
    <p class="text-muted" style="margin:0">Preparando el resumen de tu flota...</p>
</div>
<script>
(function () {
    var box = document.querySelector('#block_ai_summary .airbot-dash');
    if (!box || box.getAttribute('data-loaded')) return;
    box.setAttribute('data-loaded', '1');

    function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function md(text) {
        var items = [], paras = [];
        text.split('\n').forEach(function (line) {
            var l = esc(line.trim()).replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
            if (!l) return;
            if (/^([-*•]|\d+[.)])\s+/.test(line.trim())) items.push('<li>' + l.replace(/^([-*•]|\d+[.)])\s+/, '') + '</li>');
            else paras.push('<p style="margin:0 0 6px">' + l + '</p>');
        });
        return paras.join('') + (items.length ? '<ul style="margin:0;padding-left:18px">' + items.join('') + '</ul>' : '');
    }

    fetch(box.getAttribute('data-url'), {credentials: 'same-origin', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}})
        .then(function (r) { return r.json().then(function (d) { return {ok: r.ok, d: d}; }); })
        .then(function (res) {
            if (!res.ok || !res.d.text) { box.innerHTML = '<p class="text-muted" style="margin:0">' + esc(res.d.message || 'Resumen no disponible por ahora.') + '</p>'; return; }
            box.innerHTML = md(res.d.text) + '<p class="text-muted" style="margin:6px 0 0;font-size:11px">Actualizado ' + esc(res.d.generated_at || '') + ' · AirBot</p>';
        })
        .catch(function () { box.innerHTML = '<p class="text-muted" style="margin:0">Resumen no disponible por ahora.</p>'; });
})();
</script>
