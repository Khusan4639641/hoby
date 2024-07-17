const mix = require('laravel-mix')
const path = require('path');

const Component = require('unplugin-vue-components/webpack')
const { AntDesignVueResolver, I } = require('unplugin-vue-components/resolvers')
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
mix.options({
  hmrOptions: {
    host: process.env.APP_URL,
    port: 8000,
  }
});

mix.webpackConfig({
  plugins: [
    Component({
      resolvers: [
        AntDesignVueResolver({
          importStyle: 'less',
          resolveIcons: true
        })
      ]
    })
  ],
  module: {
    rules: [{
      test: /\.less$/,
      use: [
        {
          loader: 'less-loader',
          options: {
            lessOptions: {
              modifyVars: {
                'primary-color': '#6610F5',
              },
              javascriptEnabled: true,
            },
          },
        },
      ],
    },
  ],
  },
})


// TODO: Fix problem with file-loader (https://github.com/laravel-mix/laravel-mix/issues/2756)
mix.alias({
  '@collector': path.join(__dirname, 'resources/frontend/apps/collector'),
  '@shared': path.join(__dirname, 'resources/frontend/shared'),
})

mix.js('resources/frontend/apps/collector/collectorApp.js', 'public/dist/collector').vue({ version: 3 })
mix.js('resources/frontend/apps/panel/panelApp.js', 'public/dist/panel').vue({ version: 3 })
