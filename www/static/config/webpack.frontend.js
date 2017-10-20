const webpack = require('webpack');
const path = require('path');
const paths = require('./gulp.frontend.patchs');


module.exports = {
    // devtool: 'source-map',
    entry: paths.src.jsx_bundles,
    output: {
        path: path.resolve('./' + paths.dst.jsx),
        filename: '[name]-bundle.js'
    },
    target: "web",
    resolve: {
        alias: {
            modernizr$: path.resolve(__dirname, "./support/modernizrrc.js"),
            'react': 'preact-compat',
            'react-dom': 'preact-compat',
            // Not necessary unless you consume a module using `createClass`
            'create-react-class': 'preact-compat/lib/create-react-class',
            IntersectionObserver: 'intersection-observer'
        },
        modules: [
            'frontend/jsx',
            paths.modules.jsx,
            path.resolve('./' + paths.modules.jsx),
            'node_modules'
        ],
        extensions: ['.js', '.jsx', '.json']
    },
    module: {
        rules: [
            {
                test: /\.(js|jsx)?$/,
                exclude: /(node_modules|bower_components)/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: [
                            [ "es2015", { "modules": false }],
                            [ "es2016" ],
                            // [ "babili" ],
                            [ "react" ]
                        ],
                        plugins: [
                            ["transform-react-jsx", {
                                "pragma":"h" // default pragma is React.createElement
                            }],
                            ["module-resolver", {
                                "root": ["."],
                                "alias": {
                                    "react": "preact-compat",
                                    "react-dom": "preact-compat",
                                    // Not necessary unless you consume a module using `createClass`
                                    "create-react-class": "preact-compat/lib/create-react-class"
                                }
                            }]

                        ]
                    }
                }
            },
            {
                test: /modernizrrc(\.js)?$/,
                use: [
                    {
                        loader: 'modernizr-loader',
                        options: require(__dirname + '/support/modernizrrc.js'),
                    },
                ]
            },
        ]
    },
    plugins: [
        new webpack.optimize.UglifyJsPlugin({
            sourceMap: true,
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
                // drop_console: true,
                warnings: true
            }
        }),
        new webpack.ProvidePlugin({
            _: 'lodash',
            Promise: 'bluebird',
        }),
        new webpack.LoaderOptionsPlugin({
            minimize: true,
            debug: false,
            options: {
                context: __dirname
            }
        }),
        new webpack.DefinePlugin({
            'process.env': {
                NODE_ENV: JSON.stringify(process.env.NODE_ENV || 'development')
            }
        }),
    ]
};