var gulp = require('gulp');
var process = require('process');

var concat = require('gulp-concat'),
    uglify = require('gulp-uglify'),
    imagemin = require('gulp-imagemin'),
    cssnano = require('gulp-cssnano'),
    sass = require('gulp-sass'),
    changed = require('gulp-changed'),
    rimraf = require('gulp-rimraf'),
    cache = require('gulp-cached'),
    livereload = require('gulp-livereload');

var version = '1.0.0';

var minify = false;

var imagesOpts = {
    optimizationLevel: 5,
    progressive: true,
    interlaced: true
};

var sassOpts = {
    includePaths: [
        'vendor/foundation/scss',
        'vendor/mindy-sass/mindy'
    ]
};

var dst = {
    js: 'dist/js',
    css: 'dist/css',
    images: 'dist/images',
    sass: 'css',
    fonts: 'dist/fonts'
};

var paths = {
    js: [
        'vendor/jquery/dist/jquery.min.js',
        'vendor/foundation/js/vendor/modernizr.js',
        'vendor/jquery.cookie/jquery.cookie.js',
        'vendor/fastclick/lib/fastclick.js',
        'vendor/foundation/js/foundation.min.js',
        'vendor/jquery-form/jquery.form.js',
        'vendor/mmodal/js/jquery.mindy.modal.js',
        'vendor/fancybox/source/jquery.fancybox.pack.js',
        'vendor/fancybox/source/helpers/jquery.fancybox-thumbs.js',
        'vendor/fancybox/source/helpers/jquery.fancybox-buttons.js',
        'vendor/fancybox/source/helpers/jquery.fancybox-media.js',
        'vendor/jquery.inputmask/dist/jquery.inputmask.bundle.min.js',
        'vendor/slick-carousel/slick/slick.js',
        'vendor/sticky-kit/jquery.sticky-kit.js',
        'vendor/jquery.inputmask/dist/inputmask/jquery.inputmask.js',
        'vendor/pace/pace.js',
        'vendor/underscore/underscore.js',
        'vendor/fotorama/fotorama.js',

        'js/ajax_validation.js',
        'js/comments.js',
        'js/csrf.js',
        'js/endless.js',
        'js/endless_on_scroll.js',
        'js/js_validation.js',
        'js/links.js',
        'js/app.js'
    ],
    coffee: 'js/**/*.coffee',
    images: [
        'images/**/*'
    ],
    fonts: [
        'fonts/Glyphico/fonts/*',
        'fonts/glyphico-social/fonts/*',
        'fonts/lato/fonts/*'
    ],
    sass: 'scss/**/*.scss',
    css: [
        'vendor/slick-carousel/slick/slick.css',
        'css/**/*.css',

        'fonts/glyphico-social/css/glyphico-social.css',
        'fonts/Glyphico/css/glyphico.css',
        'fonts/lato/css/lato.css'
    ]
};

gulp.task('fonts', function() {
    return gulp.src(paths.fonts)
        .pipe(gulp.dest(dst.fonts))
        .pipe(livereload());
});

gulp.task('js', function() {
    var js = gulp.src(paths.js);
    if (minify) {
        js = js.pipe(uglify());
    }
    return js.pipe(concat(version + '.all.js'))
        .pipe(gulp.dest(dst.js))
        .pipe(livereload());
});

gulp.task('images', function() {
    var images = gulp.src(paths.images).pipe(changed(dst.images));
    if (minify) {
        images = images.pipe(imagemin(imagesOpts));
    }
    return images.pipe(gulp.dest(dst.images))
        .pipe(livereload());
});

gulp.task('sass', function() {
    return gulp.src(paths.sass)
        .pipe(sass(sassOpts))
        .pipe(gulp.dest(dst.sass));
});

gulp.task('css', ['sass'], function() {
    var css = gulp.src(paths.css);
    if (minify) {
        css = css.pipe(cssnano());
    }
    return css.pipe(concat(version + '.all.css'))
        .pipe(gulp.dest(dst.css))
        .pipe(livereload());
});

// Rerun the task when a file changes
gulp.task('watch', ['default'], function() {
    livereload.listen();

    gulp.watch(paths.js, ['js']);
    gulp.watch(paths.images, ['images']);
    gulp.watch(paths.sass, ['css']);
});

// Clean
gulp.task('clean', function() {
    return gulp.src(['dist/*'], {
        read: false
    }).pipe(rimraf());
});

gulp.task('default', ['clean'], function() {
    return gulp.start('js', 'css', 'images', 'fonts');
});