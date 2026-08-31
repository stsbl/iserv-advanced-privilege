// Ändere mich! - Toolchain

import path from 'path';
import { fileURLToPath } from 'url';
import { globSync } from 'glob';
import { WebpackManifestPlugin } from 'webpack-manifest-plugin';
import MiniCssExtractPlugin from "mini-css-extract-plugin";
import { CleanWebpackPlugin } from 'clean-webpack-plugin';
import CssMinimizerPlugin from "css-minimizer-webpack-plugin";
import CopyPlugin from 'copy-webpack-plugin';
import Babel from '@iserv/babel';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const config = {
    mode: 'production',
    entry: {
        'css/skeleton': globSync('./assets/css/*.*', {dotRelative: true}),
        'js/skeleton': globSync('./assets/js/*.*', {dotRelative: true}),
    },
    output: {
        filename: "[name].[chunkhash:8].js",
        path: path.resolve(__dirname, 'public/static'),
        publicPath: 'static/',
    },
    module: {
        rules: [
            Babel.jsBabelLoaderDefaultRule,
            {
                test: /\.(css|less)$/,
                use: [
                    {
                        loader: MiniCssExtractPlugin.loader,
                    },
                    {
                        loader: 'css-loader',
                        options: {
                            url: false
                        }
                    },
                    {
                        loader: 'less-loader',
                    },
                ]
            },
            {
                test: /\.(png|svg|jpg|jpeg|gif)$/i,
                type: 'asset/resource',
                generator: {
                    filename: 'img/[name].[hash:8][ext][query]'
                }
            },
        ]
    },
    plugins: [
        new WebpackManifestPlugin({}),
        new CleanWebpackPlugin(),
        new MiniCssExtractPlugin({
            filename: "[name].[chunkhash:8].css",
            chunkFilename: "[name].[chunkhash:8].css"
        }),
        new CssMinimizerPlugin(),
        new CopyPlugin({
            patterns: [
                {
                    from: './assets/img',
                    to: 'img/[name].[contenthash:8][ext]'
                },
                {
                    from: './node_modules/@iserv/polyfill/dist',
                    to: 'js/[name].[contenthash:8][ext]'
                },
            ]
        }),
    ],
    externals: [
        /^IServ\./i,
        {
            jquery: 'jQuery',
        }
    ],
    resolve: {
        modules: ["node_modules", "vendor"]
    },
    stats: {
        assets: true,
        chunks: false,
        children: false,
        modules: false,
        entrypoints: false,
        assetsSort: 'name',
    },
};

export default (env, argv) => {
    if (argv.mode === 'development') {
        config.devtool = 'source-map';
    }

    return config;
};
