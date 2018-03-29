<?php
/**
 * This utility generates checksum file for files on webservers.
 * Can be used to make sure, that download through FTP was performed correctly,
 * or that site wasn't hacked and files remain untouched :-)
 *
 * @author  Python aka SmiSoft
 * @version 0.1
 */

define('VALID_USER','Twilight');
define('VALID_PASSWORD','Sparkle');
define('LOG_FILE','checksum.md5');
define('CHECKSUM_EXT', '.md5');
define('DUMP_DEPTH',10);
define('COMMON_DIRECTORY_SEPARATOR','/');
define('MD5_SEPARATOR',' *');
define('MAKE_HASH_VERSION','0.1');

function GET($index,$default=null){
	return isset($_GET[$index])?$_GET[$index]:$default;
}

function POST($index,$default=null){
	return isset($_POST[$index])?$_POST[$index]:$default;
}

if (!isset($_SERVER['PHP_AUTH_USER'])) {
	header('WWW-Authenticate: Basic realm="Hash verifier utility loads server much, so is password-protected"');
	header('HTTP/1.0 401 Unauthorized');
	echo 'Hash verifier utility loads server much, so is password-protected';
	exit;
}else if (($_SERVER['PHP_AUTH_USER']!==VALID_USER) or
	($_SERVER['PHP_AUTH_PW']!==VALID_PASSWORD)) {
	header('WWW-Authenticate: Basic realm="You entered invalid login|password"');
	header('HTTP/1.0 401 Unauthorized');
	echo 'You entered invalid login|password';
	exit;
}

/**
 * Build checksum file from given directory, recursively scan at given deep
 * @param $checkSumFile resource|string file name or open for write file resource
 * @param $base string base directory, from which scan will be started, won't be output to cehcksum file
 * @param $dir string current directory, relative to base, where scan is performed, will be outputted to checksum file
 * @param $level integer recurse depth
 */
function buildChecksumFile($checkSumFile,$base,$dir='',$level=DUMP_DEPTH){
	$org=is_resource($checkSumFile);
	if(!$org)
		$checkSumFile=fopen($checkSumFile,'wt');
	if($handle=opendir($base.$dir)){
		while(($entry=readdir($handle))!==false){
			if($entry==='.') continue;
			if($entry==='..') continue;
			if(is_file($base.$dir.$entry)){
				fwrite($checkSumFile, md5_file($base.$dir.$entry).MD5_SEPARATOR.$dir.$entry.PHP_EOL);
			}else if (($level>0) and (is_dir($base.$dir.$entry))){
				print($dir.$entry.' &ndash; scanning<br>'.PHP_EOL);
				set_time_limit(10);
				buildChecksumFile($checkSumFile,$base,$dir.$entry.COMMON_DIRECTORY_SEPARATOR,$level-1);
			}
		}
		closedir($handle);
	}
	if(!$org)
		fclose($checkSumFile);
}

/**
 * Recursively scan directory at given depth, create array with filenames and their checksums
 * @param $farray array, that will be filled with records, containing fields: filename (guess, what is this) and md5 (file's checksum)
 * @param $base string base directory, from which scan will be started, won't be output to cehcksum file
 * @param $dir string current directory, relative to base, where scan is performed, will be outputted to checksum file
 * @param $level integer recurse depth
 */
function recursiveScandir(&$farray,$base,$dir,$level){
	if(!is_array($farray))
		$farray=array();
	if($handle=opendir($base.$dir)){
		while(($entry=readdir($handle))!==false){
			if($entry==='.') continue;
			if($entry==='..') continue;
			if(is_file($base.$dir.$entry))
				$farray[]=array(
					'filename'=>$dir.$entry,
					'md5'=>md5_file($base.$dir.$entry),
				);
			else if (($level>0) and (is_dir($base.$dir.$entry))){
				set_time_limit(10);
				recursiveScandir($farray,$base,$dir.$entry.COMMON_DIRECTORY_SEPARATOR,$level-1);
			}
		}
		closedir($handle);
	}
}

/**
 * Read checksum file, created with buildChecksumFile and verify all files.
 * Result is printed directly to browser.
 * @param $checkSumFile filename of checksum file, or resource, opened for read
 * @param $base string base directory, from which scan will be started, won't be output to cehcksum file
 * @param $level integer recurse depth
 * @param $showSuccess if true, then all filenames will be written, even if checksum is OK
 */
