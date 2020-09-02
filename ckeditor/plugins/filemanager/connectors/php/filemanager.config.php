<?php
/**
 *	Filemanager PHP connector configuration
 *
 *	filemanager.config.php
 *	config for the filemanager.php connector
 *
 *	@license	MIT License
 *	@author		Riaan Los <mail (at) riaanlos (dot) nl>
 *	@author		Simon Georget <simon (at) linea21 (dot) com>
 *	@copyright	Authors
 */


/**
	 * Simple helper to debug to the console
	 * 
	 * @param  Array, String $data
	 * @return String
	 */


/**
	 * Token generation for authorization purposes (SNews)
	 * 
	 * Added Januari 2017 by Jan-Paul Kleijn
	 */

function token() {
  $a = md5(substr(session_id(), 2, 7));
  $b = $_SERVER['HTTP_USER_AGENT'];
  $protocol = (isset($_SERVER['HTTPS']) && filter_var($_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN)?'https':'http');
  $token = md5($a.$b.$protocol."://".$_SERVER['HTTP_HOST']."/");
  return $token;
}


/**
 *	Check if user is authorized
 *
 *	@return boolean true is access granted, false if no access
 */

function auth() {
  // You can insert your own code over here to check if the user is authorized.
  // If you use a session variable, you've got to start the session first (session_start())
  $protocol = (isset($_SERVER['HTTPS']) && filter_var($_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN)?'https':'http');
  $token = token();
  return (isset($_SESSION[$protocol."://".$_SERVER['HTTP_HOST']."/Logged_In"]) && $_SESSION[$protocol."://".$_SERVER['HTTP_HOST']."/Logged_In"] == $token);
}


/**
 *	Language settings
 */
$config['culture'] = 'nl';


/**
 *	PHP date format
 *	see http://www.php.net/date for explanation
 */
$config['date'] = 'd M Y H:i';


/**
 *	Icons settings
 */
$config['icons']['path'] = 'images/fileicons/';
$config['icons']['directory'] = '_Open.png';
$config['icons']['default'] = 'default.png';


/**
 *	Upload settings
 */
$config['upload']['overwrite'] = false; // true or false; Check if filename exists. If false, index will be added
$config['upload']['size'] = false; // integer or false; maximum file size in Mb; please note that every server has got a maximum file upload size as well.
$config['upload']['imagesonly'] = false; // true or false; Only allow images (jpg, gif & png) upload?


/**
 *	Images array
 *	used to display image thumbnails
 */
$config['images'] = array('jpg', 'jpeg','gif','png');


/**
 *	Files and folders
 *	excluded from filtree
 */
$config['unallowed_files']= array('.htaccess');
$config['unallowed_dirs']= array('_thumbs','.CDN_ACCESS_LOGS', 'cloudservers');


/**
 *	FEATURED OPTIONS
 *	for Vhost or outside files folder
 */
// $config['doc_root'] = '/home/user/userfiles'; // No end slash


/**
 *	Optional Plugin
 *	rsc: Rackspace Cloud Files: http://www.rackspace.com/cloud/cloud_hosting_products/files/
 */
$config['plugin'] = null;
//$config['plugin'] = 'rsc';



//	not working yet
//$config['upload']['suffix'] = '_'; // string; if overwrite is false, the suffix will be added after the filename (before .ext)

?>
