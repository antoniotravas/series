/* Séries: interações do lado do cliente (sem dependências) */
(function () {
  'use strict';

  var I18N = window.I18N || {};

  // ---------- Tempos ----------

  /** "1:32.4", "1.32.4", "92,4" -> segundos; null se inválido */
  function parseTime(s) {
    s = String(s || '').trim().replace(/,/g, '.').replace(/\s+/g, ':');
    if (!s) return null;
    var dots = (s.match(/\./g) || []).length;
    if (dots >= 2) {
      var last = s.lastIndexOf('.');
      s = s.slice(0, last).replace(/\./g, ':') + s.slice(last);
    } else if (dots === 1 && /^\d\.\d{2}$/.test(s)) {
      s = s.replace('.', ':'); // "1.32" (teclado numérico) -> 1:32
    }
    var m = s.match(/^(?:(\d+):)?(?:(\d+):)?(\d+(?:\.\d{1,2})?)$/);
    if (!m) return null;
    var parts = [m[1], m[2]].filter(function (p) { return p !== undefined; });
    var sec = parseFloat(m[3]);
    if (parts.length === 2) sec += parseInt(parts[0], 10) * 3600 + parseInt(parts[1], 10) * 60;
    else if (parts.length === 1) sec += parseInt(parts[0], 10) * 60;
    return sec > 0 ? Math.round(sec * 100) / 100 : null;
  }

  function fmtTime(sec, decimals) {
    if (sec == null || isNaN(sec)) return '–';
    if (decimals == null) decimals = 1;
    var neg = sec < 0;
    sec = Math.abs(sec);
    var h = Math.floor(sec / 3600);
    var m = Math.floor((sec - h * 3600) / 60);
    var s = (sec - h * 3600 - m * 60).toFixed(decimals);
    if (parseFloat(s) >= 60) { m++; s = (0).toFixed(decimals); }
    if (h > 0 || m > 0) s = s.padStart(decimals ? 3 + decimals : 2, '0');
    var out = h > 0 ? h + ':' + String(m).padStart(2, '0') + ':' + s : (m > 0 ? m + ':' + s : s);
    return (neg ? '−' : '') + out;
  }

  function fmtPace(distanceM, sec) {
    if (!distanceM || !sec) return '–';
    var perKm = sec / (distanceM / 1000);
    var m = Math.floor(perKm / 60);
    var s = Math.round(perKm - m * 60);
    if (s === 60) { m++; s = 0; }
    return m + ':' + String(s).padStart(2, '0');
  }

  // ---------- Menu do avatar ----------

  document.querySelectorAll('[data-menu]').forEach(function (box) {
    var btn = box.querySelector('button[aria-expanded]');
    var menu = box.querySelector('.me__menu');
    function set(open) {
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      menu.classList.toggle('is-open', open);
    }
    btn.addEventListener('click', function () {
      var open = btn.getAttribute('aria-expanded') !== 'true';
      set(open);
      if (open) { var first = menu.querySelector('a, button'); if (first) first.focus(); }
    });
    document.addEventListener('click', function (e) { if (!box.contains(e.target)) set(false); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && btn.getAttribute('aria-expanded') === 'true') { set(false); btn.focus(); }
    });
  });

  // ---------- Foto do avatar: reduzir no browser antes de enviar ----------
  // as fotos do telemóvel têm vários MB (e o iPhone usa HEIC): passam a um JPEG pequeno

  var avatarInput = document.querySelector('input[type=file][name=avatar]');
  if (avatarInput && window.DataTransfer && HTMLCanvasElement.prototype.toBlob) {
    avatarInput.addEventListener('change', function () {
      var file = avatarInput.files[0];
      if (!file || !/^image\//.test(file.type || 'image/')) return;
      var submits = avatarInput.form.querySelectorAll('button[type=submit], button:not([type])');
      submits.forEach(function (b) { b.disabled = true; });
      var url = URL.createObjectURL(file);
      var img = new Image();
      var done = function () {
        URL.revokeObjectURL(url);
        submits.forEach(function (b) { b.disabled = false; });
      };
      img.onload = function () {
        var max = 640;
        var k = Math.min(1, max / Math.max(img.naturalWidth, img.naturalHeight));
        var c = document.createElement('canvas');
        c.width = Math.round(img.naturalWidth * k);
        c.height = Math.round(img.naturalHeight * k);
        var ctx = c.getContext('2d');
        ctx.fillStyle = '#fff';   // PNG transparente não fica com fundo preto
        ctx.fillRect(0, 0, c.width, c.height);
        ctx.drawImage(img, 0, 0, c.width, c.height);
        c.toBlob(function (blob) {
          if (blob) {
            var dt = new DataTransfer();
            dt.items.add(new File([blob], 'avatar.jpg', { type: 'image/jpeg' }));
            avatarInput.files = dt.files;
          }
          done();
        }, 'image/jpeg', 0.88);
      };
      img.onerror = done;   // formato que o browser não lê: vai tal como está
      img.src = url;
    });
  }

  // ---------- Perfil: o currículo só aparece a quem se marca como treinador ----------

  var coachBox = document.querySelector('input[type=checkbox][name=is_coach]');
  if (coachBox) {
    var coachOnly = document.querySelectorAll('[data-coach-only]');
    var syncCoach = function () { coachOnly.forEach(function (el) { el.hidden = !coachBox.checked; }); };
    coachBox.addEventListener('change', syncCoach);
    syncCoach();
  }

  // ---------- Feed da rede: carregar treinos mais antigos ----------

  var more = document.querySelector('[data-feed-more]');
  if (more) {
    var list = more.previousElementSibling, busy = false, btn = more.querySelector('button');
    var load = function () {
      if (busy) return;
      busy = true;
      btn.disabled = true;
      fetch(more.getAttribute('data-feed-more') + '?offset=' + more.getAttribute('data-next'), { credentials: 'same-origin', cache: 'no-store' })
        .then(function (r) { return r.ok ? r.text().then(function (html) { return { html: html, more: r.headers.get('X-Feed-More') === '1' }; }) : Promise.reject(); })
        .then(function (res) {
          var tmp = document.createElement('div');
          tmp.innerHTML = res.html;
          var flag = tmp.querySelector('[data-feed-has-more]');
          if (flag) res.more = flag.getAttribute('data-feed-has-more') === '1';
          var items = tmp.querySelectorAll('.wlist__item');
          var months = list.querySelectorAll('.wlist__month');
          var lastMonth = months.length ? months[months.length - 1].getAttribute('data-month') : null;
          tmp.querySelectorAll('.wlist > li').forEach(function (li) {
            // o mês que continua da página anterior não repete o cabeçalho
            if (li.classList.contains('wlist__month') && li.getAttribute('data-month') === lastMonth) return;
            list.appendChild(li);
          });
          more.setAttribute('data-next', parseInt(more.getAttribute('data-next'), 10) + items.length);
          if (!res.more || !items.length) more.remove();
        })
        .catch(function () {})   // fica o botão para tentar de novo
        .then(function () { busy = false; btn.disabled = false; });
    };
    // só carrega quando se pede: assim a página não cresce sem fim
    btn.addEventListener('click', load);
  }

  // ---------- Confirmações e auto-submit ----------

  document.addEventListener('submit', function (ev) {
    var btn = ev.submitter;
    var msg = (btn && btn.getAttribute('data-confirm')) || ev.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) ev.preventDefault();
  });

  // ---------- Página do treino: tipo de gráfico das séries ----------

  document.querySelectorAll('[data-lanes-toggle]').forEach(function (bar) {
    var sets = document.querySelector('.sets[data-view]');
    if (!sets) return;
    var buttons = bar.querySelectorAll('[data-view]');
    function show(view) {
      sets.setAttribute('data-view', view);
      buttons.forEach(function (b) {
        var on = b.getAttribute('data-view') === view;
        b.classList.toggle('is-on', on);
        b.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
    }
    buttons.forEach(function (b) {
      b.addEventListener('click', function () {
        var view = b.getAttribute('data-view');
        show(view);
        try { localStorage.setItem('series.lanesView', view); } catch (e) {}
      });
    });
    var saved = null;
    try { saved = localStorage.getItem('series.lanesView'); } catch (e) {}
    show(saved === 'speed' ? 'speed' : 'dev');
    bar.hidden = false;
  });

  // ---------- Seleção em lote (lista de treinos) ----------

  document.querySelectorAll('[data-check-all]').forEach(function (all) {
    var form = all.form;
    var boxes = form.querySelectorAll('input[name="ids[]"]');
    var count = form.querySelector('[data-check-count]');
    function update() {
      var n = form.querySelectorAll('input[name="ids[]"]:checked').length;
      all.checked = n > 0 && n === boxes.length;
      all.indeterminate = n > 0 && n < boxes.length;
      if (count) count.textContent = n ? count.getAttribute('data-label').replace('%n', n) : '';
    }
    all.addEventListener('change', function () {
      boxes.forEach(function (b) { b.checked = all.checked; });
      update();
    });
    boxes.forEach(function (b) { b.addEventListener('change', update); });
    update();
  });

  // lista do treinador: marcar dois treinos para comparar (um terceiro tira o mais antigo)
  document.querySelectorAll('[data-compare]').forEach(function (form) {
    var count = form.querySelector('[data-compare-count]');
    var go = form.querySelector('[data-compare-go]');
    var order = [];
    function update() {
      var n = order.length;
      count.textContent = n ? count.getAttribute('data-label').replace('%n', n) : '';
      go.disabled = n !== 2;
      go.textContent = go.getAttribute(n === 2 ? 'data-label' : 'data-two');
    }
    form.querySelectorAll('input[name="ids[]"]').forEach(function (b) {
      b.addEventListener('change', function () {
        order = order.filter(function (x) { return x !== b; });
        if (b.checked) order.push(b);
        if (order.length > 2) order.shift().checked = false;
        update();
      });
    });
    update();
  });

  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });

  // ---------- Formulário de treino ----------

  var form = document.querySelector('[data-workout-form]');
  if (form) initWorkoutForm(form);

  function initWorkoutForm(form) {
    var setsBox = form.querySelector('[data-sets]');
    var tpl = form.querySelector('[data-set-template]');

    function setBlocks() { return Array.prototype.slice.call(setsBox.querySelectorAll('[data-set]')); }

    /** Reescreve índices de nomes/ids depois de adicionar ou remover séries */
    function renumber() {
      var blocks = setBlocks();
      blocks.forEach(function (block, i) {
        block.querySelectorAll('[name]').forEach(function (el) {
          el.name = el.name.replace(/^sets\[[^\]]+\]/, 'sets[' + i + ']');
        });
        block.querySelectorAll('[id]').forEach(function (el) {
          el.id = el.id.replace(/^s[^-]+-/, 's' + i + '-');
        });
        block.querySelectorAll('label[for]').forEach(function (el) {
          el.htmlFor = el.htmlFor.replace(/^s[^-]+-/, 's' + i + '-');
        });
        block.querySelector('[data-set-label]').textContent = (I18N.set || 'Set %n').replace('%n', i + 1);
        block.querySelector('[data-remove-set]').hidden = blocks.length === 1;
      });
    }

    function renumberReps(block) {
      var idx = setBlocks().indexOf(block);
      block.querySelectorAll('[data-reps] li').forEach(function (li, k) {
        var input = li.querySelector('input');
        var label = li.querySelector('label');
        input.id = 's' + idx + '-r' + k;
        label.htmlFor = input.id;
        label.textContent = k + 1;
        input.setAttribute('aria-label', (I18N.rep || 'Rep %n').replace('%n', k + 1));
      });
    }

    function setRepCount(block, n) {
      n = Math.max(1, Math.min(60, n | 0));
      var list = block.querySelector('[data-reps]');
      var items = list.querySelectorAll('li');
      if (items.length < n) {
        var proto = items[items.length - 1];
        for (var i = items.length; i < n; i++) {
          var li = proto.cloneNode(true);
          li.querySelectorAll('input').forEach(function (inp) { inp.value = ''; });
          li.querySelector('input').classList.remove('is-invalid');
          list.appendChild(li);
        }
      } else {
        // só remove campos vazios do fim, para não apagar tempos já escritos
        for (var j = items.length - 1; j >= n; j--) {
          if (items[j].querySelector('[data-rep]').value.trim() !== '') { n = j + 1; break; }
          items[j].remove();
        }
        block.querySelector('[data-rep-count]').value = list.querySelectorAll('li').length;
      }
      renumberReps(block);
      updateLive(block);
    }

    function updateLive(block) {
      var dist = parseInt(block.querySelector('[data-distance]').value, 10) || 0;
      var times = [];
      block.querySelectorAll('[data-rep]').forEach(function (inp) {
        var v = parseTime(inp.value);
        if (v != null) times.push(v);
      });
      block.querySelectorAll('.chip').forEach(function (c) {
        c.classList.toggle('is-on', parseInt(c.getAttribute('data-quick'), 10) === dist);
      });
      var out = block.querySelector('[data-live]');
      if (!times.length) { out.innerHTML = ''; return; }
      var sum = times.reduce(function (a, b) { return a + b; }, 0);
      var avg = sum / times.length;
      var sd = Math.sqrt(times.reduce(function (a, t) { return a + (t - avg) * (t - avg); }, 0) / times.length);
      var parts = [
        [I18N.avg, fmtTime(avg)],
        [I18N.best, fmtTime(Math.min.apply(null, times))],
        [I18N.sd, '±' + sd.toFixed(1) + 's'],
      ];
      if (dist) parts.push([I18N.pace, fmtPace(dist, avg) + '/km']);
      out.innerHTML = parts.map(function (p) {
        return '<span>' + escapeHtml(p[0]) + ' <b>' + escapeHtml(p[1]) + '</b></span>';
      }).join('');
    }

    function escapeHtml(s) {
      return String(s).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    }

    form.addEventListener('click', function (ev) {
      var t = ev.target.closest('button');
      if (!t) return;
      var block = t.closest('[data-set]');
      if (t.hasAttribute('data-add-set')) {
        var last = setBlocks().slice(-1)[0];
        var frag = tpl.content.cloneNode(true);
        var newBlock = frag.querySelector('[data-set]');
        // nova série herda distância, recuperação e nº de reps da anterior: é o caso mais comum
        if (last) {
          newBlock.querySelector('[data-distance]').value = last.querySelector('[data-distance]').value;
          newBlock.querySelector('[name$="[rest]"]').value = last.querySelector('[name$="[rest]"]').value;
          newBlock.querySelector('select').value = last.querySelector('select').value;
        }
        setsBox.appendChild(newBlock);
        renumber();
        if (last) setRepCount(newBlock, last.querySelectorAll('[data-rep]').length);
        newBlock.querySelector('[data-distance]').focus();
      } else if (t.hasAttribute('data-remove-set') && block) {
        if (setBlocks().length > 1) {
          block.remove();
          renumber();
          setBlocks().forEach(renumberReps);
        }
      } else if (t.hasAttribute('data-quick') && block) {
        block.querySelector('[data-distance]').value = t.getAttribute('data-quick');
        updateLive(block);
      }
    });

    form.addEventListener('input', function (ev) {
      var block = ev.target.closest('[data-set]');
      if (!block) return;
      if (ev.target.hasAttribute('data-rep-count')) {
        if (ev.target.value !== '') setRepCount(block, parseInt(ev.target.value, 10));
        return;
      }
      if (ev.target.hasAttribute('data-rep')) ev.target.classList.remove('is-invalid');
      updateLive(block);
    });

    // normalizar o tempo ao sair do campo: "92.4" -> "1:32.4"
    form.addEventListener('focusout', function (ev) {
      var el = ev.target;
      if (!el.matches('[data-rep], [data-time]')) return;
      if (el.value.trim() === '') { el.classList.remove('is-invalid'); return; }
      var v = parseTime(el.value);
      if (v == null) { el.classList.add('is-invalid'); return; }
      el.classList.remove('is-invalid');
      if (el.hasAttribute('data-rep') || el.hasAttribute('data-target')) {
        el.value = fmtTime(v, Math.round(v * 100) % 10 ? 2 : 1); // manter centésimos só se existirem
      } else {
        el.value = fmtTime(v, 0);
      }
    });

    // Enter num tempo passa para a repetição seguinte em vez de submeter
    form.addEventListener('keydown', function (ev) {
      if (ev.key !== 'Enter' || !ev.target.matches('[data-rep]')) return;
      ev.preventDefault();
      var all = Array.prototype.slice.call(form.querySelectorAll('[data-rep]'));
      var next = all[all.indexOf(ev.target) + 1];
      if (next) next.focus();
      else ev.target.blur();
    });

    // ---------- Preencher a partir de um GPX ----------

    var gpxBox = form.querySelector('[data-gpx-fill]');
    if (gpxBox && window.fetch && window.FormData) {
      gpxBox.hidden = false;
      var gpxInput = gpxBox.querySelector('[data-gpx-input]');
      var gpxOut = gpxBox.querySelector('[data-gpx-result]');

      gpxInput.addEventListener('change', function () {
        var file = gpxInput.files[0];
        if (!file) return;
        var hasTimes = Array.prototype.some.call(form.querySelectorAll('[data-rep]'), function (i) { return i.value.trim() !== ''; });
        if (hasTimes && !window.confirm(gpxBox.getAttribute('data-replace'))) { gpxInput.value = ''; return; }

        var fd = new FormData();
        fd.append('gpx', file);
        fd.append('_csrf', form.querySelector('[name="_csrf"]').value);
        gpxOut.innerHTML = '<p class="muted small">' + escapeHtml(gpxBox.getAttribute('data-loading')) + '</p>';

        fetch(gpxBox.getAttribute('data-url'), { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function (r) { return r.json().catch(function () { return { ok: false }; }); })
          .then(function (res) {
            if (!res.ok) throw new Error(res.error || '');
            fillFromGpx(res);
            gpxOut.innerHTML = '<p class="gpxfill__ok">' + escapeHtml(res.summary) + '</p>' +
              (res.location_note ? '<p class="muted small">' + escapeHtml(res.location_note) + '</p>' : '') +
              '<ol class="gpxfill__reps">' +
              res.reps.map(function (r) {
                return '<li' + (r.work ? '' : ' class="is-ignored"') + '>' + escapeHtml(r.label) +
                  (r.work ? '' : ' (' + escapeHtml(gpxBox.getAttribute('data-ignored')) + ')') + '</li>';
              }).join('') + '</ol>';
          })
          .catch(function (err) {
            gpxOut.innerHTML = '<p class="field__error" role="alert">' + escapeHtml(err.message || gpxBox.getAttribute('data-error')) + '</p>';
          });
      });
    }

    function fillFromGpx(res) {
      form.querySelector('#workout_date').value = res.date;
      form.querySelector('#start_time').value = res.start_time;
      form.querySelector('[data-lat]').value = res.lat == null ? '' : res.lat;
      form.querySelector('[data-lon]').value = res.lon == null ? '' : res.lon;
      if (res.location) form.querySelector('#location').value = res.location;
      if (res.temperature_c != null) {
        form.querySelector('#temperature_c').value = res.temperature_c;
        form.querySelector('.wform__more').open = true;
      }
      // deixar só um bloco e reconstruir a partir dele
      setBlocks().slice(1).forEach(function (b) { b.remove(); });
      res.sets.forEach(function (s, i) {
        var block = setBlocks()[i];
        if (!block) {
          block = tpl.content.cloneNode(true).querySelector('[data-set]');
          setsBox.appendChild(block);
          renumber();
        }
        block.querySelector('[data-distance]').value = s.distance_m;
        block.querySelector('[name$="[rest]"]').value = s.rest == null ? '' : fmtTime(s.rest, 0);
        block.querySelector('select').value = s.rest_type;
        block.querySelectorAll('[data-rep]').forEach(function (inp) { inp.value = ''; });
        setRepCount(block, s.reps.length);
        block.querySelector('[data-rep-count]').value = s.reps.length;
        block.querySelectorAll('[data-rep]').forEach(function (inp, k) { inp.value = fmtTime(s.reps[k], 1); });
        block.querySelectorAll('[data-rep-hr]').forEach(function (inp, k) { inp.value = s.hr && s.hr[k] != null ? s.hr[k] : ''; });
        block.querySelectorAll('[data-rep-hr-max]').forEach(function (inp, k) { inp.value = s.hr_max && s.hr_max[k] != null ? s.hr_max[k] : ''; });
        updateLive(block);
      });
      renumber();
      setBlocks().forEach(renumberReps);
    }

    renumber();
    setBlocks().forEach(updateLive);
  }

  // ---------- Gráficos ----------

  var NS = 'http://www.w3.org/2000/svg';
  function svgEl(name, attrs, parent) {
    var el = document.createElementNS(NS, name);
    for (var k in attrs) el.setAttribute(k, attrs[k]);
    if (parent) parent.appendChild(el);
    return el;
  }

  function niceMax(v) {
    if (v <= 0) return 1;
    var p = Math.pow(10, Math.floor(Math.log10(v)));
    var n = v / p;
    var step = n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10;
    return step * p;
  }

  function tooltip(box) {
    var tip = document.createElement('div');
    tip.className = 'tooltip';
    tip.setAttribute('role', 'presentation');
    box.appendChild(tip);
    return {
      show: function (html, x, y) { tip.innerHTML = html; tip.style.left = x + 'px'; tip.style.top = y + 'px'; tip.classList.add('is-on'); },
      hide: function () { tip.classList.remove('is-on'); },
    };
  }

  function drawBars(box) {
    var pts = JSON.parse(box.getAttribute('data-points'));
    box.innerHTML = '';
    var W = box.clientWidth || 600, H = 200, padL = 34, padB = 22, padT = 8, padR = 4;
    var max = niceMax(Math.max.apply(null, pts.map(function (p) { return p.value; })));
    var svg = svgEl('svg', { viewBox: '0 0 ' + W + ' ' + H, role: 'img', 'aria-hidden': 'true' }, box);
    var grid = svgEl('g', { 'class': 'grid' }, svg);
    var axis = svgEl('g', { 'class': 'axis' }, svg);
    var plotH = H - padB - padT, plotW = W - padL - padR;
    [0, 0.5, 1].forEach(function (f) {
      var y = padT + plotH * (1 - f);
      svgEl('line', { x1: padL, x2: W - padR, y1: y, y2: y }, grid);
      var tx = svgEl('text', { x: padL - 6, y: y + 4, 'text-anchor': 'end' }, axis);
      tx.textContent = +(max * f).toFixed(1);
    });
    var slot = plotW / pts.length, bw = Math.max(4, Math.min(28, slot - 6));
    var tip = tooltip(box);
    pts.forEach(function (p, i) {
      var h = p.value / max * plotH;
      var x = padL + slot * i + (slot - bw) / 2;
      var y = padT + plotH - h;
      var bar;
      if (h > 0) {
        // topo arredondado de 4px, base direita
        var r = Math.min(4, h, bw / 2);
        bar = svgEl('path', {
          'class': 'bar' + (i === pts.length - 1 ? ' is-current' : ''),
          d: 'M' + x + ',' + (padT + plotH) + 'V' + (y + r) + 'Q' + x + ',' + y + ' ' + (x + r) + ',' + y +
             'H' + (x + bw - r) + 'Q' + (x + bw) + ',' + y + ' ' + (x + bw) + ',' + (y + r) + 'V' + (padT + plotH) + 'Z',
        }, svg);
      }
      if (i % Math.ceil(pts.length / 6) === 0 || i === pts.length - 1) {
        var lt = svgEl('text', { x: x + bw / 2, y: H - 6, 'text-anchor': 'middle' }, axis);
        lt.textContent = p.label;
      }
      var hit = svgEl('rect', { 'class': 'hit', x: padL + slot * i, y: padT, width: slot, height: plotH }, svg);
      hit.addEventListener('mouseenter', function () {
        if (bar) bar.classList.add('is-hover');
        tip.show('<strong>' + p.value.toLocaleString() + ' km</strong><br>' + p.title + '<br>' + p.extra,
          (x + bw / 2) / W * box.clientWidth, Math.min(y, padT + plotH - 4) / H * box.clientHeight);
      });
      hit.addEventListener('mouseleave', function () { if (bar) bar.classList.remove('is-hover'); tip.hide(); });
    });
  }

  function drawLines(box) {
    var series = JSON.parse(box.getAttribute('data-series'));
    var labels = JSON.parse(box.getAttribute('data-labels'));
    box.innerHTML = '';
    var W = box.clientWidth || 600, H = 220, padL = 48, padB = 22, padT = 12, padR = 12;
    var all = [].concat.apply([], series.map(function (s) { return s.values; }));
    var lo = Math.min.apply(null, all), hi = Math.max.apply(null, all);
    var span = Math.max(hi - lo, 1);
    lo -= span * 0.12; hi += span * 0.12;
    var n = labels.length, plotW = W - padL - padR, plotH = H - padT - padB;
    var X = function (i) { return padL + (n === 1 ? plotW / 2 : plotW * i / (n - 1)); };
    // eixo invertido: mais rápido (menos tempo) fica em cima
    var Y = function (v) { return padT + plotH * (v - lo) / (hi - lo); };
    var svg = svgEl('svg', { viewBox: '0 0 ' + W + ' ' + H, role: 'img', 'aria-hidden': 'true' }, box);
    var grid = svgEl('g', { 'class': 'grid' }, svg);
    var axis = svgEl('g', { 'class': 'axis' }, svg);
    [0, 0.5, 1].forEach(function (f) {
      var v = lo + (hi - lo) * f, y = Y(v);
      svgEl('line', { x1: padL, x2: W - padR, y1: y, y2: y }, grid);
      var tx = svgEl('text', { x: padL - 6, y: y + 4, 'text-anchor': 'end' }, axis);
      tx.textContent = fmtTime(v, 1);
    });
    [0, n - 1].forEach(function (i, k) {
      if (k === 1 && n === 1) return;
      var t = svgEl('text', { x: X(i), y: H - 5, 'text-anchor': k ? 'end' : 'start' }, axis);
      t.textContent = labels[i];
    });
    var cross = svgEl('line', { 'class': 'crosshair', y1: padT, y2: padT + plotH, x1: -10, x2: -10 }, svg);
    series.forEach(function (s) {
      var d = s.values.map(function (v, i) { return (i ? 'L' : 'M') + X(i) + ',' + Y(v); }).join('');
      svgEl('path', { 'class': 'line line--' + s.cls, d: d }, svg);
    });
    var dots = series.map(function (s) {
      return svgEl('circle', { 'class': 'dot dot--' + s.cls, r: 5, cx: -20, cy: -20, visibility: 'hidden' }, svg);
    });
    // rótulo direto apenas no último ponto de cada série
    series.forEach(function (s) {
      var v = s.values[n - 1];
      var t = svgEl('text', { 'class': 'label', x: X(n - 1) - 8, y: Y(v) + (s.cls === 'best' ? -9 : 17), 'text-anchor': 'end' }, svg);
      t.textContent = fmtTime(v, 1);
    });
    var tip = tooltip(box);
    var hit = svgEl('rect', { 'class': 'hit', x: padL, y: 0, width: plotW, height: H }, svg);
    function at(evX) {
      var r = svg.getBoundingClientRect();
      var px = (evX - r.left) / r.width * W;
      var i = n === 1 ? 0 : Math.round((px - padL) / plotW * (n - 1));
      return Math.max(0, Math.min(n - 1, i));
    }
    hit.addEventListener('mousemove', function (ev) {
      var i = at(ev.clientX), x = X(i);
      cross.setAttribute('x1', x); cross.setAttribute('x2', x);
      series.forEach(function (s, k) {
        dots[k].setAttribute('cx', x); dots[k].setAttribute('cy', Y(s.values[i])); dots[k].setAttribute('visibility', 'visible');
      });
      var html = '<strong>' + labels[i] + '</strong>' + series.map(function (s) {
        return '<br>' + s.name + ': <strong>' + fmtTime(s.values[i], 2) + '</strong>';
      }).join('');
      tip.show(html, x / W * box.clientWidth, Y(Math.min.apply(null, series.map(function (s) { return s.values[i]; }))) / H * box.clientHeight);
    });
    hit.addEventListener('mouseleave', function () {
      tip.hide(); cross.setAttribute('x1', -10); cross.setAttribute('x2', -10);
      dots.forEach(function (d) { d.setAttribute('visibility', 'hidden'); });
    });
  }

  var charts = document.querySelectorAll('[data-chart]');
  function drawAll() {
    charts.forEach(function (box) {
      if (box.getAttribute('data-chart') === 'bars') drawBars(box);
      else drawLines(box);
    });
  }
  if (charts.length) {
    drawAll();
    var t;
    window.addEventListener('resize', function () { clearTimeout(t); t = setTimeout(drawAll, 150); });
  }
})();

