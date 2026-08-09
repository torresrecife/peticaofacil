#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

function readArg(flag) {
  const index = process.argv.indexOf(flag);
  if (index === -1 || index + 1 >= process.argv.length) {
    return null;
  }

  return process.argv[index + 1];
}

async function main() {
  const inputPath = readArg('--input');
  const outputPath = readArg('--output');

  if (!inputPath || !outputPath) {
    throw new Error('Uso: node render-peticao.js --input arquivo.json --output arquivo.pdf');
  }

  const payload = JSON.parse(fs.readFileSync(path.resolve(inputPath), 'utf8'));

  let playwright;
  try {
    playwright = require('playwright');
  } catch (error) {
    throw new Error('Dependencia "playwright" nao instalada em scripts/pdf-renderer. Rode "npm install" nessa pasta.');
  }

  const browser = await playwright.chromium.launch({
    headless: true,
  });

  try {
    const page = await browser.newPage();
    const bodyHtml = String(payload.body_html || '');
    const title = String(payload.title || 'peticao');
    const margin = (payload.options && payload.options.margin) || {
      top: '16.9mm',
      right: '16.9mm',
      bottom: '16.9mm',
      left: '16.9mm',
    };
    const reservedHeaderSpace = '30mm';
    const reservedFooterSpace = '30mm';

    const documentHtml = `<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>${escapeHtml(title)}</title>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      background: #ffffff;
      color: #1f2933;
      font-family: Arial, Helvetica, sans-serif;
      font-size: 12pt;
      line-height: 1.6;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    body {
      word-wrap: break-word;
      counter-reset: page;
    }
    p, div, td, th, li, span, strong, u {
      line-height: 1.6;
    }
    p {
      margin: 0 0 12px;
      text-align: justify;
    }
    img {
      max-width: 100%;
      height: auto;
    }
    table {
      max-width: 100%;
      border-collapse: collapse;
      border-spacing: 0;
    }
  </style>
</head>
<body>${bodyHtml}</body>
</html>`;

    await page.setContent(documentHtml, {
      waitUntil: 'networkidle',
    });

    await page.pdf({
      path: path.resolve(outputPath),
      format: payload.options && payload.options.format ? payload.options.format : 'A4',
      printBackground: true,
      displayHeaderFooter: true,
      headerTemplate: buildTemplateHtml(payload.header_html || '', 'header', margin.left, margin.right),
      footerTemplate: buildTemplateHtml(payload.footer_html || '', 'footer', margin.left, margin.right),
      margin: {
        top: reservedHeaderSpace,
        right: margin.right,
        bottom: reservedFooterSpace,
        left: margin.left,
      },
    });
  } finally {
    await browser.close();
  }
}

function buildTemplateHtml(content, kind, paddingLeft, paddingRight) {
  const safeContent = String(content || '');
  const shellClass = kind === 'footer' ? 'template-shell template-footer' : 'template-shell template-header';
  const safePaddingLeft = String(paddingLeft || '16.9mm');
  const safePaddingRight = String(paddingRight || '16.9mm');

  return `<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    html, body {
      margin: 0;
      padding: 0;
      width: 100%;
      color: #1f2933;
      font-family: Arial, Helvetica, sans-serif;
      font-size: 9pt;
      line-height: 1.3;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      background: transparent;
    }
    .template-shell {
      width: 100%;
      box-sizing: border-box;
      margin: 0;
      padding: 0 ${safePaddingRight} 1mm ${safePaddingLeft};
    }
    .template-footer {
      padding: 1mm ${safePaddingRight} 0 ${safePaddingLeft};
    }
    .template-shell p,
    .template-shell div,
    .template-shell td,
    .template-shell th,
    .template-shell span,
    .template-shell strong,
    .template-shell u {
      line-height: 1.3;
    }
    .template-shell p {
      margin: 0;
    }
    .template-shell img {
      max-width: 100%;
      height: auto;
      display: block;
    }
    .template-shell table {
      width: 100%;
      border-collapse: collapse;
      border-spacing: 0;
      table-layout: fixed;
    }
    .template-shell td,
    .template-shell th {
      vertical-align: middle;
      padding: 0;
    }
    .template-shell .print-header-table {
      width: 100% !important;
      table-layout: fixed;
      border-collapse: collapse;
    }
    .template-shell .print-header-table td:first-child {
      width: 34%;
      text-align: left;
    }
    .template-shell .print-header-table td:last-child {
      width: 66%;
      text-align: right;
    }
    .template-shell .print-header-contact {
      width: 100%;
      margin: 0;
      font-size: 9pt;
      line-height: 1.3;
      text-align: right !important;
      white-space: normal;
    }
  </style>
</head>
<body>
  <div class="${shellClass}">${safeContent}</div>
</body>
</html>`;
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

main().catch((error) => {
  console.error(error.message || error);
  process.exit(1);
});