function verifyChecksum($checkSumFile,$base,$level=DUMP_DEPTH,$showSuccess=false){
	$org=is_resource($checkSumFile);
	if(!$org)
		$checkSumFile=fopen($checkSumFile,'rt');
	print('<ul>'.PHP_EOL);
	$temp=array();
	recursiveScandir($temp,$base,'',$level);
	while($line=fgets($checkSumFile)){
		$p=explode(MD5_SEPARATOR,rtrim($line));
		if(count($p)<2)
			continue;
		// файл существует?
		foreach ($temp as &$value){
			if($value['filename']===$p[1]){
				if($value['md5']===$p[0]){
					if($showSuccess)
						print('<li class="success">'.$value['filename'].' &ndash; OK</li>'.PHP_EOL);
				}else{
					print('<li class="fail">'.$value['filename'].' &ndash; MD5 error</li>'.PHP_EOL);
				}
				$value['filename']='';
				$p[1]='';
				break;
		 	}
		}
		if($p[1]!=='')
			print('<li class="removed">'.$p[1].' &ndash; removed</li>'.PHP_EOL);
		set_time_limit(10);
	}
	if(!$org)
		fclose($checkSumFile);
	foreach ($temp as $value){
		if($value['filename']!==''){
			print('<li class="added">'.$value['filename'].' &ndash; added</li>'.PHP_EOL);
		}
	}
	print('</ul>'.PHP_EOL);
}

function printChecksumFiles($files){
	if(count($files)==0)
		return;
	print('<form method="post">'.PHP_EOL);
	print('<h2>Verify checksum</h2>'.PHP_EOL);
	print('<input type="hidden" name="mode" value="check"/>'.PHP_EOL);
	print('<label for="filename">Checksum file:</label>'.PHP_EOL);
	print('<select id="filename" name="filename">'.PHP_EOL);
	foreach ($files as $file)
		print('<option value="'.$file.'">'.ucfirst(substr($file,0,-4)).'</option>'.PHP_EOL);
	print('</select>'.PHP_EOL);
	print('<center><input type="submit" value="Verify"/></center>');
	print('</form>'.PHP_EOL);
}

function printGenerateForm(){
	print('<form method="post">'.PHP_EOL);
	print('<h2>Create checksum file</h2>'.PHP_EOL);
	print('<input type="hidden" name="mode" value="create"/>'.PHP_EOL);
	print('<label for="newfilename">New checksum file</label>'.PHP_EOL);
	print('<input type="text" id="newfilename" name="newfilename" value="'.substr(LOG_FILE,0,-4).'" maxlength="8">'.PHP_EOL);
	print('<center><input type="submit" value="Create"/></center>');
	print('</form>'.PHP_EOL);
}

function checkFilename($filename){
	return (strlen($filename)<=8) and (preg_match('/^[a-zA-Z0-9_]+$/',$filename)===1);
}

$checkfiles=array();
foreach (scandir(dirname($_SERVER['SCRIPT_FILENAME'])) as $filename) {
	if(substr($filename, -4)===CHECKSUM_EXT)
		$checkfiles[]=$filename;
}

?><!DOCTYPE HTML><html lang='ru'>
<head>
	<meta charset="utf-8" />
	<title>Checksum verify&amp;generate v.<?=MAKE_HASH_VERSION ?></title>
	<style>
	li.success{

	}
	li.added{
		color: green;
		font-weight: bold;
	}
	li.removed{
		text-decoration: line-through;
	}
	li.fail{
		color: red;
	}
	form{
		border: 1px;
		width: 23em;
		background-color: azure;
	}
	form label{
		width: 10em;
		margin: 0.5em 1em;
		display:inline-block;
	}
	form input,form select{
		width: 10em;
		margin: 0.5em 1em;
	}
	p.error{
		width: 100%;
		text-align: center;
		background-color: red;
		font-size: +2;
	}
	</style>
</head>
<body>
	<?php
	switch (POST('mode','')) {
		case 'check':
			print('<h1>Checksum verification</h1>'.PHP_EOL.'<p>Please, wait a second...</p>'.PHP_EOL);
			$filename=POST('filename','');
			if(in_array($filename, $checkfiles)){
				verifyChecksum($filename,dirname($_SERVER['SCRIPT_FILENAME']).COMMON_DIRECTORY_SEPARATOR);
				print('<p>Test complete, <a href="'.$_SERVER['PHP_SELF'].'">return back</a>.</p>');
			}else{
				print('<p class="error">Error transmitting form! Such filename not found on server!</p>');
			}
			break;
		case 'create':
			print('<h1>Creating checksum</h1>'.PHP_EOL.'<p>Counting checksum, please wait until page loads completely and scroll to it\'s end...</p>'.PHP_EOL);
			$filename=POST('newfilename','');
			if(checkFilename($filename)){
				buildChecksumFile($filename.CHECKSUM_EXT,dirname($_SERVER['SCRIPT_FILENAME']).COMMON_DIRECTORY_SEPARATOR);
				print('<p>Count complete, checksum saved, <a href="'.$_SERVER['PHP_SELF'].'">return back</a>.</p>');
			}else{
				print('<p class="error">Invalid filename specified (use only latin characters, digits and underscores, no more than 8 characters length)!</p>');
			}
			break;
		default:
			print('<h1>Select mode</h1>'.PHP_EOL);
			printGenerateForm();
			printChecksumFiles($checkfiles);
	}?>
</body>
</html>