<?

define('__ROOT__', dirname(dirname(__FILE__)));
require_once __ROOT__."/scs-config.php";

/* FONT
* Make use of Typekit fonts by inserting your Typekit domain code here.
*/
define('_FONT','fpy7jet');

/* USE_ARTISTRY
* Make use of the artistry plugin. Possible values are:
* - true 
* - false
*/
define('_USE_ARTISTRY',false);

/* WEBSITE_DEFAULT_IMAGE
* Set a default image for use in the website, typically a logo or part thereof.
*/
define('_WEBSITE_DEFAULT_IMAGE','');

/* OVERVIEW_FULL_TEXT_PREVIEWS */
define('_OVERVIEW_FULL_TEXT_PREVIEWS',false);

/* GOOGLE_ANALYTICS_ID
* Want to use google analytics? Insert your Google Analytics ID here.
* Currently an ID is formatted by Google as 'XX-123456789-1'
*/
define('_GOOGLE_ANALYTICS_ID','UA-104414734-2');

/* CONTENT_LANGUAGE
* Language defined by syntax for UNIX, Java, etc. systems.
* Used for PHP
*/
define('_CONTENT_LANGUAGE','en_US');

/* CONTENT_LANGUAGE_BCP
* Language defined by syntax of Best Current Practice (RFC 5646).
* Used for XHTML, HTML and XML
*/
define('_CONTENT_LANGUAGE_BCP','en-US');

/* OVERVIEW_SHOW_CAT
* The category, subcategory that is shown in the overview
* Possible values are the slug of the category or an asterisk (*) to 
* show all available publications 
define('_OVERVIEW_SHOW_CAT','all-works');
*/

/* AGENDA_ITEMS_PER_PAGE
* Show the date per publication in each overview
*/
define('_AGENDA_ITEMS_PER_PAGE',10);

/* SHOW_DATE_IN_OVERVIEW
* Show the date per publication in each overview
*/
define('_SHOW_DATE_IN_OVERVIEW',false);

/* OVERVIEW_ORDERED_BY 
* How the overview will be ordered. Possible values are:
* - date
* - custom
*/
define('_OVERVIEW_ORDERED_BY', "custom");

/* OVERVIEW_ORDER
* The overview order can be ascending or descending. Possible values are:
* - asc
* - desc
*/
define('_OVERVIEW_ORDER', "asc");

/* SHORTENED TEXT DEFAULT TRESHOLD
* Can be overruled in the wysiwyg editor with inserting a horizontal rule (<hr />) into the content
* Default is set to 200 characters
*/
define('_SHORTENED_TEXT_DEFAULT_TRESHOLD', 400);

/* HOST_CONSTRUCT 
* How many directories are in the path to your web folder.
* Usually the path looks something like:
*  /home/[<username>]/public_html
*  count(explode(trim($_SERVER["DOCUMENT_ROOT"],' /')))
* Default is set to 3, the number of directories as shown in the example above.
*/
define('_HOST_CONSTRUCT', 6);

/* OVERVIEW_LOAD_ON_SCROLL
* Load the next batch of articles automatically when the user has scrolled down the page.
* The alternative is a button on the bottom of the page that needs to be clicked before a new batch
* is loaded. Possible values are:
* - true
* - false
*/
define('_OVERVIEW_LOAD_ON_SCROLL', false);

/* ----------------------------------------------------------- */

setlocale(LC_ALL,_CONTENT_LANGUAGE.'.utf8');
define('_PRE',db('prefix'));
$secure = '';
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
  $secure = 's';
}
define('_HOST','http'.$secure.'://'.$_SERVER['HTTP_HOST']);
define('_SITE',site());
define('_ADMIN',(isset($_SESSION[_HOST.'/Logged_In']) && $_SESSION[_HOST.'/Logged_In'] == token() ? true : false));
define('_FULL_ADDRESS',_HOST.catFromQuerystring());

function db($variable) {
  $db = array(
    'dbhost' => DB_HOST,
    'dbname' => DB_NAME,
    'dbuname' => DB_USER,
    'dbpass' => DB_PASSWORD,
    'prefix' => TABLE_PREFIX
  );
  return $db[$variable];
}

function token() {
  $a = md5(substr(session_id(), 2, 7));
  $b = $_SERVER['HTTP_USER_AGENT'];
  $token = md5($a.$b._HOST.'/');
  return $token;
}

function catFromQuerystring() {
  if(strpos($_SERVER['QUERY_STRING'],"=")) {
    $qs  = str_replace("index.php","",$_SERVER['PHP_SELF']);
    $qs .= str_replace("category=","",$_SERVER['QUERY_STRING']);
  } else {
    $qs = $_SERVER['PHP_SELF'];
  }
  return $qs;
}

function site() {
  $directory = dirname($_SERVER['SCRIPT_NAME']);
  $website = $directory == '/' ? _HOST.'/' : _HOST.$directory.'/';
  return $website;
}
?>
