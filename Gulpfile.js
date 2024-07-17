'use strict';

var gulp = require('gulp'),
    sass = require('gulp-sass'),
    del = require('del'),
    uglify = require('gulp-uglify-es').default,
    cleanCSS = require('gulp-csso'),
    order = require('gulp-order'),
    rename = require('gulp-rename'),
    merge = require('merge-stream'),
    autoprefixer = require('gulp-autoprefixer'),
    browserSync = require('browser-sync').create(),
    concat = require('gulp-concat'),
    vueCompiler = require('gulp-vue-compiler');


var paths = {
    src: {
        scss: './resources/sass/*.scss',
        fscss: './resources/sass/frontend/*.scss',
        bscss: './resources/sass/backend/*.scss',
        pscss: './resources/sass/panel/*.scss',
        blscss: './resources/sass/billing/*.scss',
        cscss: './resources/sass/cabinet/*.scss',
        js: './resources/js/*.js',
        vue: './resources/views/**/*.vue',
        vendorjs: './resources/vendor/js/*.js',
        vendorcss: './resources/vendor/css/*.css',
        vendorjson: './resources/vendor/json/*',
    },
    dest: {
        vendorcss: './public/assets/css/vendor/',
        vendorjs: './public/assets/js/vendor/',
        json: './public/assets/json/',
        css: './public/assets/css/',
        js: './public/assets/js/',
        fonts: './public/assets/fonts/',
        vue: './public/assets/vue/',
    },
};

// Clean task
gulp.task('clean', function () {
    return del([paths.dest.css + '*', paths.dest.js + '*', paths.dest.fonts + '*', paths.dest.json + '*']);
});

// Copy third party libraries from node_modules into /vendor
gulp.task('vendor:js', function () {
    return gulp.src([
        //'./node_modules/datatables/media/js/*',
        './node_modules/bootstrap/dist/js/*',
        './node_modules/vue2-datepicker/**/*',
        './node_modules/jquery/dist/*',
        './node_modules/axios/dist/*',
        './node_modules/vue/dist/*',
        './node_modules/moment/min/*',
        '!./node_modules/jquery/dist/core.js',
        './node_modules/popper.js/dist/umd/popper.*',
        './node_modules/@lottiefiles/lottie-player/dist/lottie-player.js',
        './node_modules/photoviewer/dist/photoviewer.min.css',
        './node_modules/photoviewer/dist/photoviewer.min.js',
        './node_modules/select2/dist/css/select2.min.css',
        './node_modules/select2-bootstrap-theme/dist/select2-bootstrap.min.css',
        './node_modules/select2/dist/js/select2.min.js',

    ]).pipe(gulp.dest(paths.dest.vendorjs));
});

function vendorCssSearch() {
    return gulp.src([
        './node_modules/datatables/media/css/*',
    ]).pipe(gulp.dest(paths.dest.vendorcss));
}

function vendorCssCompile() {
    return gulp.src([
        paths.dest.vendorcss + 'jquery.dataTables.min.css',
    ]).pipe(gulp.dest(paths.dest.css));
}

function vendorJsonCopy() {
    return gulp.src([
        paths.src.vendorjson,
    ]).pipe(gulp.dest(paths.dest.json));
}

function customVendorJs() {
    return gulp.src([
        paths.src.vendorjs,
    ]).pipe(order([
        'jquery.plugin.js',
        'datatables.js',
    ]))
        .pipe(uglify())
        .pipe(concat('vendor.min.js'))
        .pipe(gulp.dest(paths.dest.js))
        .pipe(browserSync.stream());
}

function customVendorCss() {
    return gulp.src([paths.src.vendorcss])
        .pipe(cleanCSS())
        .pipe(autoprefixer())
        .pipe(concat('vendor.min.css'))
        .pipe(gulp.dest(paths.dest.css));
}

