const gulp = require('gulp');
const concat = require('gulp-concat');
const cssnano = require('gulp-cssnano');
const imagemin = require('gulp-imagemin');
const livereload = require('gulp-livereload');
const rimraf = require('gulp-rimraf');
const sass = require('gulp-sass');
const hashsum = require('gulp-hashsum');
const uglify = require('gulp-uglify');

var config = require('./gulpconfig');
var backend = config;

function buildVendorsData(vendors) {
    var vendorsData = {};
    for (var vendor in vendors) {
        if (vendors.hasOwnProperty(vendor)) {
            var vendorConfig = vendors[vendor];
            for (var type in vendorConfig) {
                if (vendorConfig.hasOwnProperty(type)) {
                    var matches = vendorConfig[type];
                    if (typeof vendorsData[type] == 'undefined') {
                        vendorsData[type] = [];
                    }
                    if (typeof matches == 'string') {
                        vendorsData[type].unshift(matches);
                    } else {
                        vendorsData[type] = [].concat(vendorsData[type], matches)
                    }
                }
            }
        }
    }
    return vendorsData;
}


var backendVendorsData = buildVendorsData(backend.vendors);
for (var vendorType in backendVendorsData) {
    if (backendVendorsData.hasOwnProperty(vendorType)) {
        if (!backend.src.hasOwnProperty(vendorType)) {
            backend.src[vendorType] = [];
        }
        backend.src[vendorType] = [].concat(backendVendorsData[vendorType], backend.src[vendorType]);
    }
}


gulp.task('scss', function() {
    return gulp.src(backend.src.scss)
        .pipe(sass({
            includePaths: backend.src.scss_include ? backend.src.scss_include : []
        }).on('error', sass.logError))
        .pipe(gulp.dest(backend.dst.scss));
});


gulp.task('css', ['scss'], function() {
    var pipe = gulp.src(backend.src.css);
    if (config.compress) {
        pipe = pipe.pipe(cssnano())
    }
    return pipe.pipe(concat(config.name + '.css')).
    pipe(gulp.dest(backend.dst.css)).
    pipe(hashsum({filename: 'versions/css.yml', hash: 'md5'})).
    pipe(livereload());
});


gulp.task('js', function() {
    var pipe = gulp.src(backend.src.js);
    if (config.compress) {
        pipe = pipe.pipe(uglify())
    }
    return pipe.pipe(concat(config.name + '.js')).
    pipe(gulp.dest(backend.dst.js)).
    pipe(hashsum({filename: 'versions/js.yml', hash: 'md5'})).
    pipe(livereload());
});


gulp.task('images', function() {
    var pipe = gulp.src(backend.src.images);
    if (config.compress) {
        pipe = pipe.pipe(imagemin())
    }
    return pipe.pipe(gulp.dest(backend.dst.images)).pipe(livereload());
});

gulp.task('fonts', function() {
    return gulp.src(backend.src.fonts)
        .pipe(gulp.dest(backend.dst.fonts)).pipe(livereload());
});


gulp.task('raw', function() {
    return gulp.src(backend.src.raw)
        .pipe(gulp.dest(backend.dst.raw)).pipe(livereload());
});

gulp.task('watch', ['build'], function() {
    livereload({ start: true });


    gulp.watch(backend.src.raw, ['raw']);
    gulp.watch(backend.src.scss, ['css']);
    gulp.watch(backend.src.js, ['js']);
    gulp.watch(backend.src.images, ['images']);
    gulp.watch(backend.src.fonts, ['fonts']);
});


gulp.task('clear', function() {
    return gulp.src(['dist/*']).pipe(rimraf());
});

gulp.task('build', ['clear'], function(){
    gulp.start(
        'raw', 'css', 'js', 'images', 'fonts'
    );
});

gulp.task('default', function(){
    gulp.start('watch');
});
