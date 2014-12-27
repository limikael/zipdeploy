var ZipDeploy = require("./ZipDeploy");
var minimist = require("minimist");

function usage() {
	console.log("Usage: zipdeploy [options] <srcdir> <targeturl>");
	console.log("");
	console.log("Options:");
	console.log("");
	console.log("    --target <target>   - Set parameter for 'target'.");
	console.log("    --key <key>         - Set parameter for 'key'.");
	console.log("");
	process.exit(1);
}

var argv = minimist(process.argv.slice(2));

if (argv._.length != 2)
	usage();

var zipDeploy = new ZipDeploy();
zipDeploy.setSrcDir(argv._[0]);
zipDeploy.setDestUrl(argv._[1]);

if (argv.target)
	zipDeploy.param("target", argv.target);

if (argv.key)
	zipDeploy.param("key", argv.key);

zipDeploy.run().then(
	function() {},
	function(e) {
		console.log(e);
		process.exit(1);
	}
);