// ---------- Partilhar treino: imagem desenhada em canvas ----------
(function () {
  var dlg = document.querySelector('[data-share]');
  var opener = document.querySelector('[data-share-open]');
  if (!dlg || !opener || !dlg.showModal) { if (opener) opener.hidden = true; return; }

  var data = JSON.parse(dlg.querySelector('[data-share-data]').textContent);
  var canvas = dlg.querySelector('[data-share-canvas]');
  var preview = dlg.querySelector('.share__preview');
  var sendBtn = dlg.querySelector('[data-share-send]');
  var W = 1080, SIZES = { post: 1350, story: 1920, fit: 0 };   // fit: altura conforme o conteúdo
  var COND = '"Barlow Condensed", "Arial Narrow", sans-serif', BODY = '"Barlow", system-ui, sans-serif';

  var THEMES = {
    night:  { bg: ['#1B2833', '#0D141A'], ink: '#F5F7F4', muted: '#94A1AC', accent: '#E0574A', fast: '#4A8FE0', slow: '#E0574A', pb: '#F0C02E', grid: 'rgba(255,255,255,.08)', tile: 'rgba(255,255,255,.05)', track: '#C2362B', lane: 'rgba(255,255,255,.55)', trackAlpha: .9 },
    tartan: { bg: ['#CC3B2F', '#952619'], ink: '#FFFFFF', muted: 'rgba(255,255,255,.74)', accent: '#FFFFFF', fast: '#FFFFFF', slow: 'rgba(22,33,43,.5)', pb: '#F0C02E', grid: 'rgba(255,255,255,.16)', tile: 'rgba(0,0,0,.13)', track: '#16212B', lane: 'rgba(255,255,255,.5)', trackAlpha: .28 },
    light:  { bg: ['#FFFFFF', '#ECEFEA'], ink: '#16212B', muted: '#66717B', accent: '#C2362B', fast: '#1F64B5', slow: '#C2362B', pb: '#D9A400', grid: 'rgba(22,33,43,.09)', tile: 'rgba(22,33,43,.045)', track: '#C2362B', lane: 'rgba(255,255,255,.8)', trackAlpha: .92 },
    // relva do interior do estádio, com a pista em tartan
    grass:  { bg: ['#2F6E50', '#163A29'], ink: '#F5F7F4', muted: 'rgba(245,247,244,.72)', accent: '#FFFFFF', fast: '#9AD0FF', slow: '#F08A7E', pb: '#F0C02E', grid: 'rgba(255,255,255,.12)', tile: 'rgba(0,0,0,.16)', track: '#C2362B', lane: 'rgba(255,255,255,.7)', trackAlpha: .95 },
    blue:   { bg: ['#2370C8', '#123C6E'], ink: '#FFFFFF', muted: 'rgba(255,255,255,.74)', accent: '#FFFFFF', fast: '#FFFFFF', slow: 'rgba(10,16,21,.45)', pb: '#F0C02E', grid: 'rgba(255,255,255,.16)', tile: 'rgba(0,0,0,.15)', track: '#0A1015', lane: 'rgba(255,255,255,.5)', trackAlpha: .25 }
  };

  var state = { format: 'post', theme: 'night', content: 'chart', clear: false,
                show: { author: true, date: true, title: true, rest: true, stats: true, footer: true } };
  try {
    var saved = JSON.parse(localStorage.getItem('series.share') || '{}');
    if (saved.format in SIZES) state.format = saved.format;
    if (THEMES[saved.theme]) state.theme = saved.theme;
    if (['times', 'lines', 'none'].indexOf(saved.content) >= 0) state.content = saved.content;
    if (saved.show) Object.keys(state.show).forEach(function (k) { if (saved.show[k] === false) state.show[k] = false; });
    state.clear = saved.clear === true;
  } catch (e) { /* sem armazenamento: valores por omissão */ }

  // avatar (mesma origem, por isso o canvas pode ser exportado)
  var avatar = null;
  var avatarReady = new Promise(function (resolve) {
    if (!data.avatar) return resolve();
    var img = new Image();
    img.onload = function () { avatar = img; resolve(); };
    img.onerror = function () { resolve(); };
    img.src = data.avatar;
  });
  var fontsReady = document.fonts ? Promise.all([
    document.fonts.load('700 100px "Barlow Condensed"'),
    document.fonts.load('600 40px "Barlow Condensed"'),
    document.fonts.load('600 40px "Barlow"'),
    document.fonts.load('500 30px "Barlow"')
  ]).catch(function () {}) : Promise.resolve();

  function font(ctx, weight, size, family) { ctx.font = weight + ' ' + size + 'px ' + family; }
  function spacing(ctx, px) { if ('letterSpacing' in ctx) ctx.letterSpacing = px + 'px'; }

  // maior tamanho (até max) com que o texto cabe na largura
  function fit(ctx, text, weight, max, min, family, width) {
    var s = max;
    for (; s > min; s -= 2) { font(ctx, weight, s, family); if (ctx.measureText(text).width <= width) break; }
    return s;
  }

  // corta com reticências
  function clip(ctx, text, width) {
    if (ctx.measureText(text).width <= width) return text;
    while (text.length > 1 && ctx.measureText(text + '…').width > width) text = text.slice(0, -1);
    return text.trim() + '…';
  }

  // divide em linhas (no máximo n)
  function wrap(ctx, text, width, n) {
    var lines = [], cur = '';
    text.split(' ').forEach(function (w) {
      var next = cur ? cur + ' ' + w : w;
      if (ctx.measureText(next).width > width && cur) { lines.push(cur); cur = w; } else cur = next;
    });
    if (cur) lines.push(cur);
    if (lines.length > n) { lines = lines.slice(0, n); lines[n - 1] = clip(ctx, lines[n - 1] + '…', width); }
    return lines;
  }

  function roundRect(ctx, x, y, w, h, r) {
    r = Math.max(0, Math.min(r, w / 2, h / 2));
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
  }

  // curva de pista no canto: anel de tartan com as linhas das pistas
  var TRACK = { cx: W + 40, cy: -60, inner: 270, lanes: 6, lw: 40 };
  // até onde o texto pode ir, a esta altura, sem tocar na curva da pista
  function clearOfTrack(y) {
    var t = TRACK, R = t.inner + t.lanes * t.lw + 24;
    if (state.clear || y - t.cy >= R) return W;
    return t.cx - Math.sqrt(R * R - (y - t.cy) * (y - t.cy));
  }

  function track(ctx, th) {
    var cx = TRACK.cx, cy = TRACK.cy, inner = TRACK.inner, lanes = TRACK.lanes, lw = TRACK.lw;
    ctx.save();
    ctx.globalAlpha = th.trackAlpha;
    ctx.fillStyle = th.track;
    ctx.beginPath();
    ctx.arc(cx, cy, inner + lanes * lw, 0, Math.PI * 2);
    ctx.arc(cx, cy, inner, 0, Math.PI * 2, true);
    ctx.fill();
    ctx.globalAlpha = 1;
    ctx.strokeStyle = th.lane;
    ctx.lineWidth = 3;
    for (var i = 0; i <= lanes; i++) {
      ctx.beginPath();
      ctx.arc(cx, cy, inner + i * lw, Math.PI * .5, Math.PI * 1.05);
      ctx.stroke();
    }
    ctx.restore();
  }

  function brand(ctx, th, x, y, h) {
    // as três linhas de pista do logótipo
    var bw = h * 1.1, bh = h * .17, gap = (h - 3 * bh) / 2;
    ctx.fillStyle = th.accent;
    [1, .8, .6].forEach(function (f, i) {
      roundRect(ctx, x, y + i * (bh + gap), bw * f, bh, bh / 2);
      ctx.fill();
    });
    font(ctx, 700, h * 1.25, COND);
    ctx.fillStyle = th.ink;
    ctx.textBaseline = 'middle';
    ctx.fillText('Séries', x + bw + h * .35, y + h / 2 + 2);
    ctx.textBaseline = 'alphabetic';
  }

  // cabeçalho: avatar e nome e/ou data e local, conforme as opções; devolve a altura usada
  function header(ctx, th, x, y, size) {
    var showA = state.show.author, showD = state.show.date;
    if (!showA && !showD) return 0;
    if (!showA) {
      var tw0 = W - 560 - x;
      ctx.fillStyle = th.ink;
      font(ctx, 600, 38, BODY);
      ctx.fillText(clip(ctx, data.date, tw0), x, y + 38);
      if (data.place) {
        ctx.fillStyle = th.muted;
        font(ctx, 500, 30, BODY);
        ctx.fillText(clip(ctx, data.place, tw0), x, y + 80);
      }
      return data.place ? 90 : 48;
    }
    author(ctx, th, x, y, size, showD);
    return size;
  }

  function author(ctx, th, x, y, size, withDate) {
    var r = size / 2;
    ctx.save();
    ctx.beginPath();
    ctx.arc(x + r, y + r, r, 0, Math.PI * 2);
    ctx.closePath();
    if (avatar) {
      ctx.clip();
      var s = Math.min(avatar.naturalWidth, avatar.naturalHeight);
      ctx.drawImage(avatar, (avatar.naturalWidth - s) / 2, (avatar.naturalHeight - s) / 2, s, s, x, y, size, size);
    } else {
      ctx.fillStyle = th.accent;
      ctx.fill();
      font(ctx, 700, size * .42, COND);
      ctx.fillStyle = th.accent === '#FFFFFF' ? '#C2362B' : '#FFFFFF';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(data.initials, x + r, y + r + 2);
    }
    ctx.restore();
    ctx.lineWidth = 5;
    ctx.strokeStyle = th.accent;
    ctx.beginPath();
    ctx.arc(x + r, y + r, r + 7, 0, Math.PI * 2);
    ctx.stroke();

    // o texto pára antes da curva da pista
    var tx = x + size + 34, tw = W - 560 - tx;
    var dy = !withDate ? 20 : (data.place ? -18 : 0);
    ctx.textAlign = 'left';
    ctx.textBaseline = 'alphabetic';
    ctx.fillStyle = th.ink;
    font(ctx, 600, 42, BODY);
    ctx.fillText(clip(ctx, data.author, tw), tx, y + r - 6 + dy);
    if (!withDate) return;
    ctx.fillStyle = th.muted;
    font(ctx, 500, 30, BODY);
    ctx.fillText(clip(ctx, data.date, tw), tx, y + r + 36 + dy);
    if (data.place) ctx.fillText(clip(ctx, data.place, tw), tx, y + r + 76 + dy);
  }

  function tiles(ctx, th, x, y, w, cols, story) {
    var gap = 20, rows = Math.ceil(data.stats.length / cols);
    var tw = (w - gap * (cols - 1)) / cols, tH = story ? 190 : 200;
    data.stats.forEach(function (st, i) {
      var cx = x + (i % cols) * (tw + gap), cy = y + Math.floor(i / cols) * (tH + gap);
      ctx.fillStyle = th.tile;
      roundRect(ctx, cx, cy, tw, tH, 18);
      ctx.fill();
      // traço de cor no topo: média e mais rápida em destaque
      ctx.fillStyle = i === 1 ? th.fast : (i === 0 ? th.accent : th.grid);
      roundRect(ctx, cx + 24, cy + 24, 44, 6, 3);
      ctx.fill();
      ctx.fillStyle = th.muted;
      spacing(ctx, 2);
      font(ctx, 600, story ? 28 : 24, COND);
      ctx.fillText(clip(ctx, st.k.toUpperCase(), tw - 48), cx + 24, cy + (story ? 66 : 68));
      spacing(ctx, 0);
      ctx.fillStyle = th.ink;
      fit(ctx, st.v, 700, story ? 100 : 88, 40, COND, tw - 48);
      ctx.fillText(st.v, cx + 22, cy + (story ? 148 : 150));
      ctx.fillStyle = th.muted;
      font(ctx, 500, story ? 28 : 24, BODY);
      ctx.fillText(clip(ctx, st.u, tw - 48), cx + 24, cy + tH - 18);
    });
    return y + rows * tH + (rows - 1) * gap;
  }

  // gráfico de velocidade: barras ou linhas (uma linha por série)
  function chart(ctx, th, x, y, w, h, lines) {
    // cabeçalho e legenda
    ctx.fillStyle = th.muted;
    spacing(ctx, 2);
    font(ctx, 600, 26, COND);
    ctx.fillText(data.labels.chart.toUpperCase(), x, y + 26);
    spacing(ctx, 0);

    // por baixo de cada série: o nome e, se pedido, a recuperação
    var withRest = state.show.rest && data.sets.some(function (s) { return s.rest; });
    var top = y + 70, labelH = withRest ? 82 : 50, base = y + h - labelH, ch = base - top;
    var total = 0;
    data.sets.forEach(function (s) { total += s.reps.length; });
    // escala comum a todas as séries: a mais rápida é a barra mais alta;
    // a base fica abaixo da mais lenta, para as diferenças se verem
    var speeds = [];
    data.sets.forEach(function (s) { s.reps.forEach(function (t) { speeds.push(s.d / t); }); });
    var hi = Math.max.apply(null, speeds), lo = Math.min.apply(null, speeds);
    var floor = Math.max(0, lo - Math.max((hi - lo) * .6, hi * .01));
    var Y = function (v) { return base - (v - floor) / (hi - floor) * ch * .9; };
    var avgs = data.sets.map(function (s) { return s.d / s.avg; });
    var avgHi = Math.max.apply(null, avgs), avgLo = Math.min.apply(null, avgs);

    var gapSet = data.sets.length > 1 ? Math.max(24, w * .04) : 0;
    var slot = (w - gapSet * (data.sets.length - 1)) / total;
    var bw = Math.max(3, Math.min(slot * .7, 64)), r = Math.min(bw / 2, 10);

    ctx.strokeStyle = th.grid;
    ctx.lineWidth = 2;
    for (var g = 0; g < 3; g++) {
      var gy = top + ch * g / 3;
      ctx.beginPath(); ctx.moveTo(x, gy); ctx.lineTo(x + w, gy); ctx.stroke();
    }

    function pbMark(px, py) {
      ctx.fillStyle = th.pb;
      font(ctx, 700, 30, COND);
      ctx.textAlign = 'center';
      ctx.fillText('PB', px, py);
      ctx.textAlign = 'left';
    }

    var cx = x;
    data.sets.forEach(function (s) {
      var gx = cx, gw = slot * s.reps.length;
      var pts = s.reps.map(function (t, i) { return [gx + slot * (i + .5), Y(s.d / t)]; });
      if (lines) {
        // cada série à altura da sua velocidade média (mais rápida = mais acima)
        // e com as variações ampliadas dentro de uma faixa, para o ritmo se ver
        var amp = ch * .17, avgSp = s.d / s.avg;
        var dev = Math.max.apply(null, s.reps.map(function (t) { return Math.abs(s.d / t - avgSp); }));
        dev = Math.max(dev, avgSp * .004);
        var mid = avgHi > avgLo ? base - amp - 20 - (avgSp - avgLo) / (avgHi - avgLo) * (ch * .9 - 2 * amp - 20) : base - ch * .45;
        pts = s.reps.map(function (t, i) { return [gx + slot * (i + .5), mid - (s.d / t - avgSp) / dev * amp]; });
        s.mid = mid;
        var dot = Math.max(5, Math.min(slot * .2, 12));
        // área por baixo da linha
        ctx.save();
        ctx.globalAlpha = .16;
        ctx.fillStyle = th.accent;
        ctx.beginPath();
        ctx.moveTo(pts[0][0], base);
        pts.forEach(function (p) { ctx.lineTo(p[0], p[1]); });
        ctx.lineTo(pts[pts.length - 1][0], base);
        ctx.closePath();
        ctx.fill();
        ctx.restore();
        ctx.strokeStyle = th.accent;
        ctx.lineWidth = 6;
        ctx.lineJoin = ctx.lineCap = 'round';
        ctx.beginPath();
        pts.forEach(function (p, i) { if (i) ctx.lineTo(p[0], p[1]); else ctx.moveTo(p[0], p[1]); });
        ctx.stroke();
        pts.forEach(function (p, i) {
          ctx.fillStyle = s.pb[i] ? th.pb : th.accent;
          ctx.beginPath(); ctx.arc(p[0], p[1], s.pb[i] ? dot * 1.35 : dot, 0, Math.PI * 2); ctx.fill();
          if (!state.clear) {   // aro da cor do fundo, para os pontos se destacarem da linha
            ctx.strokeStyle = th.bg[1];
            ctx.lineWidth = 3;
            ctx.stroke();
          }
          if (s.pb[i]) pbMark(p[0], p[1] - dot * 1.35 - 14);
        });
      } else {
        pts.forEach(function (p, i) {
          var bx = p[0] - bw / 2, by = p[1];
          ctx.fillStyle = th.accent;
          roundRect(ctx, bx, by, bw, base - by, r);
          ctx.fill();
          ctx.fillRect(bx, base - r, bw, r);   // base reta
          if (s.pb[i] && bw >= 18) pbMark(p[0], by - 12);
        });
      }
      cx += gw;
      // média da série (tracejado)
      var ay = lines ? s.mid : Y(s.d / s.avg);
      ctx.save();
      ctx.setLineDash([10, 8]);
      ctx.strokeStyle = th.ink;
      ctx.globalAlpha = .6;
      ctx.lineWidth = 3;
      ctx.beginPath(); ctx.moveTo(gx - 6, ay); ctx.lineTo(gx + gw + 6, ay); ctx.stroke();
      ctx.restore();
      // etiqueta da série (e a recuperação por baixo)
      font(ctx, 600, 28, COND);
      if (ctx.measureText(s.label).width <= gw + gapSet * .8) {
        ctx.fillStyle = th.muted;
        ctx.textAlign = 'center';
        ctx.fillText(s.label, gx + gw / 2, base + 40);
        if (withRest && s.rest) {
          font(ctx, 500, 22, BODY);
          ctx.fillText(clip(ctx, s.rest, gw + gapSet * .8), gx + gw / 2, base + 72);
        }
        ctx.textAlign = 'left';
      }
      cx += gapSet;
    });
    ctx.fillStyle = th.ink;
    ctx.globalAlpha = .7;
    ctx.fillRect(x, base, w, 3);
    ctx.globalAlpha = 1;
  }

  // em vez do gráfico: os tempos de cada série numa grelha
  function times(ctx, th, x, y, w, h) {
    ctx.fillStyle = th.muted;
    spacing(ctx, 2);
    font(ctx, 600, 26, COND);
    ctx.fillText(data.labels.times.toUpperCase(), x, y + 26);
    spacing(ctx, 0);
    ctx.textAlign = 'right';
    font(ctx, 500, 24, BODY);
    ctx.fillText(data.labels.faster, x + w, y + 25);
    var lw = ctx.measureText(data.labels.faster).width;
    ctx.fillStyle = th.fast;
    ctx.beginPath(); ctx.arc(x + w - lw - 22, y + 17, 9, 0, Math.PI * 2); ctx.fill();
    ctx.textAlign = 'left';

    // cada série: cabeçalho e os tempos em linha; o maior tamanho de letra que couber
    var top = y + 50, avail = y + h - top, headH = 42, setGap = 14;
    function layout(S) {
      font(ctx, 700, S, COND);
      var gapX = S * .62, nW = S * .42, total = 0;
      var sets = data.sets.map(function (s) {
        var lines = [[]], lineW = 0;
        s.txt.forEach(function (t, i) {
          var iw = nW + ctx.measureText(t).width;
          if (lineW && lineW + gapX + iw > w) { lines.push([]); lineW = 0; }
          lines[lines.length - 1].push({ i: i, w: iw });
          lineW += (lineW ? gapX : 0) + iw;
        });
        total += headH + lines.length * S * 1.18;
        return lines;
      });
      total += (data.sets.length - 1) * setGap;
      return { S: S, sets: sets, total: total, gapX: gapX, nW: nW };
    }
    var L;
    // sem limite de altura (imagem ajustada ao conteúdo): tamanho fixo e confortável
    for (var S = isFinite(h) ? 64 : 56; S >= 20; S -= 2) { L = layout(S); if (L.total <= avail) break; }
    var cy = top;
    data.sets.forEach(function (s, k) {
      ctx.fillStyle = th.ink;
      font(ctx, 700, 34, COND);
      ctx.fillText(s.label, x, cy + 30);
      var lx = x + ctx.measureText(s.label).width + 16;
      ctx.fillStyle = th.muted;
      font(ctx, 500, 26, BODY);
      ctx.fillText(clip(ctx, data.labels.avg.toLowerCase() + ' ' + s.avgTxt + (s.rest && state.show.rest ? ' · ' + s.rest : ''), x + w - lx), lx, cy + 29);
      cy += headH;
      L.sets[k].forEach(function (line) {
        var lx2 = x, base = cy + L.S * .95;
        line.forEach(function (it) {
          var t = s.reps[it.i], pb = s.pb[it.i];
          // número da repetição, pequeno, por cima
          ctx.fillStyle = th.muted;
          font(ctx, 600, Math.round(L.S * .36), COND);
          ctx.fillText(it.i + 1, lx2, base - L.S * .5);
          ctx.fillStyle = pb ? th.pb : (t <= s.avg ? th.fast : th.ink);
          font(ctx, 700, L.S, COND);
          ctx.fillText(s.txt[it.i], lx2 + L.nW, base);
          if (pb) {
            ctx.fillRect(lx2 + L.nW, base + L.S * .1, it.w - L.nW, Math.max(3, L.S * .06));
          }
          lx2 += it.w + L.gapX;
        });
        cy += L.S * 1.18;
      });
      cy += setGap;
    });
    return cy - setGap - y;   // altura usada
  }

  // offset: deslocação vertical do conteúdo (para centrar quando sobra espaço)
  // fitH: altura final da imagem ajustada (na 1.ª passagem ainda não se sabe)
  function draw(offset, fitH) {
    var fit = state.format === 'fit';
    var H = fit ? (fitH || 4000) : SIZES[state.format], story = state.format === 'story', th = THEMES[state.theme];
    var sh = state.show, content = state.content;
    // sem o título grande (ou na imagem ajustada) os quadros sobem: a curva da pista fica mais pequena
    var small = fit || !sh.title;
    TRACK.inner = small ? 170 : 270;
    TRACK.lw = small ? 26 : 40;
    var M = 80, top = story ? 180 : 90, bottom = story ? 200 : 80, cw = W - 2 * M, av = story ? 120 : 104;
    canvas.width = W; canvas.height = H;
    var ctx = canvas.getContext('2d');
    ctx.textBaseline = 'alphabetic';

    // fundo transparente: só os textos e os quadros, para pôr sobre uma foto
    if (!state.clear) {
      var bg = ctx.createLinearGradient(0, 0, W * .35, H);
      bg.addColorStop(0, th.bg[0]); bg.addColorStop(1, th.bg[1]);
      ctx.fillStyle = bg;
      ctx.fillRect(0, 0, W, H);
      track(ctx, th);
    }

    var y = top + (offset || 0), y0 = y;   // y0: onde começa o conteúdo (para saber se já há algo antes)
    var hh = header(ctx, th, M, y, av);
    y += hh ? hh + 70 : 0;

    // título: o nome do treino (se houver) por cima das séries
    if (sh.title) {
    if (data.title) {
      ctx.fillStyle = th.accent;
      spacing(ctx, 3);
      font(ctx, 600, story ? 40 : 34, COND);
      ctx.fillText(clip(ctx, data.title.toUpperCase(), cw), M, y);
      spacing(ctx, 0);
      y += story ? 20 : 14;
    }
    ctx.fillStyle = th.ink;
    var big = content === 'times' ? (story ? 160 : 112) : content === 'none' ? (story ? 220 : 190) : (story ? 190 : 160), size = big;
    // cada linha pode ir até à curva da pista à sua altura; o maior tamanho
    // em que o título inteiro cabe em 3 linhas, sem cortar
    var words = data.summary.replace(/ × /g, '×').split(' '), lines;
    function layTitle(sz) {
      font(ctx, 700, sz, COND);
      var out = [''], k = 0;
      var width = function (n) { return Math.min(cw, clearOfTrack(y + n * sz * .88) - M); };
      for (var i = 0; i < words.length; i++) {
        var next = out[k] ? out[k] + ' ' + words[i] : words[i];
        if (ctx.measureText(next).width <= width(k)) { out[k] = next; continue; }
        if (!out[k] || ++k > 2) return null;
        out[k] = words[i];
        if (ctx.measureText(out[k]).width > width(k)) return null;
      }
      return out;
    }
    for (; size > 56; size -= 4) { if ((lines = layTitle(size))) break; }
    if (!lines) { font(ctx, 700, size, COND); lines = wrap(ctx, words.join(' '), cw, 3); }
    font(ctx, 700, size, COND);
    lines.forEach(function (l) { y += size * .88; ctx.fillText(l, M - 4, y); });

    // recuperação por baixo das séries: uma só se for igual em todas, senão série a série
    var rests = data.sets.filter(function (s) { return s.rest; });
    if (sh.rest && rests.length) {
      var same = rests.length === data.sets.length && rests.every(function (s) { return s.rest === rests[0].rest; });
      var rtxt = same ? rests[0].rest : rests.map(function (s) { return s.label + ' ' + s.rest; }).join(' · ');
      ctx.fillStyle = th.muted;
      font(ctx, 500, story ? 40 : 34, BODY);
      var rl = wrap(ctx, rtxt, Math.min(cw, clearOfTrack(y) - M), 2);
      rl.forEach(function (l) { y += story ? 54 : 46; ctx.fillText(l, M, y); });
      y += 6;
    }
    }

    if (data.pbs) {
      // etiqueta dourada; maior na história, que se vê no ecrã inteiro do telemóvel
      var ph = story ? 68 : 52, pf = story ? 40 : 30;
      y += sh.title ? (story ? 40 : 34) : 0;
      var label = (data.pbs > 1 ? data.labels.pbn.replace(':n', data.pbs) : data.labels.pb1).toUpperCase();
      font(ctx, 700, pf, COND);
      spacing(ctx, 2);
      ctx.fillStyle = th.pb;
      roundRect(ctx, M, y, ctx.measureText(label).width + ph * 1.4, ph, ph / 2); ctx.fill();
      ctx.fillStyle = '#16212B';
      ctx.beginPath(); ctx.arc(M + ph * .58, y + ph / 2, ph * .15, 0, Math.PI * 2); ctx.fill();
      ctx.fillText(label, M + ph * .96, y + ph / 2 + pf * .36);
      spacing(ctx, 0);
      y += ph;
    }

    if (sh.stats) {
      y += y > y0 ? 50 : 0;
      y = tiles(ctx, th, M, y, cw, story ? 2 : 4, story);
    }

    var fy, gap = y > y0 ? (story ? 64 : 56) : 0;
    if (fit) {
      // imagem ajustada: o gráfico tem altura fixa e os tempos a que precisarem
      if (content !== 'none') {
        var ct = y + gap;
        y = ct + (content === 'times' ? times(ctx, th, M, ct, cw, Infinity) : (chart(ctx, th, M, ct, cw, 480, content === 'lines'), 480));
      }
      fy = y + 78;
    } else {
      // gráfico ou tempos no espaço que sobra até ao rodapé (ou até ao fundo, sem rodapé)
      fy = H - bottom - 44;
      var chartTop = y + gap, chartH = (sh.footer ? fy - 50 : H - bottom) - chartTop;
      if (content !== 'none' && chartH > 160) {
        if (content === 'times') times(ctx, th, M, chartTop, cw, chartH);
        else chart(ctx, th, M, chartTop, cw, chartH, content === 'lines');
      }
    }

    // rodapé com a marca
    if (sh.footer) {
    ctx.fillStyle = th.grid;
    ctx.fillRect(M, fy - 28, cw, 2);
    brand(ctx, th, M, fy, 44);
    }
    if (sh.footer && data.site) {
      ctx.fillStyle = th.muted;
      font(ctx, 500, 28, BODY);
      ctx.textAlign = 'right';
      ctx.textBaseline = 'middle';
      ctx.fillText(data.site, W - M, fy + 24);
      ctx.textAlign = 'left';
      ctx.textBaseline = 'alphabetic';
    }
    preview.setAttribute('data-format', state.format);
    preview.classList.toggle('is-clear', state.clear);
    return { top: top, end: y, room: (sh.footer ? fy - 28 : H - bottom) - top, need: (sh.footer ? fy + 44 : y) + bottom };
  }

  function render() {
    return Promise.all([fontsReady, avatarReady]).then(function () {
      var r = draw(0);
      // ajustada: 2.ª passagem já com a altura certa
      if (state.format === 'fit') return draw(0, Math.ceil(r.need));
      // sem gráfico nem tempos: o bloco fica a meio, entre o topo e o rodapé
      if (state.content === 'none') {
        var free = r.room - (r.end - r.top);
        if (free > 40) draw(Math.min(free / 2, free - 60));
      }
    });
  }

  function blob() {
    return new Promise(function (resolve) { canvas.toBlob(resolve, 'image/png'); });
  }

  function pick(attr, value) {
    dlg.querySelectorAll('[' + attr + ']').forEach(function (b) {
      var on = b.getAttribute(attr) === value;
      b.classList.toggle('is-on', on);
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  }

  function choose(key, value) {
    state[key] = value;
    try { localStorage.setItem('series.share', JSON.stringify(state)); } catch (e) { /* ignorar */ }
    if (key !== 'clear' && key !== 'show') pick('data-share-' + key, value);
    render();
  }

  dlg.addEventListener('click', function (ev) {
    if (ev.target === dlg) return dlg.close();   // clique no fundo, fora do cartão
    var b = ev.target.closest('button');
    if (!b) return;
    if (b.hasAttribute('data-share-close')) dlg.close();
    else if (b.hasAttribute('data-share-format')) choose('format', b.getAttribute('data-share-format'));
    else if (b.hasAttribute('data-share-theme')) choose('theme', b.getAttribute('data-share-theme'));
    else if (b.hasAttribute('data-share-content')) choose('content', b.getAttribute('data-share-content'));
    else if (b.hasAttribute('data-share-download')) {
      blob().then(function (bl) {
        var a = document.createElement('a');
        a.href = URL.createObjectURL(bl);
        a.download = data.file;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(function () { URL.revokeObjectURL(a.href); }, 4000);
      });
    } else if (b.hasAttribute('data-share-copy')) {
      // o Safari exige a promessa dentro do ClipboardItem, no mesmo clique
      navigator.clipboard.write([new ClipboardItem({ 'image/png': blob() })]).then(function () {
        var label = b.textContent;
        b.textContent = b.getAttribute('data-done');
        setTimeout(function () { b.textContent = label; }, 2000);
      }).catch(function () { /* sem permissão */ });
    } else if (b.hasAttribute('data-share-send')) {
      blob().then(function (bl) {
        var file = new File([bl], data.file, { type: 'image/png' });
        return navigator.share({ files: [file], title: data.title || data.summary });
      }).catch(function () { /* cancelado */ });
    }
  });

  // o botão de partilha nativa só aparece onde o sistema aceita ficheiros (telemóvel)
  try {
    var probe = new File([new Blob(['x'], { type: 'image/png' })], 'x.png', { type: 'image/png' });
    sendBtn.hidden = !(navigator.canShare && navigator.canShare({ files: [probe] }));
  } catch (e) { sendBtn.hidden = true; }
  if (sendBtn.hidden) dlg.querySelector('[data-share-download]').className = 'btn btn--primary';

  var copyBtn = dlg.querySelector('[data-share-copy]');
  copyBtn.hidden = !(window.ClipboardItem && navigator.clipboard && navigator.clipboard.write);

  var clearBox = dlg.querySelector('[data-share-clear]');
  clearBox.addEventListener('change', function () { choose('clear', clearBox.checked); });
  var showBoxes = dlg.querySelectorAll('[data-share-show]');
  showBoxes.forEach(function (box) {
    box.addEventListener('change', function () {
      state.show[box.getAttribute('data-share-show')] = box.checked;
      choose('show', state.show);
    });
  });

  opener.addEventListener('click', function () {
    pick('data-share-format', state.format);
    pick('data-share-theme', state.theme);
    pick('data-share-content', state.content);
    clearBox.checked = state.clear;
    showBoxes.forEach(function (box) { box.checked = state.show[box.getAttribute('data-share-show')]; });
    dlg.showModal();
    render();
  });
})();
