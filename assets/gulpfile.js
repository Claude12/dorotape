const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const sourcemaps = require('gulp-sourcemaps');
const babel = require('gulp-babel');
const postcss = require('gulp-postcss');
const autoprefixer = require('autoprefixer');
const cssnano = require('cssnano');
const webpack = require('webpack-stream');
const ESLintPlugin = require('eslint-webpack-plugin');
const browserSync = require('browser-sync').create();

// CSS task. Compiles scss/style.scss (and any other top-level entry) to
// ../dist/css/. cssnano is told to leave colour values alone: the design
// system is authored in oklch() and color-mix(), and cssnano's default
// colormin would rewrite those into legacy formats it thinks are smaller,
// losing the wide-gamut values the design depends on.
const css = () => {
  return gulp
    .src('scss/**/*.scss')
    .pipe(sourcemaps.init())
    .pipe(sass({ errLogToConsole: true }))
    .pipe(postcss([autoprefixer, cssnano({ preset: ['default', { colormin: false }] })]))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('../dist/css/'))
    .pipe(browserSync.stream());
};

// JS task
const js = () => {
  return gulp
    .src('js/main.js')
    .pipe(
      babel({
        presets: ['@babel/env'],
      })
    )
    .pipe(
      webpack({
        mode: 'production',
        devtool: 'source-map',
        plugins: [new ESLintPlugin()],
      })
    )
    .pipe(gulp.dest('../dist/js/'))
    .pipe(browserSync.stream());
};

// Watch task
const watchFiles = () => {
  browserSync.init({
    proxy: 'http://localhost/dorotape_wordpresscms/',
    open: false,
  });

  gulp.watch('scss/**/*.scss', css);
  gulp.watch('js/**/*.js', js).on('change', browserSync.reload);
  gulp.watch('../**/*.php').on('change', browserSync.reload);
};

exports.watch = gulp.series(gulp.parallel(css, js), watchFiles);
exports.build = gulp.parallel(css, js);
