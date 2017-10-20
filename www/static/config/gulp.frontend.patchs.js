const fs = require('fs');
const imagemin = require('gulp-imagemin');



var modulesDir = 'node_modules';

var modules = fs.readdirSync(modulesDir).map(function (module) {
    return modulesDir + '/' + module;
});

module.exports = {
    dst: {
        js: 'frontend/dist/js',
        jsx: 'temp/frontend/js',
        scss: 'temp/frontend/css',
        css: 'frontend/dist/css',
        images: 'frontend/dist/images',
        fonts: 'frontend/dist/fonts',
        raw: 'frontend/dist/raw'
    },
    modules: {
        jsx: 'frontend/jsx/**/*',
    },
    src: {
        jsx_bundles: {
            app: './frontend/jsx/main.jsx'
        },
        jsx: [ // for watching
            'frontend/jsx/**/*'
        ],
        js_include: [
            'frontend/js_include/**/*',
        ],
        js: [
            'frontend/js/**/*',
            'temp/frontend/js/**/*.js'
        ],
        scss: [
            'frontend/sass/**/*.scss'
        ],
        scss_include: [
            'bower_components/compass-mixins/lib/',
        ],
        css: [
            'temp/frontend/css/**/*',
        ],
        css_raw: [
            'frontend/css/*',
        ],
        images: [
            'frontend/images/**/*.*'
        ],
        fonts: [
            'frontend/fonts/**/*'
        ],
        raw: []
    },
    vendors: {
        jquery: {
            js_include: [
                'bower_components/jquery/dist/jquery.min.js'
            ]
        },
        lato: {
            fonts: [
                'bower_components/lato-webfont/fonts/*'
            ],
            scss_include: [
                'bower_components/lato-webfont/scss/'
            ]
        },
        waves: {
            js_include: [
                'bower_components/Waves/src/js/waves.js'
            ],
            scss: [
                // 'bower_components/Waves/src/scss/waves.scss'
            ]
        },

        "what-input": {
            js_include: [
                'bower_components/what-input/dist/what-input.js'
            ]
        },
        cds: {
            scss_include: [
                'components/cds'
            ]
        },
        dotdotdot: {
            js_include: [
                'bower_components/jQuery.dotdotdot/src/jquery.dotdotdot.js'
            ]
        },

        jquery_form: {
            js_include: [
                'bower_components/jquery-form/dist/jquery.form.min.js'
            ]
        },
        webfontloader: {
            js_include: [
                'bower_components/webfontloader/webfontloader.js'
            ]
        },
        modal: {
            js_include: [
                'bower_components/mmodal/js/jquery.mindy.modal.js'
            ],
            scss_include: [
                'bower_components/mmodal/scss/'
            ]
        },
        bourbon: {
            scss_include: [
                'bower_components/bourbon/app/assets/stylesheets/'
            ]
        },
        wNumb: {
            js_include: [
                'vendors/wNumb.js'
            ]
        },
        pace: {
            js_include: [
                'bower_components/PACE/pace.js'
            ],
            css: [
                // 'bower_components/PACE/themes/black/pace-theme-minimal.css'
                // 'bower_components/PACE/themes/red/pace-theme-minimal.css'
            ]
        },
        // rwdImageMaps: {
        //     js_include: [
        //         'bower_components/jQuery-rwdImageMaps/jquery.rwdImageMaps.js'
        //     ],
        // },
        foundation: {
            js_include: [
                // 'bower_components/foundation-sites/dist/js/foundation.js', //all
                'bower_components/foundation-sites/dist/js/plugins/foundation.core.js',

                'bower_components/foundation-sites/dist/js/plugins/foundation.util.keyboard.js',
                // 'bower_components/foundation-sites/dist/js/plugins/foundation.util.box.js',
                // 'bower_components/foundation-sites/dist/js/plugins/foundation.util.nest.js',
                'bower_components/foundation-sites/dist/js/plugins/foundation.util.motion.js',
                'bower_components/foundation-sites/dist/js/plugins/foundation.util.triggers.js',
                'bower_components/foundation-sites/dist/js/plugins/foundation.util.touch.js',
                'bower_components/foundation-sites/dist/js/plugins/foundation.util.mediaQuery.js',


                'bower_components/foundation-sites/dist/js/plugins/foundation.offcanvas.js',
                'bower_components/foundation-sites/dist/js/plugins/foundation.accordion.js',
                // 'bower_components/foundation-sites/dist/js/plugins/foundation.accordionMenu.js',
                'bower_components/foundation-sites/dist/js/plugins/foundation.tabs.js',
                'bower_components/foundation-sites/dist/js/plugins/foundation.responsiveAccordionTabs.js',
                // 'bower_components/foundation-sites/dist/js/plugins/foundation.sticky.js',
                // 'bower_components/foundation-sites/dist/js/plugins/foundation.toggler.js',
                // 'bower_components/foundation-sites/dist/js/plugins/foundation.dropdown.js',
                // 'bower_components/foundation-sites/dist/js/plugins/foundation.dropdownMenu.js',
                // 'bower_components/foundation-sites/dist/js/plugins/foundation.tooltip.js',
            ],
            scss_include: [
                'bower_components/foundation-sites/scss/'
            ]
        },
    }
};