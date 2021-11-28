var gulp = require('gulp');
var phpcbf = require('gulp-phpcbf');
var beautify = require('gulp-beautify');

gulp.task('js-beautify', function() {
    return gulp.src([
            'src/**/*.js',
            '**/*.js',
            '*.js',
            '*.json',
            '!vendor/*.js',
            '!vendor/**/*.js'
        ], {
            base: './'
        })
        .pipe(beautify.js({
            indent_size: 4
        }))
        .pipe(gulp.dest('./'));
});

gulp.task('php-beautify', function() {
    return gulp.src([
            'src/**/*.php',
            '**/*.php',
            '*.php',
            '!vendor/*.php',
            '!vendor/**/*.php'
        ], {
            base: './'
        })
        .pipe(phpcbf({
            bin: './vendor/bin/phpcbf',
            standard: 'PSR12',
            warningSeverity: 0
        }))
        .pipe(gulp.dest('./'));
});

gulp.task('default', gulp.series('js-beautify', 'php-beautify'));