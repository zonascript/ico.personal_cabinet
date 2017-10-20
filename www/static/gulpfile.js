const fs = require('fs');
const gulp = require('gulp');
const concat = require('gulp-concat');
const cssnano = require('gulp-cssnano');
const imagemin = require('gulp-imagemin');
const livereload = require('gulp-livereload');
const rimraf = require('gulp-rimraf');
const sass = require('gulp-sass');
const hashsum = require('gulp-hashsum');
const uglify = require('gulp-uglify');
const autoprefixer = require('gulp-autoprefixer');
const babel = require('gulp-babel');
const browserify = require('gulp-browserify');
const spawn = require('child_process').spawn;
const inlineimage = require('gulp-inline-image');
const pump = require('pump');

let watch = false;

let frontend = require('./config/gulp.frontend');

function buildVendorsData(vendors) {
    let vendorsData = {};
    for (let vendor in vendors) {
        if (vendors.hasOwnProperty(vendor)) {
            let vendorConfig = vendors[vendor];
            for (let type in vendorConfig) {
                if (vendorConfig.hasOwnProperty(type)) {
                    let matches = vendorConfig[type];
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

let frontendVendorsData = buildVendorsData(frontend.vendors);
for (let vendorType in frontendVendorsData) {
    if (frontendVendorsData.hasOwnProperty(vendorType)) {
        if (!frontend.src.hasOwnProperty(vendorType)) {
            frontend.src[vendorType] = [];
        }
        frontend.src[vendorType] = [].concat(frontendVendorsData[vendorType], frontend.src[vendorType]);
    }
}

gulp.task('frontend:scss', function() {
    return gulp.src(frontend.src.scss)
        .pipe(sass({
            includePaths: frontend.src.scss_include ? frontend.src.scss_include : []
        }).on('error', sass.logError))
        .pipe(inlineimage())
        .pipe(gulp.dest(frontend.dst.scss));
});

gulp.task('frontend:css:raw', function() {
    let pipe = gulp.src(frontend.src.css_raw);

    return pipe.pipe(concat(frontend.config.name + '.css'))
        .pipe(gulp.dest(frontend.dst.scss));
});


gulp.task('frontend:css', ['frontend:scss', 'frontend:css:raw'], function () {
    let pipe = gulp.src(frontend.src.css)
        .pipe(autoprefixer({
            browsers: ["> 5%", "last 2 versions", "last 4 iOS versions"],
            cascade: false
        }));

    if (frontend.config.compress) {
        pipe = pipe.pipe(cssnano({
            preset: ['default'],
            discardComments: { removeAll: true, },
            reduceIdents: false
        }))
    }

    return pipe.pipe(gulp.dest(frontend.dst.css))
        .pipe(hashsum({filename: 'frontend/versions/css.yml', hash: 'md5'}))
        .pipe(livereload());
});

gulp.task('frontend:jsx', function(done){

    let args = ['./node_modules/webpack/bin/webpack.js', '--config', './config/webpack.frontend.js'];
    if (watch) {
        args.push('--progress');
        args.push('-w');
    }

    let cmd = spawn('node', args, {stdio: 'inherit'});
    cmd.on('close', function (code) {
        console.log('frontend:jsx exited with code ' + code);
        done(code);
    });
});

let fjsinc_builded = false;
gulp.task('frontend:js:includes', function(done){
    if (!fjsinc_builded) {
        let pipe = gulp.src(frontend.src.js_include);

        if (frontend.config.compress) {
            pipe = pipe.pipe(uglify(frontend.config.uglify));
            fjsinc_builded = true;
        }

        return pipe
            .pipe(concat('vendors.js'))
            .pipe(hashsum({filename: 'frontend/versions/vendor_js.yml', hash: 'md5'}))
            .pipe(gulp.dest(frontend.dst.js));
    }

    done();
});

gulp.task('frontend:js', ['frontend:js:includes'], function() {
    let pipe = gulp.src(frontend.src.js);

    return pipe
        .pipe(concat(frontend.config.name + '.js'))
        .pipe(gulp.dest(frontend.dst.js))
        .pipe(hashsum({filename: 'frontend/versions/js.yml', hash: 'md5'}))
        .pipe(livereload());
});


gulp.task('frontend:images', function() {
    let pipe = gulp.src(frontend.src.images);

    if (frontend.config.compress) {
        pipe = pipe.pipe(imagemin(frontend.config.imagemin || {}));
    }
    return pipe
        .pipe(gulp.dest(frontend.dst.images))
        .pipe(livereload());
});

gulp.task('backend:images', function() {
    let pipe = gulp.src(backend.src.images);

    if (backend.config.compress) {
        pipe = pipe.pipe(imagemin(backend.config.imagemin || {}));
    }
    return pipe
        .pipe(gulp.dest(backend.dst.images))
        .pipe(livereload());
});

gulp.task('frontend:fonts', function() {
    return gulp.src(frontend.src.fonts)
        .pipe(gulp.dest(frontend.dst.fonts)).pipe(livereload());
});


gulp.task('frontend:raw', function() {
    return gulp.src(frontend.src.raw)
        .pipe(gulp.dest(frontend.dst.raw)).pipe(livereload());
});


gulp.task('watch:frontend', ['build:frontend'], function() {
    watch = true;
    livereload({ start: true });
    // const js_watch = frontend.src.js.concat(frontend.src.jsx);

    gulp.watch(frontend.src.raw, ['frontend:raw']);
    gulp.watch(frontend.src.scss, ['frontend:css']);
    gulp.watch(frontend.src.css, ['frontend:css']);
    gulp.watch(frontend.src.js, ['frontend:js']);
    gulp.watch(frontend.src.images, ['frontend:images']);
    gulp.watch(frontend.src.fonts, ['frontend:fonts']);

    gulp.start('frontend:jsx');
});

gulp.task('prepare:frontend', ['clear:frontend' , 'frontend:jsx'], function(done){

    if (!fs.existsSync(frontend.dst.scss)){
        fs.mkdirSync(frontend.dst.scss);
    }

    if (!fs.existsSync(frontend.dst.jsx)){
        fs.mkdirSync(frontend.dst.jsx);
    }

    done();
});

gulp.task('watch', ['build'], function() {
    gulp.start(
         'watch:frontend'
    );
});


gulp.task('clear:frontend', function() {
    return gulp.src(['frontend/dist/*', 'frontend/temp/*', frontend.dst.jsx, frontend.dst.scss]).pipe(rimraf());
});

gulp.task('clear', function() {
    gulp.start(
        'clear:frontend', 'clear:backend'
    );
});

gulp.task('build:frontend', ['clear:frontend', 'prepare:frontend'], function(){
    gulp.start(
        'frontend:raw', 'frontend:css', 'frontend:js', 'frontend:images', 'frontend:fonts'
    );
});


gulp.task('build', function(){
    gulp.start(
         'build:frontend'
    );
});

gulp.task('default', function(){
    gulp.start('watch');
});
