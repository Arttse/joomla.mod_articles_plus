const fs = require('fs')
const xml2json = require('xml2json')
const gulp = require('gulp')
const zip = require('gulp-zip')

const modName = 'mod_articles_plus'
const manifestXML = fs.readFileSync('./' + modName + '/' + modName + '.xml', 'utf8')
const manifest = (JSON.parse(xml2json.toJson(manifestXML))).extension

gulp.task('create.installer', () =>
  gulp
    .src('./' + modName + '/**')
    .pipe(zip(modName + '__' + manifest.version[1] + '__installer.zip'))
    .pipe(gulp.dest('./.installers'))
)
