<!DOCTYPE html>
<html>
<head>
<title>Dropbox</title>
</head>
<body>
<h1 align="center">Dropbox</h1>
<form method="GET" action="">
	<h3>File Name:</h3>
	<input  type="file" name="file"  accept="image/*"><br/><br/>
    <input  type="submit" value="Submit">
</form>
<br/><br/>
<?php

 

// these 2 lines are just to enable error reporting and disable output buffering (don't include this in you application!)
error_reporting(E_ALL);
enable_implicit_flush();
// -- end of unneeded stuff

// if there are many files in your Dropbox it can take some time, so disable the max. execution time
set_time_limit(0);

require_once("DropboxClient.php");

// you have to create an app at https://www.dropbox.com/developers/apps and enter details below:
$dropbox = new DropboxClient(array(
	'app_key' => "*****",      // Put your Dropbox API key here
	'app_secret' => "*****",   // Put your Dropbox API secret here
	'app_full_access' => false,
),'en');


// first try to load existing access token
$access_token = load_token("access");
if(!empty($access_token)) {
	$dropbox->SetAccessToken($access_token);
	}
elseif(!empty($_GET['auth_callback'])) // are we coming from dropbox's auth page?
{
	// then load our previosly created request token
	$request_token = load_token($_GET['oauth_token']);
	if(empty($request_token)) die('Request token not found!');
	
	// get & store access token, the request token is not needed anymore
	$access_token = $dropbox->GetAccessToken($request_token);	
	store_token($access_token, "access");
	delete_token($_GET['oauth_token']);
}

// checks if access token is required
if(!$dropbox->IsAuthorized())
{
	// redirect user to dropbox auth page
	$return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?auth_callback=1";
	$auth_url = $dropbox->BuildAuthorizeUrl($return_url);
	$request_token = $dropbox->GetRequestToken();
	store_token($request_token, $request_token['t']);
	die("Authentication required. <a href='$auth_url'>Click here.</a>");
}

if(isset($_GET['delete']))
{
	$dropbox->Delete($_GET['delete']);
	echo "File Deleted";
}
if(isset($_GET['file']))
//if(empty($files)) 
{
   $upd=$_GET['file'];
   $dropbox->UploadFile($upd);
   $files = $dropbox->GetFiles("",false);
   echo "Upload Complete";
 }
 $files = $dropbox->GetFiles("",false);
 ?>
<form action="" method="get">
<table border=1>
<tr>
	<th>Image</th>
	<th>Download</th>
	<th>Delete</th>
</tr>
<?php
echo "\r\n\r\n<b>Files:</b>\r\n";
foreach ($files as $x){
?>
<tr>
	<td><?= basename($x->path) ?></td>
	<td><a href="album.php?download=<?= $x->path?>">Download</a></td>
	<td><a href="album.php?delete=<?= $x->path?>">Delete</a></td>
</tr>
<?php
}
?>
</table>
</form>
<form>
<?php
if(isset($_GET['download']))
{
	//$path=$_GET['download'];
	$file=$dropbox->GetMetadata($_GET['download']);
	$test_file = "downloaded".basename($file->path);
	$path=$dropbox->GetLink($file,false);
	print $path;
   $dropbox->DownloadFile($file,$test_file);
   $files = $dropbox->GetFiles("",false);


?>
<img src="<?= $path ?>">
<?php
}
function store_token($token, $name)
{
	if(!file_put_contents("tokens/$name.token", serialize($token)))
		die('<br />Could not store token! <b>Make sure that the directory `tokens` exists and is writable!</b>');
}

function load_token($name)
{
	if(!file_exists("tokens/$name.token")) return null;
	return @unserialize(@file_get_contents("tokens/$name.token"));
}

function delete_token($name)
{
	@unlink("tokens/$name.token");
}





function enable_implicit_flush()
{
	@apache_setenv('no-gzip', 1);
	@ini_set('zlib.output_compression', 0);
	@ini_set('implicit_flush', 1);
	for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
	ob_implicit_flush(1);
	echo "<!-- ".str_repeat(' ', 2000)." -->";
}
?>
</form>
</body>
</html>