function datepickerVendor() {
    let js = gulp.src(paths.dest.vendorjs + 'index.min.js').pipe(rename('datepicker.min.js')).pipe(gulp.dest(paths.dest.js));
    let css = gulp.src(paths.dest.vendorjs + 'index.css').pipe(rename('datepicker.min.css')).pipe(gulp.dest(paths.dest.css));
    let js_ru = gulp.src(paths.dest.vendorjs + 'locale/ru.js').pipe(rename('datepicker.locale.ru.js')).pipe(gulp.dest(paths.dest.js));
    let js_en = gulp.src(paths.dest.vendorjs + 'locale/en.js').pipe(rename('datepicker.locale.en.js')).pipe(gulp.dest(paths.dest.js));
    return merge(js, css, js_ru, js_en);
}

// Copy vendor's js to /dist
gulp.task('vendor:build', gulp.series(vendorJsonCopy, gulp.parallel(customVendorCss, customVendorJs, datepickerVendor), function () {
        let jsStream = gulp.src([
            paths.dest.vendorjs + 'bootstrap.bundle.min.js',
            paths.dest.vendorjs + 'jquery.min.js',
            paths.dest.vendorjs + 'axios.min.js',
            paths.dest.vendorjs + 'vue.min.js',
            paths.dest.vendorjs + 'moment.min.js',
            paths.dest.vendorjs + 'popper.min.js',
            //paths.dest.vendorjs+'jquery.dataTables.min.js'
        ]).pipe(gulp.dest(paths.dest.js));
        return jsStream;
    }),
);

gulp.task('bootstrap:scss', function () {
    return gulp.src(['./node_modules/bootstrap/scss/**/*'])
        .pipe(sass.sync({
            outputStyle: 'expanded',
        }).on('error', sass.logError))
        .pipe(cleanCSS())
        .pipe(autoprefixer())
        .pipe(concat('bootstrap.min.css'))
        .pipe(gulp.dest(paths.dest.css));
});

gulp.task('vendor:clear', function () {
    return del([paths.dest.vendorjs, paths.dest.vendorcss]);
});

gulp.task('vendor', gulp.series(gulp.parallel('vendor:js', 'bootstrap:scss'), 'vendor:build', 'vendor:clear'));

function extVueCompile() {
    return gulp.src(paths.src.vue)
        .pipe(vueCompiler({
            newExtension: 'js', // *.vue => *.js
        }))
        .pipe(gulp.dest(paths.dest.vue));
}

gulp.task('scss:backend', function compileScss() {
    return gulp.src([paths.src.bscss, paths.src.scss])
        .pipe(order([
            'all.scss',
            '*.scss',
        ]))
        .pipe(sass.sync({
            outputStyle: 'expanded',
        }).on('error', sass.logError))
        .pipe(cleanCSS())
        .pipe(autoprefixer())
        .pipe(concat('backend.min.css'))
        .pipe(gulp.dest(paths.dest.css));
});

gulp.task('scss:frontend', function compileScss() {
    return gulp.src([paths.src.fscss, paths.src.scss])
        .pipe(order([
            'all.scss',
            '*.scss',
        ]))
        .pipe(sass.sync({
            outputStyle: 'expanded',
        }).on('error', sass.logError))
        .pipe(cleanCSS())
        .pipe(autoprefixer())
        .pipe(concat('frontend.min.css'))
        .pipe(gulp.dest(paths.dest.css));
});

gulp.task('scss:panel', function compileScss() {
    return gulp.src([paths.src.pscss])
        .pipe(order([
            '*.scss',
        ]))
        .pipe(sass.sync({
            outputStyle: 'expanded',
        }).on('error', sass.logError))
        .pipe(cleanCSS())
        .pipe(autoprefixer())
        .pipe(concat('panel.min.css'))
        .pipe(gulp.dest(paths.dest.css));
});


gulp.task('scss:billing', function compileScss() {
    return gulp.src([paths.src.blscss])
        .pipe(order([
            '*.scss',
        ]))
        .pipe(sass.sync({
            outputStyle: 'expanded',
        }).on('error', sass.logError))
        .pipe(cleanCSS())
        .pipe(autoprefixer())
        .pipe(concat('billing.min.css'))
        .pipe(gulp.dest(paths.dest.css));
});


