const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const dest = path.join(root, 'www');
const files = ['index.html', 'app.js', 'offline.js', 'manifest.json', 'sw.js'];

fs.rmSync(dest, { recursive: true, force: true });
fs.mkdirSync(dest, { recursive: true });

for (const file of files) {
    fs.copyFileSync(path.join(root, file), path.join(dest, file));
}

console.log('www listo');
