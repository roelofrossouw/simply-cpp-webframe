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
    '.sc-chart .sc-bar:hover,.sc-chart .sc-slice:hover{opacity:.8}';
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
  // LineChart::Position() places them
  const positions = function (chart, index) {
    const tops = [];
    let up = 0;
    let down = 0;
    let total = 0;
    if (chart.stack === 'percent')
    {
      chart.series.forEach((series, number) => total += Math.abs(chart.v[number][index] ?? 0));
    }
    chart.series.forEach(function (series, number) {
      const value = chart.v[number][index];
      let shown = value === null || value === undefined ? 0 : (series.neg ? -value : value);
      if (chart.stack === 'percent') shown = total > 0 ? 100 * Math.abs(shown) / total : 0;
      if (!chart.stack)
      {
        tops[number] = value === null || value === undefined ? null : shown;
        return;
      }
      const under = shown >= 0 ? up : down;
      shown >= 0 ? up += shown : down += shown;
      tops[number] = value === null || value === undefined ? null : under + shown;
    });
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
    layer.appendChild(line);
    const hidden = hiddenIn(area.closest('.sc-chart'));
    const rows = [];
    const span = (chart.y.high - chart.y.low) || 1;
    const tops = positions(chart, index);
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
  };
  document.addEventListener('click', toggle, true);
  document.addEventListener('keydown', toggle, true);

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
