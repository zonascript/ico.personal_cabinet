const fs = require('fs');

const config = require('./gulp.frontend.patchs');
config['config'] = require('./gulp.frontend.configs');

module.exports = config;