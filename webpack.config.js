const path = require('path');

module.exports = {
    entry: {
        'workflow-editor': './assets/workflow-editor/index.js',
        'workflow-analytics': './assets/analytics/index.js',
    },
    output: {
        path: path.resolve(__dirname, 'public'),
        filename: '[name].js',
    },
    module: {
        rules: [
            {
                test: /\.jsx?$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: [
                            '@babel/preset-env',
                            ['@babel/preset-react', { runtime: 'automatic' }],
                        ],
                    },
                },
            },
            {
                test: /\.css$/,
                use: ['style-loader', 'css-loader'],
            },
        ],
    },
    resolve: {
        extensions: ['.js', '.jsx'],
    },
};