gulp.task('scss:cabinet', function compileScss() {
    return gulp.src([paths.src.cscss])
        .pipe(order([
            '*.scss',
        ]))
        .pipe(sass.sync({
            outputStyle: 'expanded',
        }).on('error', sass.logError))
        .pipe(cleanCSS())
        .pipe(autoprefixer())
        .pipe(concat('cabinet.min.css'))
        .pipe(gulp.dest(paths.dest.css));
});

gulp.task('scss:minify', gulp.parallel('scss:frontend', 'scss:backend', 'scss:panel', 'scss:billing', 'scss:cabinet'));

function devCompileScssBackend() {
    return gulp.src([paths.src.bscss, paths.src.scss])
        .pipe(order([
            'all.scss',
            '*.scss',
        ]))
        .pipe(sass.sync({
            outputStyle: 'expanded',
        }).on('error', sass.logError))
        .pipe(autoprefixer())
        .pipe(concat('backend.min.css'))
        .pipe(gulp.dest(paths.dest.css));
}

function devCompileScssFrontend() {
    return gulp.src([paths.src.fscss, paths.src.scss])
        .pipe(order([
            'all.scss',
            '*.scss',
        ]))
        .pipe(sass.sync({
            outputStyle: 'expanded',
        }).on('error', sass.logError))
        .pipe(autoprefixer())
        .pipe(concat('frontend.min.css'))
        .pipe(gulp.dest(paths.dest.css));
}

function devCompileScssPanel() {
    return gulp.src([paths.src.pscss])
        .pipe(order([
            '*.scss',
        ]))
        .pipe(sass.sync({
            outputStyle: 'expanded',
        }).on('error', sass.logError))
        .pipe(autoprefixer())
        .pipe(concat('panel.min.css'))
        .pipe(gulp.dest(paths.dest.css));
}


function devCompileScssBilling() {
    return gulp.src([paths.src.blscss])
        .pipe(order([
            '*.scss',
        ]))
        .pipe(sass.sync({
            outputStyle: 'expanded',
        }).on('error', sass.logError))
        .pipe(autoprefixer())
        .pipe(concat('billing.min.css'))
        .pipe(gulp.dest(paths.dest.css));
}

function devCompileScssCabinet() {
    return gulp.src([paths.src.cscss])
        .pipe(order([
            '*.scss',
        ]))
        .pipe(sass.sync({
            outputStyle: 'expanded',
        }).on('error', sass.logError))
        .pipe(autoprefixer())
        .pipe(concat('cabinet.min.css'))
        .pipe(gulp.dest(paths.dest.css));
}

gulp.task('scss:dev', gulp.parallel(devCompileScssBackend, devCompileScssFrontend, devCompileScssPanel, devCompileScssBilling, devCompileScssCabinet));

gulp.task('js:minify', gulp.series(extVueCompile, function () {
    return gulp.src([
        paths.src.js,
    ]).pipe(uglify())
        .pipe(concat('app.min.js'))
        .pipe(gulp.dest(paths.dest.js))
        .pipe(browserSync.stream());
}));

gulp.task('js:dev', gulp.series(extVueCompile, function () {
    return gulp.src([
        paths.src.js,
    ]).pipe(concat('app.min.js'))
        .pipe(gulp.dest(paths.dest.js))
        .pipe(browserSync.stream());
}));

gulp.task('dev', function browserDev(done) {
    gulp.watch([paths.src.scss, paths.src.fscss, paths.src.bscss, paths.src.pscss, paths.src.blscss, paths.src.cscss], gulp.series('scss:dev', function cssBrowserReload(done) {
        done(); //Async callback for completion.
    }));
    gulp.watch(paths.src.js, gulp.series('js:dev', function jsBrowserReload(done) {
        done();
    }));
    done();
});

gulp.task('build', gulp.series(gulp.parallel('scss:minify', 'js:minify'), 'vendor'));

gulp.task('default', gulp.series('clean', 'build'));
