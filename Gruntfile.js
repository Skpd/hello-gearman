module.exports = function (grunt) {

    grunt.initConfig({
        copy: {
            myfonts: {
                files: [
                    {
                        cwd: 'vendor/assets/bootstrap/fonts/',
                        src: ['**'],
                        dest: 'public/assets/fonts/',
                        expand: true
                    }
                ]
            }
        },

        concat: {
            options: {
                separator: ';'
            },
            js_files: {
                src: [
                    './src/js/*',
                    './vendor/assets/jquery/dist/jquery.js',
                    './vendor/assets/bootstrap/dist/js/bootstrap.js',
                ],
                dest: './public/assets/js/lib.js'
            }
        },
        less: {
            development: {
                options: {
                    compress: false
                },
                files: {
                    'public/assets/css/style.css': 'src/less/*.less'
                }
            }
        },
        uglify: {
            options: {
                mangle: false
            },
            js_files: {
                files: {
                    './public/assets/js/lib.js': './public/assets/js/lib.js'
                }
            }
        }
    });

    // Plugin loading
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-phpunit');

    // Task definition
    grunt.registerTask('default', ['copy', 'concat', 'uglify', 'less']);
};