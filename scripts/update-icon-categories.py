#!/usr/bin/env python3
"""Refreshes public/demos/icons.json, the icon demo's groups and search words: for each icon in BS\\Icon (php/BS/Icon.php),
its categories and tags from the Bootstrap Icons site (icons.getbootstrap.com: its icons/index.json, and the category
names from its start page). Icons the site doesn't list go under Other. Run from the package's folder:

    scripts/update-icon-categories.py
"""
import html
import json
import pathlib
import re
import urllib.request

SITE = 'https://icons.getbootstrap.com'
here = pathlib.Path(__file__).resolve().parent.parent


def fetch(path):
    request = urllib.request.Request(SITE + path, headers={'User-Agent': 'sc-webframe update-icon-categories'})
    with urllib.request.urlopen(request, timeout=60) as response:
        return response.read().decode('utf-8')


page = fetch('/')
names = {slug: html.unescape(label) for slug, label in
         re.findall(r'href="/icons/category/([a-z0-9-]+)/" data-category-link><span>([^<]+)</span>', page)}
index = {icon['n']: icon for icon in json.loads(fetch('/icons/index.json'))}

ours = re.findall(r"case (\w+) = 'bi-([a-z0-9-]+)';", (here / 'php/BS/Icon.php').read_text())
icons = []
for case, name in ours:
    found = index.get(name, {})
    icons.append({'case': case, 'name': name, 'categories': found.get('c') or ['other'], 'tags': found.get('t', [])})
used = sorted({category for icon in icons for category in icon['categories']}, key=lambda slug: (slug == 'other', names.get(slug, slug)))
categories = [{'slug': slug, 'name': names.get(slug, 'Other' if slug == 'other' else slug.replace('-', ' ').capitalize())} for slug in used]

out = here / 'public/demos/icons.json'
out.write_text(json.dumps({'source': SITE, 'categories': categories, 'icons': icons}, separators=(',', ':'), ensure_ascii=False) + '\n')
print(f'{out}: {len(icons)} icons in {len(categories)} groups, {sum(icon["categories"] == ["other"] for icon in icons)} under Other')
