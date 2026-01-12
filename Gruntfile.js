/**
 * Gruntfile for mod_gemini
 *
 * This file configures tasks to be run by Grunt (http://gruntjs.com/)
 * for building AMD JavaScript modules.
 *
 * @copyright  2026 Sergio C
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

module.exports = function(grunt) {
    var path = require('path');

    // Project configuration.
    grunt.initConfig({
        // Extract Moodle root from environment or use parent directory
        moodleroot: process.env.MOODLE_DIR || path.resolve(__dirname, '../../../'),

        // AMD module compilation
        amd: {
            src: {
                files: [{
                    expand: true,
                    cwd: 'amd/src',
                    src: ['*.js'],
                    dest: 'amd/build',
                    ext: '.min.js'
                }]
            }
        },

        // JavaScript linting with ESLint
        eslint: {
            amd: {
                src: ['amd/src/*.js']
            }
        },

        // Watch for changes
        watch: {
            amd: {
                files: ['amd/src/*.js'],
                tasks: ['amd']
            }
        }
    });

    // Load Moodle's Grunt tasks if available
    try {
        var moodleRoot = grunt.config('moodleroot');
        grunt.loadNpmTasks('grunt-contrib-watch');

        // Try to load Moodle's Grunt config
        if (grunt.file.exists(path.join(moodleRoot, 'Gruntfile.js'))) {
            require(path.join(moodleRoot, 'Gruntfile.js'))(grunt);
        }
    } catch (e) {
        grunt.log.writeln('Warning: Could not load Moodle Grunt tasks. Run npm install in Moodle root.');
    }

    // Register default task
    grunt.registerTask('default', ['amd']);
};
