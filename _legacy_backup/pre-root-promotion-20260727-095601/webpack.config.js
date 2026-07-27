const fs = require('fs');
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');

const root = __dirname;
const pagesDir = path.resolve(root, 'public', 'js', 'pages');

const pageScripts = fs.existsSync(pagesDir)
  ? fs
      .readdirSync(pagesDir)
      .filter((name) => name.endsWith('.js') && !name.endsWith('.min.js'))
      .map((name) => path.join(pagesDir, name))
      .sort()
  : [];

const entryFiles = [
  path.resolve(root, 'public', 'js', 'default.js'),
  ...pageScripts,
  path.resolve(root, 'assets', 'scss', 'main.scss')
];

module.exports = (env, argv) => {
  const isProd = (argv && argv.mode) === 'production';

  return {
    mode: isProd ? 'production' : 'development',
    entry: {
      main: entryFiles
    },
    output: {
      path: root,
      filename: isProd ? 'public/js/main.min.js' : 'public/js/main.js'
    },
    module: {
      rules: [
        {
          test: /public[\\\/]js[\\\/].*\.js$/,
          exclude: /\.min\.js$/,
          use: ['script-loader']
        },
        {
          test: /\.s?css$/,
          use: [
            MiniCssExtractPlugin.loader,
            {
              loader: 'css-loader',
              options: { url: false }
            },
            'sass-loader'
          ]
        }
      ]
    },
    plugins: [
      new MiniCssExtractPlugin({
        filename: isProd ? 'public/css/main.min.css' : 'public/css/main.css'
      })
    ],
    optimization: {
      minimize: isProd,
      minimizer: ['...', new CssMinimizerPlugin()]
    },
    stats: 'errors-warnings'
  };
};
