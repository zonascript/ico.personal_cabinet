var gulp = require('gulp');

var concat = require('gulp-concat'),
    uglify = require('gulp-uglify'),
    imagemin = require('gulp-imagemin'),
    minifyCSS = require('gulp-minify-css'),
    sass = require('gulp-sass'),
    gulpif = require('gulp-if'),
    argv = require('yargs').argv,
    process = require('process'),
    clean = require('gulp-clean'),
    coffee = require('gulp-coffee'),
    autoprefixer = require('gulp-autoprefixer'),
    livereload = require('gulp-livereload');

var version = '1.0.0';

var minifyOpts = {

};

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
    sass: './css',
    fonts: 'dist/fonts',
    wysiwyg: 'dist/ueditor107'
};

var paths = {
    js: [
        'vendor/jquery/dist/jquery.min.js',
        'vendor/modernizr/modernizr.js',
        'vendor/jquery.cookie/jquery.cookie.js',
        'vendor/fastclick/lib/fastclick.js',
        'vendor/foundation/js/foundation.min.js',
        'vendor/checkboxes.js/src/jquery.checkboxes.js',
        'vendor/select2/select2.js',
        'vendor/select2/select2_locale_ru.js',
        'vendor/jquery-autosize/jquery.autosize.min.js',
        'vendor/jquery-ui/ui/minified/jquery-ui.min.js',
        'vendor/flow.js/dist/flow.js',
        'vendor/Chart.js/Chart.js',
        'vendor/mmodal/js/jquery.mindy.modal.js',
        'vendor/fancybox/source/jquery.fancybox.pack.js',
        'vendor/pickmeup/js/jquery.pickmeup.js',
        'vendor/remarkable/dist/remarkable.js',
        'vendor/jquery-form/jquery.form.js',
        'vendor/perfect-scrollbar/js/perfect-scrollbar.jquery.js',

        'vendor/underscore/underscore.js',

        'vendor/meditor/js/utils.js',
        'vendor/meditor/js/core.js',
        'vendor/meditor/js/engine.js',
        'vendor/meditor/js/editor.js',
        'vendor/meditor/js/block.js',
        'vendor/meditor/js/plugins/text/text.js',
        'vendor/meditor/js/plugins/video.js',
        'vendor/meditor/js/plugins/lost.js',
        'vendor/meditor/js/plugins/space.js',
        'vendor/meditor/js/plugins/image.js',
        'vendor/meditor/js/plugins/map/map.js',

        'vendor/semantic-ui/dist/components/checkbox.js',
        'vendor/semantic-ui/dist/components/dropdown.js',
        'vendor/semantic-ui/dist/components/popup.js',
        'vendor/semantic-ui/dist/components/tab.js',
        'vendor/semantic-ui/dist/components/transition.js',

        // Emmet for ace.js
        'components/emmet.js',

        // Ace.js is adependency for mail templates and ueditor
        'components/ace/ace.js',
        'components/ace/theme-clouds.js',
        'components/ace/theme-crimson_editor.js',
        'components/ace/mode-html.js',
        'components/ace/mode-css.js',
        'components/ace/mode-javascript.js',
        'components/ace/mode-twig.js',
        'components/ace/worker-html.js',
        'components/ace/worker-css.js',
        'components/ace/worker-javascript.js',
        'components/ace/ext-emmet.js',

        // Codemirror is a dependency for ueditor
        'vendor/codemirror/lib/codemirror.js',
        'vendor/codemirror/mode/css/css.js',
        'vendor/codemirror/mode/javascript/javascript.js',
        'vendor/codemirror/mode/xml/xml.js',
        'vendor/codemirror/mode/htmlmixed/htmlmixed.js',

        // https://github.com/nightwing/emmet-core
        'components/emmet.js',
        'components/mtooltip/mtooltip.js',
        'components/jquery.dragsort-0.5.2.min.js',

        'js/*.js'
    ],
    coffee: 'js/**/*.coffee',
    fonts: [
        'fonts/glyphico/fonts/*{.eot,.woff,.woff2,.ttf,.svg}',
        'fonts/lato/fonts/*{.eot,.woff,.woff2,.ttf,.svg}',
        'fonts/semantic-ui/fonts/*{.eot,.woff,.woff2,.ttf,.svg}'
    ],
    images: 'images/**/*',
    sass: [
        'scss/**/*.scss'
    ],
    wysiwyg: 'vendor/ueditor107/dist/**/*',
    css: [
        'css/**/*.css',

        'fonts/lato/css/style.css',
        'fonts/glyphico/css/style.css',
        'fonts/semantic-ui/style.css',

        'vendor/meditor/css/editor.css',
        'vendor/pen/src/pen.css',
        'vendor/pickmeup/css/pickmeup.css',

        'components/mtooltip/mtooltip.css'
    ]
};

var uglifyOpts = {
    mangle: false
};

gulp.task('fonts', function() {
    return gulp.src(paths.fonts)
        .pipe(gulp.dest(dst.fonts));
});

gulp.task('coffee', function() {
    gulp.src(paths.coffee)
        .pipe(coffee({
            bare: true
        }).on('error', function(err) {
            console.log(err);
        }))
        .pipe(gulp.dest(dst.js))
});

gulp.task('wysiwyg', function() {
    return gulp.src(paths.wysiwyg)
        .pipe(gulp.dest(dst.wysiwyg));
});

gulp.task('js', ['coffee'], function() {
    return gulp.src(paths.js)
        .pipe(gulpif(argv.release, uglify(uglifyOpts)))
        .pipe(concat(version + '.all.js'))
        .pipe(gulp.dest(dst.js))
        .pipe(gulpif(process.argv.indexOf('watch') != -1, livereload()));
});

gulp.task('images', function() {
    return gulp.src(paths.images)
        .pipe(imagemin(imagesOpts))
        .pipe(gulp.dest(dst.images));
});

gulp.task('sass', function() {
    return gulp.src(paths.sass)
        .pipe(sass(sassOpts))
        .pipe(gulp.dest(dst.sass));
});

gulp.task('css', ['sass', 'fonts'], function() {
    return gulp.src(paths.css)
        .pipe(gulp.dest(dst.sass))
        .pipe(autoprefixer({
            browsers: ['last 2 versions'],
            cascade: false
        }))
        .pipe(gulpif(argv.release, minifyCSS(minifyOpts)))
        .pipe(concat(version + '.all.css'))
        .pipe(gulp.dest(dst.css))
        .pipe(gulpif(process.argv.indexOf('watch') != -1, livereload()));
});

gulp.task('watch', ['default'], function() {
    livereload.listen();
    gulp.watch(paths.wysiwyg, ['wysiwyg']);
    gulp.watch(paths.js, ['js']);
    gulp.watch(paths.images, ['images']);
    gulp.watch(paths.sass, ['css']);
});

gulp.task('clean', function() {
    return gulp.src(['dist/*'], {
        read: false
    }).pipe(clean());
});

gulp.task('build', ['clean'], function() {
    return gulp.start('js', 'css', 'images', 'fonts', 'wysiwyg');
});

gulp.task('default', function() {
    return gulp.start('build');
});
