#!/usr/bin/env node
/**
 * Copies frontend dependencies from node_modules to public/vendor/
 * Run via: npm run build
 */
const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');

function copyFile(src, dest) {
    fs.mkdirSync(path.dirname(dest), { recursive: true });
    fs.copyFileSync(src, dest);
}

function copyDir(src, dest) {
    if (!fs.existsSync(src)) {
        console.warn(`  Skipped (not found): ${src}`);
        return;
    }
    fs.mkdirSync(dest, { recursive: true });
    for (const entry of fs.readdirSync(src, { withFileTypes: true })) {
        const s = path.join(src, entry.name);
        const d = path.join(dest, entry.name);
        entry.isDirectory() ? copyDir(s, d) : fs.copyFileSync(s, d);
    }
}

function copy(from, to) {
    const src  = path.join(ROOT, from);
    const dest = path.join(ROOT, to);
    try {
        if (from.endsWith('/')) {
            copyDir(src, dest);
        } else {
            fs.mkdirSync(path.dirname(dest), { recursive: true });
            copyFile(src, dest);
        }
        console.log(`  ✓ ${from}`);
    } catch (e) {
        console.warn(`  ✗ ${from}: ${e.message}`);
    }
}

console.log('Copying frontend assets to public/vendor/ ...');

// jQuery
copy('node_modules/jquery/dist/jquery.min.js',          'public/vendor/jquery/dist/jquery.min.js');
copy('node_modules/jquery/dist/jquery.min.map',         'public/vendor/jquery/dist/jquery.min.map');

// Bootstrap 3 (CSS + JS + Fonts/Glyphicons)
copy('node_modules/bootstrap/dist/css/bootstrap.min.css',       'public/vendor/bootstrap/dist/css/bootstrap.min.css');
copy('node_modules/bootstrap/dist/js/bootstrap.min.js',         'public/vendor/bootstrap/dist/js/bootstrap.min.js');
copy('node_modules/bootstrap/dist/fonts/',                       'public/vendor/bootstrap/dist/fonts/');

// NGM map library
copy('node_modules/ngm/css/',  'public/vendor/ngm/css/');
copy('node_modules/ngm/js/',   'public/vendor/ngm/js/');

console.log('Done.');
