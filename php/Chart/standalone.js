// sc-webframe's chart hover for pages without its app.js (One Web, any other app): Chart puts this inline once per
// request when it draws outside the app. It runs once per page however often it's loaded. Plain JavaScript, no jQuery.
// On a line chart's strip: a dashed line at the time pointed at, a dot on each line, and a box with the time and each
// series' colour, name and value (highest first); on a bar or slice its own box; series hidden in the legend left out.
(function () {
  if (window.scChartHover) return;
  window.scChartHover = true;
  const svg = 'http://www.w3.org/2000/svg';

  const style = document.createElement('style');
  style.textContent =
    '.sc-chart-tip{position:fixed;z-index:10900;display:none;padding:.5rem .75rem;border:1px solid rgba(0,0,0,.15);' +
    'border-radius:.5rem;background:#fff;color:#212529;box-shadow:0 .5rem 1rem rgba(0,0,0,.15);font:13px/1.4 system-ui,' +
    '-apple-system,sans-serif;white-space:pre-line;pointer-events:none}' +
    '.sc-chart-tip.sc-chart-tip-rows{white-space:nowrap}' +
    '.sc-chart-tip-head{margin:-.125rem 0 .375rem;padding-bottom:.375rem;border-bottom:1px solid rgba(0,0,0,.12);font-weight:600}' +
    '.sc-chart-tip table{border-collapse:collapse}.sc-chart-tip td{padding:.125rem 0}' +
    '.sc-chart-tip td:nth-child(2){padding:.125rem 1.25rem .125rem .5rem;color:#6c757d}' +
    '.sc-chart-tip i{display:inline-block;width:1rem;height:.25rem;border-radius:.125rem;vertical-align:middle}' +
    '.sc-chart-tip-value{text-align:right;font-weight:600;font-variant-numeric:tabular-nums}' +
    '.sc-chart .sc-crosshair{stroke:#6c757d;stroke-width:1;stroke-dasharray:3 3}' +
    '.sc-chart .sc-hover-dot{stroke:#fff;stroke-width:2}' +
    '.sc-chart .sc-bar:hover,.sc-chart .sc-slice:hover{opacity:.8}';
  document.head.appendChild(style);

  let tip = null;
  const hiddenIn = function (chart) {
    const hidden = new Set();
    chart.querySelectorAll('.sc-legend-item.sc-off[data-series]').forEach(item => hidden.add(item.getAttribute('data-series')));
    return hidden;
  };
  const fill = function (item) {
    tip.replaceChildren();
    const rows = item.getAttribute('data-rows');
    if (!rows)
    {
      tip.classList.remove('sc-chart-tip-rows');
      tip.textContent = item.getAttribute('data-tip');
      return true;
    }
    const hidden = hiddenIn(item.closest('.sc-chart'));
    let shown = JSON.parse(rows).filter(row => row.s === undefined || !hidden.has(String(row.s)));
    if (item.getAttribute('data-sort') === 'desc')
    {
      shown = shown.slice().sort((a, b) => (b.r ?? -Infinity) - (a.r ?? -Infinity));
    }
    if (!shown.length) return false;
    tip.classList.add('sc-chart-tip-rows');
    const head = item.getAttribute('data-head');
    if (head)
    {
      const header = document.createElement('div');
      header.className = 'sc-chart-tip-head';
      header.textContent = head;
      tip.appendChild(header);
    }
    const table = document.createElement('table');
    for (const row of shown) {
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
    return true;
  };
  const crosshair = function (band) {
    const drawing = band.ownerSVGElement;
    let layer = drawing.querySelector('g.sc-hover');
    if (!layer)
    {
      layer = document.createElementNS(svg, 'g');
      layer.setAttribute('class', 'sc-hover');
      layer.setAttribute('pointer-events', 'none');
      drawing.appendChild(layer);
    }
    layer.replaceChildren();
    const x = band.getAttribute('data-x');
    const top = parseFloat(band.getAttribute('y'));
    const line = document.createElementNS(svg, 'line');
    for (const [name, value] of [['x1', x], ['x2', x], ['y1', top], ['y2', top + parseFloat(band.getAttribute('height'))]]) {
      line.setAttribute(name, value);
    }
    line.setAttribute('class', 'sc-crosshair');
    layer.appendChild(line);
    const hidden = hiddenIn(band.closest('.sc-chart'));
    for (const row of JSON.parse(band.getAttribute('data-rows') || '[]')) {
      if (row.y === undefined || hidden.has(String(row.s))) continue;
      const dot = document.createElementNS(svg, 'circle');
      dot.setAttribute('cx', x);
      dot.setAttribute('cy', row.y);
      dot.setAttribute('r', 4);
      dot.setAttribute('fill', row.c);
      dot.setAttribute('class', 'sc-hover-dot');
      layer.appendChild(dot);
    }
  };
  const move = function (event) {
    if (!tip || tip.style.display !== 'block') return;
    const gap = 16;
    let x = event.clientX + gap;
    let y = event.clientY + gap;
    if (x + tip.offsetWidth > window.innerWidth - 4) x = event.clientX - tip.offsetWidth - gap;
    if (y + tip.offsetHeight > window.innerHeight - 4) y = event.clientY - tip.offsetHeight - gap;
    tip.style.left = Math.max(4, x) + 'px';
    tip.style.top = Math.max(4, y) + 'px';
  };
  const hide = function () {
    if (tip) tip.style.display = 'none';
  };
  const itemOf = target => target instanceof Element ? target.closest('.sc-chart [data-tip]') : null;

  document.addEventListener('mouseover', function (event) {
    const item = itemOf(event.target);
    if (!item) return;
    // Our box instead of the browser's own tooltip
    const title = item.querySelector(':scope > title');
    if (title) title.remove();
    if (!tip)
    {
      tip = document.createElement('div');
      tip.className = 'sc-chart-tip';
      tip.setAttribute('role', 'tooltip');
      document.body.appendChild(tip);
    }
    if (item.matches('.sc-band[data-x]')) crosshair(item);
    if (!fill(item))
    {
      hide();
      return;
    }
    tip.style.display = 'block';
    move(event);
  });
  document.addEventListener('mousemove', function (event) {
    if (itemOf(event.target)) move(event);
  });
  document.addEventListener('mouseout', function (event) {
    const from = itemOf(event.target);
    if (!from || from.contains(event.relatedTarget)) return;
    hide();
    const drawing = from.ownerSVGElement;
    if (drawing && !(event.relatedTarget instanceof Element && drawing.contains(event.relatedTarget)))
    {
      const layer = drawing.querySelector('g.sc-hover');
      if (layer) layer.replaceChildren();
    }
  });
  // A click (One Web's drill-down opens a popup) takes the box away
  document.addEventListener('click', hide, true);
})();
