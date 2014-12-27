var ZipDeploy = require("../src/js/ZipDeploy");

module.exports = function(grunt) {
	grunt.registerMultiTask("zipdeploy", "Zip a directory and upload it to a url", function() {
		var done = this.async();
		var data = this.data;
		var zipDeploy = new ZipDeploy();

		if (!data.dir)
			grunt.fail.fatal("No dir to upload specified.");

		if (!data.url)
			grunt.fail.fatal("No target url specified.");

		zipDeploy.setSrcDir(data.dir);
		zipDeploy.setDestUrl(data.url);

		if (data.key)
			zipDeploy.param("key", data.key);

		if (data.target)
			zipDeploy.param("target", data.target);

		zipDeploy.run().then(done, grunt.fail.fatal);
	});
}