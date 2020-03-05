'use strict';

const composer = require('gulp-composer');
const del = require('del');
const gulp = require('gulp');
const webpack = require('webpack-stream');
const wpPot = require('gulp-wp-pot');
const zip = require('gulp-zip');

// Change this variables values.
const pluginPackage = 'Wordpres_Mulstisite_Recaptcha';
const pluginSlug = 'wordpress-multisite-recaptcha';
const pluginTextdomain = 'multisite-recaptcha';

/**
 * Compiles and bundles JavaScript using WebPack.
 */
function scripts() {
	const webpackConfig = require('./webpack.config.js');
	return gulp.src('.')
		.pipe(webpack(webpackConfig))
		.pipe(gulp.dest('js/'));
}

/**
 * Creates a zip file of the plugin.
 */
function compress() {
	return gulp.src([
		'help/**',
		'includes/**',
		'js/**',
		'languages/*',
		'vendor/**',
		pluginSlug + '.php'
	], { base: '../' })
		.pipe(zip(pluginSlug + '.zip'))
		.pipe(gulp.dest('./'))
}

/**
 * Executes composer on prod or dev dpending on the NODE_ENV status.
 */
function composerExe() {
	if (process.env.NODE_ENV == 'production') {
		composer('install --no-dev', { async: false });
		return composer('dump-autoload -o', { async: false });
	} else {
		composer('install', { async: false });
		return composer('dump-autoload', { async: false })
	}
}

/**
 * Removes compiled files and any cache that exits.
 */
function clean() {
	return del(['js/', '*.zip', 'languages/' + pluginTextdomain + '.pot'])
}

/**
 * Extract translatable strings from php files and save the .pot file in languages/
 */
function potCreate() {
	return gulp.src([pluginSlug + '.php', 'includes/*.php'])
		.pipe(wpPot({
			domain: pluginTextdomain,
			package: pluginPackage
		}))
		.pipe(gulp.dest('languages/' + pluginTextdomain + '.pot'));
}

/**
 * Whatch for changes int .scss and .js files and compile them.
 */
function watch() {
	gulp.watch(['src/js/*.js'], scripts);
}

/**
 * Exportes tasks.
 */
exports.build = gulp.series(clean, scripts, potCreate);
exports.clean = clean;
exports.composer = composerExe;
exports.compress = gulp.series(clean, scripts, potCreate, compress);
exports.pot = gulp.series(potCreate);
exports.scripts = scripts;
exports.watch = watch;
exports.zip = compress;
