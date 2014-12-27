zipdeploy
=========

This is a "poor man's [DevOps](http://en.wikipedia.org/wiki/DevOps) tool". With a "poor man" I mean a startup without investors.

Understand, a baba is a poor man, but he is free and wise.

Introduction
------------

zipdeploy consists of two components. The server components accept a posted zip file and extracts it in a configured folder. The client component zips a folder together and uploads it to a url.

Server
------

The server component is written in php and is initialized like this:

````php
require_once "ZipDeploy.php";

$zipDeploy=new ZipDeploy();
$zipDeploy->createTarget("hello");
$zipDeploy->dispatch();
````

The ZipDeploy.php file referenced in the `require_once` statement is [this](https://github.com/limikael/zipdeploy/blob/master/src/php/ZipDeploy.php) one. 

If we save this file as publish.php, and make it available on a url, we can then post or upload files to:

http://our.server/publish.php?target=hello

And have it extracted in the folder "hello" relative to where the script is saved. We can set some other options related to our target like this:

````php
$zipDeploy->
    createTarget("hello")->
    setTargetDir("/some/path/on/the/server")->
    setKey("my_secret_key");
````

This will cause the contents of the uploaded zip file to be extracted to `/some/path/on/the/server`, and it will also require a key to be sent along with the request. The request will have to look like this:

    http://our.server/publish.php?target=hello&key=my_secret_key

Or else the server will reject it.

Client
------

In order to talk to a server set up in the way specified above, there is also a client component. The client component comes in two flavors, one is a command line tool, the other is a grunt task. They are both contained in the same npm package, so install them with:

    npm install zipdeploy

You might want to install it using `-g` if you use the command line option and with `--save-dev` if it will be part of your npm project as a grunt task. But this is standard npm stuff, and beyond the scope of this readme file.

In order to use the command line version, run:

    zipdeploy --target=hello path/to/my/dir http://our.server/publish.php

In order to define a grunt task, use:

````javascript
grunt.initConfig({
	zipdeploy: {
		publish: {
			dir: "path/to/my/dir",
			url: "http://our.server/publish.php",
			target: "hello",
			key: "my_secret_key"
		}
	}
});
````
