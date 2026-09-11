import path from 'path';
import { fileURLToPath } from 'url';
import { CleanWebpackPlugin } from 'clean-webpack-plugin';
import CopyPlugin from 'copy-webpack-plugin';
import { WebpackManifestPlugin } from 'webpack-manifest-plugin';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const config = {
    mode: 'production',
    entry: {
        'js/advanced-privilege': './assets/js/advanced-privilege.js',
    },
    output: {
        path: path.resolve(__dirname, 'public/static'),
        publicPath: 'static/',
        filename: '[name].[contenthash:8].js',
    },
    plugins: [
        new CleanWebpackPlugin(),
        new WebpackManifestPlugin(),
        new CopyPlugin({
            patterns: [
                { from: './assets/img', to: 'img/[name].[contenthash:8][ext]' },
                { from: './node_modules/@iserv/polyfill/dist', to: 'js/[name].[contenthash:8][ext]' },
            ],
        }),
    ],
};

export default (env, argv) => {
    if ('development' === argv.mode) {
        config.devtool = 'source-map';
    }

    return config;
};
