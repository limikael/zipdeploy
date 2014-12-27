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

And have it extracted in the folder "hello".
