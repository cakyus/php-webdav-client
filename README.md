# php-webdav-client

... is under construction !

WebDav Client written in PHP

## Example

````
$client = new WebDav();

$client->connect($WEBDAV_LOCATION, $WEBDAV_USERNAME, $WEBDAV_PASSWORD);

// File Operation

$client->readFile($remoteFile);
$client->uploadFile($localFile, $remoteFile);
$client->deleteFile($remoteFile);
$client->renameFile($remoteFile, $remoteFileNew);

// Folder Operation

$client->listFolder($remoteFolder);
$client->createFolder($remoteFolder);
$client->deleteFolder($remoteFolder);
$client->renameFolder($remoteFolder, $remoteFolderNew);
````

## Configuration

Edit or create `$HOME/.env`

````
WEBDAV_LOCATION=<WebdavEndPoint>
WEBDAV_USERNAME=<YourUserName>
WEBDAV_PASSWORD=<YourPassword>
````

`WEBDAV_LOCATION` looks like https://WebdavHost/remote.php/webdav

## Command Line Interface

````
Usage: php cli.php <command> [argument..]
Commands:
  ls    List a remote directory's files and folders
  put   Copy a local file to the remote host
  get   Copy a remote file to the local PC
  rm    Delete file on remote host
  mkdir Create new folder on the remote host
  rmdir Remove folder on the remote host
  mv    Rename file or folder on remote host
````
