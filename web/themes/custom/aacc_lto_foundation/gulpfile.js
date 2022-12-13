var gulp            = require('gulp'),
  autoprefixer    = require('gulp-autoprefixer'),
  changed         = require('gulp-changed'),
  cssmin          = require('gulp-cssmin'),
  del             = require('del'),
  eol             = require('gulp-eol-enforce'),
  jshint          = require('gulp-jshint'),
  sass            = require('gulp-sass'),
  sassLint        = require('gulp-sass-lint'),
  sourcemaps      = require('gulp-sourcemaps'),
  plumber         = require('gulp-plumber'),
  rename          = require('gulp-rename'),
  uglify          = require('gulp-uglify'),
    _               = require('lodash')
  ;

var themeBase = './';
var paths = {
    img: [themeBase + '/images/**/*.{gif,png,jpg,svg}'],
    js:  [themeBase + '/js/**/*.js'],
    font: [themeBase + '/fonts/**/*.{otf,eot,svg,ttf,woff,woff2}'],
    php: ['web/{modules,themes}/**/*.{php,module,inc,install,test,profile,theme}', '!web/{modules,themes}/contrib/**/*.*'],
    sass:[themeBase + '/scss/**/*.scss'],
    sassMain: [themeBase + '/scss/color_schemes/**/*.scss'],
    distCSS: themeBase + '/dist/css/',
    distImg: themeBase + '/dist/images/',
    distJS: themeBase + '/dist/js/',
    distFont: themeBase + '/dist/fonts/'
};
var sassPaths = [
    './bower_components/foundation-sites/scss',
    '../../contrib/zurb_foundation/scss',
    './bower_components/motion-ui/src',
    './fonts'
];
// Error notification settings for plumber
var plumberErrorHandler = {
};
gulp.task('clean', function(cb) {
    // Delete dynamically-generated files
    return del([paths.distCSS, paths.distImg, paths.distJS, paths.distFont]);
});
gulp.task('eol', function() {
    return gulp.src([].concat(paths.sass, paths.php, paths.js))
      .pipe(eol('\n'));
});

gulp.task('fonts', function() {
    return gulp.src(paths.font)
      .pipe(changed(paths.distFont))
      .pipe(gulp.dest(paths.distFont));
});
gulp.task('js', function() {
    return gulp.src(paths.js)
      .pipe(plumber(plumberErrorHandler))
      .pipe(changed(paths.distJS))
        // Minify and save
      .pipe(uglify())
      .pipe(gulp.dest(paths.distJS));
});
gulp.task('jshint', function() {
    return gulp.src(paths.js)
      .pipe(plumber(plumberErrorHandler))
      .pipe(jshint())
      .pipe(jshint.reporter())
      .pipe(jshint.reporter('fail'));
});
gulp.task('sass', function() {
    return gulp.src(paths.sassMain)
      .pipe(sass({
          includePaths: sassPaths
      }))
      .pipe(plumber(plumberErrorHandler))
      .pipe(sourcemaps.init())
      .pipe(sass())
      .pipe(autoprefixer({
          browsers: ['last 3 versions', 'ie >= 9']
      }))
      .pipe(gulp.dest(paths.distCSS))
      .pipe(cssmin())
      .pipe(rename({suffix: '.min'}))
      .pipe(sourcemaps.write('./'))
      .pipe(gulp.dest(paths.distCSS));
});

gulp.task('lint:sass', function() {
    return gulp.src(paths.sass)
      .pipe(sassLint())
      .pipe(sassLint.format())
      .pipe(sassLint.failOnError())
});

// Combined tasks
gulp.task('lint', function() {
    gulp.start('eol', 'jshint', 'lint:sass');
});
gulp.task('build', ['clean'], function () {
    gulp.start('sass', 'js', 'fonts');
});

gulp.task('default', function () {
    gulp.start('test', 'build');
});
gulp.task('watch', function() {
    gulp.watch(paths.sass, ['sass']);
    gulp.watch(paths.js, ['eol', 'jshint', 'js']);
    gulp.watch(paths.font, ['fonts']);
});
gulp.task('pre-commit', ['test']);
