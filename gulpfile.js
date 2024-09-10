const { src, dest, watch, series } = require("gulp");
const terser = require("gulp-terser");
const jsobs = require("gulp-javascript-obfuscator");
const htmlmin = require("gulp-htmlmin");
const rename = require("gulp-rename");
const strip = require("gulp-strip-comments");

let gulpsass = require("gulp-sass")(require("sass"));
let sourcemaps = require("gulp-sourcemaps");
let cleanCSS = require("gulp-clean-css");

const isSourceMap = true;
const sourceMapWrite = isSourceMap ? "./" : false;

function scss(callback) {
    var scssFiles = "src/assets/scss/*.scss";
    var cssDest = "dist/assets/css";
    src(scssFiles)
        .pipe(sourcemaps.init())
        .pipe(gulpsass.sync().on("error", gulpsass.logError))
        .pipe(dest(cssDest));

    src(scssFiles)
        .pipe(sourcemaps.init())
        .pipe(gulpsass.sync().on("error", gulpsass.logError))
        .pipe(cleanCSS({ debug: true }))
        .pipe(rename({ suffix: ".min" }))
        .pipe(sourcemaps.write(sourceMapWrite))
        .pipe(dest(cssDest));

    callback();
}

function copyfile_php() {
    return src(["src/**/*.php"])
        .pipe(htmlmin({ collapseWhitespace: true, ignoreCustomFragments: [/<%[\s\S]*?%>/, /<\?[=|php]?[\s\S]*?\?>/], }))
        .pipe(dest("dist"));
}

function remove_comments_php() {
    return src(["dist/**/*.php"])
        .pipe(strip())
        .pipe(dest("dist"));
}

function jsmin() {
    return src(["src/assets/js/**/*.js"])
        .pipe(terser())
        .pipe(jsobs())
        .pipe(dest("dist/assets/js"));
}

// Updated function to simply copy images without conversion
function copyImages() {
    return src([
        "src/assets/img/**/*.{jpg,png,svg,ico}",
        "src/assets/img/*/**/*.{jpg,png,svg,ico}",
        "src/assets/img/*/*/**/*.{jpg,png,svg,ico}",
    ])
    .pipe(dest("dist/assets/img"));
}

function copyfile() {
    src("src/assets/libs/**/*").pipe(dest("dist/assets/libs"));
    return src("src/assets/iconfonts/**/*").pipe(dest("dist/assets/iconfonts"));
}

function watchTask() {
    watch(["src/**/*.php"], copyfile_php);
    watch(["src/assets/libs/**/*"], copyfile);
    watch(["src/assets/iconfonts/**/*"], copyfile);
    watch(["src/assets/js/**/*.js"], jsmin);
    watch(["src/assets/scss/**/*.scss"], scss);
    watch(["src/assets/img/**/*.{jpg,png,svg,ico}"], copyImages); // Updated to watch and copy images
}

exports.default = series(
    copyfile_php,
    remove_comments_php,
    jsmin,
    copyImages, // Updated to include the new image copying task
    copyfile,
    scss,
    watchTask
);
