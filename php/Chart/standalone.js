// sc-webframe's chart hover, for the app and for pages without its app.js (One Web, any other app): Chart puts this
// inline with the first chart of a request; it runs once per page however often it's loaded. Plain JavaScript.
//
// A line chart has one see-through area over its plot (.sc-hover-area) and one block of data (.sc-chart-data): as the
// pointer moves (or the arrow keys), the nearest time gets a dashed line, a dot on each line and a box with its time and
// each series' colour, name and value, highest first; the time's link and attributes go on the area for a click. A bar,
// slice or a line's link (anything with data-tip) shows its own box. A heatmap (.sc-heat-area) outlines the cell
// pointed at and shows its row, time and value. Series hidden in the legend are left out.
(function () {
  if (window.scChartHover) return;
  window.scChartHover = true;
  const svg = 'http://www.w3.org/2000/svg';

  const style = document.createElement('style');
  style.textContent =
    '.sc-chart-tip{position:fixed;z-index:10900;display:none;padding:.5rem .75rem;border:1px solid var(--bs-border-color,rgba(0,0,0,.15));' +
    'border-radius:.5rem;background:var(--bs-body-bg,#fff);color:var(--bs-body-color,#212529);box-shadow:0 .5rem 1rem rgba(0,0,0,.15);' +
    'font:13px/1.4 system-ui,-apple-system,sans-serif;white-space:pre-line;pointer-events:none}' +
    '.sc-chart-tip.sc-chart-tip-rows{white-space:nowrap}' +
    '.sc-chart-tip-head{margin:-.125rem 0 .375rem;padding-bottom:.375rem;border-bottom:1px solid var(--bs-border-color,rgba(0,0,0,.12));font-weight:600}' +
    '.sc-chart-tip table{border-collapse:collapse}.sc-chart-tip td{padding:.125rem 0}' +
    '.sc-chart-tip td:nth-child(2){padding:.125rem 1.25rem .125rem .5rem;color:var(--bs-secondary-color,#6c757d)}' +
    '.sc-chart-tip i{display:inline-block;width:1rem;height:.25rem;border-radius:.125rem;vertical-align:middle}' +
    '.sc-chart-tip-value{text-align:right;font-weight:600;font-variant-numeric:tabular-nums}' +
    '.sc-chart-tip-more{color:var(--bs-secondary-color,#6c757d);padding-top:.25rem}' +
    '.sc-chart .sc-crosshair{stroke:var(--bs-secondary-color,#6c757d);stroke-width:1;stroke-dasharray:3 3}' +
    '.sc-chart .sc-hover-dot{stroke:var(--bs-body-bg,#fff);stroke-width:2}' +
    '.sc-chart .sc-hover-area{outline:none}.sc-chart .sc-hover-area[data-href]{cursor:pointer}' +
    '.sc-chart .sc-bar:hover,.sc-chart .sc-slice:hover{opacity:.8}' +
    // A table legend's row pointed at brings its series forward; on a phone a table on the right goes under the drawing
    '.sc-chart svg [data-series]{transition:opacity .12s}.sc-chart svg.sc-dim [data-series]:not(.sc-lit){opacity:.15}' +
    '.sc-legend-table tbody tr[data-row]:hover,.sc-legend-table tbody tr[data-row]:focus-visible{background:var(--bs-tertiary-bg,rgba(0,0,0,.04));outline:none}' +
    '.sc-legend-table th[data-sort]:hover{color:var(--bs-body-color,#212529)!important}' +
    '@media (max-width:575.98px){.sc-legend-right{flex-direction:column}.sc-legend-right>.sc-legend-table{flex:none!important}' +
    '.sc-legend-right .sc-legend-scroll{position:static!important;max-height:12rem}}';
  document.head.appendChild(style);

  // The box shows at most this many rows; the rest are counted
  const MOST = 25;
  let tip = null;
  const data = new WeakMap();

  // A value written as the server writes it (Chart::Value()): groups of three, two decimals under 10, one under 1000
  const write = function (value) {
    if (value === null || value === undefined) return '–';
    const places = Number.isInteger(value) ? 0 : Math.abs(value) < 10 ? 2 : Math.abs(value) < 1000 ? 1 : 0;
    let text = value.toFixed(places);
    if (places > 0) text = text.replace(/\.?0+$/, '');
    if (/^-0(\.0*)?$/.test(text)) text = '0';
    const [whole, part] = text.split('.');
    return whole.replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + (part ? '.' + part : '');
  };
  const hiddenIn = function (chart) {
    const hidden = new Set();
    chart.querySelectorAll('.sc-legend-item.sc-off[data-series]').forEach(item => hidden.add(item.getAttribute('data-series')));
    return hidden;
  };
  const box = function () {
    if (!tip)
    {
      tip = document.createElement('div');
      tip.className = 'sc-chart-tip';
      tip.setAttribute('role', 'tooltip');
      document.body.appendChild(tip);
    }
    return tip;
  };
  // The box from rows of {n, c, v}: a header, then a row each (the most first when sorted)
  const fill = function (head, rows, sorted) {
    box().replaceChildren();
    if (!rows.length) return false;
    if (sorted) rows = rows.slice().sort((a, b) => (b.r ?? -Infinity) - (a.r ?? -Infinity));
    tip.classList.add('sc-chart-tip-rows');
    if (head)
    {
      const header = document.createElement('div');
      header.className = 'sc-chart-tip-head';
      header.textContent = head;
      tip.appendChild(header);
    }
    const table = document.createElement('table');
    for (const row of rows.slice(0, MOST)) {
      const line = table.insertRow();
      const mark = document.createElement('i');
      mark.style.background = row.c;
      line.insertCell().appendChild(mark);
      line.insertCell().textContent = row.n;
      const value = line.insertCell();
      value.className = 'sc-chart-tip-value';
      value.textContent = row.v;
    }
    tip.appendChild(table);
    if (rows.length > MOST)
    {
      const more = document.createElement('div');
      more.className = 'sc-chart-tip-more';
      more.textContent = '+ ' + (rows.length - MOST) + ' more';
      tip.appendChild(more);
    }
    return true;
  };
  const place = function (x, y) {
    if (!tip || tip.style.display !== 'block') return;
    const gap = 16;
    let left = x + gap;
    let top = y + gap;
    if (left + tip.offsetWidth > window.innerWidth - 4) left = x - tip.offsetWidth - gap;
    if (top + tip.offsetHeight > window.innerHeight - 4) top = y - tip.offsetHeight - gap;
    tip.style.left = Math.max(4, left) + 'px';
    tip.style.top = Math.max(4, top) + 'px';
  };
  const hide = function () {
    if (tip) tip.style.display = 'none';
  };
  const layerOf = function (drawing) {
    let layer = drawing.querySelector('g.sc-hover');
    if (!layer)
    {
      layer = document.createElementNS(svg, 'g');
      layer.setAttribute('class', 'sc-hover');
      layer.setAttribute('pointer-events', 'none');
      drawing.appendChild(layer);
    }
    return layer;
  };
  const clear = function (area) {
    hide();
    const layer = area.ownerSVGElement && area.ownerSVGElement.querySelector('g.sc-hover');
    if (layer) layer.replaceChildren();
    area.removeAttribute('data-href');
  };

  // A line chart's data block, read once
  const dataOf = function (area) {
    if (data.has(area)) return data.get(area);
    const block = area.ownerSVGElement.querySelector('script.sc-chart-data');
    const parsed = block ? JSON.parse(block.textContent) : null;
    // Each series' values come as runs: value, count, value, count ...
    const expand = function (runs) {
      const values = [];
      for (let at = 0; at < runs.length; at += 2) {
        for (let times = 0; times < runs[at + 1]; times++) values.push(runs[at]);
      }
      return values;
    };
    if (parsed)
    {
      if (parsed.v) parsed.v = parsed.v.map(expand);
      if (parsed.f) parsed.f = parsed.f.map(expand);
    }
    data.set(area, parsed);
    return parsed;
  };
  // The label nearest to x (in the drawing's units)
  const nearest = function (xs, x) {
    let low = 0;
    let high = xs.length - 1;
    while (high - low > 1)
    {
      const middle = (low + high) >> 1;
      xs[middle] < x ? low = middle : high = middle;
    }
    return Math.abs(xs[low] - x) <= Math.abs(xs[high] - x) ? low : high;
  };
  // Where each series' point is drawn at a label: its value, below zero (neg), stacked (normal, percent), as
  // LineChart::Position() places them; series hidden in the legend left out of the stack (and of percent's total).
  // What each stands on comes along as tops.unders.
  const positions = function (chart, index, hidden) {
    const tops = [];
    const unders = [];
    const out = number => hidden !== undefined && hidden.has(String(number));
    let up = 0;
    let down = 0;
    let total = 0;
    if (chart.stack === 'percent')
    {
      chart.series.forEach((series, number) => total += out(number) ? 0 : Math.abs(chart.v[number][index] ?? 0));
    }
    chart.series.forEach(function (series, number) {
      const value = chart.v[number][index];
      unders[number] = 0;
      if (out(number))
      {
        tops[number] = null;
        return;
      }
      let shown = value === null || value === undefined ? 0 : (series.neg ? -value : value);
      if (chart.stack === 'percent') shown = total > 0 ? 100 * Math.abs(shown) / total : 0;
      if (!chart.stack)
      {
        tops[number] = value === null || value === undefined ? null : shown;
        return;
      }
      const under = shown >= 0 ? up : down;
      shown >= 0 ? up += shown : down += shown;
      unders[number] = under;
      tops[number] = value === null || value === undefined ? null : under + shown;
    });
    tops.unders = unders;
    return tops;
  };
  // Show label `index` of a line chart: crosshair, dots, box (near the pointer, or the crosshair for keys), link
  const showAt = function (area, index, clientX, clientY) {
    const chart = dataOf(area);
    if (!chart || !chart.x.length) return;
    area.scIndex = index;
    const drawing = area.ownerSVGElement;
    const layer = layerOf(drawing);
    layer.replaceChildren();
    const x = chart.x[index];
    const line = document.createElementNS(svg, 'line');
    line.setAttribute('x1', x);
    line.setAttribute('x2', x);
    line.setAttribute('y1', chart.y.top);
    line.setAttribute('y2', chart.y.bottom);
    line.setAttribute('class', 'sc-crosshair');
    line.setAttribute('vector-effect', 'non-scaling-stroke');
    layer.appendChild(line);
    const hidden = hiddenIn(area.closest('.sc-chart'));
    const rows = [];
    const span = (chart.y.high - chart.y.low) || 1;
    const tops = positions(chart, index, hidden);
    chart.series.forEach(function (series, number) {
      if (hidden.has(String(number))) return;
      const value = chart.v[number][index];
      if (value === null || value === undefined) return;
      const dot = document.createElementNS(svg, 'circle');
      dot.setAttribute('cx', x);
      dot.setAttribute('cy', chart.y.bottom - (tops[number] - chart.y.low) / span * (chart.y.bottom - chart.y.top));
      dot.setAttribute('r', 4);
      dot.setAttribute('fill', series.c);
      dot.setAttribute('class', 'sc-hover-dot');
      layer.appendChild(dot);
      rows.push({n: series.n, c: series.c, v: chart.f ? chart.f[number][index] : write(value), r: value});
    });
    // The time's page and the attributes an app's click reads, on the area until the next move
    const href = chart.href ? chart.href[index] : null;
    href ? area.setAttribute('data-href', href) : area.removeAttribute('data-href');
    for (const name in chart.attrs || {}) {
      const value = chart.attrs[name][index];
      value === null || value === undefined ? area.removeAttribute(name) : area.setAttribute(name, value);
    }
    if (!fill(chart.head[index], rows, true))
    {
      hide();
      return;
    }
    tip.style.display = 'block';
    if (clientX === undefined)
    {
      const point = drawing.createSVGPoint();
      point.x = x;
      point.y = chart.y.top;
      const screen = point.matrixTransform(drawing.getScreenCTM());
      clientX = screen.x;
      clientY = screen.y + 20;
    }
    place(clientX, clientY);
  };
  const pointedAt = function (area, event) {
    const chart = dataOf(area);
    if (!chart || !chart.x.length) return;
    const drawing = area.ownerSVGElement;
    const point = drawing.createSVGPoint();
    point.x = event.clientX;
    point.y = event.clientY;
    const inside = point.matrixTransform(drawing.getScreenCTM().inverse());
    showAt(area, nearest(chart.x, inside.x), event.clientX, event.clientY);
  };

  // A heatmap (php/Chart/Heatmap.php): the cell under the pointer outlined, and a box with its row, time and value
  const heatAt = function (area, event) {
    const chart = dataOf(area);
    if (!chart || !chart.heat) return;
    const drawing = area.ownerSVGElement;
    const point = drawing.createSVGPoint();
    point.x = event.clientX;
    point.y = event.clientY;
    const inside = point.matrixTransform(drawing.getScreenCTM().inverse());
    const column = Math.min(chart.columns - 1, Math.max(0, Math.floor((inside.x - chart.left) / chart.width * chart.columns)));
    const row = Math.min(chart.rows.length - 1, Math.max(0, Math.floor((inside.y - chart.top) / chart.row)));
    const layer = layerOf(drawing);
    layer.replaceChildren();
    const value = chart.f[row][column];
    if (value === null || value === undefined)
    {
      hide();
      return;
    }
    const cell = document.createElementNS(svg, 'rect');
    const width = chart.width / chart.columns;
    for (const [name, size] of [['x', chart.left + column * width], ['y', chart.top + row * chart.row], ['width', width], ['height', chart.row]]) {
      cell.setAttribute(name, size);
    }
    cell.setAttribute('fill', 'none');
    cell.setAttribute('stroke', 'currentColor');
    cell.setAttribute('stroke-width', 1.5);
    layer.appendChild(cell);
    fill(chart.rows[row], [{n: chart.heads[column], c: chart.color, v: value}], false);
    tip.style.display = 'block';
    place(event.clientX, event.clientY);
  };
  const heatOf = target => target instanceof Element ? target.closest('.sc-chart .sc-heat-area') : null;
  document.addEventListener('mousemove', function (event) {
    const area = heatOf(event.target);
    if (area) heatAt(area, event);
  });
  document.addEventListener('mouseout', function (event) {
    const area = heatOf(event.target);
    if (area && !(event.relatedTarget instanceof Element && area.contains(event.relatedTarget)))
    {
      hide();
      const layer = area.ownerSVGElement.querySelector('g.sc-hover');
      if (layer) layer.replaceChildren();
    }
  });

  const itemOf = target => target instanceof Element ? target.closest('.sc-chart [data-tip]') : null;
  const areaOf = target => target instanceof Element ? target.closest('.sc-chart .sc-hover-area') : null;

  document.addEventListener('mousemove', function (event) {
    const area = areaOf(event.target);
    if (area)
    {
      pointedAt(area, event);
      return;
    }
    if (itemOf(event.target)) place(event.clientX, event.clientY);
  });
  document.addEventListener('mouseover', function (event) {
    const item = itemOf(event.target);
    if (!item) return;
    // Our box instead of the browser's own tooltip
    const title = item.querySelector(':scope > title');
    if (title) title.remove();
    const rows = item.getAttribute('data-rows');
    if (rows)
    {
      const hidden = hiddenIn(item.closest('.sc-chart'));
      const shown = JSON.parse(rows).filter(row => row.s === undefined || !hidden.has(String(row.s)));
      if (!fill(item.getAttribute('data-head'), shown, item.getAttribute('data-sort') === 'desc'))
      {
        hide();
        return;
      }
    }
    else
    {
      box().classList.remove('sc-chart-tip-rows');
      tip.replaceChildren();
      tip.textContent = item.getAttribute('data-tip');
    }
    tip.style.display = 'block';
    place(event.clientX, event.clientY);
  });
  document.addEventListener('mouseout', function (event) {
    const area = areaOf(event.target);
    if (area && !(event.relatedTarget instanceof Element && area.contains(event.relatedTarget)))
    {
      clear(area);
      return;
    }
    const item = itemOf(event.target);
    if (item && !item.contains(event.relatedTarget)) hide();
  });
  // The arrow keys move along a line chart's times; Escape puts it away
  document.addEventListener('keydown', function (event) {
    const area = areaOf(event.target);
    if (!area) return;
    const chart = dataOf(area);
    if (!chart || !chart.x.length) return;
    const last = chart.x.length - 1;
    let index = area.scIndex ?? last;
    if (event.key === 'ArrowLeft') index = Math.max(0, (area.scIndex ?? last + 1) - 1);
    else if (event.key === 'ArrowRight') index = Math.min(last, (area.scIndex ?? -1) + 1);
    else if (event.key === 'Home') index = 0;
    else if (event.key === 'End') index = last;
    else if (event.key === 'Escape')
    {
      clear(area);
      return;
    }
    else return;
    event.preventDefault();
    showAt(area, index);
  });
  document.addEventListener('focusout', function (event) {
    const area = areaOf(event.target);
    if (area) clear(area);
  });
  // Outside sc-webframe's app (app.js does it there): a legend item hides or shows its series, greyed and struck
  // through while hidden; the click goes no further (One Web's drill-down on the chart's cell would take it)
  const toggle = function (event) {
    const item = event.target instanceof Element ? event.target.closest('.sc-chart-legend .sc-legend-item[data-series]') : null;
    if (!item || typeof window.loadPage === 'function') return;
    if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') return;
    event.preventDefault();
    event.stopPropagation();
    const off = item.getAttribute('aria-pressed') === 'true';
    item.setAttribute('aria-pressed', off ? 'false' : 'true');
    item.classList.toggle('sc-off', off);
    item.style.opacity = off ? '.4' : '';
    item.style.textDecoration = off ? 'line-through' : '';
    item.closest('.sc-chart').querySelectorAll('svg [data-series="' + item.getAttribute('data-series') + '"]').forEach(function (element) {
      element.style.display = off ? 'none' : '';
    });
    restack(item.closest('.sc-chart'));
  };

  // A stacked chart without the series hidden in its legend: the others stacked again where they'd be without them, as
  // LineChart::Position() and BarChart::Plot() stack them (percent: their shares of what's shown); the axis stays as it
  // is. With nothing hidden the server's drawing comes back. app.js calls it in the app (window.scChartRestack).
  const drawnAs = new WeakMap();
  const keepDrawn = function (element, name) {
    let saved = drawnAs.get(element);
    if (!saved)
    {
      saved = {};
      drawnAs.set(element, saved);
    }
    if (!(name in saved)) saved[name] = element.getAttribute(name);
  };
  const putBack = function (element) {
    const saved = drawnAs.get(element);
    if (!saved) return;
    Object.keys(saved).forEach(function (name) {
      saved[name] === null ? element.removeAttribute(name) : element.setAttribute(name, saved[name]);
    });
  };
  const restackLines = function (drawing, chart, hidden) {
    const count = chart.x.length;
    const span = (chart.y.high - chart.y.low) || 1;
    const y = value => Math.round(chart.y.bottom - (value - chart.y.low) / span * (chart.y.bottom - chart.y.top));
    const at = [];
    for (let index = 0; index < count; index++) at[index] = positions(chart, index, hidden);
    drawing.querySelectorAll('g[data-series]').forEach(function (group) {
      const number = parseInt(group.getAttribute('data-series'), 10);
      group.querySelectorAll('[data-run], circle[data-i]').forEach(function (element) {
        // A line drawn by <use> (OpenSeries()) has its shape in <defs>
        const href = element.tagName.toLowerCase() === 'use' ? element.getAttribute('href') : null;
        const shape = href ? drawing.querySelector(href.replace(/[^#\w-]/g, '')) : element;
        if (!shape) return;
        keepDrawn(shape, element.tagName.toLowerCase() === 'circle' ? 'cy' : 'd');
        if (!hidden.size || hidden.has(String(number)))
        {
          putBack(shape);
          return;
        }
        if (element.tagName.toLowerCase() === 'circle')
        {
          const top = at[parseInt(element.getAttribute('data-i'), 10)][number];
          if (top !== null) element.setAttribute('cy', y(top));
          return;
        }
        const [from, to] = element.getAttribute('data-run').split(' ').map(Number);
        const points = [];
        for (let index = from; index <= to; index++) points.push(chart.x[index] + ' ' + y(at[index][number] ?? 0));
        let path = 'M' + points.join('L');
        if (element.getAttribute('stroke') === 'none')
        {
          // An area: down to what it stands on, back from right to left
          const lower = [];
          for (let index = to; index >= from; index--) lower.push(chart.x[index] + ' ' + y(at[index].unders[number]));
          path += 'L' + lower.join('L') + 'Z';
        }
        shape.setAttribute('d', path);
      });
    });
  };
  const restackBars = function (drawing, stack, hidden) {
    const [low, high, top, bottom] = stack.getAttribute('data-y').split(' ').map(Number);
    const span = (high - low) || 1;
    const y = value => bottom - (value - low) / span * (bottom - top);
    const ups = {};
    const downs = {};
    // In the document's order: a label's bars in the series' order, as drawn
    drawing.querySelectorAll('rect.sc-bar[data-i]').forEach(function (bar) {
      keepDrawn(bar, 'y');
      keepDrawn(bar, 'height');
      if (!hidden.size || hidden.has(bar.getAttribute('data-series')))
      {
        putBack(bar);
        return;
      }
      const index = bar.getAttribute('data-i');
      const value = parseFloat(bar.getAttribute('data-v')) || 0;
      const from = value >= 0 ? ups[index] || 0 : downs[index] || 0;
      value >= 0 ? ups[index] = from + value : downs[index] = from + value;
      const upper = y(Math.max(from, from + value));
      bar.setAttribute('y', upper.toFixed(2));
      bar.setAttribute('height', Math.max(0.5, y(Math.min(from, from + value)) - upper).toFixed(2));
    });
  };
  const restack = function (chart) {
    const drawing = chart && chart.querySelector('svg');
    if (!drawing) return;
    const hidden = hiddenIn(chart);
    const area = drawing.querySelector('.sc-hover-area');
    const lines = area ? dataOf(area) : null;
    if (lines && lines.stack) restackLines(drawing, lines, hidden);
    const stack = drawing.querySelector('.sc-stack[data-y]');
    if (stack) restackBars(drawing, stack, hidden);
  };
  window.scChartRestack = restack;
  document.addEventListener('click', toggle, true);
  document.addEventListener('keydown', toggle, true);

  // A table legend (Chart::Legend('table')), in the app and outside it. Its rows sorted again by a column: names A-Z,
  // numbers highest first, the same column again turning it round; rows without a value last, Others always last.
  const sortLegend = function (table, key, desc) {
    const body = table && table.tBodies[0];
    if (!body) return;
    const heads = Array.from(table.querySelectorAll('th[data-sort]'));
    const column = heads.findIndex(function (head) { return head.getAttribute('data-sort') === key; });
    if (column < 0) return;
    const rows = Array.from(body.rows);
    const number = function (row) { return parseInt(row.getAttribute('data-row'), 10) || 0; };
    const series = rows.filter(function (row) { return !row.hasAttribute('data-others'); });
    series.sort(function (a, b) {
      let by;
      if (key === 'name')
      {
        by = a.cells[0].textContent.localeCompare(b.cells[0].textContent, undefined, {numeric: true, sensitivity: 'base'});
        if (desc) by = -by;
      }
      else
      {
        const x = a.cells[column].getAttribute('data-value') || '', y = b.cells[column].getAttribute('data-value') || '';
        by = x === '' || y === '' ? (x === '') - (y === '') : (desc ? parseFloat(y) - parseFloat(x) : parseFloat(x) - parseFloat(y));
      }
      return by || number(a) - number(b);
    });
    series.concat(rows.filter(function (row) { return row.hasAttribute('data-others'); })).forEach(function (row) {
      body.appendChild(row);
    });
    heads.forEach(function (head) {
      const on = head.getAttribute('data-sort') === key;
      head.setAttribute('aria-sort', on ? (desc ? 'descending' : 'ascending') : 'none');
      const arrow = head.querySelector('.sc-sort-arrow');
      if (arrow) arrow.textContent = on ? (desc ? ' \u2193' : ' \u2191') : '';
    });
  };
  // For app.js: a dashboard refreshed keeps its legends' sort
  window.scChartSortLegend = sortLegend;
  const sortBy = function (event) {
    const head = event.target instanceof Element ? event.target.closest('.sc-legend-table th[data-sort]') : null;
    if (!head) return;
    if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') return;
    event.preventDefault();
    event.stopPropagation();
    const now = head.getAttribute('aria-sort');
    sortLegend(head.closest('table'), head.getAttribute('data-sort'), now === 'none' ? head.getAttribute('data-sort') !== 'name' : now === 'ascending');
  };
  document.addEventListener('click', sortBy, true);
  document.addEventListener('keydown', sortBy, true);
  // A row pointed at (or focused): its series at full strength, the others dimmed
  const light = function (row, on) {
    const chart = row.closest('.sc-chart');
    const drawing = chart && chart.querySelector('svg');
    if (!drawing) return;
    drawing.querySelectorAll('.sc-lit').forEach(function (element) { element.classList.remove('sc-lit'); });
    drawing.classList.toggle('sc-dim', on);
    if (on)
    {
      drawing.querySelectorAll('[data-series="' + row.getAttribute('data-row') + '"]').forEach(function (element) {
        element.classList.add('sc-lit');
      });
    }
  };
  const rowOf = function (target) {
    return target instanceof Element ? target.closest('.sc-legend-table tbody tr[data-row]') : null;
  };
  ['mouseover', 'focusin'].forEach(function (type) {
    document.addEventListener(type, function (event) {
      const row = rowOf(event.target);
      if (row && rowOf(event.relatedTarget) !== row) light(row, true);
    });
  });
  ['mouseout', 'focusout'].forEach(function (type) {
    document.addEventListener(type, function (event) {
      const row = rowOf(event.target);
      if (row && rowOf(event.relatedTarget) !== row) light(row, false);
    });
  });

  // Text at its own size on screen. A chart is drawn at its Size() and scales to its container's width, so a wide panel
  // would draw its labels (axis ticks, row names, annotations, a pie's total, messages) larger, and a narrow one smaller.
  // Stretched wider, each text gets the size that shows it at its nominal pixels; squeezed narrower it shrinks with the
  // drawing as before, as the margins and label spacing were laid out for that size. Each chart is measured when it
  // appears (also loaded later, by AJAX) and when its width changes. Lines don't need it: they're drawn non-scaling.
  const nominal = new WeakMap();
  const sizeText = function (drawing, stretch) {
    drawing.querySelectorAll('text').forEach(function (text) {
      if (!nominal.has(text))
      {
        const own = text.closest('[font-size]');
        nominal.set(text, parseFloat(own ? own.getAttribute('font-size') : '') || 11);
      }
      text.style.fontSize = (nominal.get(text) / stretch).toFixed(2) + 'px';
    });
  };

  // A chart with axes (data-plot: its plot's left, top, right and bottom) also gets the space around its labels in
  // screen pixels, as Grafana draws it: the x labels a few pixels under the plot (under the annotation markers, drawn at
  // their own size), the y labels a few pixels left of it, and the drawing trimmed to them and a two-pixel edge. The
  // positions are worked out from those the server drew, so this can run again (a resize) and comes back to them when
  // the chart isn't stretched.
  const drawn = new WeakMap();
  const placeLabels = function (drawing, saved, plot, stretch) {
    const [left, top, right, bottom] = plot;
    const px = function (pixels) { return pixels / stretch; };
    const markers = drawing.querySelectorAll('.sc-annotation-marker');
    markers.forEach(function (marker) {
      const tip = (marker.getAttribute('points') || '').trim().split(/\s+/)[2];
      const x = tip ? parseFloat(tip.split(',')[0]) : 0;
      marker.setAttribute('transform', 'translate(' + x + ' ' + bottom + ') scale(' + (1 / stretch).toFixed(4) + ') translate(' + -x + ' ' + -bottom + ')');
    });
    const under = markers.length ? 12 : 3;
    let minX = left, maxX = right, minY = top, maxY = bottom + px(markers.length ? 10 : 0);
    drawing.querySelectorAll('.sc-x-label').forEach(function (text) {
      // Its top `under` pixels below the plot: placed, then moved by what its box says (fonts' ascents differ)
      text.setAttribute('y', (bottom + px(under + (nominal.get(text) || 11))).toFixed(2));
      const box = text.getBBox();
      if (box.height) text.setAttribute('y', (parseFloat(text.getAttribute('y')) + bottom + px(under) - box.y).toFixed(2));
    });
    drawing.querySelectorAll('.sc-y-label').forEach(function (text) {
      const at = saved.at.get(text);
      if (!at) return;
      text.setAttribute('x', (left - px(5)).toFixed(2));
      text.setAttribute('y', (at.y - 4 + px(0.35 * (nominal.get(text) || 11))).toFixed(2));
    });
    drawing.querySelectorAll('.sc-x-label, .sc-y-label').forEach(function (text) {
      const box = text.getBBox();
      if (!box.width) return;
      minX = Math.min(minX, box.x);
      maxX = Math.max(maxX, box.x + box.width);
      minY = Math.min(minY, box.y);
      maxY = Math.max(maxY, box.y + box.height);
    });
    const [x0, y0, w0, h0] = saved.view;
    const edge = px(2);
    const x = Math.max(x0, minX - edge), y = Math.max(y0, minY - edge);
    return [x, y, Math.min(x0 + w0, maxX + edge) - x, Math.min(y0 + h0, maxY + edge) - y];
  };
  const restore = function (drawing, saved) {
    drawing.setAttribute('viewBox', saved.view.join(' '));
    drawing.setAttribute('height', saved.height);
    saved.at.forEach(function (at, element) {
      if (at.x !== null) element.setAttribute('x', at.x);
      if (at.y !== null) element.setAttribute('y', at.y);
    });
    drawing.querySelectorAll('.sc-annotation-marker').forEach(function (marker) {
      marker.removeAttribute('transform');
    });
  };
  const fit = function (drawing) {
    const width = drawing.getBoundingClientRect().width;
    if (!width) return;
    let saved = drawn.get(drawing);
    if (!saved)
    {
      const view = drawing.viewBox && drawing.viewBox.baseVal;
      if (!view || !view.width) return;
      // The height attribute too: with height:auto the browser takes the shape from width and height, not the viewBox
      saved = {view: [view.x, view.y, view.width, view.height], height: drawing.getAttribute('height'), at: new Map()};
      drawing.querySelectorAll('.sc-x-label, .sc-y-label').forEach(function (element) {
        saved.at.set(element, {x: element.hasAttribute('x') ? parseFloat(element.getAttribute('x')) : null,
          y: element.hasAttribute('y') ? parseFloat(element.getAttribute('y')) : null});
      });
      drawn.set(drawing, saved);
    }
    const plot = (drawing.getAttribute('data-plot') || '').split(' ').map(Number);
    let stretch = Math.max(1, width / saved.view[2]);
    if (stretch === 1 || plot.length !== 4 || plot.some(isNaN))
    {
      if (plot.length === 4) restore(drawing, saved);
      sizeText(drawing, stretch);
      return;
    }
    // Trimming the drawing stretches it further, which makes the text smaller in it again: settle in a few rounds
    let view = saved.view;
    for (let round = 0; round < 4; round++)
    {
      sizeText(drawing, stretch);
      view = placeLabels(drawing, saved, plot, stretch);
      const next = Math.max(1, width / view[2]);
      if (Math.abs(next - stretch) < 0.005) break;
      stretch = next;
    }
    drawing.setAttribute('viewBox', view.map(function (n) { return n.toFixed(2); }).join(' '));
    // The trimmed shape, so the drawing isn't letterboxed into the old one
    const across = parseFloat(drawing.getAttribute('width')) || saved.view[2];
    drawing.setAttribute('height', (across * view[3] / view[2]).toFixed(2));
  };
  const resized = typeof ResizeObserver === 'function' ? new ResizeObserver(function (entries) {
    entries.forEach(function (entry) {
      fit(entry.target);
    });
  }) : null;
  const watched = new WeakSet();
  const watch = function (root) {
    if (!root.querySelectorAll) return;
    const found = root.matches && root.matches('.sc-chart svg[viewBox]') ? [root] : root.querySelectorAll('.sc-chart svg[viewBox]');
    found.forEach(function (drawing) {
      if (watched.has(drawing)) return;
      watched.add(drawing);
      resized ? resized.observe(drawing) : fit(drawing);
    });
  };
  watch(document);
  if (typeof MutationObserver === 'function')
  {
    new MutationObserver(function (changes) {
      changes.forEach(function (change) {
        change.addedNodes.forEach(watch);
      });
    }).observe(document.documentElement, {childList: true, subtree: true});
  }
  window.addEventListener('resize', function () {
    if (!resized) document.querySelectorAll('.sc-chart svg[viewBox]').forEach(fit);
  });

  // A click takes the box away; outside sc-webframe's app a time's link is followed here (app.js follows it in the app)
  document.addEventListener('click', function (event) {
    hide();
    const area = areaOf(event.target);
    if (area && area.getAttribute('data-href') && typeof window.loadPage !== 'function')
    {
      window.location.href = area.getAttribute('data-href');
    }
  }, true);
  document.addEventListener('keydown', function (event) {
    const area = areaOf(event.target);
    if (area && event.key === 'Enter' && area.getAttribute('data-href') && typeof window.loadPage !== 'function')
    {
      window.location.href = area.getAttribute('data-href');
    }
  });
})();
