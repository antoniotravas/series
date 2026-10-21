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
      bar.classList.toggle('is-speed', view === 'speed');
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
          li.querySelector('input').value = '';
          li.querySelector('input').classList.remove('is-invalid');
          list.appendChild(li);
        }
      } else {
        // só remove campos vazios do fim, para não apagar tempos já escritos
        for (var j = items.length - 1; j >= n; j--) {
          if (items[j].querySelector('input').value.trim() !== '') { n = j + 1; break; }
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
