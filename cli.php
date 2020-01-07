<?php

// check cli

if (PHP_SAPI != 'cli') {
	exit('Invalid PHP_SAPI');
}

function cmd_help(){
	echo 'WebDav command line interface
Usage: php cli.php <command> [argument..]
Commands:
  ls   List a remote directory\'s files and folders
  put  Copy a local file to the remote host
  get  Copy a remote file to the local PC
  del   Delete file on remote host
';
}

function main(){

	if ($_SERVER['argc'] == 1){
		return cmd_help();
	}

	if ($_SERVER['argv'][1] == 'ls'){
		return cmd_ls();
	} elseif ($_SERVER['argv'][1] == 'put'){
		return cmd_put($_SERVER['argv'][2]);
	} elseif ($_SERVER['argv'][1] == 'del'){
		return cmd_rm($_SERVER['argv'][2]);
	}

	return cmd_help();
}

function cmd_ls(){

	env_init();
	lib_init();

	$client = new WebDav();
	$client->connect(getenv('WEBDAV_LOCATION'), getenv('WEBDAV_USERNAME'), getenv('WEBDAV_PASSWORD'));

	$fileCollection = $client->getFolderItemCollection('/');
	foreach ($fileCollection as $file) {
		echo $file."\n";
	}
}

function cmd_put($localFile){

	env_init();
	lib_init();

	$remoteFile = basename($localFile);

	$client = new WebDav();
	$client->connect(getenv('WEBDAV_LOCATION'), getenv('WEBDAV_USERNAME'), getenv('WEBDAV_PASSWORD'));
	$client->uploadFile($localFile, $remoteFile);
}

function cmd_get(){

}

function cmd_del($remoteFile){

	env_init();
	lib_init();

	$client = new WebDav();
	$client->connect(getenv('WEBDAV_LOCATION'), getenv('WEBDAV_USERNAME'), getenv('WEBDAV_PASSWORD'));
	$client->deleteFile($remoteFile);
}

// Initialize environment variables

function env_init(){

	$configFile = $_SERVER['HOME'].'/.env';
	if (is_file($configFile) == false) {
		throw new \Exception("configFile is not found. $configFile");
	}

	$config = parse_ini_file($configFile);

	foreach ($config as $configName => $configValue) {
		putenv($configName.'='.$configValue);
	}
}

// Initialize libraries

function lib_init(){
	require_once('WebDav.php');
}

main();
