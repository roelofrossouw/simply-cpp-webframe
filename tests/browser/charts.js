const out = [];
const check = (ok, what) => out.push((ok ? 'ok   ' : 'FAIL ') + what);
const data = id => JSON.parse(document.querySelector('#' + id + ' script.sc-chart-data').textContent);
const yOf = (d, v) => Math.round(d.y.bottom - (v - d.y.low) / ((d.y.high - d.y.low) || 1) * (d.y.bottom - d.y.top));
const firstY = path => parseFloat(path.getAttribute('d').match(/^M\s*[\d.]+[ ,]([\d.]+)/)[1]);
const ys = path => [...path.getAttribute('d').matchAll(/[ML]\s*([\d.]+)[ ,]([\d.]+)/g)].map(m => +m[2]);
const click = el => el.dispatchEvent(new MouseEvent('click', {bubbles: true, cancelable: true}));
const legend = (id, n) => document.querySelector('#' + id + ' .sc-legend-item[data-series="' + n + '"]');
const line = (id, n) => document.querySelector('#' + id + ' g[data-series="' + n + '"] path[fill="none"]');
const area = (id, n) => document.querySelector('#' + id + ' g[data-series="' + n + '"] path[stroke="none"]');
try {
  // Normal stack: hide a, b sits on zero, c on b
  let d = data('A');
  const before = {b: line('A', 1).getAttribute('d'), bArea: area('A', 1).getAttribute('d'), c: line('A', 2).getAttribute('d')};
  check(firstY(line('A', 1)) === yOf(d, 5), 'drawn: b on a (1+4)');
  click(legend('A', 0));
  check(legend('A', 0).classList.contains('sc-off'), 'a hidden');
  check(JSON.stringify(ys(line('A', 1))) === JSON.stringify([4, 5, 6].map(v => yOf(d, v))), 'b alone: its own values ' + ys(line('A', 1)));
  check(JSON.stringify(ys(line('A', 2))) === JSON.stringify([5, 6, 7].map(v => yOf(d, v))), 'c on b: 5 6 7');
  const bArea = ys(area('A', 1));
  check(bArea.slice(3).every(v => v === yOf(d, 0)), 'b\'s area down to zero ' + bArea);
  click(legend('A', 0));
  check(line('A', 1).getAttribute('d') === before.b && area('A', 1).getAttribute('d') === before.bArea && line('A', 2).getAttribute('d') === before.c, 'shown again: the server\'s drawing back');
  click(legend('A', 1));
  check(JSON.stringify(ys(line('A', 2))) === JSON.stringify([2, 3, 4].map(v => yOf(d, v))), 'b hidden: c on a: 2 3 4');
  click(legend('A', 1));
  // Percent: hide a, b fills 100%
  d = data('P');
  click(legend('P', 0));
  check(ys(line('P', 1)).every(v => v === yOf(d, 100)), 'percent: b alone at 100% ' + ys(line('P', 1)));
  // Bars: hide a, b from zero
  const bar = (n, i) => document.querySelectorAll('#B rect.sc-bar[data-series="' + n + '"]')[i];
  const stack = document.querySelector('#B .sc-stack').getAttribute('data-y').split(' ').map(Number);
  const by = v => stack[3] - (v - stack[0]) / (stack[1] - stack[0]) * (stack[3] - stack[2]);
  const was = [bar(1, 0).getAttribute('y'), bar(1, 0).getAttribute('height')];
  click(legend('B', 0));
  check(Math.abs(+bar(1, 0).getAttribute('y') - by(3)) < 0.01 && Math.abs(+bar(1, 0).getAttribute('height') - (by(0) - by(3))) < 0.01, 'bars: b from zero');
  check(Math.abs(+bar(1, 1).getAttribute('y') - by(4)) < 0.01, 'bars: second label too');
  click(legend('B', 0));
  check(bar(1, 0).getAttribute('y') === was[0] && bar(1, 0).getAttribute('height') === was[1], 'bars shown again: as drawn');
  // Table legend row toggles the same way
  d = data('T');
  click(document.querySelector('#T tr[data-series="0"]'));
  check(JSON.stringify(ys(line('T', 1))) === JSON.stringify([4, 5, 6].map(v => yOf(d, v))), 'table legend: a hidden, b on zero');
  // Table sort and highlight
  const th = document.querySelector('#T th[data-sort="last"]');
  click(th);
  check(th.getAttribute('aria-sort') === 'descending' && document.querySelector('#T tbody tr').getAttribute('data-row') === '1', 'table: sorted by last, highest first');
  click(th);
  check(th.getAttribute('aria-sort') === 'ascending' && document.querySelector('#T tbody tr').getAttribute('data-row') === '0', 'table: again, turned round');
  click(document.querySelector('#T th[data-sort="name"]'));
  check(document.querySelector('#T tbody tr').getAttribute('data-row') === '0', 'table: by name A-Z');
  const row = document.querySelector('#T tbody tr[data-row="1"]');
  row.dispatchEvent(new MouseEvent('mouseover', {bubbles: true}));
  const svg = document.querySelector('#T svg');
  check(svg.classList.contains('sc-dim') && svg.querySelector('g[data-series="1"]').classList.contains('sc-lit') && !svg.querySelector('g[data-series="0"]').classList.contains('sc-lit'), 'row pointed at: its series lit, the rest dimmed');
  row.dispatchEvent(new MouseEvent('mouseout', {bubbles: true}));
  check(!svg.classList.contains('sc-dim'), 'pointer away: back');
} catch (e) { out.push('ERROR ' + e + ' ' + e.stack); }
document.getElementById('out').textContent = out.join('\n');
