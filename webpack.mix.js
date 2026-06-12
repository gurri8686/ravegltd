const mix = require('laravel-mix');
const NodePolyfillPlugin = require("node-polyfill-webpack-plugin") 
/**
 * env loader
 */
require('dotenv').config()
/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */
/**
 * env loader
 */
let webpack = require('webpack')
let dotenvplugin = new webpack.DefinePlugin({
    'process.env': {
        APP_NAME: JSON.stringify(process.env.APP_NAME || 'Aplication Name'),
        TINYMCE_URL_MIN: JSON.stringify(process.env.TINYMCE_URL_MIN || 'https://up2-cdn.dev-process.com/tinymce/tinymce.min.js'),
        LOCKDOWN: JSON.stringify(process.env.LOCKDOWN || 'n40waXgv6yjOtUWZggH8HlJ7e1fVIV3TXieii5Xn5oESL9KbZnqtPuIf0fHzLDBszDeGKkaXoqv76qIo2zffBhlmGI0Az9x0GoSWIcO7nmV6HkXFSQ0dEjAvsTp12Y5bccgFn6ATHL3L0eB28pkGo0zon5jhOxYUD6EAADlVO9bR5Ciaaxoin0zt8de1qtrBYNt1NVqY'),
    }
})

// React app (the shared CDN bundle) -> public/cdn, which is what {{ env('CDN_DOMAIN') }} serves.
// Source moved in from the ts-cdn repo so ravegltd builds its own UI bundle.
mix.js('./src/resources/js/app.js', 'public/cdn/js')
    .react()
    .extract(['react'])
    .postCss('./src/resources/css/app.css', 'public/cdn/css');

// Legacy Laravel/Vue auth scaffold (login pages) — its output is already committed at
// public/js/app.js and rarely changes. Re-enable the line below only if you need to rebuild it:
// mix.js('resources/js/app.js', 'public/js').vue().sass('resources/sass/app.scss', 'public/css');

/**
 * env loader
 */
mix.webpackConfig({
    plugins: [
        dotenvplugin,
        new NodePolyfillPlugin()
    ],
    resolve: {
        fallback: {
            fs: require.resolve('crypto-browserify')
        }
    }
});