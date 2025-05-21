const fs = require('fs');
const path = require('path');
const archiver = require('archiver');

// Konfigurasi
const PLUGIN_NAME = 'whatsapp-notify';
const VERSION = require('../package.json').version;
const OUTPUT_DIR = path.join(__dirname, '../dist');
const SOURCE_DIR = path.join(__dirname, '../');

// Daftar file dan direktori yang diabaikan
const EXCLUDED = [
  'node_modules',
  '.git',
  'dist',
  'build',
  'zip',
  'scripts',
  'package.json',
  'package-lock.json',
  'gulpfile.js',
  '.gitignore',
  'README.md',
  '.github',
  '.vscode',
  '.idea',
  'docs/development',
  'logs',
  'temp',
  '.DS_Store',
  'Thumbs.db'
];

// Buat direktori output jika belum ada
if (!fs.existsSync(OUTPUT_DIR)) {
  fs.mkdirSync(OUTPUT_DIR, { recursive: true });
}

// Path file ZIP output
const zipFilePath = path.join(OUTPUT_DIR, `${PLUGIN_NAME}-${VERSION}.zip`);
const output = fs.createWriteStream(zipFilePath);
const archive = archiver('zip', { zlib: { level: 9 } });

output.on('close', () => {
  console.log(`✅ ZIP berhasil dibuat: ${zipFilePath}`);
  console.log(`📦 Ukuran: ${(archive.pointer() / 1024).toFixed(2)} KB`);
});

archive.on('error', (err) => {
  throw err;
});

archive.pipe(output);

// Fungsi untuk memeriksa apakah file/direktori harus diabaikan
function shouldExclude(filePath) {
  const relativePath = path.relative(SOURCE_DIR, filePath);
  return EXCLUDED.some(excluded => 
    relativePath === excluded || relativePath.startsWith(excluded + path.sep)
  );
}

// Fungsi rekursif untuk menambahkan file ke ZIP
function addFilesToArchive(dir, baseInZip) {
  const files = fs.readdirSync(dir);
  
  files.forEach(file => {
    const filePath = path.join(dir, file);
    if (shouldExclude(filePath)) return;
    
    const stat = fs.statSync(filePath);
    const relativePath = path.relative(SOURCE_DIR, filePath);
    const pathInZip = path.join(baseInZip, file);
    
    if (stat.isDirectory()) {
      addFilesToArchive(filePath, pathInZip);
    } else {
      archive.file(filePath, { name: pathInZip });
      console.log(`📄 Ditambahkan: ${relativePath}`);
    }
  });
}

// Mulai proses
console.log('🔍 Menyiapkan file untuk ZIP...');
addFilesToArchive(SOURCE_DIR, PLUGIN_NAME);
archive.finalize();
