var fs = require('fs');

var modulesDir = '../../app/Modules';

var modules = fs.readdirSync(modulesDir).map(function (module) {
    return modulesDir + '/' + module;
});

module.exports = {
    compress: false,
    name: 'main',
    dst: {
        js: 'dist/js',
        scss: 'temp/css',
        css: 'dist/css',
        images: 'dist/images',
        fonts: 'dist/fonts',
        raw: 'dist/raw'
    },
    src: {
        js: [
            'js/*.js'
        ].concat(modules.map(function(dir) {
            return dir + '/static/js/**/*.*'
        })),
        scss: [
            'scss/**/*.scss'
        ].concat(modules.map(function(dir) {
            return dir + '/static/scss/**/*.*'
        })),
        css: [
            'temp/css/*',
            'fonts/GothamPro/css/GothamPro.css',
            'fonts/icons/css/style.css'
        ].concat(modules.map(function(dir) {
            return dir + '/static/css/**/*.*'
        })),
        images: [
            'images/**/*.*'
        ],
        fonts: [
            'fonts/GothamPro/fonts/**/*',
            'fonts/icons/fonts/*'
        ],
        raw: [

        ].concat(modules.map(function(dir) {
            return dir + '/static/raw/*/**'
        }))
    },
    vendors: {
        jquery: {
            js: [
                'bower_components/jquery/dist/jquery.min.js'
            ]
        },
        modal: {
            js: [
                'components/modal/modal.js'
            ]
        },
        underscore: {
            js: [
                'bower_components/underscore/underscore.js'
            ]
        },
        confirm: {
            js: [
                'components/confirm/jquery.confirm.js'
            ]
        },
        cds: {
            scss_include: [
                'components/cds'
            ]
        },
        deparam: {
            js: [
                'components/deparam/jquery.deparam.js'
            ]
        },
        ui_custom: {
            js: [
                'components/ui-custom/jquery-ui.min.js'
            ],
            css: [
                'components/ui-custom/jquery-ui.min.css'
            ]
        },
        flow: {
            js: [
                'bower_components/flow-js/dist/flow.js'
            ]
        },
        files_field: {
            js: [
                'components/fields/js/filesfield.js'
            ]
        },
    }
};