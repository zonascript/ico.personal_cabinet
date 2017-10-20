const imagemin = require('gulp-imagemin');

module.exports = {
    name: 'main',
    compress: true,
    inline_image: {
        baseDir: './frontend/css'
    },
    webpack: require('./webpack.frontend'),
    imagemin: [
        imagemin.gifsicle({
            interlaced: true
        }),
        imagemin.jpegtran({
            progressive: true,
            optimize: true,
        }),
        imagemin.optipng({
            optimizationLevel: 7,
            bitDepthReduction: true,
            colorTypeReduction: true,
            paletteReduction: true,
            buffer: true
        }),
        imagemin.svgo({plugins: [{
            removeViewBox: false,
            removeComments: true,
            removeMetadata: true,
            removeUselessDefs: true,
            removeDimensions: true,
            removeEditorsNSData: true,
            removeEmptyAttrs: true,
            removeHiddenElems: true,
            removeEmptyContainers: true,
            cleanupEnableBackground: true,
            cleanupIDs: true,
            minifyStyles: true,
            collapseGroups: true,
            convertPathData: true
        }]})
    ],
    uglify: {
        compress: {
            sequences: true,
            properties: true,
            drop_debugger: true,
            dead_code: true,
            conditionals: true,
            booleans: true,
            unused: true,
            if_return: true,
            join_vars: true,
            drop_console: false,
            warnings: true
        }
    }
};