var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .autoProvidejQuery()
    .enableVersioning()
    .addEntry('app', './assets/admin/js/app.js')
    .enableSingleRuntimeChunk()
    .addRule({
        test: require.resolve('./assets/admin/js/vendors.bundle.js'),
        use: ['script-loader']
    })
    .addRule({
        test: require.resolve('./assets/admin/js/scripts.bundle.js'),
        use: ['script-loader']
    })
;

// build the admin configuration
const admin = Encore.getWebpackConfig();

// Set a unique name for the config (needed later!)
admin.name = 'admin';

// reset Encore to build the second config
Encore.reset();

/**
Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('mobile', './assets/js/mobile.js')
    .addStyleEntry('mobile', './assets/css/mobile.less')
    .enableLessLoader()
    .enableSourceMaps(!Encore.isProduction())
;

// build the second configuration
const secondConfig = Encore.getWebpackConfig();

// Set a unique name for the config (needed later!)
secondConfig.name = 'secondConfig';

 **/

// export the final configuration as an array of multiple configurations
module.exports = [admin];

// Config for front end will implement later