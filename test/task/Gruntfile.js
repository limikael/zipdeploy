module.exports = function(grunt) {
	grunt.loadTasks("../../tasks");

	grunt.initConfig({
		zipdeploy: {
			all: {
				dir: "test",
				url: "http://limikael.altervista.org/",
				target: "test",
				key: "blaj"
			}
		}
	});

	grunt.registerTask("default", ["zipdeploy"])
}