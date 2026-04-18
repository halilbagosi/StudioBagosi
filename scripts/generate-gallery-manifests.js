#!/usr/bin/env node
/**
 * Scans each set's `folder` from data/gallery.json and writes folder/manifest.json
 * listing all image files (for static hosting — browsers cannot read directories).
 *
 * Run from repo root: node scripts/generate-gallery-manifests.js
 */
'use strict';

var fs = require('fs');
var path = require('path');

var root = path.resolve(__dirname, '..');
var galleryJsonPath = path.join(root, 'data', 'gallery.json');

var IMG_EXT = {
  '.jpg': true,
  '.jpeg': true,
  '.png': true,
  '.webp': true,
  '.gif': true,
  '.avif': true
};

function isImageFile(name) {
  if (name === 'manifest.json' || name.startsWith('.')) return false;
  var ext = path.extname(name).toLowerCase();
  return !!IMG_EXT[ext];
}

function naturalSort(a, b) {
  return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
}

var raw = fs.readFileSync(galleryJsonPath, 'utf8');
var data = JSON.parse(raw);

if (!data || !Array.isArray(data.sets)) {
  console.error('Invalid gallery.json');
  process.exit(1);
}

data.sets.forEach(function (set) {
  if (!set.folder) return;
  var dir = path.join(root, set.folder);
  if (!fs.existsSync(dir) || !fs.statSync(dir).isDirectory()) {
    console.warn('Skip (missing folder):', set.folder);
    return;
  }
  var names = fs
    .readdirSync(dir)
    .filter(isImageFile)
    .sort(naturalSort);
  var out = path.join(dir, 'manifest.json');
  fs.writeFileSync(out, JSON.stringify(names, null, 2) + '\n', 'utf8');
  console.log(set.name + ': ' + names.length + ' → ' + path.relative(root, out));
});

console.log('Done.');
