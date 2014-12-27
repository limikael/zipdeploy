var Thenable = require("tinp");
var uuid = require("node-uuid");
var os = require("os");
var qsub = require("qsub");
var fs = require("fs");

/**
 * Client side functionality for zipdeploy.
 * Takes a directory, zips it together and posts
 * it to a url.
 * @class ZipDeploy
 */
function ZipDeploy() {
	this.destUrl = null;
	this.srcDir = null;
	this.parameters = {};
}

/**
 * Set destination url.
 * @method setDestUrl
 */
ZipDeploy.prototype.setDestUrl = function(destUrl) {
	this.destUrl = destUrl;
}

/**
 * Set source dir.
 * @method setSrcDir
 */
ZipDeploy.prototype.setSrcDir = function(srcDir) {
	this.srcDir = srcDir;
}

/**
 * Run.
 * @method run
 */
ZipDeploy.prototype.run = function() {
	this.runThenable = Thenable();
	this.zipFileName = os.tmpdir() + "/" + uuid.v1() + ".zip";

	this.zipJob = qsub("zip");
	this.zipJob.arg("-r");
	this.zipJob.arg(this.zipFileName);
	this.zipJob.cwd(this.srcDir);
	this.zipJob.arg(fs.readdirSync(this.srcDir));
	this.zipJob.expect(0);

	this.zipJob.run().then(
		this.onZipComplete.bind(this),
		this.runThenable.reject.bind(this.runThenable)
	);

	return this.runThenable;
}

/**
 * Set url parameter.
 * @method param
 */
ZipDeploy.prototype.param = function(name, value) {
	this.parameters[name] = value;
}

/**
 * Get full dest url, including parameters.
 * @method getFullDestUrl
 */
ZipDeploy.prototype.getFullDestUrl = function() {
	var a = [];

	for (var p in this.parameters)
		a.push(p + "=" + encodeURIComponent(this.parameters[p]));

	var s = a.join("&");

	if (this.destUrl.indexOf("?") >= 0)
		return this.destUrl + "&" + s;

	else
		return this.destUrl + "?" + s;
}

/**
 * The zip command is complete.
 * @method onZipComplete
 */
ZipDeploy.prototype.onZipComplete = function() {
	this.curlJob = qsub("curl");

	this.curlJob.arg("-s", "--form", "upload=@" + this.zipFileName);
	this.curlJob.arg(this.getFullDestUrl());
	this.curlJob.expectOutput("OK").expect(0);

	this.curlJob.run().then(
		this.onCurlComplete.bind(this),
		this.runThenable.reject.bind(this.runThenable)
	);
}

/**
 * Curl complete.
 * @method onCurlComplete
 */
ZipDeploy.prototype.onCurlComplete = function() {
	fs.unlinkSync(this.zipFileName);
	this.runThenable.resolve();
}

module.exports = ZipDeploy;