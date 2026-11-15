/* AirBot - panel del asistente IA (sin dependencias de compilacion).
   Lee la respuesta del servidor como eventos SSE: conversation, delta,
   source, action, done, error. */
(function () {
  'use strict';

  var root = document.getElementById('airbot');
  var launcher = document.getElementById('airbot-launcher');
  if (!root || !launcher) return;

  var PERIOD_TAG = '{{elegir_periodo}}';

  var cfg = {
    chat: root.getAttribute('data-url-chat'),
    suggestions: root.getAttribute('data-url-suggestions'),
    conversations: root.getAttribute('data-url-conversations'),
    csrf: root.getAttribute('data-csrf'),
    i18n: {}
  };
  try { cfg.i18n = JSON.parse(root.getAttribute('data-i18n')) || {}; } catch (e) {}

  var el = {
    body: document.getElementById('airbot-body'),
    welcome: document.getElementById('airbot-welcome'),
    suggestions: document.getElementById('airbot-suggestions'),
    messages: document.getElementById('airbot-messages'),
    history: document.getElementById('airbot-history'),
    form: document.getElementById('airbot-form'),
    input: document.getElementById('airbot-input'),
    send: root.querySelector('.airbot-send'),
    quota: document.getElementById('airbot-quota')
  };

  var state = { conversationId: null, busy: false, loaded: false };

  /* ---------------- Abrir / cerrar ---------------- */

  function open() {
    root.hidden = false;
    document.body.classList.add('airbot-open');
    launcher.setAttribute('aria-expanded', 'true');
    if (!state.loaded) { state.loaded = true; loadSuggestions(); }
    setTimeout(function () { el.input.focus(); }, 50);
  }

  function close() {
    root.hidden = true;
    document.body.classList.remove('airbot-open');
    launcher.setAttribute('aria-expanded', 'false');
  }

  function toggle() { root.hidden ? open() : close(); }

  launcher.addEventListener('click', open);
  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && !e.shiftKey && !e.altKey && (e.key === 'b' || e.key === 'B')) {
      var t = e.target;
      var editable = t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName));
      if (editable && t !== el.input) return;
      e.preventDefault();
      toggle();
    } else if (e.key === 'Escape' && !root.hidden) {
      close();
    }
  });

  document.querySelectorAll('[data-airbot-open]').forEach(function (btn) {
    btn.addEventListener('click', function (e) { e.preventDefault(); open(); });
  });

  root.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-airbot]');
    if (!btn) return;
    var action = btn.getAttribute('data-airbot');
    if (action === 'close') close();
    if (action === 'new') newConversation();
    if (action === 'history') toggleHistory();
  });

  /* ---------------- Utilidades ---------------- */

  function t(key, repl) {
    var s = cfg.i18n[key] || key;
    if (repl) Object.keys(repl).forEach(function (k) { s = s.replace(':' + k, repl[k]); });
    return s;
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function node(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }

  function scrollDown() { el.body.scrollTop = el.body.scrollHeight; }

  function setQuota(remaining) {
    el.quota.textContent = (remaining === null || remaining === undefined) ? '' : t('quota_left', { count: remaining });
  }

  function request(method, url) {
    return fetch(url, {
      method: method,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': cfg.csrf, 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (data) {
        if (!r.ok) throw new Error(data.message || t('error'));
        return data;
      });
    });
  }

  /* ---------------- Markdown basico y seguro ---------------- */

  function inline(text) {
    var s = escapeHtml(text);
    s = s.replace(/`([^`]+)`/g, '<code>$1</code>');
    s = s.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    s = s.replace(/(^|[^*])\*([^*\n]+)\*/g, '$1<em>$2</em>');
    s = s.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>');
    s = s.replace(/(^|[\s(])(https?:\/\/[^\s<)]+)/g, '$1<a href="$2" target="_blank" rel="noopener noreferrer">$2</a>');
    return s;
  }

  function renderMarkdown(md) {
    var lines = md.replace(/\r/g, '').split('\n');
    var html = [];
    var i = 0;

    while (i < lines.length) {
      var line = lines[i];

      if (/^\s*\|.*\|\s*$/.test(line) && i + 1 < lines.length && /^\s*\|?\s*:?-{2,}/.test(lines[i + 1])) {
        var rows = [];
        while (i < lines.length && /^\s*\|.*\|\s*$/.test(lines[i])) { rows.push(lines[i]); i++; }
        var cells = function (r) { return r.trim().replace(/^\||\|$/g, '').split('|').map(function (c) { return c.trim(); }); };
        var head = cells(rows[0]);
        var out = '<div class="airbot-table-wrap"><table><thead><tr>' + head.map(function (c) { return '<th>' + inline(c) + '</th>'; }).join('') + '</tr></thead><tbody>';
        rows.slice(2).forEach(function (r) {
          out += '<tr>' + cells(r).map(function (c) { return '<td>' + inline(c) + '</td>'; }).join('') + '</tr>';
        });
        html.push(out + '</tbody></table></div>');
        continue;
      }

      if (/^\s*([-*•])\s+/.test(line)) {
        var items = [];
        while (i < lines.length && /^\s*([-*•])\s+/.test(lines[i])) { items.push(lines[i].replace(/^\s*[-*•]\s+/, '')); i++; }
        html.push('<ul>' + items.map(function (it) { return '<li>' + inline(it) + '</li>'; }).join('') + '</ul>');
        continue;
      }

      if (/^\s*\d+[.)]\s+/.test(line)) {
        var oitems = [];
        while (i < lines.length && /^\s*\d+[.)]\s+/.test(lines[i])) { oitems.push(lines[i].replace(/^\s*\d+[.)]\s+/, '')); i++; }
        html.push('<ol>' + oitems.map(function (it) { return '<li>' + inline(it) + '</li>'; }).join('') + '</ol>');
        continue;
      }

      if (/^\s*$/.test(line)) { i++; continue; }

      var para = [];
      while (i < lines.length && !/^\s*$/.test(lines[i]) && !/^\s*([-*•]|\d+[.)])\s+/.test(lines[i]) && !/^\s*\|.*\|\s*$/.test(lines[i])) {
        para.push(lines[i].replace(/^#+\s*/, ''));
        i++;
      }
      html.push('<p>' + para.map(inline).join('<br>') + '</p>');
    }

    return html.join('');
  }

  /* ---------------- Pantallas (lista fija) ---------------- */

  var SCREENS = {
    unidades: function () { clickFirst('a[href="#objects_tab"]'); },
    eventos: function () { clickFirst('a[href="#events_tab"]'); },
    historial: function () { clickFirst('a[href="#history_tab"]'); },
    reportes: function () { clickFirst('[data-modal="reports_create"]'); },
    alertas: function () { clickFirst('[data-modal="alerts"]'); },
    enviar_comando: function () { clickFirst('[data-modal="send_command"]'); },
    compartir: function () { clickFirst('[data-modal="sharing"]'); },
    geocercas: function () { appCall('geofences', 'geofencing_tab'); },
    rutas: function () { appCall('routes', 'routes_tab'); },
    pois: function () { appCall('pois', 'pois_tab'); }
  };

  function clickFirst(selector) {
    var target = document.querySelector(selector);
    if (!target) return;
    if (window.jQuery) window.jQuery(target).trigger('click'); else target.click();
  }

  function appCall(module, tab) {
    if (window.app && window.app[module] && typeof window.app[module].list === 'function') window.app[module].list();
    if (window.app && typeof window.app.openTab === 'function') window.app.openTab(tab);
  }

  function runAction(action) {
    if (action && action.type === 'screen' && SCREENS[action.screen]) {
      SCREENS[action.screen]();
      if (window.innerWidth < 768) close();
    }
  }

  /* ---------------- Burbujas ---------------- */

  function hideWelcome() { el.welcome.hidden = true; }

  function addUserBubble(text) {
    hideWelcome();
    el.messages.appendChild(node('div', 'airbot-msg airbot-msg-user', text));
    scrollDown();
  }

  function createBotBubble() {
    hideWelcome();
    var wrap = node('div', 'airbot-msg airbot-msg-bot');
    var sources = node('details', 'airbot-sources');
    sources.hidden = true;
    sources.appendChild(node('summary'));
    sources.appendChild(node('ul'));
    var text = node('div', 'airbot-text');
    var typing = node('div', 'airbot-typing');
    typing.innerHTML = '<span></span><span></span><span></span>';
    typing.setAttribute('aria-label', t('thinking'));
    var actions = node('div', 'airbot-actions');
    var periods = node('div', 'airbot-periods');
    wrap.appendChild(sources);
    wrap.appendChild(typing);
    wrap.appendChild(text);
    wrap.appendChild(actions);
    wrap.appendChild(periods);
    el.messages.appendChild(wrap);
    scrollDown();

    var bubble = {
      raw: '',
      wrap: wrap,
      setText: function (raw) {
        bubble.raw = raw;
        var hasPeriods = raw.indexOf(PERIOD_TAG) !== -1;
        text.innerHTML = renderMarkdown(raw.split(PERIOD_TAG).join('').trim());
        if (raw) typing.hidden = true;
        periods.innerHTML = '';
        if (hasPeriods) {
          (cfg.i18n.periods || []).forEach(function (label) {
            var chip = node('button', 'airbot-chip', label);
            chip.type = 'button';
            chip.addEventListener('click', function () { ask(label); });
            periods.appendChild(chip);
          });
        }
      },
      appendText: function (delta) {
        // Texto que llega despues de una consulta: parrafo nuevo.
        var sep = (bubble.breakNext && bubble.raw.trim()) ? '\n\n' : '';
        bubble.breakNext = false;
        bubble.setText(bubble.raw.replace(/\s+$/, sep ? '' : bubble.raw.match(/\s*$/)[0]) + sep + delta);
      },
      addSource: function (label) {
        bubble.breakNext = true;
        sources.hidden = false;
        sources.querySelector('ul').appendChild(node('li', null, label));
        var count = sources.querySelectorAll('li').length;
        var first = sources.querySelector('li').textContent;
        sources.querySelector('summary').textContent = t('consulted') + ' ' + first + (count > 1 ? ' (+' + (count - 1) + ')' : '');
      },
      addAction: function (action) {
        var btn = node('button', 'airbot-action', action.label || 'Abrir');
        btn.type = 'button';
        btn.addEventListener('click', function () { runAction(action); });
        actions.appendChild(btn);
      },
      error: function (message) {
        typing.hidden = true;
        wrap.classList.add('airbot-msg-error');
        if (!bubble.raw) text.textContent = message;
        else text.appendChild(node('p', null, message));
      },
      finish: function () { typing.hidden = true; }
    };

    return bubble;
  }

  /* ---------------- Enviar pregunta (streaming) ---------------- */

  function ask(question) {
    question = (question || '').trim();
    if (!question || state.busy) return;

    showChat();
    state.busy = true;
    el.send.disabled = true;
    addUserBubble(question);
    var bubble = createBotBubble();

    var payload = {
      message: question,
      conversation_id: state.conversationId,
      screen: currentScreen()
    };

    fetch(cfg.chat, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'text/event-stream',
        'X-CSRF-TOKEN': cfg.csrf,
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(payload)
    }).then(function (response) {
      var type = response.headers.get('Content-Type') || '';
      if (!response.ok || type.indexOf('text/event-stream') === -1) {
        return response.json().catch(function () { return {}; }).then(function (data) {
          throw new Error(data.message || t('error'));
        });
      }
      return readStream(response, bubble);
    }).catch(function (err) {
      bubble.error(err && err.message ? err.message : t('error'));
    }).then(function () {
      bubble.finish();
      state.busy = false;
      el.send.disabled = false;
      scrollDown();
    });
  }

  function readStream(response, bubble) {
    var reader = response.body.getReader();
    var decoder = new TextDecoder('utf-8');
    var buffer = '';

    function handle(eventName, data) {
      switch (eventName) {
        case 'conversation': state.conversationId = data.id; break;
        case 'delta': bubble.appendText(data.text || ''); scrollDown(); break;
        case 'source': bubble.addSource(data.label); break;
        case 'action': bubble.addAction(data); break;
        case 'done': setQuota(data.remaining); break;
        case 'error': bubble.error(data.message || t('error')); break;
      }
    }

    function pump() {
      return reader.read().then(function (result) {
        if (result.done) return;
        buffer += decoder.decode(result.value, { stream: true });
        var parts = buffer.split('\n\n');
        buffer = parts.pop();
        parts.forEach(function (raw) {
          var name = 'message';
          var data = '';
          raw.split('\n').forEach(function (line) {
            if (line.indexOf('event:') === 0) name = line.slice(6).trim();
            else if (line.indexOf('data:') === 0) data += line.slice(5).trim();
          });
          if (!data) return;
          try { handle(name, JSON.parse(data)); } catch (e) { /* evento incompleto */ }
        });
        return pump();
      });
    }

    return pump();
  }

  function currentScreen() {
    var active = document.querySelector('#sidenav li.active a, #sidebar .nav-tabs li.active a');
    var href = active ? (active.getAttribute('href') || '') : '';
    var map = { '#objects_tab': 'mapa / vehiculos', '#events_tab': 'eventos', '#history_tab': 'historial',
      '#geofencing_tab': 'geocercas', '#routes_tab': 'rutas', '#pois_tab': 'pois' };
    return map[href] || 'mapa';
  }

  el.form.addEventListener('submit', function (e) {
    e.preventDefault();
    var q = el.input.value;
    el.input.value = '';
    autosize();
    ask(q);
  });

  el.input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      el.form.dispatchEvent(new Event('submit', { cancelable: true }));
    }
  });

  function autosize() {
    el.input.style.height = 'auto';
    el.input.style.height = Math.min(el.input.scrollHeight, 120) + 'px';
  }
  el.input.addEventListener('input', autosize);

  /* ---------------- Sugerencias ---------------- */

  function loadSuggestions() {
    request('GET', cfg.suggestions).then(function (data) {
      el.suggestions.innerHTML = '';
      (data.suggestions || []).forEach(function (s) {
        var chip = node('button', 'airbot-chip', s);
        chip.type = 'button';
        chip.addEventListener('click', function () { ask(s); });
        el.suggestions.appendChild(chip);
      });
      setQuota(data.remaining);
    }).catch(function (err) {
      el.suggestions.innerHTML = '';
      el.suggestions.appendChild(node('div', 'airbot-empty', err.message));
    });
  }

  /* ---------------- Conversaciones ---------------- */

  function showChat() {
    el.history.hidden = true;
    el.messages.hidden = false;
  }

  function newConversation() {
    if (state.busy) return;
    state.conversationId = null;
    el.messages.innerHTML = '';
    showChat();
    el.welcome.hidden = false;
    el.input.focus();
  }

  function toggleHistory() {
    if (!el.history.hidden) {
      showChat();
      el.welcome.hidden = el.messages.children.length > 0;
      return;
    }
    el.welcome.hidden = true;
    el.messages.hidden = true;
    el.history.hidden = false;
    el.history.innerHTML = '';
    el.history.appendChild(node('div', 'airbot-empty', t('thinking')));

    request('GET', cfg.conversations).then(function (data) {
      el.history.innerHTML = '';
      var list = data.conversations || [];
      if (!list.length) {
        el.history.appendChild(node('div', 'airbot-empty', t('no_history')));
        return;
      }
      list.forEach(function (c) {
        var item = node('div', 'airbot-history-item');
        item.setAttribute('role', 'button');
        item.tabIndex = 0;
        item.appendChild(node('span', 't', c.title || '...'));
        item.appendChild(node('span', 'd', (c.updated_at || '').slice(0, 10)));
        var del = node('button', 'x', '×');
        del.type = 'button';
        del.title = 'Eliminar';
        del.addEventListener('click', function (e) {
          e.stopPropagation();
          request('DELETE', cfg.conversations + '/' + c.id).then(function () {
            item.remove();
            if (state.conversationId === c.id) newConversation();
          });
        });
        item.appendChild(del);
        var openIt = function () { loadConversation(c.id); };
        item.addEventListener('click', openIt);
        item.addEventListener('keydown', function (e) { if (e.key === 'Enter') openIt(); });
        el.history.appendChild(item);
      });
    }).catch(function (err) {
      el.history.innerHTML = '';
      el.history.appendChild(node('div', 'airbot-empty', err.message));
    });
  }

  function loadConversation(id) {
    request('GET', cfg.conversations + '/' + id).then(function (data) {
      state.conversationId = data.id;
      el.messages.innerHTML = '';
      showChat();
      (data.turns || []).forEach(function (turn) {
        if (turn.role === 'user') {
          addUserBubble(turn.text);
        } else {
          var b = createBotBubble();
          (turn.sources || []).forEach(b.addSource);
          b.setText(turn.text || '');
          (turn.actions || []).forEach(b.addAction);
          b.finish();
        }
      });
      if (!(data.turns || []).length) el.welcome.hidden = false;
      scrollDown();
    }).catch(function (err) {
      showChat();
      createBotBubble().error(err.message);
    });
  }
})();
