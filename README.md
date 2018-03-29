# Hasher
Create and verify checksums for files on your web server.

## Requirements
PHP 5.4 and newer
It wasn't tested under PHP 5.3, but I think, it will work also.

## Security
* due to high load, created by script, access to it is password-protected by standart HTTP authorization. By default, user and password are Twilight/Sparkle respectively. Change them before uploading to public-access servers!
* don't set username and password the same, as username and password, used to access to your database, FTP server or other accounts.

## Usage
* put makeHash.php on your local web server, in root folder of your project. Files from this point and deeper will be scanned.
* in Browser type URL of your local makeHash.php (for example, http://localhost/makeHash.php).
* due to absence any checksum files on your local server, script show you only "Create checksum file" option. Type any filename (can contain only latin characters, digits and underscores, you may leave default "checksum" name) and click "Create".
* after some time (depending on count of files in the project), page load will stop and you'll see a list of directories, which was scanned. At the end of the list, you may find a link to return back to main page.
* **WARNING** don't use F5 (or Reload command) in your browser - this will simply restart process of calculating checksum!
* when you turn back to main page, you will see, that there are two options now: to create checksum file and to verify shecksum. You may create any number of checksum files, so in "Verify checksum" part, you should select one of previously created files using dropdown list. Click "Verify".
* after some time, you will see a report about file checking. Errors in files are highlighted with red color, added files are highlighted with green color and removed files are striked out. At the end of the report, you may find link, redirecting you to main page again.
* upload makeHash.php script and recently created MD5 checksum file to the server, to the similar folder.
* in Browser, type URL of your remote makeHash.php (for example, http://www.myproject.com/makeHash.php).
* type your username and password. I hope, it won't be default "Twilight/Sparkle".
* select uploaded checksum file in dropdown list and click "Verify".
* a few seconds later, you will see report about differences between local files and remote files, like you see, when test files on local server.
* now, you may check at any time consistency of files in your project to make sure, that your site wasn't hacked or defaced.

## Feature
* script create standart MD5 checksum files, which may be used to check in any other program, like Total Commander.

## Known bugs and issues
* checksum file will never pass the check and will be shown as "MD5 error". Don't be afraid.
* some files MUST be different on localhost and remote host. For example, parameters for connection to database. There are no exclusions now.
* you can't verify files in folders UPPER than folder, where makeHash.php located.

## License
MIT License