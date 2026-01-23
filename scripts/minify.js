const fs = require('fs');
const path = require('path');
const { minify } = require('terser');
const CleanCSS = require('clean-css');

const root = path.resolve(__dirname, '..');

const jsFiles = [
  path.join(root, 'public', 'js', 'default.js'),
  ...getPageScripts()
];

const cssFiles = [
  path.join(root, 'public', 'css', 'template.css'),
  path.join(root, 'public', 'css', 'custom-theme', 'jquery-ui-1.8.23.custom.css')
];

async function minifyAll() {
  for (const file of jsFiles) {
    await minifyJs(file);
  }
  for (const file of cssFiles) {
    minifyCss(file);
  }
}

function getPageScripts() {
  const dir = path.join(root, 'public', 'js', 'pages');
  if (!fs.existsSync(dir)) {
    return [];
  }
  return fs
    .readdirSync(dir)
    .filter((name) => name.endsWith('.js') && !name.endsWith('.min.js'))
    .map((name) => path.join(dir, name));
}

async function minifyJs(filePath) {
  if (!fs.existsSync(filePath)) {
    return;
  }
  const code = fs.readFileSync(filePath, 'utf8');
  const result = await minify(code, {
    compress: true,
    mangle: true
  });
  if (!result || !result.code) {
    throw new Error(`Terser failed for ${filePath}`);
  }
  const outPath = filePath.replace(/\.js$/, '.min.js');
  fs.writeFileSync(outPath, result.code + '\n', 'utf8');
  process.stdout.write(`Minified: ${outPath}\n`);
}

function minifyCss(filePath) {
  if (!fs.existsSync(filePath)) {
    return;
  }
  const code = fs.readFileSync(filePath, 'utf8');
  const output = new CleanCSS({}).minify(code);
  if (output.errors && output.errors.length > 0) {
    throw new Error(`CleanCSS failed for ${filePath}: ${output.errors.join(', ')}`);
  }
  const outPath = filePath.replace(/\.css$/, '.min.css');
  fs.writeFileSync(outPath, output.styles + '\n', 'utf8');
  process.stdout.write(`Minified: ${outPath}\n`);
}

minifyAll().catch((err) => {
  console.error(err.message);
  process.exit(1);
});
