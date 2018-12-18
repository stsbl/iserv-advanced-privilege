
let merge = require('webpack-merge');
let path = require('path');
let baseConfig = require(path.join(process.env.WEBPACK_BASE_PATH, 'webpack.config.base.js'));

let webpackConfig = {
    entry: {
        'js/form': './assets/js/form.js',
    },
};

module.exports = merge(baseConfig.get(__dirname), webpackConfig);