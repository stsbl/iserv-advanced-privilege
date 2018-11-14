// src/Stsbl/AdvancedPrivilegeBundle/Resources/webpack.config.js
let merge = require('webpack-merge');
let path = require('path');
let baseConfig = require(path.join(process.env.WEBPACK_BASE_PATH, 'webpack.config.base.js'));

let webpackConfig = {
    entry: {
        'js/adv_priv': './assets/js/adv_priv.js',
    },
};

module.exports = merge(baseConfig.get(__dirname), webpackConfig);