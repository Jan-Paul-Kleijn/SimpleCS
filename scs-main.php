<?php
define('_DATEFORM',d_format());
if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() === 1){
  $_POST = array_map( 'stripslashes', $_POST );
  $_GET = array_map( 'stripslashes', $_GET );
  $_COOKIE = array_map( 'stripslashes', $_COOKIE );
}
$dblink = connect_to_db();
$val = mysqli_query($dblink, 'select 1 FROM '._PRE.'articles LIMIT 1');
if($val === FALSE) {
  include('lang/NL.php');
  exit(notification(0,'db_tables_error',false));
}
$language_array = array('EN'=>'English','NL'=>'Nederlands');
if(s('language') != 'EN' && file_exists('lang/'.s('language').'.php') == true) include('lang/'.s('language').'.php');
else include('lang/EN.php');

$standaard_categorieen = array(l('archive'),'contact','agenda','sitemap',s('login_url'));
$l['cat_listSEF'] = implode(",",$standaard_categorieen);
if(_ADMIN) {
  $l['cat_listSEF'] .= ',action,orders,show_order,process,extra_contents,logout,groupings,admin_groupings';
  $l['cat_listSEF'] .= ',administration,scs-category,admin_article,scs-article,extra_new,scs-page,scs-event';
  $l['cat_listSEF'] .= ',scs-categories,scs-articles,scs-pages,scs-events,scs-settings';
}
$l['divider'] = '-';
$l['paginator'] = 'p';
$l['comment_pages'] = 'c';
$l['ignored_items'] = '.,..,backup,cgi-bin,.htaccess,Thumbs.db,snews.php,index.php,lib.php,css,lang,js,'.s('language').'.php,ckeditor,icons,php,error,stats,images';

if(isset($_POST['Loginform']) && !_ADMIN) {
  $user = checkUserPass($_POST['uname']);
  $pass = checkUserPass($_POST['pass']);
  unset($_POST['uname'],$_POST['pass']);
  if (checkMathCaptcha() && md5($user) === s('username') && md5($pass) === s('password')) {
    $_SESSION[_SITE.'Logged_In'] = token();
    exit(notification(0,l('login'),'administration'));
  } else { 
    exit(notification(1,l('err_Login'),s('login_url')));
  }
}
if(array_key_exists('submit_text',$_POST) && !_ADMIN){
  die (notification(2,l('error_not_logged_in'),'home'));
}
function getCategorySEF($urlArray) {
  global $standaard_categorieen;
  $categorySEF = "404";
  if(count($urlArray) > 0) {
    if(($urlArray[0] == s('overview_pagename') && count($urlArray) > 1) && isMainCategory($urlArray[0])===false) {
      $categorySEF = "404";
    } elseif(in_array($urlArray[0], $standaard_categorieen)===false) {
      if(count($urlArray) == 1) {
        if(isPage($urlArray[0]) || $urlArray[0] == s('overview_menuname') || isMainCategory($urlArray[0])===true || false !== strpos($urlArray[0], 'feeded-') || count(array_filter($standaard_categorieen,"isiegelijk")) != 0 || (_ADMIN && frontendMode()===false)) {
          $categorySEF = $urlArray[0];
        }
      } elseif(count($urlArray) == 2) {
        if(isMainCategory($urlArray[0]) || isSubCategory($urlArray[0])) {
          if(isArticle($urlArray[1]) || isSubCategory($urlArray[1])) $categorySEF = $urlArray[0];
        }
      } elseif(count($urlArray) == 3) {
        if(isMainCategory($urlArray[0])) {
          if(isSubCategory($urlArray[1])) {
            if(isArticle($urlArray[2]) || ((substr( $urlArray[2], 0, 1) == l('paginator') || substr( $urlArray[2], 0, 1) == l('comment_pages')) && is_numeric(trim(substr($urlArray[2], 1)),' /'))) $categorySEF = $urlArray[0];
          }
        }
      } elseif(count($urlArray) == 4) {
        if(isMainCategory($urlArray[0])) {
          if(isSubCategory($urlArray[1])) {
            if(isArticle($urlArray[2])) {
              if((substr( $urlArray[3], 0, 1) == l('paginator') || substr( $urlArray[3], 0, 1) == l('comment_pages')) && is_numeric(trim(substr($urlArray[3], 1)),' /')) $categorySEF = $urlArray[0];
            }
          }
        } elseif(in_array($urlArray[0], $standaard_categorieen)) {
          $categorySEF = $urlArray[0];
        }
      } elseif(in_array($urlArray[0], $standaard_categorieen)) {
        $categorySEF = $urlArray[0];
      }
    } else {
      $categorySEF = $urlArray[0];
    }
  }
  $categorySEF = $categorySEF == "404" ? $categorySEF : clean(cleanXSS($categorySEF));
  return $categorySEF;
}
if(array_key_exists('category',$_GET)) {
  $url = explode('/', trim(clean($_GET['category'])," /"));
  $categorySEF = getCategorySEF($url);
}

if(array_key_exists('category',$_GET) && in_array($categorySEF, $standaard_categorieen)===false) {
  if(isset($url[1])) {
    if(isSubCategory($url[1])) {
      $subcatSEF = clean($url[1]);
    } elseif(isArticle($url[1])) {
      $articleSEF = clean($url[1]);
    }
  }
//  if(isset($url[1])) $subcatSEF = clean($url[1]);
  if (substr($url[0], 0, 1) == l('comment_pages') && is_numeric(substr($url[0], 1, 1))) $commentsPage = clean($url[0]);
  if (isset($url[2])) $articleSEF= clean($url[2]);
  if (isset($url[3])) $commentsPage = clean($url[3]);
  if (_ADMIN) {
    $pub_a = ''; $pub_c = ''; $pub_x = '';
  } else {
    $pub_a = ' AND a.published = 1';
    $pub_c = ' AND c.published =\'YES\'';
    $pub_x = ' AND x.published =\'YES\'';
  }
  $articleSEF = isset($articleSEF)?$articleSEF:'';
  $subcatSEF = isset($subcatSEF)?$subcatSEF:'';

  if ($articleSEF && (is_numeric(trim(substr($articleSEF, 1),' /')) && (substr($articleSEF, 0, 1) == l('paginator') || substr( $articleSEF, 0, 1) == l('comment_pages')))) {
    $MainQuery = 'SELECT
      a.id AS id, title, position, description_meta, keywords_meta,
      c.id AS catID, c.name AS name, c.description, x.name AS xname
      FROM '._PRE.'articles AS a,
        '._PRE.'categories AS c
      LEFT JOIN '._PRE.'categories AS x
        ON c.subcat=x.id
      WHERE a.category=c.id
        '.$pub_a.$pub_c.$pub_x.'
        AND x.seftitle="'.$categorySEF.'"
        AND c.seftitle="'.$subcatSEF.'"
        AND a.seftitle="'.$articleSEF.'"
    ';
  } elseif($subcatSEF && (is_numeric(trim(substr($subcatSEF, 1),' /')) && (substr($subcatSEF, 0, 1) == l('paginator') || substr( $subcatSEF, 0, 1) == l('comment_pages')))) {
    $Try_Article = mysqli_query($dblink,'SELECT
        a.id AS id, title, position, description_meta, keywords_meta,
        c.id as catID, name, description, subcat
      FROM '._PRE.'articles AS a
      LEFT JOIN '._PRE.'categories AS c
        ON category =  c.id
      WHERE c.seftitle = "'.$categorySEF.'"
        AND a.seftitle ="'.$subcatSEF.'"
        '.$pub_a.$pub_c.'
        AND subcat = 0
    ');
    $R = mysqli_fetch_assoc($Try_Article);
    if(empty($R)) {
      $MainQuery = 'SELECT c.id AS catID, c.name AS name, c.description, c.subcat, x.name AS xname FROM '._PRE.'categories AS x LEFT JOIN '._PRE.'categories AS c ON c.subcat = x.id WHERE x.seftitle = "'.$categorySEF.'" AND c.seftitle = "'.$subcatSEF.'" '.$pub_c.$pub_x;
    }
  } elseif($categorySEF && (is_numeric(trim(substr($subcatSEF, 1),' /')) && (substr($subcatSEF, 0, 1) == l('paginator') || substr( $subcatSEF, 0, 1) == l('comment_pages')))) {
    $Try_Article = mysqli_query($dblink, 'SELECT id, title, position, category, description_meta, keywords_meta FROM '._PRE.'articles AS a WHERE seftitle = "'.$categorySEF.'" '.$pub_a);
    $R = mysqli_fetch_assoc($Try_Article);
    if(empty($R)) {
      $MainQuery ="SELECT id AS catID, name, description FROM "._PRE."categories AS c WHERE seftitle = '".$categorySEF."' AND subcat = 0 ".$pub_c;
    }
  } else {
    switch(true):
      case ((is_numeric(trim(substr($subcatSEF, 1),' /')) && (substr($subcatSEF, 0, 1) == l('paginator') || substr( $subcatSEF, 0, 1) == l('comment_pages')))) :
        break;
      case (false !== strpos($categorySEF, 'feeded-')) :
        die(rss_contents($categorySEF));
      default:
        if($articleSEF=='') {
          if(isPage($categorySEF)) {
            $MainQuery = "SELECT id, title, position, category, description_meta, keywords_meta FROM "._PRE."articles AS a WHERE seftitle = '".$categorySEF."' AND position=3 ".$pub_a;
          } else {
            if($subcatSEF!='') {
              $needle = $subcatSEF;
            } elseif($categorySEF!='') {
              $needle = $categorySEF;
            }
            $MainQuery = "SELECT id AS catID, name, description FROM "._PRE."categories AS c WHERE seftitle = '".$needle."' ".$pub_c;
          }
        } else {
          $MainQuery = "SELECT id, title, position, category, description_meta, keywords_meta FROM "._PRE."articles AS a WHERE seftitle = '".$articleSEF."' AND position=1 ".$pub_a;
        }
    endswitch;
  }
 	if (!empty($MainQuery)){
	 	 $Mainresult = mysqli_query($dblink,$MainQuery);
    if(strpos($_GET['category'],"/")) {
      $tussengedoe = explode("/",$_GET['category']);
      $_GET['category'] = $tussengedoe[0];
      $eventTitle = array_pop($tussengedoe);
    }
    if (mysqli_num_rows($Mainresult) === 1 ) {
	 	 	 $R = mysqli_fetch_assoc($Mainresult);
	 	 } else if(!in_array(str_replace("/","",$_GET['category']),explode(',',l('cat_listSEF'))) && $_GET['category'] != "" && $_GET['category'] != s('overview_menuname')) {
	 	  	$categorySEF = '404';
	  	 	unset($subcatSEF,$articleSEF);
    }
	  	update_articles();
	 }
} elseif(array_key_exists('action',$_GET)) {
  $a = cleanASCII($_GET['action']);
  if(_ADMIN===false && in_array($a, $standaard_categorieen)===false) {
    $url[] = '404';
  } else {
    $url[] = $a;
  }
} else {
  $url = [];
  if (s('display_page') !== 0) {
    $_ID = s('display_page');
    $_POS = 3;
  }
}

if(!empty($R['category'])) $_CAT = $R['category'];
if(!empty($R['category'])) $_CAT = $R['category'];
if(!empty($R['id'])) $_ID = $R['id'];
else $_ID = "";
if(!empty($R['title'])) $_TITLE = $R['title'];
if(!empty($R['position'])) $_POS = $R['position'];
if(!empty($R['catID'])) $_catID = $R['catID'];
if(!empty($R['name'])) $_NAME = $R['name'];
if(!empty($R['xname'])) $_XNAME = $R['xname'];
if(!empty($R['keywords_meta'])) $_KEYW = $R['keywords_meta'];
if(!empty($R['description_meta']))  $_DESCR = $R['description_meta'];
else $_DESCR = s('website_description');  //else $_DESCR = $R['description'];
if(isset($url[3]) && empty($_XNAME)) $commentsPage = $url[2];

function isHomepage() {
  if(array_key_exists('category',$_GET)) {
  } else {
    if(!_ADMIN || frontendMode()) {
      return true;
    }
  }
  return false;
}
function getIdentity() {
  $getIdentity = trim($_SERVER['PHP_SELF'],"/");
  $identity = explode("/",$getIdentity)[0];
  return $identity;
}
function is404() {
  global $url,$categorySEF;
  if($categorySEF == "404" || (!empty($url) && $url[0] == "404")) return true;
  return false;
}
function doctype() {
  global $url,$categorySEF;
  $xml_excludes=array("scs-article","scs-category","admin_article","extra_new","scs-page","scs-event","administration","scs-settings","scs-articles","scs-pages","?");
  $offset = 60 * 60 * 24 * 30;
  $ExpStr = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";

  if((count($url) > 3 && (substr($url[3], 0, 1) != l('comment_pages') && is_numeric(substr($url[3], 1, 1))===false)) || is404() ) { 
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    $_SERVER['REDIRECT_STATUS'] = 404;
  }

  $mimetype = (isset($_SERVER) && array_key_exists('HTTP_ACCEPT',$_SERVER) && stristr($_SERVER['HTTP_ACCEPT'],"application/xhtml+xml")) ? "application/xhtml+xml" : "text/html";
  if($mimetype == "application/xhtml+xml" && frontendMode()===true) {
    header('Content-type: application/xhtml+xml; charset=utf-8');
    header('Cache-Control: must-revalidate');
//    header($ExpStr);
    $doctype  ="<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $doctype .="<html xmlns=\"http://www.w3.org/1999/xhtml\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.w3.org/MarkUp/SCHEMA/xhtml11.xsd\" xml:lang=\""._CONTENT_LANGUAGE_BCP."\">\n";
    $doctype .="  <head>\n";
    $doctype .="    <meta name=\"robots\" content=\"index, follow\" />\n";
  } else {
    header('Content-type: text/html; charset=UTF-8');
    header('Cache-Control: must-revalidate');
//    header($ExpStr);
    $doctype ="<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
    $doctype.="<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\""._CONTENT_LANGUAGE_BCP."\">\n";
    $doctype.="  <head>\n";
    $doctype.="    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
    $doctype.="    <meta name=\"robots\" content=\"index, follow\" />\n";
  }

  if($mimetype == "application/xhtml+xml" && frontendMode()===true) {
  } else {
  }
  echo $doctype;
  
}
function isPage($slug) {
  global $dblink;
  $slug = clean(cleanXSS($slug));
  $query = mysqli_fetch_array(mysqli_query($dblink,"SELECT COUNT(*) FROM "._PRE."articles WHERE seftitle = '".$slug."' AND position = 3 AND published = 1"))[0];
  if($query == 0) {
    return false;
  }
  return true;
}
function isMainCategory($mc) {
  global $dblink;
  $mc = clean(cleanXSS($mc));
  $q = mysqli_query($dblink,"SELECT COUNT(*) FROM "._PRE."categories WHERE seftitle = '".$mc."' AND subcat = 0 AND published = 'YES'");
  $a = mysqli_fetch_array($q,MYSQLI_NUM);
  $numberOf = (int)$a[0];
  if($numberOf < 1) {
    return false;
  }
  return true;
}
function frontendMode() {
  if(array_key_exists('action',$_GET) && _ADMIN) return false;
  else {
    $backend_and_semibackend_slugs = explode(",", l('cat_listSEF'));
    $semibackend_slugs = array(l('archive'),'contact','agenda','sitemap',s('login_url'));
    $isBackendPage = array_walk(
      $backend_and_semibackend_slugs,
      function($v)use(&$backend) {
        global $url;
        if(count($url)>0 && $v==$url[0]) {
          $backend[]=$v;
        }
      }
    );
    $semibackend = [];
    $isSemibackendPage = array_walk(
      $semibackend_slugs,
      function($v)use(&$semibackend) {
        global $url;
        if(count($url)>0 && $v==$url[0]) {
          $semibackend[]=$v;
        }
      }
    );
    if(empty($backend) || count($semibackend)>0) return true;
    else return false;
  }
}
function isCategory($c) {
  global $dblink;
  $c = clean(cleanXSS($c));
  $query = mysqli_fetch_array(mysqli_query($dblink,"SELECT COUNT(*) FROM "._PRE."categories WHERE seftitle = '".$c."' AND position = 1 AND published = 'YES'"))[0];
  if($query === 0) {
    return false;
  }
  return true;
}
function isArticle($a) {
  global $dblink;
  $a = clean(cleanXSS($a));
  $query = mysqli_fetch_array(mysqli_query($dblink,"SELECT COUNT(*) FROM "._PRE."articles WHERE seftitle = '".$a."' AND position = 1 AND published = 1"))[0];
  if($query == 0) {
    return false;
  }
  return true;
}
function isEvent($e) {
  global $dblink;
  $e = clean(cleanXSS($e));
  $query = mysqli_fetch_array(mysqli_query($dblink,"SELECT COUNT(*) FROM "._PRE."articles WHERE seftitle = '".$e."' AND position = 4 AND published = 1"))[0];
  if($query == 0) {
    return false;
  }
  return true;
}
function isSubCategory($sc) {
  global $dblink;
  $sc = clean(cleanXSS($sc));
  $query = mysqli_fetch_array(mysqli_query($dblink,"SELECT COUNT(*) FROM "._PRE."categories WHERE seftitle = '".$sc."' AND subcat > 0 AND published = 'YES'"))[0];
  if($query == 0) {
    return false;
  }
  return true;
}
function title() {
  global $categorySEF, $_DESCR, $_KEYW, $_TITLE, $_NAME, $_XNAME, $_ID, $eventTitle, $url;
  $dblink = connect_to_db();
  $catname = '';
  $isCategory = false;
  $subcatSEF = '';
  $identity = $categorySEF == "" ? s('home_sef') : $categorySEF;

  if(count($url) == 2) {
    if(isMainCategory($url[0]) && isSubCategory($url[1])) {
      $subcatSEF = $url[1];
    }
  }

  if(array_key_exists('category',$_GET)) {
    $catlistarray = explode("/", rtrim($_GET['category'],'/') );
    $lastcat = clean(cleanXSS(end($catlistarray)));
  } else $lastcat = '';
  if(array_key_exists('action',$_GET) && array_key_exists('id',$_GET)) {
    $action = clean(cleanXSS($_GET['action']));
    $id = clean(cleanXSS($_GET['id']));
    if($action=="scs-category") {
      $catname = mysqli_query($dblink, "SELECT name FROM "._PRE."categories WHERE id='".$id."'");
      $isCategory = true;
    }
  }
  if($catname == '') {
    $sef = $subcatSEF!='' ? $subcatSEF : $categorySEF;
    $catname = mysqli_query($dblink, "SELECT name FROM "._PRE."categories WHERE seftitle='".$sef."'");
  }  
  if($_ID == "" && array_key_exists('id',$_GET)) $_ID = $_GET['id'];
  $gethomepage = $_ID=="" && $categorySEF=="" ? " OR id='".s('display_page')."'" : "";
  $artname = mysqli_query($dblink, "SELECT title,position FROM "._PRE."articles WHERE id=".$_ID);
  $homepagename = mysqli_query($dblink, "SELECT title FROM "._PRE."articles WHERE id='".s('display_page')."'");
  $artdesc = mysqli_query($dblink, "SELECT text,description_meta FROM "._PRE."articles WHERE id=".$_ID);
  $image = array();
  if($artname && mysqli_num_rows($artname)!=0 && $isCategory === false) {
    $arti = mysqli_fetch_array($artname);
    $artTitle = $arti['title'];
    $artPos = $arti['position'];
    $artdAr = mysqli_fetch_array($artdesc);
    if(preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $artdAr['text'], $image)) {
      $image_url_array = explode('/',$image['src']);
      $image_url_has_protocol = false; 
      if($image['src'] != "") {
        if(is_array($image_url_array)) {
          if(!substr($image_url_array[0],-1) == ':') $image['src'] = _HOST."/".$image['src'];
        }
      } else {
        $image['src'] = _HOST."/images/facebook_logo.jpg";
      }
    }
    $artd = trim($artdAr['description_meta']," ")!="" ? $artdAr['description_meta'] : tease($artdAr['text'],4,"\n",'YES');
  } else {
    if(array_key_exists('search_query',$_POST)) {
      $artTitle = l('search');
    } elseif(array_key_exists('action',$_GET)) {
      $artTitle = l($_GET['action']);
    } elseif(l($categorySEF)!==false) {
      $artTitle = l($categorySEF);
    } else {
      $artTitle = str_replace(array("-","_"),array(" "," "),$categorySEF);
    }
  }
  if(mysqli_num_rows($catname)!=0) {
    $rt = mysqli_fetch_array($catname);
    $title = $_TITLE=="" ? ucfirst($rt['name']) . " | " : ucfirst($_TITLE) . " | ";
  } else {
    if($categorySEF=="404" && $identity=="") $title = ucfirst(l('error_404_title')) . " | ";
    elseif($identity==="agenda") {
      if($eventTitle!="") $title = ucfirst($eventTitle) . " | ";
      else $title = ucfirst($identity) . " | ";
    }
    elseif($_ID == s('display_page')) {
      $rt = mysqli_fetch_array($homepagename);
      $title = ucfirst($rt['title']) . " | ";
    }
    elseif($_TITLE=="") {
      if($artTitle!='') $title = ucfirst($artTitle). " | ";
      else {
       if($identity!='') $title = "";
       else $title = ucfirst(s('overview_menuname')). " | ";
      }
    }
    else $title = ucfirst($_TITLE) . " | ";
  }
  $title .= s('website_title');
  $title = stripslashes(htmlspecialchars(entity($title), ENT_QUOTES, 'UTF-8', FALSE));
  echo "    <base href=\""._HOST."\" />\n";
  echo "    <title>".$title."</title>\n";
  echo "    <meta name=\"description\" content=\"".(!empty($_DESCR) ? $_DESCR : s('website_description'))."\" />\n";
  echo "    <meta name=\"keywords\" content=\"".(!empty($_KEYW) ? $_KEYW : s('website_keywords'))."\" />\n";
  echo "    <meta property=\"og:title\" content=\"".ucfirst($title)."\" />\n";
  echo "    <meta property=\"og:type\" content=\"website\" />\n";
  echo "    <meta property=\"og:site_name\" content=\"".s('website_title')."\" />\n";
  echo "    <meta property=\"og:url\" content=\"".htmlspecialchars(_FULL_ADDRESS, ENT_QUOTES, 'UTF-8')."\" />\n";
  $thisDesc = trim(strip_tags(entity((!empty($artd)?$artd:(!empty($_DESCR) ? $_DESCR : s('website_description'))))));
  echo "    <meta property=\"og:description\" content=\"".(!empty($artd)?$artd:s('website_description'))."\" />\n";
  if(array_key_exists('src',$image)) {
    echo "    <meta property=\"og:image\" content=\"".$image['src']."\" />\n";
  } elseif(_WEBSITE_DEFAULT_IMAGE != '') {
    echo "    <meta property=\"og:image\" content=\""._WEBSITE_DEFAULT_IMAGE."\" />\n";
  }
  echo "    <meta property=\"fb:admins\" content=\"".s('facebook_admin')."\" />\n";
  echo "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" />\n";
  if(frontendMode()) {
    echo "    <link rel=\"stylesheet\" href=\"/font/font-loader.css?ver=5.2.3\" type=\"text/css\" media=\"all\" />\n";
    echo "    " . csscrush_tag("/css/styles.css");
    echo "    <script type=\"text/javascript\" src=\""._HOST."/js/script.js\"></script>\n";
    if(_USE_ARTISTRY===true) {
      echo "    <script type=\"text/javascript\" src=\"/js/artistry.js\"></script>\n";
    }
  } else {
    echo "    <link rel=\"stylesheet\" type=\"text/css\" href=\"css/cms.css\" media=\"screen\" />\n";
    echo "    <script type=\"text/javascript\" src=\"js/cms.js\"></script>\n";
  }
  if(_GOOGLE_ANALYTICS_ID != ''){
    echo "    <script type=\"text/javascript\">(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){;(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');ga('create', '"._GOOGLE_ANALYTICS_ID."', 'auto');ga('send', 'pageview');</script>\n";
  }
  echo "  </head>\n";
}
function logout_link() {
	if(_ADMIN) {
    $logout  = "<div class=\"logout center\">\n";
    $logout .= "  <a href=\"administration\" title=\"".l('administration')."\">".l('administration')."</a> ".l('divider')."\n";
    $logout .= "  <a href=\"logout\" title=\"".l('logout')."\">".l('logout')."</a>\n"; 
    $logout .= "</div>\n";
  } else {
    $logout = "";
  }
	echo $logout;
}
function login_link() {
  if(_ADMIN) {
    return "<li><a class=\"logout\" href=\"logout\" title=\"".ucfirst(l('logout'))."\" tabindex=\"".tabindex()."\">".ucfirst(l('logout'))."</a></li>\n";
  } else {
    return "<li><a class=\"login\" href=\"login\" title=\"".ucfirst(l('login'))."\" tabindex=\"".tabindex()."\">".ucfirst(l('login'))."</a></li>\n";
  }
}
function getCatIdBySEF($catsef) {
  $dblink = connect_to_db();
  $excludeHidden = _ADMIN ? "NO" : "YES";
  $resObj = mysqli_query($dblink,"SELECT id FROM "._PRE."categories WHERE seftitle='".$catsef."' AND subcat=0 AND (published = 'YES' OR published = '".$excludeHidden."')");
  return mysqli_fetch_assoc($resObj)['id'];
}
function categories($nrofcats=3) {
  global $categorySEF;
  $dblink = connect_to_db();
  $qwr = !_ADMIN ? ' AND a.visible=\'YES\'' : '';
  if (s('num_categories') == 'on') {
    $count = ', COUNT(DISTINCT a.id) as total';
    $join = 'LEFT OUTER JOIN '._PRE.'articles AS a
      ON (a.category = c.id AND a.position = 1  AND a.published = 1'.$qwr.')';
  } else {
    $count ='';
    $join='';
  }
  $result = mysqli_query($dblink, 'SELECT c.seftitle, c.name, description, c.id AS parent'.$count.' FROM '._PRE.'categories AS c '.$join.' WHERE c.subcat = 0 AND c.published = \'YES\' GROUP BY c.id ORDER BY c.catorder,c.id LIMIT '.$nrofcats.'');
  if (mysqli_num_rows($result) > 0){
    while ($r = mysqli_fetch_array($result)) {
      $category_title = $r['seftitle'];
      $r['name'] = (s('language')!='EN' && $r['name'] == 'Uncategorized' && $r['parent']==1) ? ucfirst(l('uncategorised')) : $r['name'];
      $class = $category_title == $categorySEF ? ' class="current"' : '';
      if (isset($r['total'])) { $num='('.$r['total'].')'; }
      echo '<li><a'.$class.' href="'.$category_title.'/" title="'.$r['name'].' - '.$r['description'].'" tabindex="'.tabindex().'">'.$r['name'].$num.'</a>';
      $parent = $r['parent'];
      if ($category_title == $categorySEF) { subcategories($parent); }
      echo '</li>';
    }
  } else {
    echo '<li>'.ucfirst(l('no_categories')).'</li>';
  }
}
function subcategories($parent) {
  global $categorySEF, $subcatSEF;
  $dblink = connect_to_db();
  $qwr = !_ADMIN ? ' AND a.visible=\'YES\'' : '';
  if (s('num_categories') == 'on') {
    $count = ', COUNT(DISTINCT a.id) AS total';
    $join ='LEFT OUTER JOIN '._PRE.'articles AS a
      ON (a.category = c.id AND a.position = 1 AND a.published = 1'.$qwr.')';
  } else {
    $count ='';
    $join='';
  }
  $subresult = mysqli_query($dblink, 'SELECT c.seftitle AS subsef, description, name'.$count.'
    FROM '._PRE.'categories AS c '.$join.'
    WHERE c.subcat = '.$parent.' AND c.published = \'YES\'
    GROUP BY c.id
    ORDER BY c.catorder,c.id');
  if (mysqli_num_rows($subresult) !== 0) {
    echo '<ul>';
    while ($s = mysqli_fetch_array($subresult)) {
      $subSEF = $s['subsef'];
      $class = $subSEF == $subcatSEF ? ' class="current"' : '';
      if (isset($s['total'])) {
        $num=' ('.$s['total'].')';
      }
      echo '<li class="subcat"><a'.$class.' href="'.$categorySEF.'/'.$subSEF.'/" title="'.$s['description'].'" tabindex="'.tabindex().'">'.$s['name'].$num.'</a></li>';
    }
    echo '</ul>';
  }
}
function pages() {
  global $categorySEF;
  $defaultpages = [];
  $subpages = [];
  $qwr = !_ADMIN ? ' AND visible=\'YES\'' : '';
  $class = empty($categorySEF) ? ' class="current"' : '';
  $tabindex = 20;
  $link = connect_to_db();
  echo "      <div id=\"hoofdmenu\" class=\"hoofdmenu transDirect\">\n        <ul>\n";
  echo "          <li><span".$class." data-action=\"goback\" tabindex=\"".$tabindex++."\" title=\"".ucfirst(l('previouspage'))."\"><svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" class=\"ln-icon ln-icon-back-circle\" viewBox=\"0 0 20 20\"><path d=\"M2.782 3.782c1.794-1.794 4.18-2.782 6.718-2.782s4.923 0.988 6.718 2.782 2.782 4.18 2.782 6.717-0.988 4.923-2.782 6.718-4.18 2.782-6.718 2.782-4.923-0.988-6.718-2.782-2.782-4.18-2.782-6.718 0.988-4.923 2.782-6.717zM9.5 19c4.687 0 8.5-3.813 8.5-8.5s-3.813-8.5-8.5-8.5c-4.687 0-8.5 3.813-8.5 8.5s3.813 8.5 8.5 8.5z\"></path><path d=\"M3.647 10.147l4-4c0.195-0.195 0.512-0.195 0.707 0s0.195 0.512 0 0.707l-3.147 3.146h10.293c0.276 0 0.5 0.224 0.5 0.5s-0.224 0.5-0.5 0.5h-10.293l3.146 3.147c0.195 0.195 0.195 0.512 0 0.707-0.098 0.098-0.226 0.146-0.353 0.146s-0.256-0.049-0.353-0.147l-4-4c-0.195-0.195-0.195-0.512 0-0.707z\"></path></svg></span></li>\n";
  echo "          <li><a".$class." href=\"/\" tabindex=\"".$tabindex++."\">".ucfirst(s('home_sef'))."</a></li>\n";
  if(s('show_overview_in_menu') == 'on') {
    echo "          <li><a".$class." href=\"/".s('overview_menuname')."\" tabindex=\"".$tabindex++."\">".ucfirst(entity(str_replace('-',' ',s('overview_menuname'))))."</a></li>\n";
  }
  $query = "SELECT id, seftitle, title, visible, published, default_page FROM "._PRE."articles WHERE position = 3".$qwr." AND id <> '".s('display_page')."' ORDER BY artorder ASC, id";
  $result = mysqli_query($link, $query);
  while ($r = mysqli_fetch_array($result)) {
    if($r['id'] == s('display_page')) {
      $homepage = $r;
    } else if(!is_numeric($r['default_page'])) {
      $defaultpages[$r['id']] = $r;
    } else {
      $subpages[$r['id']] = $r;
    }
  }
  if(!empty($homepage)) {
    unset($defaultpages[$homepage['id']]);
    array_unshift ( $defaultpages, $homepage );
  }
  foreach($defaultpages as $defpagearray) {
    $catSEF = cat_rel($defpagearray['id'],'seftitle');
    if($catSEF != "") $catSEF .= "/";
    $title = stripslashes(htmlspecialchars(entity($defpagearray['title']), ENT_QUOTES, 'UTF-8', FALSE));
    $class = ($categorySEF == $defpagearray['seftitle'])? ' class="current"' : '';
    if ($defpagearray['visible'] == 'NO' || $defpagearray['published'] != 1) {
      if(_ADMIN) {
        if ($defpagearray['id'] != s('display_page')) {
          echo "          <li class=\"unpublished\"><a".$class." href=\"".$defpagearray['seftitle']."\" tabindex=\"".$tabindex++."\">".$title."</a>";
        }
      }
    } else {
      echo "          <li><a".$class." href=\"".$defpagearray['seftitle']."\" tabindex=\"".$tabindex++."\">".$title."</a>";
    }
    $sublist = '';
    foreach($subpages as $subpagearray) {
      if($subpagearray['default_page'] == $defpagearray['id']) {
        if($subpagearray['visible'] == 'NO' || $subpagearray['published'] != 1) {
          if(_ADMIN) {
            $sublist = '      <ul>';
            break;
          }
        } else {
          $sublist = '      <ul>';
          break;
        }
      }
    }
    if($sublist=='      <ul>') {
      echo "\n      ".$sublist."\n";
      foreach($subpages as $subpagearray) {
        if($subpagearray['default_page'] == $defpagearray['id']) {
          $title = stripslashes(htmlspecialchars(entity($subpagearray['title']), ENT_QUOTES, 'UTF-8', FALSE));
          $class = ($categorySEF == $subpagearray['seftitle'])? ' class="current"' : '';
          if ($subpagearray['visible'] == 'NO' || $subpagearray['published'] != 1) {
            if(_ADMIN) {
              if ($subpagearray['id'] != s('display_page')) {
                echo "        <li class=\"unpublished\"><a".$class." href=\"".$subpagearray['seftitle']."\" tabindex=\"".$tabindex++."\">".$title."</a></li>\n";
              }
            }
          } else {
            echo "        <li><a".$class." href=\"".$subpagearray['seftitle']."\" tabindex=\"".$tabindex++."\">".$title."</a></li>\n";
          }
        }
      }
      echo "            </ul>\n          </li>\n";


    } else {
      if($defpagearray['visible'] == 'NO' || $defpagearray['published'] != 1) {
        if(_ADMIN) {
          if ($defpagearray['id'] != s('display_page')) {
            echo "</li>\n";
          }
        }
      } else {
        echo "</li>\n";
      }
    }
  }
  $class = ($categorySEF == 'contact') ? ' class="current"': '';
  if(s('show_agenda') == 'on') {
    echo "          <li><a".$class." href=\""."agenda\" tabindex=\"".$tabindex++."\">".ucfirst(l('agenda'))."</a></li>\n";
  }
  echo "          <li><a".$class." href=\""."contact\" tabindex=\"".$tabindex++."\">".ucfirst(l('contact'))."</a></li>\n";
  echo "        </ul>\n      </div>\n";
}
function paginator($pageNum, $maxPage, $pagePrefix) {
  global $categorySEF,$subcatSEF, $articleSEF,$_ID, $_catID,$_POS, $_XNAME;
  switch (true){
    case  !$_ID && !$_catID && $categorySEF!=s('overview_menuname') :
      $uri_pag ='';
      break;
    case  $_ID && $_XNAME :
      $uri_pag = $categorySEF.'/'.$subcatSEF.'/'.$articleSEF.'/';
      break;
    case  $_POS == 1 || $_XNAME :
      $uri_pag = $categorySEF.'/'.$subcatSEF.'/';
      break;
    default :
      $uri_pag = $categorySEF.'/';
  }
  $link = '<a href="'.$uri_pag ;
  $prefix = !empty($pagePrefix) ? $pagePrefix : '';
  if ($pageNum > 1) {
    $prev = (($pageNum-1)==1 ? rtrim($link,'/') : $link.$prefix.($pageNum - 1).'/').'" title="'.ucfirst(l('page')).' '.($pageNum - 1).'" tabindex="'.tabindex().'">&#8249;&#160;'.l('previous_page').'</a>';
    $first = rtrim($link,'/').'" title="'.ucfirst(l('first_page')).' '.l('page').'" tabindex="'.tabindex().'">&#171;&#160;'.l('first_page').'</a>';
    } else {
    $prev = "<span class=\"paginator-nolink\">&#8249;&#160;".l('previous_page')."</span>";
    $first ="<span class=\"paginator-nolink\">&#171;&#160;".l('first_page')."</span>";
  }
  if ($pageNum < $maxPage) {
    $next = $link.$prefix.($pageNum + 1).'/" title="'.ucfirst(l('page')).' '.($pageNum + 1).'" tabindex="'.tabindex().'">'.l('next_page').'&#160;&#8250;</a>';
    $last = $link.$prefix.$maxPage.'/" title="'.ucfirst(l('last_page')).' '.l('page').'" tabindex="'.tabindex().'">'.l('last_page').'&#160;&#187;</a>';
  } else {
    $next = "<span class=\"paginator-nolink\">".l('next_page')."&#160;&#8250;</span>";
    $last = "<span class=\"paginator-nolink\">".l('last_page')."&#160;&#187;</span>";
  }
  echo "<div class=\"center AlignCenter\">\n  <div class=\"paginator\">\n";
  echo "".$first."&#160;&#160;".$prev."<span class=\"pagenr\">".$pageNum." / ".$maxPage."</span>".$next."&#160;&#160;".$last."\n";
  echo "  </div>\n</div>\n";
}
function center() {
  if (isset($_SESSION[_SITE.'fatal'])) {
    echo $_SESSION[_SITE.'fatal'];
    unset($_SESSION[_SITE.'fatal']);
  } else {
    global $categorySEF, $url, $subcatSEF, $articleSEF;
    $action = '';
    switch(true) {
      case  isset($_GET['category']):
        if(count($url) < 4 || substr($url[3], 0, 1) == l('comment_pages') && is_numeric(substr($url[3], 1, 1))) $action = $categorySEF;
        else $action = '404';
      break;
      case  array_key_exists('action',$_GET):
        $action = $categorySEF == '404' || $url[0] == '404' ? '404' : clean(cleanXSS($_GET['action']));
      break;
    }
    switch(true) {
      case  isset($_POST['search_query']):
        search(); return; break;
      case  isset($_POST['comment']):
        comment('comment_posted'); return; break;
      case  isset($_POST['contactform']):
        contact(); return; break;
      case  isset($_POST['submit_text']):
        processing(); return; break;
    }
    if (_ADMIN) {
      switch ($action) {
        case  'administration':
          administration(); return; break;
        case  'scs-settings':
          settings(); return; break;
        case  'scs-categories':
          admin_categories(); return; break;
        case  'scs-category':
          form_categories(); return; break;
        case  'admin_subcategory':
          form_categories('sub'); return; break;
        case  'groupings':
          admin_groupings(); return; break;
        case  'admin_groupings':
          form_groupings(); return; break;
        case  'scs-articles':
          admin_articles('article_view'); return; break;
        case  'extra_contents':
          admin_articles('extra_view'); return; break;
        case  'scs-pages':
          admin_articles('page_view'); return; break;
        case  'scs-events':
          admin_articles('agenda_view'); return; break;
        case  'admin_article':
          form_articles(''); return; break;
        case  'scs-article':
          form_articles('scs-article'); return; break;
        case  'orders':
          orders(); return; break;
        case  'show_order':
          show_order($subcatSEF); return; break;
        case  'extra_new':
          form_articles('extra_new'); return; break;
        case  'scs-page':
          form_articles('scs-page'); return; break;
        case  'scs-event':
          form_articles('scs-event'); return; break;
        case  'editcomment':
          edit_comment(); return; break;
        case  'process':
          processing(); return; break;
        case  'logout':
          session_unset();
          session_destroy();
          session_write_close();
          exit(notification(0,l('logout'),'home'));
      }
    }
    switch ($action) {
      case  l('archive'):
        archive();
        break;
      case  'sitemap':
        sitemap(); break;
      case  'contact':
        contact(); break;
      case  'agenda':
        agenda_show('split'); break;
      case  s('login_url'):
        login(); break;
      case  '404':
        echo notification(0,'404','');
        break;
      default:
        articles();
        break;
    }
  }
}
function tab_count() {
  static $idx = 40;
  return "a tabindex=\"" . $idx++."\" class";
}
function addgenerictab() {
  static $idx = 40;
  return "<a tabindex=\"". $idx++."\" ";
}
function get_client_ip() {
  if (array_key_exists('HTTP_CLIENT_IP',$_SERVER)) return $_SERVER['HTTP_CLIENT_IP'];
  else if(array_key_exists('HTTP_X_FORWARDED_FOR',$_SERVER)) return $_SERVER['HTTP_X_FORWARDED_FOR'];
  else if(array_key_exists('HTTP_X_FORWARDED',$_SERVER)) return $_SERVER['HTTP_X_FORWARDED'];
  else if(array_key_exists('HTTP_FORWARDED_FOR',$_SERVER)) return $_SERVER['HTTP_FORWARDED_FOR'];
  else if(array_key_exists('HTTP_FORWARDED',$_SERVER)) return $_SERVER['HTTP_FORWARDED'];
  else if(array_key_exists('REMOTE_ADDR',$_SERVER)) return $_SERVER['REMOTE_ADDR'];
  else return 'UNKNOWN';
}
function removeEmptyTags($haystack,$tagname='p') {
  $pattern = isset($tagname) ? "/<".$tagname."[^>]*><\\/".$tagname."[^>]*>/" : "/<[^\/>]*>([\s]?)*<\/[^>]*>/";
  return preg_replace($pattern, '', $haystack);
} 
function removeEmptyP($haystack) {
  $pattern = "/<p[^>]*>([\s]|&nbsp;)*<\/p>/";
  return preg_replace($pattern, '', $haystack);
}
function createShortText($input) {
  $text = strip_tags_content($input, '<script>', true);
  $text = strip_tags(trim($text),'<hr><br>');
  $text = nl2br($text,true);
  $text = preg_replace('/^\s*(?:<br\s*\/?>\s*)*/i', '', $text);
  $text = preg_replace('#<br />(\s*<br />)+#', '<br />', $text);
  $treshold = strpos($text,'<hr />') - 1;
  $treshold = $treshold < 0 ? _SHORTENED_TEXT_DEFAULT_TRESHOLD : $treshold;
  $text = trim(substr($text,0,$treshold));
  $text = entity($text);
  return $text;
}
function tease($body, $sentencesToDisplay=2, $newlineReplacement=" || ", $removeH1headers='YES', $htmlText=false) {
  $body = preg_replace('~<\s*\bscript\b[^>]*>(.*?)<\s*\/\s*script\s*>~is', '', $body);
  $body = removeEmptyP($body);
  $h1 = extract_tags( $body, 'h1', false );
  $h1Text = !empty($h1)? $h1[0]['contents'] : l('no_title');
  $ul = extract_tags( $body, 'ul', null, true );
  $ulTag = !empty($ul)? $ul[0]['full_tag'] : false;
  $p = extract_tags( $body, 'p', false );
  if(!empty($p)) {
    $x = 0;
    $pBundle = '';
    foreach($p as $par) {
      if($x < $sentencesToDisplay) {
        $pBundle .= "<p>\n".$par['contents']."\n</p>\n";
      }
      $x++;
    }
  } else {
    $p = false;
  }
  if($htmlText) {
    if(!$p) {
      $nakedBody = $ulTag;
    } else {
      $nakedBody = $pBundle;
    }
  } else { 
    $body = $removeH1headers == 'YES' ? strip_tags_content(entity($body),'<h1>',TRUE) : entity($body);
    $body = preg_replace('/\s+/',' ',$body);
    $arrFind = array("<li","</li>");
    $arrChange = array("* <li","</li>\\n");

    $body = str_replace($arrFind,$arrChange,$body);
    $nakedBody = preg_replace("/(?<! )(?<!^)(?<![A-Z])[A-Z]/",$newlineReplacement."$0", trim(strip_tags($body)," "));
    $nakedBody = str_replace('"', "", $nakedBody);
    $verynakedBody = preg_replace('/\s+/',' ',removeEmptyP($body) );
    $re = '/ (?<= [.!?] | [.!?][\'"] ) (?<! Etc\. | Nr\. | Mw\. | Dr\. | Dhr\. | Prof\. | Mr\. | T\.V\.A\. ) \s+ /ix';
    $sentences = preg_split($re,$verynakedBody, -1, PREG_SPLIT_NO_EMPTY);
    if (count($sentences) <= $sentencesToDisplay) return $removeH1headers == 'YES' ? $nakedBody : "<h1>".$h1Text."</h1>".$nakedBody;
    $stopAt = 0;
    foreach ($sentences as $i => $sentence) {
      $stopAt += strlen($sentence);
      if ($i >= $sentencesToDisplay - 1) break;
    }
    $stopAt += ($sentencesToDisplay * 2);
    $nakedBody = trim(substr($nakedBody, 0, $stopAt));
  }
  $body = $removeH1headers == 'YES' ? $nakedBody : "<h1>".$h1Text."</h1>".$nakedBody;
  return $nakedBody;
}
function articles() {
  global $categorySEF, $subcatSEF, $articleSEF, $_ID, $_POS, $_catID, $_XNAME;
  $link = connect_to_db();
  if(_OVERVIEW_ORDERED_BY === "date") {
    $order = "a.date "._OVERVIEW_ORDER;
  } elseif(_OVERVIEW_ORDERED_BY === "custom") {
    if($_catID) {
      if($_catID && empty($subcatSEF)) {
        $order = "x.catorder "._OVERVIEW_ORDER.", a.artorder "._OVERVIEW_ORDER;
      } else {
        $order = "a.artorder "._OVERVIEW_ORDER;
      }
    } else {
      $order = "c.catorder "._OVERVIEW_ORDER.", a.artorder "._OVERVIEW_ORDER;
    }
  } else {
    $order = "a.date "._OVERVIEW_ORDER;
  }
  $SEF = '';
  $frontpage = s('display_page');
  $title_not_found = "<h1 id=\"cstart\">".ucfirst(l('none_yet'))."</h1>\n";
  if(_ADMIN) {
    $visible='';
    $create_title = "<p>\n".ucfirst(l('create_new'))." <a href=\""._SITE."administration\" title=\"".ucfirst(l('administration'))."\" tabindex=\"".tabindex()."\">".ucfirst(l('administration'))."</a>\n</p>\n";
  } else {
    $visible =' AND a.visible=\'YES\' ';
  }
  if(!$_ID) {
    if($articleSEF) $SEF = $articleSEF;
    elseif($categorySEF) $SEF = $categorySEF;
    elseif($subcatSEF) $SEF = $subcatSEF;
    else {
      if(!$_catID && s('overview_pagename') != "") {
        $_catID = getCatIdBySEF(s('overview_pagename'));
      }
    }
    $currentPage = strpos($SEF, l('paginator')) === 0 ? str_replace(l('paginator'), '', $SEF) : '';
    if($_catID) {
      if(empty($subcatSEF)) {
        $count = "SELECT COUNT(a.id) AS num FROM "._PRE."articles AS a LEFT JOIN ("._PRE."categories AS c INNER JOIN "._PRE."categories AS x ON x.subcat = c.id) ON x.id = a.category WHERE (a.category=".$_catID." OR x.subcat=".$_catID.")".$visible." AND a.published=1";

      } else {
        $count = 'SELECT COUNT(a.id) AS num FROM '._PRE.'articles AS a WHERE position = 1 AND a.published = 1 AND category = '.$_catID.$visible.' GROUP BY category';
      }
    } else {
      $count = "SELECT COUNT(a.id) AS num FROM "._PRE."articles AS a LEFT OUTER JOIN "._PRE."categories as c ON category = c.id LEFT OUTER JOIN "._PRE."categories as x ON c.subcat =  x.id AND (x.published='YES') WHERE show_on_home='YES'".$visible." AND position=1 AND a.published=1 AND c.published ='YES' GROUP BY show_on_home";
    }
    $data = mysqli_fetch_assoc(mysqli_query($link,$count));
    $num = (int)$data['num'];
  } elseif($_ID == s('display_page') && !empty(checkGET('category'))) {
    echo "<script type=\"text/javascript\">setTimeout(function(){document.location.href='"._HOST."';},0);</script>\n";
  }
  if($frontpage != 0 && $SEF=='') $_ID=$_ID?$_ID:$frontpage;
  echo "      <div id=\"lead\" class=\"page\">\n";
  if($_POS == 3) {
    if($_ID == s('display_page')) $divContainerName = 'homepage';
    else $divContainerName = 'webpage';
  } elseif($_POS == 1 && $_ID != 0) {
    $divContainerName = 'blogpage';
  } elseif(!$_ID && $_POS!=4) {
    $divContainerName = 'category-overview page_margins';
  } elseif($_POS == 4) {
    $divContainerName = 'agendapage page_margins';
  } else {
    $divContainerName = 'defaultpage page_margins';
  }
  echo "        <div id=\"main\" class=\"".$divContainerName."\">\n";
  if ($_ID || (!$_catID && $frontpage != 0 && "x".$categorySEF != s('overview_pagename') && $categorySEF != s('overview_menuname'))) {
    if (!$_ID) {
      $_ID = $frontpage;
    }
    switch ($_POS) {
      case  1: 
        $admin_page = "scs-articles";
        $ip=str_replace(".","_",get_client_ip());
        break;
      case  2: 
        $admin_page = "scs-extra";
        break;
      case  3: 
        $admin_page = "scs-pages";
        break;
      case  4:
        $admin_page = "scs-events";
        break;
    }
    $query_articles = 'SELECT a.id AS aid,title,a.seftitle AS asef,text,a.date,a.position,a.mod_date,a.displaytitle,a.displayinfo,a.show_author,a.author,a.commentable,a.visible,a.show_on_home,a.socialbuttons FROM '._PRE.'articles AS a WHERE id ='.$_ID.$visible;
  } else {
    $admin_page = "scs-articles";
    if($num > 0 ) {
      $articleCount = s('article_limit');
      $article_limit = (empty($articleCount) || $articleCount < 1) ? 12 : $articleCount;
      $totalPages = ceil($num/$article_limit);
      if (!isset($currentPage) || !is_numeric($currentPage) || $currentPage < 1) $currentPage = 1;
      if(!$_catID && !$_ID && s('overview_pagename') != "" && "x".$categorySEF != s('overview_pagename')) {
        $_catID = getCatIdBySEF(s('overview_pagename'));
      }
      if($_catID) {
        if($_catID && empty($subcatSEF)) {
          $query_articles = "select
              a.id AS aid,title,a.seftitle AS asef,text,a.date,a.mod_date,a.position,x.name as subcatname,c.id as cid,
              a.displaytitle,a.show_author,a.author,a.displayinfo,a.commentable,a.show_on_home,a.visible,a.socialbuttons,a.artorder
            from "._PRE."articles a
            left join (
              "._PRE."categories c inner join "._PRE."categories x on x.subcat = c.id
            ) on x.id = a.category
            where (a.category=".$_catID." OR x.subcat=".$_catID.")".$visible."
            and a.published=1
            order by ".$order."
            limit ".($currentPage - 1) * $article_limit.",".$article_limit;
        } else {
          $query_articles = "SELECT
              a.id AS aid,title,a.seftitle AS asef,text,a.date,a.mod_date,a.position,
              a.displaytitle,a.show_author,a.author,a.displayinfo,a.commentable,a.show_on_home,a.visible,a.socialbuttons,a.artorder
          FROM "._PRE."articles AS a
         WHERE position = 1
           AND a.published = 1
           AND category = ".$_catID.$visible."
         ORDER BY ".$order."
         LIMIT ".($currentPage - 1) * $article_limit.",".$article_limit;
        }
      } else {
        $query_articles = "SELECT
            a.id AS aid,title,a.seftitle AS asef,text,a.date,a.mod_date,a.position,x.name as subcatname,
            a.displaytitle,a.show_author,a.author,a.displayinfo,a.commentable,a.show_on_home,a.visible,a.socialbuttons,a.artorder,
            c.id as cid,c.name AS name,c.seftitle AS csef,c.catorder,
            x.name AS xname,x.seftitle AS xsef
          FROM "._PRE."articles AS a
          LEFT OUTER JOIN "._PRE."categories AS c
            ON category = c.id
          LEFT OUTER JOIN "._PRE."categories AS x
            ON c.subcat =  x.id AND x.published ='YES'
         WHERE show_on_home = 'YES'
           AND position = 1
           AND a.published = 1
           AND c.published = 'YES'".$visible."
      ORDER BY ".$order."
         LIMIT ".($currentPage - 1) * $article_limit.",".$article_limit;
      }
    }
  }
  if(isset($query_articles)) {
    $result = mysqli_query($link, $query_articles);
  } else {
    $result = false;
  }
  if(!$result) {
    echo "  <h1 id=\"cstart\" class=\"category-description-title\">".ucfirst(l('none_yet'))."</h1>\n";
    echo $title_not_found;
    if (_ADMIN) echo $create_title;
    else echo "\n<p>".ucfirst(l('no_articles'))."</p>\n";
    echo "        </div>\n      </div>\n";
  } else {
    $n=0;
    $type = ($_POS == 0 ? 0 : ($_POS - 1));
    $clearFloats = "";
    while ($r = mysqli_fetch_array($result)) {
      $infoline = ($r['displayinfo'] == 'YES') ? TRUE : FALSE;
      $infolineText = "";
      $socialbuttons = ($r['socialbuttons'] == 'YES') ? TRUE : FALSE;
      $date = strtotime($r['date']);
      $text = stripslashes(entity($r['text']));
      $text = preg_replace_callback('/<a /', 'addgenerictab', $text);
      if(!$_ID) {
        $shorten = TRUE;
        $divleadid = "lead-".$n;
        $divleadclass = "lead";
      } else {
        $shorten = FALSE;
        $contenttypes = array('blog','extra','page','agenda');
        $divleadid = $contenttypes[$type];
        $divleadclass = $contenttypes[$type]."-contents";
      }
      if(_ADMIN) {
        $comments_query = 'SELECT id FROM '._PRE.'comments WHERE articleid = '.$r['aid'].'';
      } else {
        $comments_query = 'SELECT id FROM '._PRE.'comments WHERE articleid = '.$r['aid'].' AND approved = \'True\'';
      }
      $comments_result = mysqli_query($link,$comments_query);
      $comments_num = mysqli_num_rows($comments_result);
      $a_date_format = strftime(_DATEFORM, $date);
      $mod_date_format = ' ('.l('last_edit').' '.strftime(_DATEFORM, strtotime($r['mod_date'])).')';
      if(array_key_exists('csef',$r)) {
        if($uri = $r['xsef']) {
          $uri = $r['xsef'].'/'.$r['csef'];
        } else {
          $uri = $r['csef'];
        }
      } elseif ($_XNAME) {
        $uri = $categorySEF.'/'.$subcatSEF;
      } elseif ($categorySEF=='') {
        $uri = s('overview_pagename');
      } elseif (array_key_exists('cid',$r)) {
        if($categorySEF == s('overview_menuname')) {
          $categorySEF = s('overview_pagename');
        }
        $uri = $r['cid'] == 0 ? $categorySEF : $categorySEF."/".$r['subcatname'];
      } else {
        $uri = $categorySEF;
      }
      $title = stripslashes(htmlspecialchars(entity($r['title']), ENT_QUOTES, 'UTF-8', FALSE));
      $visbackuri=(!$_ID && !empty($currentPage) && $r['position']==1)? s('overview_menuname'):$r['position']==3?$uri:$uri.'/'.$r['asef'];
      if($r['visible'] == 'YES') {
        $visiblity = "<a href=\""._SITE."index.php?action=process&amp;task=hide&amp;item=".$admin_page."&amp;id=".$r['aid']."&amp;back=".$visbackuri."\" tabindex=\"".tabindex(400)."\"><img class=\"icon\" src=\"images/icons/show.png\" alt=\"visible\" title=\"".ucfirst(l('hide'))."\" /></a>\n";
        $vis="";
      } else {
        $visiblity = "<a href=\""._SITE."index.php?action=process&amp;task=show&amp;item=".$admin_page."&amp;id=".$r['aid']."&amp;back=".$visbackuri."\" tabindex=\"".tabindex(400)."\"><img class=\"icon\" src=\"images/icons/hide.png\" alt=\"hidden\" title=\"".ucfirst(l('show'))."\" /></a>\n";
        $vis=" unpublished";
      }
      $social_link = _SITE.$uri."/".$r['asef']."/";
      if($r['displaytitle'] == 'NO') {
        $h1 = extract_tags( $text, 'h1', NULL, false );
        if(count($h1) > 0) {
          $title = trim($h1[0]['contents']);
        } else {
          $title = l('no_title');
        }
      }
      $title = ucfirst(strtolower($title));
      if(!$_ID) {
        $cl = s('display_page')==$r['aid'] ? 'frontpage' : 'lead-title';
        if($n==0) {
          $catInfo = getCat($_catID);
          if(!empty($_catID)) {
            $catName = ucfirst($catInfo->name);
            $catDesc = $catInfo->description;
          } else {
            $catName = ucfirst(str_replace("-"," ",s('overview_menuname')));
            $catDesc = '';
          }
          echo "<h1 id=\"cstart\" class=\"category-description-title\">".$catName."</h1>\n";
          echo $catDesc."\n";
          echo "<div>\n";
        }
        echo "<div class=\"".$divleadclass.$vis."\">\n";
        if(!$_ID && _SHOW_DATE_IN_OVERVIEW===true) {
          echo "<p class=\"date\">\n";
          echo "  ".ucfirst($a_date_format);
          echo "</p>\n";
        }
        echo "<h2 class=\"".$cl."\"><a href=\"".$uri."/".$r['asef']."\" tabindex=\"".tabindex(50)."\">".$title."</a></h2>\n";
      } else {
        if ($r['displaytitle'] == 'YES') echo "<h1 id=\"cstart\"".($_ID == s('display_page')?" class=\"homepage\"":"").">".$title."</h1>\n";
        if ($r['position']==1 && $r['show_author']=='on') echo "<span class=\"auteur page_margins\">".ucfirst(l('written_by'))." ".ucfirst(s('company_contact'))."</span>\n";
      }
      if($_ID) {
        if ($r['position']==3 || $r['position']==2) {
          echo removeEmptyP($text)."\n";
        } else {
          $text = removeEmptyP($text);
          $text = str_replace("\r","",$text);
          libxml_use_internal_errors(true);
          $html= new DOMDocument(); 
          $html -> loadHTML( 
            $text,
            LIBXML_NOERROR
            | LIBXML_NOWARNING
          );
          $classname="alternate-content";
          $finder = new DomXPath($html);
          $nodes = $html->getElementsByTagName("p");
          if($nodes->length > 0) {
            $shortContent = $nodes->item(0);
            $contentDiv = $nodes->item(0);
            $infolineText = "";
            if($r['displayinfo']=="YES" || $r['socialbuttons']=="YES" || _ADMIN) {
              $infolineText .= "          <div class=\"meta\">\n            <hr class=\"infoline\" />\n";
            }
            if ($r['displayinfo']=="YES") {
              $infolineText .= "        <p class=\"date\">".ucfirst($a_date_format)/*.(!empty($r['mod_date']) ? $mod_date_format:'')*/."</p>\n";
            }
            if(_ADMIN) {
              $edit_link  = "              <a href=\"index.php?action=admin_article&amp;id=".$r['aid']."\" title=\"".ucfirst($title)."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/art_edit.png\" alt=\"edit\" title=\"".ucfirst(l('edit_art'))."\" /></a>\n";
              $edit_link .= "              ".$visiblity;
              $infolineText  .= "            <div class=\"edit-control\">\n".$edit_link."            </div>\n";
            }
            if (!empty($_POS) && empty($currentPage)) {
              if ($socialbuttons) {
                if (checkPOST('geen_koekie') != "NOCOOKIE") {
                  $infolineText .= "        <script type=\"text/javascript\">simpleCS.checkCookie();</script>\n";
                } else {
                  $infolineText .= "        <script type=\"text/javascript\">setCookie('jsCookieCheck','NOCOOKIE',365);</script>\n";
                }
                $infolineText .= socializer($social_link);
              }
            }
            if($infoline || $socialbuttons || _ADMIN) {
              $infolineText .= "          </div>\n";
            }

            if($infoline) {
            }
            $impliedTags = array("<html>","</html>","<body>","</body>");
            $deleteTags = array("","","","");
            $str = $html->saveXML($html->documentElement);
            $str = str_replace($impliedTags,$deleteTags,$str);
            echo $str;
            if($r['commentable'] == 'YES') {
              comment($r['commentable']);
            }
          } else {
            echo removeEmptyP($text)."\n";
            if(_ADMIN) {
              $title = extract_tags($title,'span');
              $edit_link = "  <a href=\"index.php?action=admin_article&amp;id=".$r['aid']."\" title=\"".ucfirst($title)."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/art_edit.png\" alt=\"edit\" title=\"".ucfirst(l('edit_art'))."\" /></a>\n";
              $edit_link .= "  ".$visiblity;
              $infolineText .= "<div class=\"edit-control\" id=\"edit-control\">\n".$edit_link."</div>\n";
            }
            if (!empty($_POS) && empty($currentPage)) {
              if ($socialbuttons) {
                if (checkPOST('geen_koekie') != "NOCOOKIE") {
                  $infolineText .= "<script type=\"text/javascript\">simpleCS.checkCookie();</script>\n";
                  if (array_key_exists('jsCookieCheck',$_COOKIE)) {
                    if ($_COOKIE["jsCookieCheck"] != "NOCOOKIE") {
                      $infolineText .= socializer($social_link);
                    }
                  } else {
                    $infolineText .= socializer($social_link);
                  }
                } else {
                  $infolineText .= "<script type=\"text/javascript\">simpleCS.setCookie('jsCookieCheck','NOCOOKIE',365);</script>\n";
                }                
              }
            }
          }
        }
        echo $infolineText."          <div class=\"clearFloats\"></div>\n";
      } else {
        preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $text, $image);
        $text = createShortText($text);
        if(array_key_exists('src',$image)) {
          $imageSource = $image['src'];
        } else {
          $imageSource = "images/SimpleCS_logo.png"; 
        }
        echo "<div class=\"lead-previewtext\">\n";
        echo "<span class=\"mask\"><img src=\"".$imageSource."\" class=\"lead-previewimage floatLeft\" alt=\"\" /></span>\n";
        echo "<p>\n".$text."\n</p>\n";
        echo "</div>\n";
        if(_ADMIN) {
          echo "  <div class=\"edit-control\">\n";
          echo "    <a href=\""._HOST."/?action=admin_article&amp;id=".$r['aid']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/art_edit.png\" alt=\"edit\" title=\"".ucfirst(l('edit_art'))."\" /></a>\n";
          echo "    ".$visiblity."\n";;
          echo "  </div>\n";
        }
        $clearFloats = "          <div class=\"clearFloats\"></div>\n";
        echo "</div>\n";
      }
      $n++;
    }
    if(!$_ID) {
      if(!isHomepage() && $num > s('article_limit')) {
        echo $clearFloats;
        if(_OVERVIEW_ORDERED_BY==="date") {
          $marker = $date;
        } else {
          $marker = s('article_limit');
        }
        echo "          <button name=\"loadbatch\" id=\"loadbatch\" type=\"button\" value=\"".$marker."\" data-lastinlist=\"".$marker."\">More artwork</button>\n";
      }
      echo "</div>\n";
    }
    echo "        </div>\n";
    echo "      </div>\n";
  }
}
function socializer($url) {
  $twit_url = rawurlencode($url);
  $soc  = "            <div id=\"social_network_buttons\" class=\"page_margins\">\n";
  $soc .= '              <div id="fb-root"></div>'."\n";
  $soc .= '              <script type="text/javascript" class="cc-onconsent-social">(function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(d.getElementById(id))return;js=d.createElement(s);js.id=id;js.src="//connect.facebook.net/en_US/sdk.js#xfbml=1&amp;version=v2.9";fjs.parentNode.insertBefore(js,fjs);}(document,"script","facebook-jssdk"));</script>'."\n";
  $soc .= "              <div class=\"fb-like\" data-href=\"".$url."\" data-layout=\"button\" data-action=\"recommend\" data-size=\"large\" data-show-faces=\"false\" data-share=\"true\"></div>\n";
  $soc .= "            </div>\n";
  return $soc;
}
function comment($freeze_status) {
  global $categorySEF, $subcatSEF, $articleSEF, $_ID, $commentsPage;
  $formlink = _SITE.$categorySEF."/".$subcatSEF."/";
  $dblink = connect_to_db();
  $_SESSION[_SITE.'comment'] = '';
  $_SESSION[_SITE.'comment']['sent'] = '';
  if (isset($commentsPage)) {
   $commentsPage = str_replace(ucfirst(l('comment_pages')),'',$commentsPage);
  }
  if (strpos($articleSEF, l('paginator')) === 0) {
   $articleSEF = str_replace(l('paginator'), '', $articleSEF);
  }
  if (!isset($commentsPage) || !is_numeric($commentsPage) || $commentsPage < 1) {
   $commentsPage = 1;
  }
  $comments_order = s('comments_order');
  if(array_key_exists(_SITE.'comment',$_SESSION) && $_SESSION[_SITE.'comment']['sent']!=true) {
    $_SESSION[_SITE.'comment']['sent']=false;
  }
  $errors='';
  if (isset($_POST['comment'])) {
    $name = trim($_POST['name']);
    $name = preg_replace('/[^a-zA-Z0-9_\s-]/', '', $name);     if (empty($name)) { $name = ''; }     $name = strlen($name) > 1 ? clean(cleanXSS($name)) : null;
    $comment = cleanWords(trim($_POST['text']));
    $comment = strlen($comment) > 4 ? clean(cleanXSS($comment)) : null;
    $ip = (strlen($_POST['ip']) < 16) ? clean(cleanXSS($_POST['ip'])) : null;
    $email = filter_var($_POST["email"], FILTER_VALIDATE_EMAIL);
    $post_article_id = (is_numeric($_POST['id']) && $_POST['id'] > 0) ? $_POST['id'] : null;
    $postArt = clean(cleanXSS($_POST['article']));
    $postArtID = retrieve('category','articles','id',$post_article_id);
    if ($postArtID == 0) {
      $postCat = '' ;
    } else {
      $postCat = cat_rel($postArtID, 'seftitle').'/';
    }
    $back_link = _SITE.$postCat.$postArt;
 
    if(!$name) $errors .= "<li>".l('name_error')."</li>\n";
    if(!$comment) $errors .= "<li>".l('txt_error')."</li>\n";
    $chmc = checkMathCaptcha();
    if(!$chmc) $errors .= "<li>".l('mathcaptcha_error')."</li>\n";
    if (_ADMIN) {
      $doublecheck = 1;
      $ident=1;
    } else {
      $contentCheck = retrieve('id', 'comments', 'comment', $comment);
      $ident = !$contentCheck || (time() - $_SESSION[_SITE.'poster']['time']) > s('comment_repost_timer') || $_SESSION[_SITE.'poster']['ip'] !== $ip ? 1 : 0;
      if(array_key_exists(_SITE.'poster',$_SESSION)) {
        $doublecheck = $_SESSION[_SITE.'poster']['article'] === "$comment:|:$post_article_id" && (time()-$_SESSION[_SITE.'poster']['time']) < s('comment_repost_timer') ? 0 : 1;
      } else {
        $doublecheck = 0;
      }
    }
    if ($ip == $_SERVER['REMOTE_ADDR'] && $comment && $name && $post_article_id  && $chmc && $doublecheck == 1 && $ident == 1) {
      $time = date('Y-m-d H:i:s');
      unset($_SESSION[_SITE.'poster']);
      $approved = s('approve_comments') != 'on'|| _ADMIN ? 'True' : '';
      $query = 'INSERT INTO '._PRE.'comments(articleid, name, comment, time, approved) VALUES'."('$post_article_id', '$name', '$comment', '$time', '$approved')";
      mysqli_query($dblink,$query);
      $_SESSION[_SITE.'poster']['article']="$comment:|:$post_article_id";
      $_SESSION[_SITE.'poster']['time'] = time();
      $_SESSION[_SITE.'poster']['ip'] = $ip;
      $commentStatus = ucfirst(l('comment_thx'));
      if(s('approve_comments') == 'on'&& !_ADMIN) {
        $commentReason  = "<p>Wij hebben ervoor gekozen om reacties te controleren alvorens ze te publiceren. Dit duurt doorgaans niet langer dan 1 werkdag.</p>\n";
        $commentReason .= "<p>Hieronder ziet u een overzicht van de reactie die u naar ons heeft toegestuurd.</p>\n";
        $commentReason .= "<p class=\"reactieoverzicht\">\n";
        $commentReason .= "<span class=\"reactieoverzichtLeft\">Naam</span><span class=\"reactieoverzichtRight\">".$name."</span>\n";
        if($email)$commentReason .= "<span class=\"reactieoverzichtLeft\">Email adres</span><span class=\"reactieoverzichtRight\">".$email."</span>\n";
        $commentReason .= "<span class=\"reactieoverzichtLeft\">Reactie</span><span class=\"reactieoverzichtRight\">".$comment."</span>\n</p>\n";
      } else {
        $commentReason  = "<p>Uw reactie is met succes geplaatst.</p>\n";
        $commentReason .= "<p>Hieronder ziet u een overzicht van de reactie die u op onze website heeft geplaatst.</p>\n";
        $commentReason .= "<p class=\"reactieoverzicht\">\n";
        $commentReason .= "<span class=\"reactieoverzichtLeft\">Naam</span><span class=\"reactieoverzichtRight\">".$name."</span>\n";
        if($email)$commentReason .= "<span class=\"reactieoverzichtLeft\">Email adres</span><span class=\"reactieoverzichtRight\">".$email."</span>\n";
        $commentReason .= "<span class=\"reactieoverzichtLeft\">Reactie</span><span class=\"reactieoverzichtRight\">".$comment."</span>\n</p>\n";
      }
      if (!_ADMIN) {
        $head_me = array(
          'to'   => array(s('website_email')=>ucfirst(s('website_title'))),
          'from' => array($email=>$name)
        );
        $head_ct = array(
          'to'   => array($email=>$name),
          'from' => array(s('website_email')=>ucfirst(s('website_title')))
        );
        if (s('approve_comments') == 'on') {
          $subject_me = ucfirst(l('approve_subject_me'));
          $subject_ct = ucfirst(l('approve_subject_ct'));
          $body_me  = "<p>Aan de administratie van ".ucfirst(s('website_title')).",<br />\n";
          $body_me .= "Een bezoeker heeft een reactie op ".str_replace("http","",_SITE)." achtergelaten. Uw goedkeuring is vereist om deze reactie op uw website zichtbaar te maken.</p>\n";
          $body_me .= "<p>Inhoud van de reactie:</p>\n";
          $body_me .= "<hr />\n";
          $body_me .= "<p>\n";
          $body_me .= "Naam: ".$name."<br />\n";
          $body_me .= "Email adres: ".$email."<br />\n";
          $body_me .= "Reactie:\r\n".$comment."<br />\n";
          $body_me .= "<a href=\"".$back_link."\">Dit is de link naar het artikel waarop gereageerd is</a></p>\n";
          $body_ct  = "<p>Geachte bezoeker,<br />\n";
          $body_ct .= "Wij hebben ervoor gekozen om reacties te controleren alvorens ze te publiceren. Dit duurt doorgaans niet langer dan 1 werkdag.</p>\n";
          $body_ct .= "<p>\n";
          $body_ct .= "Inhoud van uw reactie:</p>\n";
          $body_ct .= "<hr />\n";
          $body_ct .= "<p>\n";
          $body_ct .= "Naam: ".$name."<br />\n";
          $body_ct .= "E-mailadres: ".$email."<br />\n";
          $body_ct .= "Reactie:<br />".$comment."<br />\n";
          $body_ct .= "</p>\n";
          $body_ct .= "<p><a href=\"".$back_link."\">Dit is de link naar het artikel waarop u gereageerd heeft</a></p>\n";
          $body_ct .= "<p>Wij vertrouwen erop dat wij u zo voldoende geinformeerd hebben. Mocht u desondanks nog vragen hebben dan kunt u contact met ons opnemen via onderstaand telefoonnummer</p>\n";
          $body_ct .= "<p>Met vriendelijke groet,</p>\n";
          $body_ct .= "<p>".s('company_contact')."<br />\n";
          $body_ct .= ucfirst(s('website_title'))."<br />\n";
          $body_ct .= "tel: ".s('company_phone')."</p>\n";
          $status = ucfirst(l('approve_status'));
        } else {
          $subject_me = ucfirst(l('added_subject_me'));
          $subject_ct = ucfirst(l('added_subject_ct'));
          $body_me  = "<p>Aan de administratie van ".ucfirst(s('website_title')).",<br />\n";
          $body_me .= "Een bezoeker heeft een reactie op ".str_replace("http","",_SITE)." achtergelaten.</p>\n";
          $body_me .= "<p>Inhoud van de reactie:<br />\n";
          $body_me .= "Naam: ".$name."<br />\n";
          $body_me .= "E-mailadres: ".$email."<br />\n";
          $body_me .= "Reactie:\r\n".$comment."<br />\n";
          $body_me .= "<a href=\"".$back_link."\">Dit is de link naar het artikel waarop gereageerd is</a></p>\n";
          $body_ct  = "<p>Geachte bezoeker,<br />\n";
          $body_ct .= "Uw reactie is geplaatst op ".str_replace("http","",_SITE).". Wij sturen u deze e-mail ter bevestiging van de plaatsing.</p>\n";
          $body_ct .= "<p>\n";
          $body_ct .= "Inhoud van uw reactie:</p>\n";
          $body_ct .= "<hr />\n";
          $body_ct .= "<p>\n";
          $body_ct .= "Naam: ".$name."<br />\n";
          $body_ct .= "E-mailadres: ".$email."<br />\n";
          $body_ct .= "Reactie:<br />".$comment."<br />\n";
          $body_ct .= "</p>\n";
          $body_ct .= "<p><a href=\"".$back_link."\">Dit is de link naar het artikel waarop u gereageerd heeft</a></p>\n";
          $body_ct .= "<p>Wij vertrouwen erop dat wij u zo voldoende geinformeerd hebben. Mocht u desondanks nog vragen hebben dan kunt u contact met ons opnemen via onderstaand telefoonnummer</p>\n";
          $body_ct .= "<p>Met vriendelijke groet,</p>\n";
          $body_ct .= "<p>".s('company_contact')."<br />\n";
          $body_ct .= s('website_title')."<br />\n";
          $body_ct .= "tel: ".s('company_phone')."</p>\n";
          $status = ucfirst(l('added_status'));
        }
        include("../../php/mail.php");
        if($email) {
          email::send($head_ct,$subject_ct,$body_ct);
          $commentReason .= "<p>Een kopie van uw reactie is gestuurd naar ".$email."</p>\n";
        } else {
          $commentReason .= "<p>Een kopie van uw reactie kon niet gestuurd worden omdat het emailadres niet of niet volledig is ingevuld.</p>\n";
        }
        if(s('mail_on_comments') == 'on') {
          email::send($head_me,$subject_me,$body_me);
        }
        $_SESSION[_SITE.'comment']['sent']=true;
      }
    } else {
      if(array_key_exists('fail', $_SESSION[_SITE.'comment']) && $_SESSION[_SITE.'comment']['fail'] == true) {
        $commentStatus = ucfirst(l('refresh_no'));
        $commentReason = '<p>'.ucfirst(l('refresh_whynot')).'</p>';
      } else {
        if($_SESSION[_SITE.'comment']['sent']==true) {
          $commentStatus = ucfirst(l('refresh_no'));
          $commentReason = '<p>'.ucfirst(l('refresh_whynot')).'</p>';
        } else {
          $commentStatus = ucfirst(l('comment_error'));
          $commentReason  = "<p>\n".ucfirst(l('comment_error_found'))."\n</p>\n";
          $commentReason .= "<ul>\n".$errors."</ul>\n";
        }
      }
      $fail = true;
      $back_link .= "#comments";
      $_SESSION[_SITE.'comment']['name'] = $name;
      $_SESSION[_SITE.'comment']['comment'] = br2nl($comment);
      $_SESSION[_SITE.'comment']['email'] = $email;
      $_SESSION[_SITE.'comment']['fail'] = $fail;
    }
    echo "<h1 id=\"cstart\">".$commentStatus."</h1>\n";
    if (!empty($commentReason)) {
      echo $commentReason;
    }
    echo "<p><a href=\"".$back_link."\" tabindex=\"".tabindex()."\">".ucfirst(l('back'))."</a></p>\n";
  } else {
    echo "<div class=\"center comments_start\">\n";
    echo "<h2 id=\"comments_start\">".ucfirst(l('comments'))."</h2>\n";
    $commentCount = s('comment_limit');
    $comment_limit = (empty($commentCount) || $commentCount < 1) ? 100 : $commentCount;
    if (isset($commentsPage)) {
      $pageNum = $commentsPage;
    }
    $offset = ($pageNum - 1) * $comment_limit;
    if(_ADMIN) {
      $totalrows = "SELECT count(id) AS num FROM "._PRE."comments WHERE articleid = '$_ID'";
    } else {
      $totalrows = "SELECT count(id) AS num FROM "._PRE."comments WHERE articleid = '$_ID' AND approved = 'True'";
    }
    $rowsresult = mysqli_query($dblink,$totalrows);
    $numrows = $rowsresult -> num_rows;
    if ($numrows > 0) {
      if(_ADMIN) {
        $query = 'SELECT id,articleid,name,url,comment,time,approved FROM '._PRE.'comments WHERE articleid = '.$_ID.' ORDER BY id '.$comments_order.' LIMIT '."$offset, $comment_limit";
      } else {
        $query = 'SELECT id,articleid,name,url,comment,time,approved FROM '._PRE.'comments WHERE articleid = '.$_ID.' AND approved = \'True\' ORDER BY id '.$comments_order.' LIMIT '."$offset, $comment_limit";
      }      
      $result = mysqli_query($dblink,$query);
      $ordinal = 1;
      $date_format = s('date_format');
      tabindex(40);
      while ($r = mysqli_fetch_array($result)) {
        $date = strftime(_DATEFORM, strtotime($r['time']));
        $commentNum = $offset + $ordinal;
        $edit_link = "<a href=\""._SITE."index.php?action=editcomment&amp;commentid=".$r['id']."\" title=\"".ucfirst(l('edit'))." ".ucfirst(l('comment'))."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/comment_edit.png\" alt=\"edit\" title=\"".ucfirst(l('edit'))." ".ucfirst(l('comment'))."\" /></a>\n";
        $edit_link .= "<a href=\""._SITE."index.php?action=process&amp;task=deletecomment&amp;commentid=".$r['id']."\" title=\"".ucfirst(l('delete'))." ".ucfirst(l('comment'))."\" onclick=\"return pop();\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/delete_16.png\" alt=\"delete\" title=\"".ucfirst(l('delete'))." ".ucfirst(l('comment'))."\" /></a>\n";
        if ($r['approved']!='True') {
          echo "<div class=\"shaded\">\n";
        } else {
          echo "<div>\n";
        }
        echo "<p>\n";
        echo "<a id=\"".ucfirst(l('comment')).$commentNum."\" name=\"".ucfirst(l('comment')).$commentNum."\" tabindex=\"".tabindex()."\"><img src=\"images/icons/comments.png\" alt=\"\" title=\"Reactie door ".$r['name']."\" /></a>\n";
        echo $r['comment'];
        echo "\n</p>\n";
        if(_ADMIN) echo "<p class=\"edit_link\">\n".$edit_link."</p>\n";
        echo "<p class=\"date\">\n";
        $name=!empty($r['url'])?"<a href=\"".$r['url']."\" title=\"".$r['url']."\" rel=\"nofollow\" tabindex=\"".tabindex()."\">".$r['name']."</a>":$r['name'];
        echo ucfirst(l('by'))." <span class=\"normal\">".$name."</span> ".l('on')." ".$date;
        echo "</p>\n";
        echo "</div>\n";
        $ordinal++;
      }
      $maxPage = ceil($numrows / $comment_limit);
      $back_to_page = ceil(($numrows + 1) / $comment_limit);
      if ($maxPage > 1) {
        paginator($pageNum, $maxPage,ucfirst(l('comment_pages')));
      }
    }
    if ($freeze_status != 'freezed' && s('freeze_comments') != 'YES') {
      if ($numrows == 0) echo '<p>'.ucfirst(l('no_comment')).'</p>';
      if(array_key_exists('fail', $_SESSION[_SITE.'comment']) && $_SESSION[_SITE.'comment']['sent'] == false) {
        $name = $_SESSION[_SITE.'comment']['name'];
        $comment = $_SESSION[_SITE.'comment']['comment'];
        $url = $_SESSION[_SITE.'comment']['url'];
        $email = $_SESSION[_SITE.'comment']['email'];
      } else {
        $url = $name = $comment = $email ='';
      }
      unset($_SESSION[_SITE.'comment']);
      $art_value = empty($articleSEF) ? $subcatSEF : $articleSEF;
      echo "<div id=\"comments\">\n";
      echo html_input('form', '', 'comment', '', '', '', '', '', '', '', '', '', 'post', $formlink, '', '');
      echo html_input('fieldset','','','','','','','','','','','','','',ucfirst(l('addcomment')),'');
      echo html_input('text', 'name', 'name', $name, ucfirst(l('name')).' '.l('required'), 'text', '', '', '', '', '', '', '', '', '', tabindex(20));
      echo html_input('textarea', 'text', 'text', $comment, ucfirst(l('comment')).' '.l('required'), '', '', '', '', '', '', '', '', '', '', tabindex());
      echo "<p class=\"privacy\">Een kopie van uw reactie wordt gestuurd naar onderstaand e-mailadres. Om uw privacy te waarborgen wordt dit e-mailadres niet bewaard. Voor meer informatie over uw privacy, <a href=\"../privacyverklaring.php\" tabindex=\"".tabindex()."\">lees onze privacyverklaring</a></p>";
      echo html_input('text', 'email', 'email', $email, ucfirst(l('email')), 'text', '', '', '', '', '', '', '', '', '', tabindex());
      echo mathCaptcha();
      echo "<p>\n";
      echo html_input('hidden', 'category', 'category', $categorySEF, '', '', '', '', '', '', '', '', '', '', '');
      echo html_input('hidden', 'id', 'id', $_ID, '', '', '', '', '', '', '', '', '', '', '');
      echo html_input('hidden', 'article', 'article', $art_value, '', '', '', '', '', '', '', '', '', '', '');
      echo html_input('hidden', 'commentspage', 'commentspage', $back_to_page, '', '', '', '', '', '', '', '', '', '', '');
      echo html_input('hidden', 'ip', 'ip', $_SERVER['REMOTE_ADDR'], '', '', '', '', '', '', '', '', '', '', '');
      echo html_input('hidden', 'time', 'time', time(), '', '', '', '', '', '', '', '', '', '', '');
      echo html_input('submit', 'comment', '', ucfirst(l('send')), '', 'btn btn-default', '', '', '', '', '', '', '', '', '', tabindex());
      echo "</p>\n";
      echo html_input('fieldset','','','','','','','','','','','','','','end','');
      echo "</form>\n</div>\n</div>\n";
    } else {
      echo "<p>\n".ucfirst(l('frozen_comments'))."</p>\n";
    }
  }
}
function archive($start = 0, $size = 200) {
  echo "<div class=\"page\">\n";
  echo "  <div class=\"homepage\">\n";
  echo "<h1>".ucfirst(l('archive'))."</h1>\n";
  $query = "SELECT id FROM "._PRE."articles WHERE position = 1 AND published = 1 AND visible = 'YES' ORDER BY date DESC LIMIT $start, $size";
  $result = mysqli_query($query);
  $count = mysqli_num_rows($result);
  if ($count === 0) {
    echo "<p>".ucfirst(l('no_articles'))."</p>\n";
  } else {
    while ($r = mysqli_fetch_array($result)) {
      $Or_id[] = 'a.id ='.$r['id'];
    }
    $Or_id = implode(' OR ',$Or_id);
    $query = "SELECT title,a.seftitle AS asef,a.date AS date,c.name AS name,c.seftitle AS csef,x.name AS xname,x.seftitle AS xsef FROM "._PRE."articles AS a LEFT OUTER JOIN "._PRE."categories AS c ON category = c.id LEFT OUTER JOIN "._PRE."categories AS x ON c.subcat =  x.id WHERE ($Or_id) AND a.published = 1 AND c.published ='YES' AND (x.published ='YES' || x.published IS NULL) ORDER BY date DESC LIMIT $start, $size";
    $result = mysqli_query($query);
    $month_names = explode(',',l('month_names'));
    echo "<ul class=\"archive\">\n";
    while ($r = mysqli_fetch_array($result)) {

      $year = substr($r['date'], 0, 4);
      $month = intval(substr($r['date'], 5, 2)) -1;
      if ($last <> $year.$month) {
        echo "<li class=\"list_header\"><h2>".ucfirst($month_names[$month])." ".$year."</h2></li>\n";
      }
      $last = $year.$month;
      $link = isset($r['xsef']) ? $r['xsef'].'/'.$r['csef'] : $r['csef'];
      echo "<li><a href=\""._SITE.$link."/".$r['asef']."/\" tabindex=\"".tabindex()."\">".$r['title']." (".$r['name'].")</a></li>\n";
    }
    echo"</ul>\n";
  }
    echo"  </div>\n</div>\n";
}
function agenda_show($type="normal") {
  if ($type=="homepage") {
    echo "<div id=\"agenda\">\n";
    agenda(3,"homepage");
    echo "  <div class=\"listing\">\n";
    agenda("menu","homepage");
    echo "  <img src=\"images/listingfade.png\" class=\"listingfade\" alt=\"\" />\n";
    echo "  </div>\n";
    echo "</div>\n";
  } elseif ($type == "normal") {
    agenda("full");
  } elseif ($type == "split") {
    echo "<div id=\"lead\" class=\"page\">\n";
    echo "  <div id=\"main\" class=\"agenda\">\n";
    echo "    <div class=\"main_right\">\n";
    echo "      <h1>".ucfirst(l('agenda'))."</h1>\n";
    agenda("full",s('article_limit')*20);
    echo "    </div>\n";
    echo "    <div class=\"main_left\">\n";
    agenda("menu",12);
    echo "    </div>\n";
    echo "    <hr />\n";
    echo "  </div>\n";
    echo "</div>\n";
  }
}
function agenda($mode="full",$tipo="normal") {
  $mysqli = connect_to_db();
  if ($mysqli->connect_errno) {
     echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
  }
  $isUser = isUser();
  $limit = _AGENDA_ITEMS_PER_PAGE;
  $showfulltext = 0;
  $months=array('januari','februari','maart','april','mei','juni','juli','augustus','september','oktober','november','december');
  $getcat=str_replace("category=", "", $_SERVER['QUERY_STRING']);
  if(substr($getcat,-1) != "/") $getcat .= "/";
  $trail = explode("/",str_replace("agenda/","",$getcat));
  $trail = array_filter($trail);
  if(!empty($trail[0])) $type = count($trail)==1?"jaar":(count($trail)==2?"maand":"dag");
  else $type="frontpage";
  if($mode=="full" && $type!="frontpage") {
   if(is_numeric($tipo)) {
     $limit = $tipo;
   }
   switch ($type) {
     case "jaar":
       if (!$queryStatement = $mysqli->prepare("SELECT * FROM articles WHERE position=4 AND (articles.visible='YES' OR articles.visible=?) AND (articles.published=1 OR articles.published=2) AND articles.date >= ? AND articles.date < DATE_ADD(?, INTERVAL 1 YEAR) ORDER BY articles.date DESC LIMIT ?")) {
         echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
       }
       $year_start = strtotime("01-01-".$trail[0]);
       $_QV_date = strftime('%Y-%m-%d', $year_start);
       if (!$queryStatement->bind_param("sssi", $isUser, $_QV_date, $_QV_date, $limit)) {
         echo "Binding parameters failed: (" . $queryStatement->errno . ") " . $queryStatement->error;
       }
       break;
     case "maand":
       if (!$queryStatement = $mysqli->prepare("SELECT * FROM articles WHERE position=4 AND (articles.visible='YES' OR articles.visible=?) AND (articles.published=1 OR articles.published=2) AND articles.date >= ? AND articles.date < DATE_ADD(?, INTERVAL 1 MONTH) ORDER BY articles.date DESC LIMIT ?")) {
         echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
       }
       $maandnr=array_search($trail[1],$months)!==false?array_search($trail[1],$months)+1:$trail[1];
       $month_start = strtotime("01-".str_pad($maandnr, 2, '0', STR_PAD_LEFT)."-".$trail[0]);
       $_QV_date = strftime('%Y-%m-%d', $month_start);
       if (!$queryStatement->bind_param("sssi", $isUser, $_QV_date, $_QV_date, $limit)) {
         echo "Binding parameters failed: (" . $queryStatement->errno . ") " . $queryStatement->error;
       }
       break;
     case "dag":
       if (!$queryStatement = $mysqli->prepare("SELECT * FROM articles WHERE position=4 AND (articles.visible='YES' OR articles.visible=?) AND (articles.published=1 OR articles.published=2) AND articles.seftitle = ? AND articles.date >= ? AND articles.date < DATE_ADD(?,INTERVAL 1 DAY) ORDER BY articles.date DESC LIMIT ?")) {
         echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
       }
       $maandnr=array_search($trail[1],$months)!==false?array_search($trail[1],$months)+1:$trail[1];
       $dagnr_sql = strtotime($trail[2]."-".str_pad($maandnr, 2, '0', STR_PAD_LEFT)."-".$trail[0]);
       $_QV_seftitle = end($trail);
       $_QV_date = strftime('%Y-%m-%d', $dagnr_sql);
       if (!$queryStatement->bind_param("ssssi", $isUser, $_QV_seftitle, $_QV_date, $_QV_date, $limit)) {
         echo "Binding parameters failed: (" . $queryStatement->errno . ") " . $queryStatement->error;
       }
       $showfulltext = 1;
       break;
     default:
   }
  } else {
   if (!$queryStatement = $mysqli->prepare("SELECT * FROM articles WHERE position=4 AND (articles.visible='YES' OR articles.visible=?) AND (articles.published=1 OR articles.published=2) ORDER BY articles.date DESC LIMIT ?")) {
     echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
   }
   $limit = (is_numeric($mode))? $mode : ((is_numeric($tipo))? $tipo : $limit);
   if (!$queryStatement->bind_param("si", $isUser, $limit)) {
     echo "Binding parameters failed: (" . $queryStatement->errno . ") " . $queryStatement->error;
   }
  }
  if (!$queryStatement->execute()) {
   echo "Execute failed: (" . $queryStatement->errno . ") " . $queryStatement->error;
  }
  $queryStatement->store_result();
  $t=0;
  $day=0;
  $month="";
  $year=0;
  $year_incr = false;
  $month_incr = false;
  $day_incr = false;
  while($r = fetchAssocStatement($queryStatement)) {
    $title=entity($r['title']);
    $seftitle = $r['seftitle'];
    $text = str_replace("<a ","<a tabindex=\"".tabindex()."\" ", $r['text']);
    $text = $showfulltext == 0?str_replace("</p>","</p><br />",$text):$text;
    $short_display = strpos($text,'<hr />');
    $date=strtotime($r['date']);
    $h=$mode=="full"?($t==0?($type=="dag"?"h1":"h2"):"h2"):($t==0?"h2":"h3");
    if (strftime('%Y',$date)!=$year) {
      $year=strftime('%Y',$date);
      $year_incr = true;
    } else $year_incr = false;
    if (strftime('%B',$date)!=$month) {
      $month=strftime('%B',$date);
      $month_incr = true;
    } else $month_incr = false;
    if (strftime('%d',$date)!=$day) {
      $day=strftime('%d',$date);
      $day_incr = true;
    } else $day_incr = false;
    if ($r['visible'] == 'YES') {
      $visiblity = "<a href=\""._HOST."?action=process&amp;task=hide&amp;item=scs-events&amp;id=".$r['id']."&amp;back=".ltrim($_SERVER['REQUEST_URI'],'/')."\" tabindex=\"".tabindex(400)."\"><img class=\"icon\" src=\"images/icons/show.png\" alt=\"visible\" title=\"".ucfirst(l('hide'))."\" /></a>\n";
      $vis=" published";
    } else {
      $visiblity = "<a href=\""._HOST."?action=process&amp;task=show&amp;item=scs-events&amp;id=".$r['id']."&amp;back=".ltrim($_SERVER['REQUEST_URI'],'/')."\" tabindex=\"".tabindex(400)."\"><img class=\"icon\" src=\"images/icons/hide.png\" alt=\"hidden\" title=\"".ucfirst(l('show'))."\" /></a>\n";
      $vis=" unpublished";
    }
    switch (true) {
      case ($mode=="menu"):
        if ($year_incr == true) {
          echo $t==0?"":"  </div>\n</div>\n";
          echo "      <div id=\"".$year."\" class=\"agenda_".$mode." year\">\n";
          echo ($tipo=="homepage" && $t==0)?"<a class=\"listing-link\" href=\"agenda\">toon agenda</a>\n":"";
          echo "        <h2><a href=\"agenda/".strftime('%Y',$date)."\">".$year."</a></h2>\n";
          echo "        <hr />\n";
        }
      case ($mode=="full"):
        if($mode=="full") {
          if ($type!="dag") {
            $divopen = "        <div class=\"agenda_item".$vis."\">\n";
            $titlelink = "<".$h." id=\"agendapunt-".($t+1)."\"><a href=\"agenda/".$year."/".$month."/".$day."/".$seftitle."\">".$title."</a></".$h.">\n";
            $dateline = "<span class=\"agenda_".$mode." list\"><span class=\"weekday\">".ucfirst(strftime('%A',$date))."</span><span class=\"bigger bold\">".ltrim(strftime('%d',$date),'0')."</span> <span class=\"textmonth\">".ucfirst(strftime('%B',$date))."</span> <span class=\"textyear\">".strftime('%Y',$date)."</span></span>\n";
          } else {
            $divopen = "        <div class=\"".$vis."\">\n";
            $titlelink = "<".$h." id=\"agendapunt-".($t+1)."\">".$title."</".$h.">\n";
            $dateline = "<span class=\"agenda_".$mode." single\"><span class=\"weekday\">".ucfirst(strftime('%A',$date))."</span><span class=\"bigger bold\">".ltrim(strftime('%d',$date),'0')."</span> <span class=\"textmonth\">".ucfirst(strftime('%B',$date))."</span> <span class=\"textyear\">".strftime('%Y',$date)."</span></span>\n";
          }
          if($showfulltext == 1) {
            echo $divopen.$dateline.$titlelink.entity($text);
          } else {
            $shorten = $short_display === false ? 320 : $short_display;
            echo $divopen.$dateline.$titlelink."<p>\n".entity(trim(substr(strip_tags(trim($text)),0,$shorten)))."\n</p>\n";
          }
          $edit_link = "            <a href=\""._HOST."?action=admin_article&amp;id=".$r['id']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/art_edit.png\" alt=\"edit\" title=\"".ucfirst(l('edit_art'))."\" /></a>\n";
          $edit_link.= "            ".$visiblity;
          if(_ADMIN) echo "          <div class=\"edit-control\">\n".$edit_link."          </div>\n";
          echo "        </div>\n";
        } elseif($mode=="menu") {
          if ($month_incr == true) {
            echo "          <h3><a href=\"agenda/".strftime('%Y',$date)."/".$month."\">".ucfirst($month)."</a></h3>\n";
          }
          echo "          <div class=\"".$vis."\"><span class=\"agenda_".$mode." day\"><a href=\"agenda/".$year."/".$month."/".$day."/".$seftitle."\"><span class=\"numberday\">".ltrim(strftime('%d',$date),'0')."</span> <span class=\"textday\">".$title."</span></a></span></div>\n";
          if ( ($t + 1)==$tipo ) {
            $c=mysqli_query($mysqli,"SELECT COUNT(*) FROM articles WHERE position=4 AND show_on_home='YES'");
            if ( $c -> num_rows > s('article_limit') ) {
              echo "<div class=\"agenda_menu_end\"><a href=\"agenda/#agendapunt-".($t+2)."\">".ucfirst(l('view_more_articles'))."</a></div>\n";
            }
          }
        }
      break;
      case (is_numeric($mode)):
        if (strpos($tipo,"homepage")!== false) {
          echo "<div class=\"agenda_item agenda_homepage\">\n";
          echo "  <div class=\"agenda_item_inhoud\">\n";
          echo "    <div class=\"date\">\n";
          echo "      <span class=\"weekday\">".ucfirst(strftime('%a',$date))."</span><span class=\"bigger bold\">".ltrim(strftime('%d',$date),'0')."</span> <span class=\"textmonth\">".ucfirst(strftime('%b',$date))."</span>\n";
          echo "    </div>\n";
          echo "    <div class=\"item\">\n";
          echo "      <h3><a href=\"agenda/".$year."/".$month."/".$day."/".$seftitle."\">".$title."</a></h3>\n";
          $version = filter_var($tipo, FILTER_SANITIZE_NUMBER_INT);
          if($version == "") {
          } elseif($version == 1) {
            echo entity($text);
          } elseif($version == 2) {
            $shorten = $short_display === false ? 320 : $short_display;
            echo "      <p>\n".entity(trim(substr(strip_tags(trim($text),"<br>"),0,$shorten)))."\n</p>\n";
          } elseif($version == 3) {
            $shorten = $short_display === false ? 50000 : $short_display;
            echo "      <p>\n".entity(trim(substr(strip_tags(trim($text),"<br><a><img>"),0,$shorten)))."\n</p>\n";
          }
          echo "      <img class=\"lowfade\" src=\"images/lowfade.png\" alt=\"\" />\n";
          echo "    </div>\n";
          echo "  </div>\n";
          echo "</div>\n";
        }
    }
    $t++;
  }
  if($mode=="menu") {
    if($t>0) {
      echo "      </div>\n";
    }
  } elseif($mode=="full") {
    if($type!="frontpage") {
      echo "<p class=\"center small\"><strong>Eerdere afspraken</strong></p>\n";
    }
    if(!empty($date) && $t >= $limit) {
      echo "<button name=\"loadbatch\" id=\"loadbatch\" type=\"button\" value=\"\" data-lastinlist=\"".$date."\">".l('view_more_articles')."</button>";
    }
  }
  if($t==0) { 
    if($mode=="menu") {
      echo "<h2>(".ucfirst(l('none_yet')).")</h2>\n";
    } else {
      echo "<h1 id=\"cstart\">(".ucfirst(l('none_yet')).")</h1>\n";
    }
  }    
  $queryStatement->close();
  $mysqli->close();
}
function fetchAssocStatement($stmt) {
  if($stmt->num_rows>0) {
    $result = array();
    $md = $stmt->result_metadata();
    $params = array();
    while($field = $md->fetch_field()) {
      $params[] = &$result[$field->name];
    }
    call_user_func_array(array($stmt, 'bind_result'), $params);
    if($stmt->fetch()) return $result;
  }
  return null;
}
function contact() {
  if(isset($_POST['name']))$_SESSION[_SITE.'name']=cleanXSS($_POST['name']);
  if(isset($_POST['email']))$_SESSION[_SITE.'email']=cleanXSS($_POST['email']);
  if(isset($_POST['message']))$_SESSION[_SITE.'message']=cleanXSS($_POST['message']);
  if (!isset($_POST['contactform'])) {
    $_SESSION[_SITE.'time'] = time();
    echo "<div id=\"lead\" class=\"page\">\n";
    echo "  <div id=\"main\" class=\"contactpage\">\n";
    echo "      <h1 id=\"cstart\">".ucfirst(l("contact_header"))."</h1>\n";
    echo "      <p>".l("contact_story")."</p>\n";
    echo "      <hr class=\"dotted\" />\n";
    echo "      <form method=\"post\" action=\""._SITE."\" id=\"contactpagina\" accept-charset=\"UTF-8\">\n";
    echo "        <p><label for=\"name\">".ucfirst(l("name"))."</label>\n";
    echo "        <input type=\"text\" name=\"name\" id=\"name\" maxlength=\"100\" class=\"text\" value=\"".checkSESSION(_SITE.'name','')."\" tabindex=\"".tabindex(25)."\" /></p>\n";
    echo "        <p><label for=\"email\">".ucfirst(l("email"))." *</label>\n";
    echo "        <input type=\"text\" name=\"email\" id=\" email\" maxlength=\"320\" class=\"text\" value=\"".checkSESSION(_SITE.'email')."\" tabindex=\"".tabindex()."\" /></p>\n";
    echo "        <p><label for=\"message\">".ucfirst(l("message"))."</label>\n";
    echo "        <textarea name=\"message\" rows=\"5\" cols=\"5\" placeholder=\"Schrijf hier uw bericht\" id=\"message\" tabindex=\"".tabindex()."\">".checkSESSION(_SITE.'message')."</textarea></p>\n";
    echo mathCaptcha();
    echo "        <p class=\"buttons\"><input type=\"submit\" name=\"contactformi\" id=\"contactformi\" class=\"btn btn-default\" value=\"".ucfirst(l("send"))."\" tabindex=\"".tabindex()."\" /></p>\n";
    echo "        <input type=\"hidden\" name=\"contactform\" id=\"contactform\" value=\"send\"/>\n";
    echo "        <input type=\"hidden\" name=\"time\" id=\"time\" value=\"".time()."\"/>\n";
    echo "        <input type=\"hidden\" name=\"ip\" id=\"ip\" value=\"".$_SERVER["REMOTE_ADDR"]."\"/>\n";
    echo "      </form>\n";
    echo "  </div>\n";
    echo "</div>\n";
    echo "<div class=\"spacer\"></div>\n";
  } elseif(isset($_SESSION[_SITE.'time'])) {
    $count = $magic = 0;
    if( get_magic_quotes_gpc() ){ $magic = 1; }
    foreach($_POST as $k => $v){
      if($count===8) die;
      if($magic) $$k = stripslashes($v);
      else $$k = $v;
      ++$count;
    }
    $name = (isset($name[0]) && ! isset($name[300]) ) ? trim($name) : null;
    $name = ! preg_match('/[\\n\\r]/', $name) ? $name : die;
    $mail = (isset($email[6]) && ! isset($email[320]) ) ? trim($email) : null;
    $mail = ! preg_match('/[\\n\\r]/', $mail) ? $mail : die;
    $message = (isset($message[10]) && !isset($message[6000]) ) ? $message : null;
    $name = isset($name) ? clean(cleanXSS(trim($name))) : null;
    $mail = isset($email) ? clean(cleanXSS(trim($email))) : null;
    $message = isset($message) ? clean(cleanXSS(strip_tags($message))) : null;
    $time = ( isset($_SESSION[_SITE.'time']) && ((time() - $_SESSION[_SITE.'time']) > 10)) ? $_SESSION[_SITE.'time'] : null ;
    if ( isset($ip) && $ip === $_SERVER['REMOTE_ADDR'] && $time && $name && checkMathCaptcha() && filter_var($mail, FILTER_VALIDATE_EMAIL)) {
      $message = str_replace("\\r\\n","",$message);
      unset($_SESSION[_SITE.'time']);
      echo notification(0,l('contact_sent'),'home');
      include($_SERVER['DOCUMENT_ROOT']."/php/mail.php");
      $head_me = array(
        'to'   => array(s('website_email')=>s('website_title')),
        'from' => array($mail=>$name)
      );
      $head_ct = array(
        'to'      =>array($mail=>$name),
        'from'    =>array(s('website_email')=>s('website_title'))
      );
      $subject_me = "Een websitebezoeker heeft een vraag";
      $subject_ct = s('contact_subject');
      $body_me  = "<div style=\"width:100%;height:100%;background-color:#fafafa;margin:0px;padding:0px;font-family:Arial;font-size:1em\">";
      $body_me .= "  <div style=\"position:relative;max-width:800px;width:98%;padding:0px 1% 30px 1%;margin:0px;background-color:#ffffff\">";
      $body_me .= "    Geachte redactie,<br /><br />Via de website wilde ik u graag iets laten weten. Mijn naam is ".$name." en mijn bericht leest u hieronder.<br /><br />";
      $body_me .= "    ".$message."<br /><br />";
      $body_me .= "    Datum: ".date('l, j F Y')."<br /><br />";
      $body_me .= "    ".$name;
      $body_me .= "  </div>";
      $body_me .= "  <p style=\"position:relative;max-width:800px;width:98%;padding:15px 1%;margin:0px;text-align:center\">";
      $body_me .= "    <a style=\"text-decoration:none\" href=\"".proto_sub_domain(true)."\">".proto_sub_domain(true)."</a>";
      $body_me .= "  </p>";
      $body_me .= "</div>";
      $body_ct  = "<div style=\"width:100%;height:100%;background-color:#fafafa;margin:0px;padding:0px;font-family:Arial;font-size:1em\">";
      $body_ct .= "  <div style=\"position:relative;max-width:800px;width:98%;padding:0px 1% 30px 1%;margin:0px;background-color:#ffffff\">";
      $body_ct .= "    Geachte heer/mevrouw,<br /><br />Bedankt voor uw vraag/opmerking; wij stellen uw interesse en/of inbreng zeer op prijs! Hieronder treft u een kopie aan van uw vraag/opmerking.<br /><br />";
      $body_ct .= "    ".$message."<br /><br />";
      $body_ct .= "    Datum: ".date('l, j F Y')."<br /><br />";
      $body_ct .= "    Wij nemen zo spoedig mogelijk contact met u op.<br /><br />";
      $body_ct .= "    Met vriendelijke groet,<br /><br />";
      $body_ct .= "    ".s('company_contact')."<br />";
      $body_ct .= "    <a href=\""._SITE."\">".s('website_title')."</a><br /><br />";
      $body_ct .= "    tel: <a href=\"tel:".s('company_phone')."\">".s('company_phone')."</a>";
      $body_ct .= "  </div>";
      $body_ct .= "  <p style=\"position:relative;max-width:800px;width:98%;padding:15px 1%;margin:0px;text-align:center\">";
      $body_ct .= "    <a style=\"text-decoration:none\" href=\"".proto_sub_domain(true)."\">".proto_sub_domain(true)."</a>";
      $body_ct .= "  </p>";
      $body_ct .= "</div>";
      email::send($head_me,$subject_me,$body_me);
      email::send($head_ct,$subject_ct,$body_ct);
      unset($_SESSION[_SITE.'name'],$_SESSION[_SITE.'email'],$_SESSION[_SITE.'message']);
    } else {
      echo notification(1,l('contact_not_sent'),'contact');
    }
  }
}
function proto_sub_domain($withproto) {
  if($withproto===true) {
    if ( isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
      $protocol = 'https://';
    }
    else {
      $protocol = 'http://';
    }
    return $protocol . $_SERVER["HTTP_HOST"];
  } else {  
    return $_SERVER["HTTP_HOST"];
  }
}
function mostvisited($items=5) {
  echo "<ol class=\"funkiest\">\n";
  $q = "SELECT arts.title,arts.seftitle,cats.seftitle,stats.visits FROM "._PRE."articles AS arts INNER JOIN "._PRE."funkystats AS stats ON arts.id = stats.funkyartid LEFT OUTER JOIN "._PRE."categories as cats ON arts.category = cats.id WHERE arts.show_on_home = 'YES' ORDER BY stats.visits DESC LIMIT ".$items."";
  $r = mysqli_query($q);
  while($mostfunky=mysqli_fetch_array($r)) {
    echo "<li><a tabindex=\"".tabindex()."\" href=\"".$mostfunky[2]."/".$mostfunky[1]."\">".$mostfunky[0]."</a> <span>(".$mostfunky[3].")</span></li>\n";
  }
  echo "</ol>\n";
}
function cat_menu($nr,$cat) {
  echo "<ul>\n";
  $r=mysqli_query("SELECT arts.title AS title, arts.seftitle AS asef, cats.seftitle AS csef FROM "._PRE."articles AS arts INNER JOIN "._PRE."categories AS cats ON arts.category = cats.id WHERE cats.seftitle='".$cat."' ORDER BY arts.date LIMIT ".$nr."");
  while ($article = mysqli_fetch_array($r)) {
    echo "  <li><a href=\"".$cat."/".$article['asef']."\">".$article['title']."</a></li>\n";
  }
  echo "</ul>\n";
}
function menu_articles($start = 0, $size = 4, $cat_specific = 0) {
  global $categorySEF, $_catID, $subcatSEF;
  switch ($cat_specific){
  case  1 :
    $subcat = !empty($_catID) && empty($subcatSEF) ? " AND category = ".$_catID : "";
    break;
  case  2 :
    $subcat = !empty($_catID) ? " AND category = ".$_catID : "";
    break;
  default:
    $subcat = "";
  }
  $query = "SELECT title,a.seftitle AS asef,date,c.name AS name,c.seftitle AS csef,x.name AS xname,x.seftitle AS xsef FROM "._PRE."articles AS a LEFT OUTER JOIN "._PRE."categories AS c ON category = c.id LEFT OUTER JOIN "._PRE."categories AS x ON c.subcat =  x.id AND x.published ='YES' WHERE position = 1 AND a.published = 1 AND c.published ='YES' AND a.visible = 'YES' AND a.show_on_home = 'YES'".$subcat." ORDER BY date DESC LIMIT ".$size." OFFSET ".$start;
  $result = mysqli_query($query);
  $count = mysqli_num_rows($result);
  $idx=30;
  echo "<ul>\n";
  if ( $count === 0) {
    echo "<li>".ucfirst(l('no_articles'))."</li>\n";
  } else {
    while ($r = mysqli_fetch_array($result)) {
      $name = s('show_cat_names') == 'on' ? ' ('.$r['name'].')' : '';
      $date = date("d M Y", strtotime($r['date']));
      $link = isset($r['xsef']) ? $r['xsef'].'/'.$r['csef'] : $r['csef'];
      echo "  <li><a href=\""._SITE.$link."/".$r['asef']."/\" title=\"".$r['title']."\" tabindex=\"".$idx++."\">".$r['title'].$name."</a></li>\n";
    }
  }
  echo "</ul>\n";
}
function new_comments($number = 5, $stringlen = 30) {
  $query = "SELECT a.id AS aid,title,a.seftitle AS asef,category,co.id,articleid,co.name AS coname,comment,c.name,c.seftitle AS csef,c.subcat,x.name,x.seftitle AS xsef FROM "._PRE."comments AS co LEFT OUTER JOIN "._PRE."articles AS a ON articleid = a.id LEFT OUTER JOIN "._PRE."categories AS c ON category = c.id AND c.published ='YES' LEFT OUTER JOIN "._PRE."categories AS x ON c.subcat = x.id AND x.published ='YES' WHERE a.published = 1 AND (a.commentable = 'YES' || a.commentable = 'FREEZ' ) AND approved = 'True' ORDER BY co.id DESC LIMIT $number";
  $result = mysqli_query($query);
  if (mysqli_num_rows($result) === 0) {
    echo "              <li>".ucfirst(l('no_comments'))."</li>\n";
  } else {
    $comlim = s('comment_limit');
    $comment_limit = $comlim < 1 ? 1 : $comlim;
    $comments_order = s('comments_order');
    $idx = 30;
    while ($r = mysqli_fetch_array($result)) {
      $loopr = mysqli_query("SELECT id FROM "._PRE."comments WHERE articleid = '".$r[articleid]."' AND approved = 'True' ORDER BY id $comments_order");
      $num = 1;
      while ($r_art = mysqli_fetch_array($loopr)) {
        if ($r_art['id'] == $r['id']) {
          $ordinal = $num;
        }
      $num++;
      }
      $name = $r['coname'];
      $comment = strip_tags($r['comment']);
      $page = ceil($ordinal / $comment_limit);
      $ncom = $name.' ('.$comment;
      $ncom = strlen($ncom) > $stringlen ? substr($ncom, 0, $stringlen - 3).'...' : $ncom;
      $ncom.= strlen($name) < $stringlen ? ')' : '';
      $ncom = str_replace(' ...', '...', $ncom);
      $paging = $page > 1 ? '/'.ucfirst(l('comment_pages')).$page : '';       unset($link);       if (isset($r['xsef'])) { $link = $r['xsef'].'/'; }
      if (isset($r['csef'])) { $link .= $r['csef'].'/'; }
      $link .= $r['asef'];       echo "              <li><a href=\""._SITE.$link.$paging."/#".ucfirst(l('comment')).$ordinal."\" title=\"".ucfirst(l('comment_info'))." ".$r['title']."\" tabindex=\"".$idx++."\">".$ncom."</a></li>\n";
    }
  }
}
function searchform() { 
  echo "<form id=\"search_engine\" method=\"post\" action=\""._SITE."\" accept-charset=\"".s('charset')."\">\n";
  echo "  <fieldset class=\"search\">\n";
  echo "    <input class=\"searchfield\" name=\"search_query\" type=\"text\" value=\"\" id=\"keywords\" tabindex=\"11\" />\n";
  echo "    <div class=\"searchbuttons\">\n";
  echo "      <input class=\"searchbutton\" name=\"submit\" type=\"submit\" value=\"".ucfirst(l('search_button'))."\" tabindex=\"12\" />\n";
  echo "    </div>\n";
  echo "  </fieldset>\n";
  echo "</form>\n";
}
function search($limit = 20) {
  $search_query = clean(cleanXSS(checkPOST('search_query')));
  echo "<div class=\"page\">\n";
  if (strlen($search_query) < 4 || $search_query == ucfirst(l('search_keywords'))) {
    echo "<p>".ucfirst(l('charerror'))."</p>\n";
  } else {
    $keywords = explode(' ', $search_query);
    $keyCount = count($keywords);
    $query = 'SELECT a.id FROM '._PRE.'articles AS a LEFT OUTER JOIN '._PRE.'categories AS c ON category = c.id AND c.published =\'YES\' LEFT OUTER JOIN '._PRE.'categories AS x ON c.subcat =  x.id AND x.published =\'YES\' WHERE position != 2 AND a.published = 1 AND';
    if(!_ADMIN){
      $query = $query.' a.visible = \'YES\' AND ';
    }
    if ($keyCount > 1) {
      for ($i = 0; $i < $keyCount - 1; $i++) {
        $query = $query.' (title LIKE "%'.$keywords[$i].'%" ||
          text LIKE "%'.$keywords[$i].'%" ||
          keywords_meta LIKE "%'.$keywords[$i].'%") &&';
      }
      $j = $keyCount - 1;
      $query = $query.'(title LIKE "%'.$keywords[$j].'%" ||
        text LIKE "%'.$keywords[$j].'%" ||
        keywords_meta LIKE "%'.$keywords[$j].'%")';
    } else {
      $query = $query.'(title LIKE "%'.$keywords[0].'%" ||
        text LIKE "%'.$keywords[0].'%" ||
        keywords_meta LIKE "%'.$keywords[0].'%")';
    }
    $query = $query.' ORDER BY id DESC LIMIT '.$limit;
    $result = mysqli_query($query);
    $numrows = mysqli_num_rows($result);
    if (!$numrows) {
      echo "<p>\n".ucfirst(l('noresults'))." <strong>".stripslashes($search_query)."</strong>\n.</p>\n";
    } else {
      echo "<p>\n<strong>".$numrows."</strong> ".($numrows>1?l('resultsfound'):l('resultfound'))." op het zoekwoord <strong>".stripslashes($search_query)."</strong>\n</p>\n";
      while ($r = mysqli_fetch_array($result)) {
        $Or_id[] = 'a.id ='.$r['id'];
      }
      $Or_id = implode(' OR ',$Or_id);
      $query = 'SELECT title,text,a.seftitle AS asef, a.date AS date, a.position AS position, a.displayinfo AS displayinfo, c.name AS name, c.seftitle AS csef, x.name AS xname, x.seftitle AS xsef FROM '._PRE.'articles AS a LEFT OUTER JOIN '._PRE.'categories AS c ON category = c.id LEFT OUTER JOIN '._PRE.'categories AS x ON c.subcat =  x.id WHERE '.$Or_id;
      $result = mysqli_query($query);
      while ($r = mysqli_fetch_array($result)) {
        $name = isset($r['name']) ? ' ('.$r['name'].')' : '';
        if (isset($r['xsef']))  $link = $r['xsef'].'/'.$r['csef'].'/';
        else $link = isset($r['csef']) ? $r['csef'].'/' : '';
        echo "<div class=\"hit\">\n<h3><a href=\""._SITE.$link.$r['asef']."/\" tabindex=\"".tabindex()."\">".$r['title']."</a></h3>\n";
        preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $r['text'], $image);
        echo "<p class=\"previewtext\">\n".(array_key_exists('src',$image)?"<img src=\"".$image['src']."\" class=\"".((strpos(tease($r['text'],s('previewlines')),'.') & 1) ?"previewimageLeft":"previewimageRight")."\" alt=\"\" />\n":"").tease($r['text'],s('previewlines'))."\n</p>\n";
        echo "</div>\n";
      }
    }
  }
  echo '<p><strong><a href="#" id="goback" tabindex="'.tabindex().'">'.ucfirst(l('backhome')).'</a></strong></p>';
  echo "</div>\n";
}
function rss_contents($rss_item){
  header("Content-type:application/atom+xml");
  echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
  echo "<feed xmlns=\"http://www.w3.org/2005/Atom\">\n";
  $limit = s('rss_limit');
  $dblink = connect_to_db();
  switch($rss_item) {
    case  'feeded-articles':
      $heading = ucfirst(l('articles'));
      $query = _PRE.'articles WHERE position = 1 AND visible = \'YES\' AND published = 1 ORDER BY date';
      break;
    case  'feeded-pages':
      $heading = ucfirst(l('pages'));
      $query = _PRE.'articles WHERE position = 3 AND visible = \'YES\' AND published = 1 ORDER BY date';
      break;
    case  'feeded-agenda':
      $heading = ucfirst(l('agenda'));
      $query = _PRE.'articles WHERE position = 4 AND visible = \'YES\' AND published = 1 ORDER BY date';
      break;
    case  'feeded-comments':
      $heading = ucfirst(l('comments'));
      $query = _PRE.'comments WHERE approved = \'True\' ORDER BY id';
      break;
    case  'feeded-sitemap':
      $heading = ucfirst(l('pages')) . " - " .ucfirst(l('articles'));
      $query = _PRE.'articles WHERE position = 1 OR position = 3 OR position = 4 AND visible = \'YES\' AND published = 1 ORDER BY date';
      break;
  }
  echo "  <title>".s('website_title')." ".l('divider')." ".$heading."</title>\n";
  echo "  <subtitle>".strftime(_DATEFORM)."</subtitle>\n";
  echo "  <link rel=\"self\" href=\"http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF'])."/".$rss_item."/\" />\n";
  echo "  <updated>".date("c")."</updated>\n";
  echo "  <author>\n";
  echo "    <name>".s('company_contact')."</name>\n";
  echo "  </author>\n";
  echo "  <id>"._SITE."</id>\n";
  if($rss_item == 'feeded-pages' || $rss_item == 'feeded-sitemap') {
    echo "  <entry>\n";
    echo "    <id>"._SITE."</id>\n";
    echo "    <title type=\"html\">".s('website_title')."</title>\n";
    echo "    <updated>".date("c")."</updated>\n";
    echo "    <author>\n";
    echo "      <name>".s("website_title")."</name>\n";
    echo "    </author>\n";
    echo "    <link rel=\"alternate\" hreflang=\""._CONTENT_LANGUAGE_BCP."\" href=\""._SITE."\" />\n";
    echo "    <summary type=\"xhtml\">\n";
    echo "      <div xmlns=\"http://www.w3.org/1999/xhtml\">\n";
    echo "        <p>".clean(cleanXSS(s("website_description")))."</p>\n";
    echo "      </div>\n";
    echo "    </summary>\n";
    echo "  </entry>\n";
  }
  $result = mysqli_query($dblink,"SELECT * FROM $query DESC LIMIT $limit");
  $numrows = mysqli_num_rows($result);
  $comments_order = s('comments_order');
  $ordinal = $comments_order == 'DESC' ? 1 : $numrows;
  $comment_link = '';
  $comment_limit = s('comment_limit') < 1 ? 1 : s('comment_limit');
  $comments_order = s('comments_order');
  while ($r = mysqli_fetch_assoc($result)) {
    switch($rss_item) {
      case  'feeded-articles':
      case  'feeded-pages':
      case  'feeded-agenda':
      case  'feeded-sitemap':
        $date = date(DateTime::ATOM, strtotime($r['date']));
        if ($r['category'] == 0) {
          $categorySEF = '';
        } else {
          $categorySEF = cat_rel($r['category'], 'seftitle').'/';
        }
        $articleSEF = $r['seftitle'];
        $title = $r['title'];
        $text = $r['text'];
      break;
      case  'feeded-comments':
        $subquery = "SELECT id FROM "._PRE.'comments'."
          WHERE articleid = ".$r['articleid']."
          ORDER BY id $comments_order";
        $subresult = mysqli_query($dblink,$subquery);
        $num = 1;
        while ($subr = mysqli_fetch_array($subresult)) {
          if ($subr['id'] == $r['id']) {
            $ordinal = $num;
          }
          $num++;
        }
        $page = ceil($ordinal / $comment_limit);
        $articleSEF = retrieve('seftitle', 'articles', 'id', $r['articleid']);
        $articleCat = retrieve('category', 'articles', 'id', $r['articleid']);
        $articleTitle = retrieve('title', 'articles', 'id', $r['articleid']);
        if ($articleCat == 0) {
          $categorySEF = '';
        } else {
          $categorySEF = cat_rel($articleCat, 'seftitle').'/';
        }
        if (!empty($articleSEF)) {
          $paging = $page > 1 ? $page.'/' : '';
          $comment_link = 'c_'.$paging.'#'.ucfirst(l('comment')).$ordinal;         }
        $date = date(DateTime::ATOM, strtotime($r['time']));
        $title = $articleTitle.' - '.$r['name'];
        $text = $r['comment'];
      break;
    }
    $link = _SITE.$categorySEF.$articleSEF.'/'.$comment_link;
    $title = stripslashes(htmlspecialchars(entity($title), ENT_QUOTES, 'UTF-8', FALSE));


    echo "  <entry>\n";
    echo "    <id>".$link."</id>\n";
    echo "    <title type=\"html\">".strip($title)."</title>\n";
    echo "    <updated>".$date."</updated>\n";
    echo "    <author>\n";
    echo "      <name>".s("website_title")."</name>\n";
    echo "    </author>\n";
    echo "    <link rel=\"alternate\" hreflang=\""._CONTENT_LANGUAGE_BCP."\" href=\"".$link."\" />\n";
    echo "    <summary type=\"xhtml\">\n";
    echo "      <div xmlns=\"http://www.w3.org/1999/xhtml\">\n";
    echo "        <p>".createShortText($text)."</p>\n";
    echo "      </div>\n";
    echo "    </summary>\n";
    echo "  </entry>\n";
  }
  echo "</feed>\n";
  exit;
}
function feeder() {
  echo "<a href=\"feeded-articles/\" tabindex=\"".tabindex()."\"><img class=\"atom\" src=\"../../images/icons/rss.png\" alt=\"Atom articles\" title=\"".ucfirst(l( 'rss_articles' ))."\" /></a>\n";
}

function login() {
  if (!_ADMIN) {
    echo "<div id=\"lead\" class=\"page\">\n";
    echo "  <div id=\"main\" class=\"webpage\">\n";
    echo "    <h1 id=\"cstart\">".ucfirst(l('login'))."</h1>\n";
    echo html_input('form', '', '', '', '', '', '', '', '', '', '', '', 'post', s('login_url'), '');
    echo html_input('fieldset','','','','','','','','','','','','','','','');
    echo html_input('text', 'uname', 'uname', '', ucfirst(l('username')), 'text', '', '', '', '', '', '', '', '', '', tabindex());
    echo html_input('password', 'pass', 'pass', '', ucfirst(l('password')), 'text', '', '', '', '', '', '', '', '', '', tabindex());
    echo mathCaptcha();
    echo "        <p class=\"buttons\">";
    echo html_input('submit', 'submit', 'submit', ucfirst(l('login')), '', 'btn btn-default', '', '', '', '', '', '', '', '', '', tabindex());
    echo html_input('hidden', 'Loginform', 'Loginform', 'True', '', '', '', '', '', '', '', '', '', '', '');
    echo "        </p>\n";
    echo html_input('fieldset','','','','','','','','','','','','','','end','');
    echo "    </form>\n";
    echo "  </div>\n";
    echo "</div>\n";
  } else {
    echo "<div id=\"lead\" class=\"page\">\n";
    echo "  <div id=\"main\" class=\"webpage\">\n";
    echo "    <h1>".ucfirst(l('logged_in'))."</h1>";
    echo "    <p><a href=\""._SITE."logout\" title=\"".ucfirst(l('logout'))."\" tabindex=\"".tabindex()."\">".ucfirst(l('logout'))."</a></p>";
    echo "    <p><a href=\""._SITE."administration\" title=\"".ucfirst(l('goto_admin'))."\" tabindex=\"".tabindex()."\">".ucfirst(l('goto_admin'))."</a></p>";
    echo "  </div>\n";
    echo "</div>\n";
  }
}
function stats($field, $position) {
  $link = connect_to_db();
  if (!empty($position)) {
    $pos = " WHERE position = $position";
  } else {
    $pos = '';
  }
  $query = 'SELECT id FROM '._PRE.$field.$pos;
  $result = mysqli_query($link,$query);
  $numrows = mysqli_num_rows($result);
  return $numrows;
}
function html_input($type, $name, $id, $value, $label, $css, $script1, $script2, $script3, $checked, $units, $sentance, $method, $action, $legend, $tabidx='') {
  $lbl = !empty($label) ? "<label for=\"".$id."\">".ucfirst($label)."</label>\n" : '';
  $ID = !empty($id) ? ' id="'.$id.'"' : '';
  $style = !empty($css) ? ' class="'.$css.'"' : '';
  $js1 = !empty($script1) ? ' '.$script1 : '';
  $js2 = !empty($script2) ? ' '.$script2 : '';
  $js3 = !empty($script3) ? ' '.$script3 : '';
  $tabs = !empty($tabidx) ? ' tabindex="'.$tabidx.'"' : '';
  $attribs = $ID.$style.$js1.$js2.$js3.$tabs;
  $val = ' value="'.$value.'"';
  $input = '<input type="'.$type.'" name="'.$name.'"'.$attribs;
  if ($legend != '' && $legend != 'end') $legend = "      <h2>".$legend."</h2>\n";
  switch($type) {
    case  'form': $output = (!empty($method) && $method != 'end') ? "<form method=\"".$method."\" action=\"".$action."\"".$attribs." accept-charset=\"".s('charset')."\">\n" : "</form>\n"; break;
    case  'fieldset': $output = ($legend != 'end') ? "<fieldset".$attribs.">\n".$legend : "</fieldset>\n"; break;
    case  'text':
    case  'password': $output = "<p>\n".$lbl.$input.$val." />\n".$units.ucfirst($sentance)."\n</p>\n"; break;
    case  'checkbox':
    case  'radio': $check = $checked == 'ok' ? " checked=\"checked\"" : ""; $output = "<p>\n<label>\n".$input.$check." />\n".ucfirst($label)."</label>\n".ucfirst($sentance)."\n</p>\n"; break;
    case  'hidden':
    case  'submit':
    case  'reset':
    case  'button': $output = $input.$val." />\n"; break;
    case  'textarea': $output = "<p>\n".$lbl."<textarea name=\"".$name."\"".$attribs.">\n".$value."</textarea>\n</p>\n"; break;
  }
  return $output;
}
function administration() {
  if (!_ADMIN) {
    echo( notification(1,ucfirst(l('error_not_logged_in')),'login'));
  } else {
    $link = connect_to_db();
    $catnum = mysqli_fetch_assoc(mysqli_query($link,"SELECT COUNT(id) as catnum FROM "._PRE.'categories'.""));
    foreach ($_POST as $key) { unset($_POST[$key]); }
    echo "<div class=\"admin_main_menu\">\n";
    echo "  <div id=\"iconbar\" class=\"iconbar\">\n";
    echo "    <a class=\"button settings\" href=\"scs-settings\" title=\"".ucfirst(l('settings'))."\" tabindex=\"".tabindex()."\">Instellingen</a>\n";
    echo "    <a class=\"button logout\" href=\"logout\" title=\"".ucfirst(l('logout'))."\" tabindex=\"".tabindex()."\">Uitloggen</a>\n";
    echo "  </div>\n";
    echo "  <div class=\"admin_main_row\">\n";
    echo "    <h2>".ucfirst(l('pages'))."</h2>\n";
    administration_show_item(ucfirst(l('view'))." ".ucfirst(l('pages')),'scs-pages','webpage.png','Bekijk of verander de webpagina\'s van de website');
    administration_show_item(ucfirst(l('add_new_page')),'scs-page','add_webpage.png','Maak een nieuwe webpagina aan');
    echo "  </div>\n";
    echo "  <div class=\"admin_main_row\">\n";
    echo "    <h2>".ucfirst(l('agenda'))."</h2>\n";
    administration_show_item(ucfirst(l('view'))." ".ucfirst(l('agenda')),'scs-events','calendar.png','Bekijk of verander de inhoud van de agenda');
    administration_show_item(ucfirst(l('add_new_event')),'scs-event','add_calendar.png','Maak een nieuwe gebeurtenis aan in de agenda');
    echo "  </div>\n";
    echo "  <div class=\"admin_main_row\">\n";
    echo "    <h2>".ucfirst(l('weblog'))."</h2>\n";
    administration_show_item(ucfirst(l('view'))." ".ucfirst(l('categories')),'scs-categories','folder.png','Bekijk of verander de categorieen van het weblog');
    administration_show_item(ucfirst(l('add_new_category')),'scs-category','add_folder.png','Maak een nieuwe categorie aan');
    administration_show_item(ucfirst(l('view'))." ".ucfirst(l('articles')),'scs-articles','blogpage.png','Bekijk of verander de publicaties van het weblog');
    administration_show_item(ucfirst(l('add_new_article')),'scs-article','add_blogpage.png','Maak een nieuwe publicatie aan');
    echo "</div>\n";
  }
}
function administration_show_item($admin_item_name,$admin_item_link,$admin_item_icon,$admin_item_clue) {
  echo "  <div class=\"admin_main_field\">\n";
  echo "    <a class=\"admin_main_icon\" href=\"".$admin_item_link."\"><img src=\"images/icons/large/".$admin_item_icon."\" alt=\"\">\n";
  echo "    <h3 class=\"admin_main_item\">".$admin_item_name."</h3>\n";
  echo "    <p class=\"admin_main_clue\">\n";
  echo $admin_item_clue;
  echo "    </p></a>\n";
  echo "  </div>\n";
}

function settings() {   echo "<div>\n";
  echo html_input('form','','','','','cms','','','','','','','post', 'index.php?action=process&amp;task=save_settings','');
  echo "  <div id=\"iconbar\" class=\"iconbar\">\n";
  echo "    <button id=\"save\" type=\"submit\" value=\"".ucfirst(l('save'))."\" name=\"save\" title=\"".ucfirst(ucfirst(l('settings'))." ".ucfirst(l('save')))."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/settings_accept.png\" alt=\"save\" /></button>\n";
  echo "    <button id=\"knop1\" class=\"knob_on\" type=\"button\" onclick=\"flip('sub-1');\" title=\"".ucfirst(ucfirst(l('general'))." ".ucfirst(l('settings')))."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/settings.png\" alt=\"settings\" /></button>\n";
  echo "    <button id=\"knop2\" type=\"button\" onclick=\"flip('sub-2');\" title=\"".ucfirst(l('contents')." ".l('settings'))."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/text_page.png\" alt=\"settings\" /></button>\n";
  echo "    <button id=\"knop3\" type=\"button\" onclick=\"flip('sub-3');\" title=\"".ucfirst(l('facebook')." ".l('settings'))."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/facebook.png\" alt=\"settings\" /></button>\n";
  echo "    <button id=\"knop4\" type=\"button\" onclick=\"flip('sub-4');\" title=\"".ucfirst(l('comment')." ".l('settings'))."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/comments.png\" alt=\"settings\" /></button>\n";
  echo "    <button id=\"knop5\" type=\"button\" onclick=\"flip('up_div');\" title=\"".ucfirst(l('upchange'))."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/up.png\" alt=\"settings\" /></button>\n";
  echo "    <button id=\"knop6\" type=\"button\" title=\"".ucfirst(l('back'))."\" onclick=\"window.location.assign('"._SITE."administration/')\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/back.png\" alt=\"back\" /></button>\n";
  echo "  </div>\n  <div class=\"settings\">";
  echo "    <div id=\"sub-1\" style=\"display:block\" class=\"scs-sub\">\n";
  echo html_input('fieldset','','','','','','','','','','','','','',ucfirst(l('global_settings')),'');
  echo html_input('text', 'website_title', 'webtitle', s('website_title'), ucfirst(l('company_name')),'','','','','','','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'company_name_info\',1,true)" /><span id="company_name_info" class="cms_infotext">'.ucfirst(l('company_name_title')).'</span>','','','',tabindex());
  echo html_input('text', 'company_contact', 'com_con', s('company_contact'), ucfirst(l('a_company_contact')),'','','','','','','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'com_con_info\',1,true)" /><span id="com_con_info" class="cms_infotext">'.ucfirst(l('a_company_contact_title')).'</span>','','','',tabindex());
  echo html_input('text', 'website_email', 'we', s('website_email'), ucfirst(l('a_website_email')),'','','','','','','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'email_info\',1,true)" /><span id="email_info" class="cms_infotext">'.ucfirst(l('a_website_email_title')).'</span>','','','',tabindex());
  echo html_input('text', 'contact_subject', 'cs', s('contact_subject'), ucfirst(l('a_contact_subject')),'','','','','','','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'con_sub_info\',1,true)" /><span id="con_sub_info" class="cms_infotext">'.ucfirst(l('a_contact_subject_title')).'</span>','','','',tabindex());
  echo html_input('text', 'company_phone', 'com_pho', s('company_phone'), ucfirst(l('a_company_phone')),'','','','','','','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'com_pho_info\',1,true)" /><span id="com_pho_info" class="cms_infotext">'.ucfirst(l('a_company_phone_title')).'</span>','','','',tabindex());
 	echo "      <div class=\"p\">\n<label for=\"lang\">".ucfirst(l('a_language'))."</label>\n<div class=\"select-refaced-container\"><select name=\"language\" id=\"lang\" tabindex=\"".tabindex(4)."\">\n";
  global $language_array;
 	$n_lang = count($language_array);
 	for ($x_lang=0; $x_lang<$n_lang; $x_lang++) {
    $keysonlang = array_keys($language_array);
    echo $keysonlang[$x_lang];
    echo "<option value=\"".$keysonlang[$x_lang]."\"";
	    if (s('language') == $keysonlang[$x_lang]) { echo " selected=\"selected\""; }
	    echo ">".$language_array[$keysonlang[$x_lang]]."</option>\n";
 	}
 	echo "</select>\n      </div>\n      <img src=\"images/information.png\" class=\"cms_infobutton\" alt=\"\" onclick=\"flip('lang_info',1,true)\" />\n<span id=\"lang_info\" class=\"cms_infotext\">".ucfirst(l('a_language_title'))."</span>\n    </div>\n";
  echo html_input('text', 'login_url', 'loginurl', s('login_url'), ucfirst(l('url_to_logpanel')),'','onkeypress="return SEFrestrict(event);"','','','','','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'login_url_info\',1,true)" /><span id="login_url_info" class="cms_infotext">'.ucfirst(l('url_to_logpanel_title')).'</span>','','','',tabindex());
  echo html_input('hidden', 'charset', 'char', s('charset') == '' ? 'UTF-8' : s('charset'), ucfirst(l('charset')),'','','','','','','','','','');
  echo html_input('hidden', 'date_format', 'dt', s('date_format'), ucfirst(l('a_date_format')),'','','','','','','','','','');
  echo html_input('fieldset','','','','','','','','','','','','','','end','');
  echo "</div>\n";
  echo "<div id=\"sub-2\" style=\"display:none\" class=\"scs-sub\">\n";
  echo html_input('fieldset','','','','','','','','','','','','','',ucfirst(l('contents').' '.l('settings')),'');
 	echo "<div class=\"p\">\n<label for=\"dp\">".ucfirst(l('a_display_page'))."</label>\n<div class=\"select-refaced-container\"><select name=\"display_page\" id=\"dp\" tabindex=\"".tabindex()."\">\n";
 	echo "<option value=\"0\"".(s('display_page') == 0 ? " selected=\"selected\"" : "").">".ucfirst(l('show_no_arts'))."</option>\n";
 	$query = "SELECT id,title,date FROM "._PRE."articles WHERE position = 3 AND default_page='NO' ORDER BY date ASC";
  $link = connect_to_db();
	 $result = mysqli_query($link,$query);
	 while ($r = mysqli_fetch_array($result)) {
	   echo "<option value=\"".$r['id']."\"";
	   if (s('display_page') == $r['id']) { echo " selected=\"selected\""; }
	   echo ">".$r['title']."</option>\n";
	 }
	 echo "</select>\n</div>\n<img src=\"images/information.png\" class=\"cms_infobutton\" alt=\"\" onclick=\"flip('display_page_info',1,true)\" />\n<span id=\"display_page_info\" class=\"cms_infotext\">".ucfirst(l('a_display_page_title'))."</span>\n</div>\n";
  echo html_input('text', 'home_sef', 'webSEF', s('home_sef') == '' ? ucfirst(l('home_sef')) : s('home_sef'), ucfirst(l('a_home_sef')), '', 'onkeypress="return SEFrestrict(event);"','','','','','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'home_sef_info\',1,true)" /><span id="home_sef_info" class="cms_infotext">'.ucfirst(l('a_home_sef_title')).'</span>','','','',tabindex());
  echo html_input('text', 'website_description', 'wdesc', s('website_description'), ucfirst(l('a_description')),'','','','','','','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'description_info\',1,true)" /><span id="description_info" class="cms_infotext">'.ucfirst(l('a_description_title')).'</span>','','','',tabindex());
  echo html_input('text', 'website_keywords', 'wkey', s('website_keywords'), ucfirst(l('a_keywords')),'','','','','','','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'keywords_info\',1,true)" /><span id="keywords_info" class="cms_infotext">'.ucfirst(l('a_keywords_title')).'</span>','','','',tabindex());
  echo html_input('checkbox','show_agenda','sag','', ucfirst(l('show_agenda')),'','','','',(s('show_agenda') == 'on' ? 'ok' : ''),'','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'show_agenda_info\',1,true)" /><span id="show_agenda_info" class="cms_infotext">'.ucfirst(l('show_agenda_title')).'</span>','','','',tabindex());
  echo "<h3>".ucfirst(l('overview_settings'))."</h3>\n";
 	echo "<div class=\"p\">\n<label for=\"dbp\">".ucfirst(l('overview_pagename'))."</label>\n<div class=\"select-refaced-container\">";
 	echo "<select name=\"overview_pagename\" id=\"dbp\" tabindex=\"".tabindex()."\">\n";
 	echo "<option value=\"\"".(s('overview_pagename') == "" ? " selected=\"selected\"" : "").">".ucfirst(l('show_no_cats'))."</option>\n";
 	$query = "SELECT id,seftitle,name FROM "._PRE."categories WHERE subcat = 0 ORDER BY name ASC";
 	$result = mysqli_query($link,$query);
	 while ($r = mysqli_fetch_array($result)) {
	   echo "<option value=\"".$r['seftitle']."\"";
	   if (s('overview_pagename') == $r['seftitle']) echo " selected=\"selected\"";
	   echo ">".$r['name']."</option>\n";
	 }
 	echo "<option value=\"x".s('overview_menuname')."\"".(s('overview_pagename') == "x".s('overview_menuname') ? " selected=\"selected\"" : "").">".ucfirst(l('show_all_cats'))."</option>\n";
 	echo "</select>\n</div>\n<img src=\"images/information.png\" class=\"cms_infobutton\" alt=\"\" onclick=\"flip('display_blogpage_info',1,true)\" />\n<span id=\"display_blogpage_info\" class=\"cms_infotext\">".ucfirst(l('a_display_blogpage_title'))."</span>\n</div>\n";
  echo html_input('checkbox','show_overview_in_menu','soim','', ucfirst(l('show_overview_in_menu')),'','','','',(s('show_overview_in_menu') == 'on' ? 'ok' : ''),'','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'show_overview_in_menu_info\',1,true)" /><span id="show_overview_in_menu_info" class="cms_infotext">'.ucfirst(l('show_overview_in_menu_title')).'</span>','','','',tabindex());
  echo "<p><label for=\"ovmename\">".ucfirst(l('overview_menuname'))."</label><input type=\"text\" name=\"overview_menuname\" value=\"".(s('overview_menuname')!=""?s('overview_menuname'):l('articles'))."\" id=\"ovmename\" tabindex=\"".tabindex()."\" onkeypress=\"return SEFrestrict(event);\" onchange=\"if(this.value==''){this.value='".l('articles')."';get_lastchild(document.getElementById('dbp')).value='x".l('articles')."';}else{get_lastchild(document.getElementById('dbp')).value='x'+this.value;}\" />\n<img src=\"images/information.png\" class=\"cms_infobutton\" alt=\"\" onclick=\"flip('overview_menuname_info',1,true)\" />\n<span id=\"overview_menuname_info\" class=\"cms_infotext\">".ucfirst(l('overview_menuname_title'))."</span>\n</p>";
  echo html_input('text','article_limit','artl',s('article_limit'), ucfirst(l('a_article_limit')),'','style="width:40px"','onkeypress="return NUMrestrict(event);"','','',' <span class="input_units">'.l('articles').'</span>','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'article_limit_info\',1,true)" /><span id="article_limit_info" class="cms_infotext">'.ucfirst(l('a_article_limit_title')).'</span>','','','',tabindex());
  echo html_input('text','previewlines','prel',s('previewlines'), ucfirst(l('previewlines')),'','style="width:40px"','onkeypress="return NUMrestrict(event);"','','',' <span class="input_units">'.l('lines').'</span>','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'previewlines_info\',1,true)" /><span id="previewlines_info" class="cms_infotext">'.ucfirst(l('previewlines_title')).'</span>','','','',tabindex());
  echo html_input('checkbox','display_pagination','dpag','',ucfirst(l('a_display_pagination')),'','','','',(s('display_pagination') == 'on' ? 'ok' : ''),'','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'pagination_info\',1,true)" /><span id="pagination_info" class="cms_infotext">'.ucfirst(l('a_display_pagination_title')).'</span>','','','',tabindex());
  echo html_input('checkbox','show_cat_names','scn','',ucfirst(l('a_show_category_name')),'','','','',(s('show_cat_names') == 'on' ? 'ok' : ''),'','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'show_category_name_info\',1,true)" /><span id="show_category_name_info" class="cms_infotext">'.ucfirst(l('a_show_category_name_title')).'</span>','','','',tabindex());
  echo html_input('checkbox','num_categories','nc', '', ucfirst(l('a_num_categories')),'','','','',(s('num_categories') == 'on' ? 'ok' : ''),'','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'num_categories_info\',1,true)" /><span id="num_categories_info" class="cms_infotext">'.ucfirst(l('a_num_categories_title')).'</span>','','','',tabindex());
  echo "<h3>".ucfirst(l('publish_settings'))."</h3>\n";
  echo html_input('checkbox','show_title_on','sto','', ucfirst(l('show_title_on')),'','','','',(s('show_title_on') == 'on' ? 'ok' : ''),'','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'show_title_on_info\',1,true)" /><span id="show_title_on_info" class="cms_infotext">'.ucfirst(l('show_title_on_title')).'</span>','','','',tabindex());
  echo html_input('checkbox','show_author_on','sao','', ucfirst(l('show_author_on')),'','','','',(s('show_author_on') == 'on' ? 'ok' : ''),'','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'show_author_on_info\',1,true)" /><span id="show_author_on_info" class="cms_infotext">'.ucfirst(l('show_author_on_title')).'</span>','','','',tabindex());
  echo html_input('checkbox','show_info_on','sio','', ucfirst(l('show_info_on')),'','','','',(s('show_info_on') == 'on' ? 'ok' : ''),'','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'show_info_on_info\',1,true)" /><span id="show_info_on_info" class="cms_infotext">'.ucfirst(l('show_info_on_title')).'</span>','','','',tabindex());
  echo html_input('checkbox','display_new_on_home','dnoh','',ucfirst(l('a_display_new_on_home')),'','','','',(s('display_new_on_home') == 'on' ? 'ok' : ''),'','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'display_new_on_home_info\',1,true)" /><span id="display_new_on_home_info" class="cms_infotext">'.ucfirst(l('a_display_new_on_home_title')).'</span>','','','',tabindex());
  echo "<h3>".ucfirst(l('feeds'))."</h3>\n";
  echo html_input('text', 'rss_limit', 'rssl', s('rss_limit'), ucfirst(l('a_rss_limit')),'','style="width:40px"','onkeypress="return NUMrestrict(event);"','','',' <span class="input_units">'.l('feeds').'</span>','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'rss_limit_info\',1,true)" /><span id="rss_limit_info" class="cms_infotext">'.ucfirst(l('a_rss_limit_title')).'</span>','','','',tabindex());
  echo html_input('fieldset','','','','','','','','','','','','','','end','');
  echo "</div>\n";

  echo "<div id=\"sub-3\" style=\"display:none\" class=\"scs-sub\">\n";
  echo html_input('fieldset','','','','','','','','','','','','','',ucfirst(l('facebook').' '.l('settings')),'');
  echo html_input('text', 'facebook_admin', 'fadmin', s('facebook_admin'), ucfirst(l('a_facebook_admin')),'','','','','','','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'facebook_admin_info\',1,true)" /><span id="facebook_admin_info" class="cms_infotext">'.ucfirst(l('a_facebook_admin_title')).'</span>','','','',tabindex());
  echo html_input('checkbox','display_social_buttons','dsb','',ucfirst(l('social_buttons_on')),'','','','',(s('display_social_buttons') == 'on' ? 'ok' : ''),'','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'display_social_buttons_info\',1,true)" /><span id="display_social_buttons_info" class="cms_infotext">'.ucfirst(l('social_buttons_on_title')).'</span>','','','',tabindex());
  echo html_input('fieldset','','','','','','','','','','','','','','end','');
  echo "</div>\n";

  echo "<div id=\"sub-4\" style=\"display:none\" class=\"scs-sub\">\n";
  echo html_input('fieldset','','','','','','','','','','','','','',ucfirst(l('comment').' '.l('settings')),'');
  echo html_input('checkbox','enable_comments','ecm','',ucfirst(l('enable_comments')),'','','','',(s('enable_comments') == 'on' ? 'ok' : ''),'','<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'enable_comments_info\',1,true)" /><span id="enable_comments_info" class="cms_infotext">'.ucfirst(l('enable_comments_title')).'</span>','','','',tabindex());
  echo html_input('fieldset','','','','','','','','','','','','','','end','');
  echo "</div>\n";

  echo "</div>\n";   echo "</form>\n";
    echo html_input('form','','','','','cms changeup','','','','','','','post','index.php?action=process&amp;task=changeup','');
  echo "<div id=\"up_div\" style=\"display:none\" class=\"scs-sub\">\n";
  echo html_input('fieldset','','','','','','','','','','','','','',ucfirst(l('upchange')),'');
  echo "<p class=\"outside\">".ucfirst(l('login_limit')).":</p>\n";
  echo "<ul class=\"outside\">\n<li>".ucfirst(l('limit1'))."</li>\n<li>".ucfirst(l('limit2'))."</li>\n<li>".ucfirst(l('limit3'))."</li>\n</ul>\n";
  echo html_input('text','uname','uname','',ucfirst(l('username')),'','','','','','','','','','',tabindex());
  echo html_input('password','pass1','pass1','',ucfirst(l('password')),'','','','','','','','','','',tabindex());
  echo html_input('password','pass2','pass2','',ucfirst(l('password2')),'','','','','','','','','','',tabindex());
  echo html_input('hidden','task','task','changeup','','','','','','','','','','','');
  echo "<button id=\"submit_pass\" class=\"up_button\" type=\"submit\" value=\"".ucfirst(l('save'))."\" name=\"submit_pass\" title=\"".ucfirst(ucfirst(l('settings'))." ".ucfirst(l('save')))."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/settings_accept.png\" alt=\"settings\" /></button>\n";
  echo html_input('fieldset','','','','','','','','','','','','','','end','');
  echo "</div>\n";
  echo "</form>\n";
  echo "</div>\n";
}
function category_list() {
  echo "<select name=\"subcat\" id=\"subcat\" class=\"cms\">\n";
  $selected =" selected=\"selected\"";
  $link = connect_to_db();
	 $result = mysqli_query($link,"SELECT id,name FROM "._PRE."categories WHERE subcat = 0 ORDER BY catorder, id");

  if(key_exists('id',$_GET)) {
    $var = $_GET['id'];
    echo "<option value=\"0\">".ucfirst(l('not_sub'))."</option>\n";
    while ($r = mysqli_fetch_array($result)) {
      $child = retrieve('subcat','categories','id',$_GET['id']);
      if ($r['id'] == $child) {
        echo "<option value=\"".$r['id']."\"".$selected.">".$r['name']."</option>\n";
      } elseif ($_GET['id']!=$r['id']){
        echo "<option value=\"".$r['id']."\">".$r['name']."</option>\n";
      }
    }
  } else {
    echo "<option value=\"0\">".ucfirst(l('not_sub'))."</option>\n";
    while ($r = mysqli_fetch_array($result)) {
      echo "<option value=\"".$r['id']."\">".$r['name']."</option>\n";
    }
  }
  echo "</select>\n";
}
function form_categories($subcat=0) {
  $link = connect_to_db();
  if (isset($_GET['id']) && is_numeric($_GET['id']) && !is_null($_GET['id'])) {
    $categoryid = $_GET['id'];
    $query = mysqli_query($link,"SELECT id,name,seftitle,published,description,subcat,catorder FROM "._PRE."categories WHERE id=".$categoryid);
    $r = mysqli_fetch_array($query);
    $jresult = mysqli_query($link,"SELECT name FROM "._PRE.'categories'." WHERE id = ".$r['subcat']);
    while($j = mysqli_fetch_array($jresult)) {
      $name = $j['name'];
    }
    $frm_action = _SITE.'index.php?action=process&amp;task=scs-category&amp;id='.$categoryid;
    $frm_add_edit = $r['subcat'] == '0' ? ucfirst(l('edit')).' '.ucfirst(l('category')) : ucfirst(l('edit')).' '.ucfirst(l('subcategory')).' '.$name ;
    if (array_key_exists(_SITE.'temp',$_SESSION) && array_key_exists('description',$_SESSION[_SITE.'temp'])) {
      $frm_description = str_replace('&', '&amp;', $_SESSION[_SITE.'temp']['description']);
    } else {
      $frm_description = str_replace('&', '&amp;', $r['description']);
    }
    $frm_name = $r['name'];
    $sub_cat = $r['subcat'];
    $frm_sef_title = $r['seftitle'];
    $frm_publish = $r['published'] == 'YES' ? 'ok' : '';
    $catorder = $r['catorder'];
    $frm_task = 'edit_category';
    $frm_submit = ucfirst(l('edit'));
  } else {
    $sub_cat = checkGET('sub_id') != '' ? checkGET('sub_id') : 0;
    if ($sub_cat!=0) {
      $jresult = mysqli_query($link,"SELECT name FROM "._PRE."categories WHERE id = '".$sub_cat."'");
      while($j = mysqli_fetch_array($jresult)) {
        $name = $j['name'];
      }
    }
    list($catorder)=mysqli_fetch_row(mysqli_query($link,"SELECT MAX(catorder) FROM categories"));
    $catorder=$catorder+1;
    $frm_name = '';
    $frm_action = _SITE.'index.php?action=process&amp;task=scs-category';
    $frm_add_edit = empty($sub_cat) ? ucfirst(l('add_category')) : ucfirst(l('add_subcategory')).' ('.$name.')';
    $frm_sef_title = array_key_exists('name',$_POST)?($_POST['name'] == '' ? cleanSEF($_POST['name']) : cleanSEF($_POST['seftitle'])):'';
    $frm_description = '';
    $frm_publish = 'ok';
    $frm_task = 'add_category';
    $frm_submit = ucfirst(l('add'));
  }
  echo "<div>\n";
  echo html_input('form', '', 'edit', '', '', 'cms', '', '', '', '', '', '', 'post', $frm_action, '');
  if($sub_cat == 0) {
    $cat_fieldset = ucfirst(l($frm_task));
    $publish = ucfirst(l('publish_category'));
    $cat_name = ucfirst(l('cat_name'));
    $cat_seftitle = ucfirst(l('cat_seftitle'));
  } else {
    $cat_fieldset = $frm_task == 'add_category' ? ucfirst(l('add_subcategory')) : ucfirst(l('edit_subcategory'));
    $publish = ucfirst(l('publish_subcategory'));
    $cat_name = ucfirst(l('subcat_name'));
    $cat_seftitle = ucfirst(l('subcat_seftitle'));
  }
  
  echo "  <div id=\"iconbar\" class=\"iconbar\">\n";
  echo "    <button id=\"".$frm_task."\" type=\"submit\" value=\"".$frm_submit."\" name=\"".$frm_task."\" title=\"".ucfirst(l('save'))."\"><img class=\"icon\" src=\"images/icons/folder_accept.png\" alt=\"save\" /></button>\n";
  if (!empty($categoryid)) {
    echo html_input('hidden', 'id', 'id', $categoryid, '', '', '', '', '', '', '', '', '', '', '');
    echo "    <button id=\"delete_category\" type=\"submit\" value=\"".ucfirst(l('delete'))."\" name=\"delete_category\" title=\"".ucfirst(l('delete'))."\" onclick=\"return pop();\"><img class=\"icon\" src=\"images/icons/delete_folder.png\" alt=\"delete\" /></button>\n";
  }
  echo "    <button id=\"knop12\" type=\"button\" title=\"".ucfirst(l('back'))."\" onclick=\"window.location.assign('"._SITE."scs-categories/')\"><img class=\"icon\" src=\"images/icons/back.png\" alt=\"back\" /></button>\n";
  echo "    <button id=\"knop7\" type=\"button\" title=\"".ucfirst(l('home'))."\" onclick=\"window.location.assign('"._SITE."administration/')\"><img class=\"icon\" src=\"images/icons/home.png\" alt=\"home\" /></button>\n";
  echo "  </div>\n";
  echo "  <div class=\"settings\">\n";
  echo html_input('fieldset','','','','','','','','','','','','','',$cat_fieldset,'');
  echo html_input('text', 'name', 't', $frm_name, $cat_name, '', 'onchange="genSEF(this,document.forms[\'edit\'].seftitle)"', 'onkeyup="genSEF(this,document.forms[\'edit\'].seftitle)"', '', '', '', '<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'name_info\',1,true)" /><span id="name_info" class="cms_infotext">'.ucfirst(l('cat_name_title')).'</span>', '', '', '');
  echo html_input('text', 'seftitle', 's', $frm_sef_title, $cat_seftitle, '', '', '', '', '', '', '<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'seftitle_info\',1,true)" /><span id="seftitle_info" class="cms_infotext">'.ucfirst(l('cat_seftitle_title')).'</span>', '', '', '');
  echo "    <div class=\"p\">\n";
  echo "      <label for=\"ckedit_txt\">".ucfirst(l('description'))."</label>\n";
  echo "      <script type=\"text/javascript\" src=\"ckeditor/ckeditor.js\"></script>\n";
  echo "      <textarea id=\"ckedit_txt\" name=\"description\">" . stripslashes(htmlspecialchars(entity($frm_description), ENT_QUOTES, 'UTF-8', FALSE)) . "</textarea>\n";
  echo "      <script type=\"text/javascript\">\n";
  echo "        CKEDITOR.replace( 'ckedit_txt', {filebrowserBrowseUrl: 'ckeditor/plugins/filemanager/index.html', language: '".strtolower(s('language'))."'} );\n";
  echo "      </script>\n";
  echo "      <img src=\"images/information.png\" class=\"cms_infobutton\" alt=\"\" onclick=\"flip('description_info',1,true)\" /><span id=\"description_info\" class=\"cms_infotext\">".ucfirst(l('cat_description_title'))."</span>\n";
  echo "    </div>\n";
  echo "    <div class=\"p\">\n<label>".ucfirst(l('make_subcategory_of'))."</label>\n";
  echo "    <div class=\"select-refaced-container\">\n";
  category_list();
  echo "    </div>\n<img src=\"images/information.png\" class=\"cms_infobutton\" alt=\"\" onclick=\"flip('subcat_info',1,true)\" />\n<span id=\"subcat_info\" class=\"cms_infotext\">".ucfirst(l('make_subcategory_of_title'))."</span>\n</div>\n";
  echo html_input('checkbox', 'publish', 'pub', 'YES', ucfirst($publish), '', '', '', '', $frm_publish, '', '<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'publish_info\',1,true)" /><span id="publish_info" class="cms_infotext">'.ucfirst(l('publish_title')).'</span>', '', '', '');
  if (!empty($sub_cat)) {
  }
  echo html_input('hidden', 'catorder', 'catorder', $catorder, '', '', '', '', '', '', '', '', '', '', '');
  echo html_input('hidden', 'task', 'task', 'scs-category', '', '', '', '', '', '', '', '', '', '', '');
  echo html_input('fieldset','','','','','','','','','','','','','','end','');
  echo "\n</div>\n</form>\n</div>\n";
}
function admin_categories() {
  $link = 'index.php?action=scs-category';
  $dblink = connect_to_db();
  echo "<div>\n";
  echo html_input('form', '', '', '', '', 'cms', '', '', '', '', '', '', 'post', 'index.php?action=process&amp;task=reorder', '');
  echo "  <input type=\"hidden\" name=\"order\" id=\"order\" value=\"scs-categories\" />\n";
  echo "  <div id=\"iconbar\" class=\"iconbar\">\n";
  echo "    <button id=\"knop1\" type=\"button\" onclick=\"window.location.assign('"._SITE."scs-category/');\" title=\"".ucfirst(l('add_new_category'))."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/add_cat.png\" alt=\"add\" /></button>\n";
  echo "    <button id=\"knop2\" type=\"submit\" class=\"button\" value=\"Order Content\" name=\"reorder\" title=\"".ucfirst(l('order_category'))."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/cat_reorder.png\" alt=\"reorder\" /></button>\n";
  echo "    <button id=\"knop3\" type=\"button\" title=\"".ucfirst(l('back'))."\" onclick=\"window.location.assign('"._SITE."administration/')\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/back.png\" alt=\"back\" /></button>\n";
  echo "  </div>\n";
  $query = 'SELECT id, name, description, published, subcat, catorder FROM '._PRE.'categories WHERE subcat = 0 ORDER BY catorder,id ASC';
  $result = mysqli_query($dblink,$query);
  echo "<div class=\"settings\">\n";
  echo html_input('fieldset','','','','','','','','','','','','','',ucfirst(l('categories')),'');
  echo "  <div class=\"scs-sub\">\n";
  if (!$result || !mysqli_num_rows($result)) {
    echo "<p>\n".ucfirst(l('category_not_exist'))."\n</p>\n";
  } else {
    $subID = 0;
    while ($r = mysqli_fetch_array($result)) {
      if($r['published']=="YES") {
        $cat_class="cat";
        $cat_pub="";
      } else {
        $cat_class="cat unpublished";
        $cat_pub=" <span class=\"small\">(".ucfirst(l('cat_unpublished')).")</span>";
      }
      $subquery = 'SELECT id,name,description,published,catorder FROM '._PRE.'categories WHERE subcat = '.$r['id'].' ORDER BY catorder,id ASC';
      $subresult = mysqli_query($dblink,$subquery);
      echo "<p class=\"".$cat_class."\"".(mysqli_num_rows($subresult)? " onclick=\"flip('sub-".$subID."','',true);\"" : "")." style=\"cursor:pointer\">\n";
      echo "  <input type=\"text\" name=\"cat_".$r['id']."\" value=\"".$r['catorder']."\" size=\"1\" tabindex=\"".tabindex()."\" title=\"".ucfirst(l('reorder'))."\" />\n";
      echo "  <a href=\""._SITE.$link."&amp;id=".$r['id']."\" title=\"".l('edit_category')."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/cat_edit.png\" title=\"".ucfirst(l('edit_category'))."\" alt=\"edit\" /></a>\n";
      echo "  <a href=\""._SITE.$link."&amp;sub_id=".$r['id']."\" title=\"".l('add_subcategory')."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/add_subcat.png\" title=\"".ucfirst(l('add_subcategory'))."\" alt=\"edit\" /></a>\n";
      echo "  <a href=\""._SITE.$link."&amp;id=".$r['id']."\" title=\"".l('edit_category')."\" tabindex=\"".tabindex()."\">".$r['name'].$cat_pub."</a>\n</p>\n";
      if(mysqli_num_rows($subresult)) {
        echo "<div id=\"sub-".$subID."\" style=\"display:none\">\n";
        while ($sub = mysqli_fetch_array($subresult)) {
          if($sub['published']=="YES") {
            $subcat_class="cat sub";
            $subcat_pub="";
          } else {
            $subcat_class="subcat_unpublished";
            $subcat_pub=" <span class=\"small\">(".ucfirst(l('subcat_unpublished')).")</span>";
          }
          echo "<p class=\"".$subcat_class."\">\n";
          echo "  <input type=\"text\" name=\"cat_".$sub['id']."\" value=\"".$sub['catorder']."\" size=\"1\" tabindex=\"".tabindex()."\" title=\"".ucfirst(l('reorder'))."\" />\n";
          echo "  <a href=\""._SITE.$link."&amp;id=".$sub['id']."\" title=\"".l('edit_subcategory')."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/subcat_edit.png\" title=\"".ucfirst(l('edit_subcategory'))."\" alt=\"edit\" /></a>\n";
          echo "  <a href=\""._SITE.$link."&amp;id=".$sub['id']."\" title=\"".l('edit_subcategory')."\" tabindex=\"".tabindex()."\">".$sub['name'].$subcat_pub."</a>\n</p>\n";
        }
        echo "</div>\n";
        $subID++;
      }
    }
  }
  echo "  </div>\n";
  echo html_input('fieldset','','','','','','','','','','','','','','end','');
  echo "</div>\n";
  echo "</form>\n";
  echo "</div>\n";
}
function delete_cat($id){
  $link = connect_to_db();
  $catdata = mysqli_fetch_array(mysqli_query($link,"SELECT catorder,subcat FROM "._PRE.'categories'." WHERE id = $id"));
  $cat_order = $catdata['catorder'];
  $cat_subcat = $catdata['subcat'];
  mysqli_query($link,"DELETE FROM "._PRE."categories WHERE id = '$id' LIMIT 1");
  $query = mysqli_query($link,"SELECT id,catorder FROM "._PRE."categories WHERE catorder > ".$cat_order." AND subcat = ".$cat_subcat);
  while ($r = mysqli_fetch_array($query)) {
    mysqli_query($link,"UPDATE "._PRE."categories SET catorder = ".($catorder - 1)." WHERE id = ".$r[id]);
  }
}
function form_articles($contents) {
  $link = connect_to_db();
  if (is_numeric(checkGET('id')) && !is_null(checkGET('id'))) {
    $id = $_GET['id'];
    $query = mysqli_query($link,"SELECT * FROM "._PRE."articles WHERE id=".$id);
    $r = mysqli_fetch_array($query);
    $article_category = $r['category'];
    $position = ($r['position'] == 0 || $r['position'] == "") ? 1 : $r['position'];
    $edit_page = $r['page_extra'];
    $extraid = $r['extraid'];
    switch ($position) {
      case  1:
        $frm_fieldset = ucfirst(l('edit')).' '.l('article');
        $flip_div='show';
        $frm_position1 = ' selected="selected"';
        $back_to_overview = 'scs-articles';
        break;
      case  2:
        $frm_fieldset = ucfirst(l('edit')).' '.l('extra_contents');
        $flip_div='show';
        $frm_position2 = ' selected="selected"';
        $back_to_overview = 'scs-extra';
        break;
      case  3:
        $frm_fieldset = ucfirst(l('edit')).' '.l('page');
        $flip_div='show';
        $frm_position3 = ' selected="selected"';
        $back_to_overview = 'scs-pages';
        break;
      case  4:
        $frm_fieldset = ucfirst(l('edit')).' '.l('agenda');
        $flip_div='show';
        $frm_position4 = ' selected="selected"';
        $back_to_overview = 'scs-events';
        break;
    }
    $frm_action = _SITE.'index.php?action=process&amp;task=admin_article&amp;id='.$id;
    if (array_key_exists(_SITE.'temp',$_SESSION) && array_key_exists('text',$_SESSION[_SITE.'temp'])) {
      $frm_title = $_SESSION[_SITE.'temp']['title'];
      $frm_sef_title = cleanSEF($_SESSION[_SITE.'temp']['seftitle']);
      $frm_text = str_replace('&', '&amp;', $_SESSION[_SITE.'temp']['text']);
      $frm_meta_desc = cleanSEF(checkSESSION('description_meta',_SITE.'temp'));
      $frm_meta_key = cleanSEF(checkSESSION('keywords_meta',_SITE.'temp'));
    } else {
      $frm_title = $r['title'];
      $frm_sef_title = $r['seftitle'];
      $frm_text = str_replace('&', '&amp;', $r['text']);
      $frm_meta_desc = $r['description_meta'];
      $frm_meta_key = $r['keywords_meta'];
    }
    $frm_display_title = $r['displaytitle'] == 'YES' ? 'ok' : '';
    $frm_display_info = $r['displayinfo'] == 'YES' ? 'ok' : '';
    $frm_publish = $r['published'] == 1 ? 'ok' : '';
    $show_in_subcats = $r['show_in_subcats'] == 'YES' ? 'ok' : '';
    $frm_showonhome = $r['show_on_home'] == 'YES' ? 'ok' : '';
    $frm_social_buttons = $r['socialbuttons'] == 'YES' ? 'ok' : '';
    $frm_show_author = $r['show_author'] == 'on' ? 'ok' : '';
    $frm_show_title = $r['displaytitle'] == 'YES' ? 'ok' : '';
    $frm_show_info = $r['displayinfo'] == 'YES' ? 'ok' : '';
    $frm_commentable = ($r['commentable'] == 'YES' || $r['commentable'] == 'FREEZ') ? 'ok' : '';
    $frm_task = 'edit_article';
    $frm_submit = ucfirst(l('edit_button'));
  } else {


    list($id)=mysqli_fetch_row(mysqli_query($link,"SELECT MAX(id) FROM articles"));


    $id=$id+1;
    switch ($contents) {
      case  'scs-article':
        $frm_fieldset = ucfirst(l('scs-article'));
        $flip_div='';
        $position = 1;
        $frm_position1 = ' selected="selected"';
        $back_to_overview = 'scs-articles';
        break;
      case  'extra_new':
        $frm_fieldset = ucfirst(l('extra_new'));
        $flip_div='';
        $position = 2;
        $frm_position2 = ' selected="selected"';
        $back_to_overview = 'scs-extra';
        break;
      case  'scs-page':
        $frm_fieldset = ucfirst(l('scs-page'));
        $flip_div='';
        $position = 3;
        $frm_position3 = ' selected="selected"';
        $back_to_overview = 'scs-pages';
        break;
      case  'scs-event':
        $frm_fieldset = ucfirst(l('scs-event'));
        $flip_div='';
        $position = 4;
        $frm_position4 = ' selected="selected"';
        $back_to_overview = 'scs-events';
        break;
    }
    if (empty($frm_fieldset)) $frm_fieldset =  ucfirst(l('scs-article'));
    $frm_action = _SITE.'index.php?action=process&amp;task=admin_article&amp;contenttype='.$contents;
    if(array_key_exists(_SITE.'temp',$_SESSION)) {
      $frm_title = cleanSEF(checkSESSION('title',_SITE.'temp'));
      $frm_sef_title = cleanSEF(checkSESSION('seftitle',_SITE.'temp'));
      $frm_text = cleanSEF(checkSESSION('text',_SITE.'temp'));
      $frm_meta_desc = cleanSEF(checkSESSION('description_meta',_SITE.'temp'));
      $frm_meta_key = cleanSEF(checkSESSION('keywords_meta',_SITE.'temp'));
    } else {
      $frm_title = $frm_sef_title = $frm_text = $frm_meta_desc = $frm_meta_key = '';
    }
    $frm_display_title = 'ok';
    $frm_display_info = ($contents == 'extra_new') ? '' : 'ok';
    $frm_publish = 'ok';
    $show_in_subcats = 'ok';
    $frm_showonhome = s('display_new_on_home') == 'on' ? 'ok' : '';
    $frm_social_buttons = s('display_social_buttons') == 'on' ? 'ok' : '';
    $frm_show_author = s('show_author_on') == 'on' ? 'ok' : '';
    $frm_show_title = s('show_title_on') == 'on' ? 'ok' : '';
    $frm_show_info = s('show_info_on') == 'on' ? 'ok' : '';
    $frm_commentable = ($contents == 'extra_new' || $contents == 'scs-page' || $contents == 'scs-event' || s('enable_comments') != 'YES') ? '' : 'ok';
    $frm_task = 'add_article';
    $frm_submit = l('submit');
  }
  switch ($position) {
    case  1:
      $data_fieldset = l('article');
      $data_arttitle = ucfirst(l('art_title'));
      $data_seftitle = ucfirst(l('art_sef_title'));
      $data_contents = ucfirst(l('art_contents'));
      $data_pubincat = ucfirst(l('publish_in_category'));
      $info_pubincat = ucfirst(l('publish_in_category_title'));
    break;
    case  2:
      $data_fieldset = l('extra_ct');
      $data_arttitle = ucfirst(l('ext_title'));
      $data_seftitle = ucfirst(l('ext_sef_title'));
      $data_contents = ucfirst(l('ext_contents'));
    break;
    case  3:
      $data_fieldset = l('page');
      $data_arttitle = ucfirst(l('pag_title'));
      $data_seftitle = ucfirst(l('pag_sef_title'));
      $data_contents = ucfirst(l('pag_contents'));
    break;
    case  4:
      $data_fieldset = l('agd_item');
      $data_arttitle = ucfirst(l('agd_title'));
      $data_seftitle = ucfirst(l('agd_sef_title'));
      $data_contents = ucfirst(l('agd_contents'));
    break;
  }
  
  $info_arttitle = ucfirst(l('title_title'));
  $info_seftitle = ucfirst(l('sef_title_title'));
  $info_contents = ucfirst(l('contents_title'));
  
  $catnum = mysqli_fetch_assoc(mysqli_query($link,"SELECT COUNT(id) as catnum FROM "._PRE."categories"));
  if ($contents == 'scs-article' && $catnum['catnum'] < 1) {
    echo ucfirst(l('create_cat'));
  } else {
    echo "<div>\n";
    echo html_input('form', '', '', '', '', 'cms', '', '', '', '', '', '', 'post', $frm_action, '');
    echo "  <div class=\"iconbar\" id=\"iconbar\">\n";
    if (!empty($add)) echo $add;
    echo "    <button type=\"submit\" id=\"knop1\" value=\"Opslaan\" name=\"".$frm_task."\" title=\"".ucfirst(l('save'))."\"><img class=\"icon\" src=\"images/icons/accept_page.png\" alt=\"save\" /></button>\n";
    if (!empty($id)) {
      echo "    <button type=\"submit\" id=\"knop2\" value=\"Verwijder\" onclick=\"return pop();\" name=\"delete_article\" title=\"".ucfirst(l('delete').' '.l('article'))."\"><img class=\"icon\" src=\"images/icons/delete_page.png\" alt=\"delete\" /></button>\n";
    }
    echo "    <button type=\"button\" id=\"knop3\" class=\"knob_on\" onclick=\"flip('edit_article');\" title=\"".ucfirst(l('edit').' '.l('article'))."\"><img class=\"icon\" src=\"images/icons/art_new.png\" alt=\"edit\" /></button>\n";
    echo "    <button type=\"button\" id=\"knop4\" onclick=\"flip('art_settings');\" title=\"".ucfirst(l('article').' '.l('settings'))."\"><img class=\"icon\" src=\"images/icons/page_process.png\" alt=\"settings\" /></button>\n";
    if ($contents != 'extra_new' && $position != 2 && $contents != 'scs-page' && $position != 3) {
      echo "    <button type=\"button\" id=\"knop5\" onclick=\"flip('admin_publish_date');\" title=\"".ucfirst(l('future_posting'))."\"><img class=\"icon\" src=\"images/icons/calendar_empty.png\" alt=\"date\" /></button>\n";
    }
    echo "    <button type=\"button\" id=\"knop6\" title=\"".ucfirst(l('back'))."\" onclick=\"window.location.assign('"._SITE.$back_to_overview."/')\"><img class=\"icon\" src=\"images/icons/back.png\" alt=\"back\" /></button>\n";
    echo "    <button type=\"button\" id=\"knop7\" title=\"".ucfirst(l('home'))."\" onclick=\"window.location.assign('"._SITE."administration/')\"><img class=\"icon\" src=\"images/icons/home.png\" alt=\"home\" /></button>\n";
    echo "  </div>\n";
    echo "  <div class=\"settings\">\n";
    echo "    <div id=\"edit_article\" style=\"display:block\" class=\"scs-sub\">\n";
    echo html_input('fieldset','','','','','','','','','','','','','',ucfirst($data_fieldset),'');
		  if ($contents != 'scs-page' && $position != 3 && $contents != 'scs-event' && $position != 4) {
      echo "      <div class=\"p\">\n<label>".$data_pubincat."</label>\n";
      echo "      <div class=\"select-refaced-container\">\n<select name=\"define_category\" id=\"cms_a_cat\" onchange=\"dependancy('scs-articles');\" class=\"cms\" tabindex=\"".tabindex(10)."\">\n";
      $category_query = 'SELECT id,name,subcat FROM '._PRE.'categories WHERE subcat = 0 ORDER BY catorder,id ASC';
      $category_result = mysqli_query($link,$category_query);
      while ($cat = mysqli_fetch_array($category_result)) {
        $cat_selected = (!empty($article_category) && $article_category == $cat['id']) ? " selected=\"selected\"" : "";
        echo "      <option value=\"".$cat['id']."\"".$cat_selected.">".$cat['name']."</option>\n";
        $subquery = 'SELECT id,name,subcat FROM '._PRE.'categories WHERE subcat = '.$cat['id'].' ORDER BY catorder,id ASC';
        $subresult = mysqli_query($link, $subquery);
        while ($s = mysqli_fetch_array($subresult)) {
          $subcat_selected = $article_category == $s['id']? " selected=\"selected\"" : "";
          echo "      <option value=\"".$s['id']."\"".$subcat_selected." style=\"background:#fafafa url('img/icons/sub.gif') no-repeat 10px 0px; padding-left:10px\">".$s['name']."</option>\n";
        }
      }
      echo "    </select>\n</div>\n<img src=\"images/information.png\" class=\"cms_infobutton\" alt=\"\" onclick=\"flip('publish_in_category_info',1,true)\" />\n<span id=\"publish_in_category_info\" class=\"cms_infotext\">".$info_pubincat."</span>\n</div>";
    } else {
      echo html_input('hidden', 'define_category', 'cms_a_cat', '', '', '', '', '', '', '', '', '', '', '', '');
    }
    echo "    ".html_input('text', 'title', 'at', stripslashes(htmlspecialchars(entity($frm_title), ENT_QUOTES, 'UTF-8', FALSE)), $data_arttitle, '', 'onchange="genSEF(this,document.forms[0].seftitle)"', 'onkeyup="genSEF(this,document.forms[0].seftitle)"', '', '', '', '<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'art_title_info\',1,true)" /><span id="art_title_info" class="cms_infotext">'.$info_arttitle.'</span>', '', '', '');
    echo "    ".html_input('text', 'seftitle', 'as', $frm_sef_title, $data_seftitle, '', '', '', '', '', '', '<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'art_sef_title_info\',1,true)" /><span id="art_sef_title_info" class="cms_infotext">'.$info_seftitle.'</span>', '', '', '');
    echo "    <div class=\"p\">\n";
    echo "      <label>".$data_contents."</label>\n";
    echo "      <script type=\"text/javascript\" src=\"ckeditor/ckeditor.js\"></script>\n";
    echo "      <textarea id=\"ckedit_txt\" name=\"text\">" . stripslashes(htmlspecialchars(entity($frm_text), ENT_QUOTES, 'UTF-8', FALSE)) . "</textarea>\n";
    echo "      <script type=\"text/javascript\">\n";
    echo "        CKEDITOR.replace( 'ckedit_txt', {filebrowserBrowseUrl: 'ckeditor/plugins/filemanager/index.html', language: '".strtolower(s('language'))."'} );\n";
    echo "      </script>\n";
    echo "      <img src=\"images/information.png\" class=\"cms_infobutton\" alt=\"\" onclick=\"flip('contents_info',1,true)\" />\n<span id=\"contents_info\" class=\"cms_infotext\">".$info_contents."</span>\n";
    echo "    </div>\n";
    echo html_input('fieldset','','','','','','','','','','','','','','end','');
    echo "  </div>\n";
    echo "  <div id=\"art_settings\" style=\"display: none\" class=\"scs-sub\">";
    echo html_input('fieldset','','','','','','','','','','','','','',ucfirst($data_fieldset." ".l('settings')),'');
    echo html_input('hidden', 'position', 'position', $position, '', '', '', '', '', '', '', '', '', '', '');
    if ($contents != 'scs-event' && $position != 4) {
      echo "    ".html_input('checkbox', 'publish_article', 'pu', 'YES', ucfirst(l('publish_article')), '', '', '', '', $frm_publish, '', '<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'publish_article_info\',1,true)" /><span id="publish_article_info" class="cms_infotext">'.ucfirst(l('publish_article_title')).'</span>', '', '', '');
    } else {
      echo html_input('hidden', 'publish_article', 'pu', 'on', '', '', '', '', '', '', '', '', '', '', '');
    }
    $query_has_subs = "SELECT COUNT(*) as num FROM "._PRE."articles WHERE default_page='".$id."'";
    $array_has_subs = mysqli_fetch_array(mysqli_query($link,$query_has_subs));
    $default = empty($r) ? 'NO' : $r['default_page'];
    if (($contents == 'scs-page' || $position == 3) && ($array_has_subs['num']==0 && $id!=s('display_page'))) {
      echo "<div class=\"p\">\n<label for=\"dp\">".ucfirst(l('a_pagegroup'))."</label>\n<div class=\"select-refaced-container\"><select name=\"default_page\" id=\"dp\">\n";
      echo "<option value=\"NO\"".($default == 'NO' ? " selected=\"selected\"" : "").">".ucfirst(l('default_page'))."</option>\n";
      $query = "SELECT id,title,date FROM "._PRE."articles WHERE position = 3 AND id <> ".$id." AND id <> ".s('display_page')." AND default_page='NO' ORDER BY date ASC";
      $result = mysqli_query($link,$query);
      while ($rs = mysqli_fetch_array($result)) {
        echo "      <option value=\"".$rs['id']."\"".($rs['id']==$default ? " selected=\"selected\"" : "").">".$rs['title']."</option>\n";
    	 }
     	echo "</select>\n</div>\n<img src=\"images/information.png\" class=\"cms_infobutton\" alt=\"\" onclick=\"flip('pagegroup_info',1,true)\" />\n<span id=\"pagegroup_info\" class=\"cms_infotext\">".ucfirst(l('pagegroup_title'))."</span>\n</div>\n";
    } else {
      echo html_input('hidden', 'default_page', 'defp', 'NO', '', '', '', '', '', '', '', '', '', '', '');
    }
    if ($contents != 'scs-page' && $position != 3) {
      echo "    ".html_input('checkbox', 'show_on_home', 'sho', 'YES', ucfirst(l('show_on_home')), '', '', '', '', $frm_showonhome, '', '<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'show_on_home_info\',1,true)" /><span id="show_on_home_info" class="cms_infotext">'.ucfirst(l('show_on_home_title')).'</span>', '', '', '');
    } else {
      echo html_input('hidden', 'show_on_home', 'sho', 'NO', '', '', '', '', '', '', '', '', '', '', '');
    }
    if ($contents != 'extra_new' && $position != '2') {
      echo html_input('text', 'description_meta', 'dm', $frm_meta_desc, ucfirst(l('description_meta')), '', 'maxlength="128"', '', '', '', '', '<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'description_meta_info\',1,true)" /><span id="description_meta_info" class="cms_infotext">'.ucfirst(l('description_meta_title')).'</span>', '', '', '');
      echo html_input('text', 'keywords_meta', 'km', $frm_meta_key, ucfirst(l('keywords_meta')), '', 'maxlength="128"', '', '', '', '', '<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'keywords_meta_info\',1,true)" /><span id="keywords_meta_info" class="cms_infotext">'.ucfirst(l('keywords_meta_title')).'</span>', '', '', '');
    } else {
      echo html_input('hidden', 'description_meta', 'dm', '', '', '', '', '', '', '', '', '', '', '', '');
      echo html_input('hidden', 'keywords_meta', 'km', '', '', '', '', '', '', '', '', '', '', '', '');
    }
    echo html_input('checkbox', 'display_title', 'dti', 'YES', ucfirst(l('display_title')), '', '', '', '', $frm_show_title, '', '<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'show_title_info\',1,true)" /><span id="show_title_info" class="cms_infotext">'.ucfirst(l('display_title_title')).'</span>', '', '', '');
    if ($contents != 'extra_new' && $position != 2 && $contents != 'scs-page' && $position != 3) {
      if ($contents != 'scs-event' && $position != 4) {
        echo html_input('checkbox', 'show_author', 'sa', 'YES', ucfirst(l('show_author')), '', '', ' onclick="flip(\'author\',\'1\',true);"', '', $frm_show_author, '', '<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'show_author_info\',1,true)" /><input id="author" type="text" name="author" style="display:'.($frm_show_author=="ok"?"block":"none").'" value="'.s('company_contact').'" /><span id="show_author_info" class="cms_infotext">'.ucfirst(l('show_author_title')).'</span>', '', '', '');
        echo html_input('checkbox', 'display_info', 'di', 'YES', ucfirst(l('display_info')), '', '', '', '', $frm_show_info, '', '<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'display_info_info\',1,true)" /><span id="display_info_info" class="cms_infotext">'.ucfirst(l('display_info_title')).'</span>', '', '', '');
      } else {
        echo html_input('hidden', 'display_info', 'di', 'on', '', '', '', '', '', '', '', '', '', '', '');
      }
      echo html_input('checkbox', 'social_buttons', 'sb', 'YES', ucfirst(l('display_social_buttons')), '', '', '', '', $frm_social_buttons, '', '<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'social_buttons_info\',1,true)" /><span id="social_buttons_info" class="cms_infotext">'.ucfirst(l('social_buttons_title')).'</span>', '', '', '');
      echo html_input('checkbox', 'commentable', 'ca', 'YES', ucfirst(l('enable_commenting')), '', '', '', '', $frm_commentable, '', '<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'commentable_info\',1,true)" /><span id="commentable_info" class="cms_infotext">'.ucfirst(l('commentable_title')).'</span>', '', '', '');
      if (!empty($id) && isset($r)) {
        if ($r['commentable'] == 'FREEZ') {
          $comm_checked = "ok";
        } else if ($r['commentable'] == 'YES') {
          $comm_checked = "";
        } else {
          $comm_checked = "";
        }
      }
    } else {
      echo html_input('hidden', 'display_info', 'di', '', '', '', '', '', '', '', '', '', '', '', '');
      echo html_input('hidden', 'social_buttons', 'sb', '', '', '', '', '', '', '', '', '', '', '', '');
      echo html_input('hidden', 'commentable', 'ca', '', '', '', '', '', '', '', '', '', '', '', '');
    }    
    echo html_input('fieldset','','','','','','','','','','','','','','end','');
    echo "    </div>\n";
    if ($contents != 'extra_new' && $position != 2 && $contents != 'scs-page' && $position != 3) {
      echo "    <div id=\"admin_publish_date\" style=\"display:none\" class=\"scs-sub\">\n";
      echo html_input('fieldset','','','','','','','','','','','','','',ucfirst($data_fieldset." ".l('future_posting')),'');
      echo "      <p>\n".ucfirst(l('server_time')).": ".strftime("%A %e %B %Y")."\n</p>\n";
      $onoff_status = isset($r)?($r['published'] == '2' ? 'ok' : ''):'2';
      if ($contents != 'scs-event' && $position != 4) {
        echo html_input('checkbox', 'fposting', 'fp', 'YES', ucfirst(l('enable')), '', '', 'onclick="flip(\'pagenderen\',\'1\',true);"', '', $onoff_status, '', '<img src="images/information.png" class="cms_infobutton" alt="" onclick="flip(\'fposting_info\',1,true)" /><span id="fposting_info" class="cms_infotext">'.ucfirst(l('fposting_title')).'</span>', '', '', '');
        echo "      <p id=\"pagenderen\">\n";
        echo "      <script type=\"text/javascript\">document.getElementById('pagenderen').style.display=(document.getElementById('fp').checked)?'block':'none';</script>\n";
      } else {
        echo html_input('hidden', 'fposting', 'fp', 'on', '', '', '', '', '', '', '', '', '', '', '');
        echo "      <p id=\"pagenderen\" style=\"display:block\">\n";
      }
      echo "        <span style=\"display:block; padding-bottom:10px\">".ucfirst(l('article_date'))."</span>\n";
      if(!empty($id) && isset($r)) {
        $dateval = substr($r['date'],0,10);
      } else {
        $dateval = substr(date('Y-m-d H:i:s'),0,10);
      }
      $dateval_array = explode("-",$dateval);
      $dateval = intval($dateval_array[2])."-".intval($dateval_array[1])."-".intval($dateval_array[0]);
      echo "        <input type=\"text\" name=\"date\" id=\"date\" value=\"".$dateval."\" />\n";
      echo "        <img src=\"images/information.png\" class=\"cms_infobutton\" alt=\"\" onclick=\"flip('date_info',1,true)\" />\n<span id=\"date_info\" class=\"cms_infotext\">".ucfirst(l('date_title'))."</span>\n";
      echo "      </p>\n";
      echo "      <script type=\"text/javascript\">calendar.set(\"date\");</script>\n";
      echo html_input('hidden', 'task', 'task', 'admin_article', '', '', '', '', '', '', '', '', '', '', '');
  	  if (!empty($id)) {
        echo html_input('hidden', 'article_category', 'article_category',  (isset($article_category)?$article_category:""), '', '', '', '', '', '', '', '', '', '', '');
        echo html_input('hidden', 'id', 'id', $id, '', '', '', '', '', '', '', '', '', '', '');
      }
      echo html_input('fieldset','','','','','','','','','','','','','','end','');
      echo "    </div>\n";
    } else {
      echo html_input('hidden', 'fposting', 'fp', 'NO', '', '', '', '', '', '', '', '', '', '', '');
      echo html_input('hidden', 'task', 'task', 'admin_article', '', '', '', '', '', '', '', '', '', '', '');
  	  if (!empty($id)) {
        echo html_input('hidden', 'article_category', 'article_category', (isset($article_category)?$article_category:""), '', '', '', '', '', '', '', '', '', '', '');
        echo html_input('hidden', 'id', 'id', $id, '', '', '', '', '', '', '', '', '', '', '');
      }
    }
    echo "  </div>\n";
    echo "</form>\n</div>\n";
  }
}
function admin_articles($contents) {
  global $categorySEF, $subcatSEF;
  $link = "<a href=\""._SITE.$categorySEF."/";
  $dblink = connect_to_db();
  switch ($contents) {
    case  'article_view':
      $title = ucfirst(l('articles'));
      $sef = 'scs-article';
      $goto = 'scs-articles';
      $p = 1;
      $qw = 'position < 2 AND position >-1 ';
    break;
    case  'extra_view':
      $title = ucfirst(l('extra_contents'));
      $sef = 'extra_new';
      $goto = 'extra_contents';
      $p = '2';
      $qw = 'position = 2 ';
    break;
    case  'page_view':
      $title = ucfirst(l('pages'));
      $sef = 'scs-page';
      $p = '3';
      $goto = 'scs-pages';
      $qw = 'position = 3 ';
    break;
    case  'agenda_view':
      $title = ucfirst(l('agenda'));
      $sef = 'scs-event';
      $p = '4';
      $goto = 'scs-events';
      $qw = 'position = 4 ';
    break;
  }
  $subquery = 'AND '.$qw;
  if (stats('articles',$p) > 0) {
    $add = "        <p class=\"head_right\">\n<span>".ucfirst(l('sort_articles'))."</span>\n";
    $add.= "          <a class=\"sort\" onmousedown=\"this.style.borderStyle='inset';\" onmouseup=\"this.style.borderStyle='outset';\" href=\""._SITE.$categorySEF."/\" tabindex=\"".tabindex()."\">".ucfirst(l('all'))."</a>\n";
    $add.= "          <a class=\"sort\" onmousedown=\"this.style.borderStyle='inset';\" onmouseup=\"this.style.borderStyle='outset';\" href=\""._SITE.$categorySEF."/".ucfirst(l('year'))."\" tabindex=\"".tabindex()."\">".ucfirst(l('year'))."</a>\n";
    $add.= "          <a class=\"sort\" onmousedown=\"this.style.borderStyle='inset';\" onmouseup=\"this.style.borderStyle='outset';\" href=\""._SITE.$categorySEF."/".ucfirst(l('month'))."\" tabindex=\"".tabindex()."\">".ucfirst(l('month'))."</a>\n      </p>\n";
  } else {
    $add = '';
  }
  if ($subcatSEF == ucfirst(l('year')) || $subcatSEF == ucfirst(l('month'))) {
    $query = 'SELECT DISTINCT(YEAR(date)) AS dyear FROM '._PRE.'articles WHERE '.$qw.' ORDER BY date DESC';
    $result = mysqli_query($dblink,$query);
    $month_names = explode(',', ucfirst(l('month_names')));
    echo "<div>\n";
    echo "  <form class=\"cms\">\n";
    echo "    <div id=\"iconbar\" class=\"iconbar\">\n";
    echo $add;
    echo "      <button id=\"knop1\" type=\"button\" onclick=\"window.location.assign('"._SITE.$sef."/');\" title=\"".ucfirst(l('add_new_page'))."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/add_page.png\" alt=\"add\" /></button>\n";
    echo "      <button id=\"knop2\" type=\"button\" title=\"".ucfirst(l('back'))."\" onclick=\"window.location.assign('"._SITE."administration/')\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/back.png\" alt=\"back\" /></button>\n";
    echo "    </div>\n";
    echo "    <div class=\"settings\">\n";
    if ($result){
      echo "    <fieldset class=\"\">\n";
      echo "      <h2>".l('articles')."</h2>\n";
      echo "      <div class=\"scs-sub\">\n";
      while ($r = mysqli_fetch_array($result)) {
        $ryear = $r['dyear'];
        if ($subcatSEF == ucfirst(l('month'))) {
          echo "<p class=\"cat\" onclick=\"flip('y".$r['dyear']."','',true)\" style=\"cursor:pointer\">".$r['dyear']."</p>";
          echo "<div id=\"y".$r['dyear']."\">\n";
          $qx = "SELECT DISTINCT(MONTH(date)) AS dmonth FROM "._PRE."articles WHERE ".$qw." AND YEAR(date)=".$ryear." ORDER BY date ASC";
          $rqx = mysqli_query($dblink,$qx);
          while ($rx = mysqli_fetch_array($rqx)){
            $m = $rx['dmonth'] - 1;
            echo "<p class=\"cat sub\"><a href=\""._SITE.$categorySEF."/".ucfirst(l('year'))."=".$r['dyear'].";".ucfirst(l('month'))."=".$rx['dmonth']."\" tabindex=\"".tabindex()."\">".$month_names[$m]."</a></p>";
          }
          echo "</div>\n";
        } else {
          echo "<p class=\"cat\"><a href=\""._SITE.$categorySEF."/".ucfirst(l('year'))."=".$r['dyear']."\" tabindex=\"".tabindex()."\">".$r['dyear']."</a></p>";
        }
      }
      echo "      </div>\n";      
      echo "    </fieldset>\n";      
    }
    echo "    </div>\n";      
    echo "  </form>\n";
    echo "</div>\n";
    return;
  }
  $txtYear = ucfirst(l('year'));
  $txtMonth = ucfirst(l('month'));
  if (substr($subcatSEF, 0, strlen($txtYear)) == $txtYear) {
    $year = substr($subcatSEF, strlen($txtYear)+1, 4);
  }
  $find = strpos($subcatSEF,ucfirst(l('month')));
  if ($find > 0) {
    $month = substr($subcatSEF, $find + strlen($txtMonth) + 1, 2);
  }
  $filterquery = !empty($year) ? "AND YEAR(date)='".$year."' " : '';
  $filterquery .= !empty($month) ? "AND MONTH(date)='".$month."' " : '';
  if(empty($month)) !empty($year)?$datum=strftime("%Y", strtotime($year."/1/1")):$datum=date("Y");
  else $datum=strftime("%B %Y", strtotime($year.'/'.$month.'/1'));
  $no_content = !empty($filterquery) ? "Geen publicaties in ".$datum : ucfirst(l('article_not_exist'));
  echo "<div>\n";
  echo "  " . html_input('form', '', '', '', '', 'cms', '', '', '', '', '', '', 'post', 'index.php?action=process&amp;task=reorder', '');
  echo "    <input type=\"hidden\" name=\"order\" id=\"order\" value=\"".$goto."\" />\n";
  echo "    <div id=\"iconbar\" class=\"iconbar\">\n";
  echo $add;
  echo "      <button id=\"knop1\" type=\"button\" onclick=\"window.location.assign('"._SITE.$sef."/');\" title=\"".ucfirst(l('add_new_article'))."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/add_page.png\" alt=\"add\" /></button>\n";
  if($contents != 'agenda_view') echo "      <button type=\"submit\" id=\"reorder\" class=\"button\" value=\"Order Content\" name=\"reorder\" title=\"".ucfirst(l('order_article'))."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/art_reorder.png\" alt=\"reorder\" /></button>\n";
  echo "      <button id=\"knop3\" type=\"button\" title=\"".ucfirst(l('back'))."\" onclick=\"window.location.assign('"._SITE."administration/')\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/back.png\" alt=\"back\" /></button>\n";
  echo "    </div>\n";
  echo "    <div class=\"settings\">\n";
  if ($contents == 'extra_view') {
    $cat_array_irregular = array('-1','-3');
    foreach ($cat_array_irregular as $cat_value) {
      $legend_label = $cat_value == -3 ? l('pages') : l('all');
      $page_only_xsql = $cat_value == -3 ? 'page_extra ASC,' : '';
      $sql = "SELECT id, title, seftitle, date, published, artorder, visible, default_page, page_extra FROM "._PRE."articles WHERE category = ".$cat_value." AND position = ".$p." ".$filterquery." ORDER BY ".$page_only_xsql." artorder DESC, date DESC ";
      $query = mysqli_query($dblink,$sql) or die(mysqli_error());
      $num_rows = mysqli_num_rows($query);
      echo '<div class="innerpanel">';
      echo '<p class="admintitle">'.$legend_label.'</p>';
      if ($num_rows == 0) {
        echo $no_content;
      } else {
        $lbl_filter = -5;
        while ($r = mysqli_fetch_array($query)) {
          if ($cat_value == -3) {
            if ($lbl_filter != $r['page_extra']) {
              $assigned_page = retrieve('title','articles','id',$r['page_extra']);
              echo !$assigned_page ? l('all_pages') : $assigned_page;
            }
          }
          $order_input = '<input type="text" name="page_'.$r['id'].'" value="'.$r['artorder'].'" size="1" tabindex="'.tabindex().'" /> &nbsp;';
          echo '<p>'.$order_input.'<strong title="'.date(s('date_format'), strtotime($r['date'])).'">  '.ucfirst($r['title']).'</strong> ';
          if ($r['default_page'] != 'YES'){
            echo  l('divider').' <a href="'._SITE.'index.php?action=admin_article&amp;id='.$r['id'].'" tabindex="'.tabindex().'">'.l('edit').'</a> ';
          }
          $visiblity = $r['visible'] == 'YES' ? '<a href="'._SITE.'index.php?action=process&amp;task=hide&amp;item='.$item.'&amp;id='.$r['id'].'" tabindex="'.tabindex().'">'.l('hide').'</a>' : l('hidden').' ( <a href="'._SITE.'index.php?action=process&amp;task=show&amp;item='.$item.'&amp;id='.$r['id'].'">'.l('show').'</a> )' ;
          echo ' '.l('divider').' '.$visiblity;
          if ($r['published'] == 2) echo  l('divider').' ['.l('status').' '.l('future_posting').']';
          if ($r['published'] == 0) echo  l('divider').' ['.l('status').' '.l('unpublished').']';
          echo '</p>';
          $lbl_filter = $r['page_extra'];
        }
      }
      echo '</div>';
    }
  }
  if ($contents == 'article_view' || $contents == 'extra_view') {
    $item = $contents == 'extra_view' ? 'extra_contents': 'scs-articles';
    $cat_query = "SELECT id, name, seftitle FROM "._PRE.'categories'." WHERE subcat = 0 ORDER BY catorder ASC";
    $cat_res = mysqli_query($dblink,$cat_query);
    $num = mysqli_num_rows($cat_res);
    echo "    " . html_input('fieldset','','','','','','','','','','','','','',ucfirst(l('articles')),'');
    echo "      <div class=\"scs-sub\">\n";

    if (!$cat_res || !$num) {
      echo "        <p><span class=\"small\">".ucfirst(l('no_categories'))."</span></p>\n";
    } else {
      $sql = "SELECT id, title, seftitle, date, published, artorder, visible, default_page FROM "._PRE.'articles'." WHERE category = '0' AND position = $p $subquery ORDER BY artorder DESC, date DESC ";
      $query = mysqli_query($dblink,$sql) or die(mysqli_error());
      $num_rows = mysqli_num_rows($query);
      if ($num_rows > 0) {
        echo '<div class="innerpanel">'."\n";
        echo '<h3>'.ucfirst(l('no_category_set')).'</h3>'."\n";
        while ($O = mysqli_fetch_array($query)) {
          $order_input = '<input type="text" name="page_'.$O['id'].'" value="'.$O['artorder'].'" size="1" tabindex="'.tabindex().'" /> &#160;'."\n";
          echo '<p>'.$order_input.'<strong title="'.date(s('date_format'), strtotime($O['date'])).'">'.ucfirst($O['title']).'</strong> '."\n";
          if ($O['visible'] == 'YES') {
            echo '<a href="'._SITE.'index.php?action=process&amp;task=hide&amp;item='.$item.'&amp;id='.$O['id'].'" tabindex="'.tabindex().'">'.ucfirst(l('hide')).'</a>'."\n";
          } else {
            echo ucfirst(l('hidden')).' ( <a href="'._SITE.'index.php?action=process&amp;task=show&amp;item='.$item.'&amp;id='.$O['id'].'" tabindex="'.tabindex().'">'.ucfirst(l('show')).'</a> )'."\n";
          }
          if ($O['published'] == 2) echo ucfirst(l('divider')).' ['.ucfirst(l('status')).' '.ucfirst(l('future_post')).']';
          if ($O['published'] == 0) echo ucfirst(l('divider')).' ['.ucfirst(l('status')).' '.ucfirst(l('unpublished')).']';
          echo "</p>\n";
        }
        echo "</div>\n";
      }
      echo "<div class=\"scs-sub\">\n";
      while ($row = mysqli_fetch_array($cat_res)) {
        echo "<p class=\"cat\" onclick=\"flip('cat".$row['id']."','',true)\" style=\"cursor:pointer\">\n";
        echo "<strong class=\"lighten\">".ucfirst($row['name'])."</strong>\n";
        $sql = "SELECT id, title, seftitle, date, published, artorder, visible, default_page FROM "._PRE.'articles'." WHERE category = '".$row['id']."' AND position = $p $subquery $filterquery ORDER BY artorder DESC, date DESC";
        $query = mysqli_query($dblink,$sql);
        echo "\n</p>\n";
        echo "<div id=\"cat".$row['id']."\" style=\"display:none\" class=\"subcat\">\n";
        while ($r = mysqli_fetch_array($query)) {
          if ($r['visible'] == 'NO' || $r['published'] != 1) {
            echo "<p class=\"art sub unpublished\">\n";
          } else {
            echo "<p class=\"art sub\">\n";
          }
          echo "<input type=\"text\" name=\"page_".$r['id']."\" value=\"".$r['artorder']."\" size=\"1\" tabindex=\"".tabindex()."\" title=\"".ucfirst(l('reorder'))."\" />\n";
          echo "<span class=\"inlinetoolbar\">\n";
          echo "<a href=\""._SITE.$row['seftitle']."/".$r['seftitle']."/\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/art_view.png\" alt=\"view\" title=\"".ucfirst(l('view_art'))."\" /></a>\n";
          echo "<a href=\""._SITE."index.php?action=admin_article&amp;id=".$r['id']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/art_edit.png\" alt=\"edit\" title=\"".ucfirst(l('edit_art'))."\" /></a>\n";
          if ($r['visible'] == 'YES') {
            echo "<a href=\""._SITE."index.php?action=process&amp;task=hide&amp;item=".$goto."&amp;id=".$r['id']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/show.png\" alt=\"visible\" title=\"".ucfirst(l('hide'))."\" /></a>\n";
          } else {
            echo "<a href=\""._SITE."index.php?action=process&amp;task=show&amp;item=".$goto."&amp;id=".$r['id']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/hide.png\" alt=\"hidden\" title=\"".ucfirst(l('show'))."\" /></a>\n";
          }
          echo "</span>\n";
          echo "<span class=\"title\" title=\"Publicatie op ".strftime(_DATEFORM, strtotime($r['date']))."\"><a href=\""._SITE."index.php?action=admin_article&amp;id=".$r['id']."\" tabindex=\"".tabindex()."\">".ucfirst($r['title'])."</a></span>";
          if ($r['published'] == 2) echo "<span class=\"small\">(".ucfirst(l('future_post')).")</span>";
          if ($r['published'] == 0) echo "<span class=\"small\">(".ucfirst(l('unpublished')).")</span>";
          echo "\n</p>\n";
        }
        $query2 = mysqli_query($dblink,"SELECT id, name, seftitle, published FROM "._PRE.'categories'." WHERE subcat = '$row[id]' ORDER BY catorder ASC");
        while ($row2 = mysqli_fetch_array($query2)){
          $catart_sql2 = "SELECT id, title, seftitle, date, published, artorder, visible FROM "._PRE.'articles'." WHERE category = '$row2[id]' $subquery $filterquery ORDER BY category ASC, artorder DESC, date DESC";
          $catart_query2 = mysqli_query($dblink,$catart_sql2);
          $num_rows2 = mysqli_num_rows($catart_query2);
          $art_subcat_class = $row2['published']=="NO"? "cat sub unpublished" : "cat sub";
          echo "<p class=\"".$art_subcat_class."\" onclick=\"flip('subcat".$row2['id']."',1,true)\" style=\"cursor:pointer\"><span class=\"subcat\">\n";
          echo "<strong class=\"lighten\">".ucfirst($row2['name'])."</strong>\n";
          if ($num_rows2 == 0) echo " <span class=\"small\">(".$no_content.")</span>\n";
          elseif ($row2['published']=="NO") echo " <span class=\"small\">(".ucfirst(l('subcat_unpublished')).")</span>\n";
          echo "\n</span></p>\n";
          echo "<div id=\"subcat".$row2['id']."\" style=\"display:none\" class=\"subcat\">\n";
          while ($ca_r2 = mysqli_fetch_array($catart_query2)) {
            $catSEF = cat_rel($row2['id'],'seftitle');
            if($catSEF != "") $catSEF .= "/";
            if ($ca_r2['visible'] == 'NO' || $ca_r2['published'] != 1) {
              echo "<p class=\"art sub unpublished\">\n";
            } else {
              echo "<p class=\"art sub\">\n";
            }
            echo "<input type=\"text\" name=\"page_".$ca_r2['id']."\" value=\"".$ca_r2['artorder']."\" size=\"1\" tabindex=\"".tabindex()."\" />\n";
            echo "<span class=\"inlinetoolbar\">\n";
            echo "<a href=\""._SITE.$catSEF.$ca_r2['seftitle']."/\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/art_view.png\" alt=\"view\" title=\"".ucfirst(l('view_art'))."\" /></a>\n";
            echo "<a href=\""._SITE."index.php?action=admin_article&amp;id=".$ca_r2['id']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/art_edit.png\" alt=\"edit\" title=\"".ucfirst(l('edit_art'))."\" /></a>\n";
            if ($ca_r2['visible'] == 'YES') {
              echo "<a href=\""._SITE."index.php?action=process&amp;task=hide&amp;item=".$goto."&amp;id=".$ca_r2['id']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/show.png\" alt=\"visible\" title=\"".ucfirst(l('hide'))."\" /></a>\n";
            } else {
              echo "<a href=\""._SITE."index.php?action=process&amp;task=show&amp;item=".$goto."&amp;id=".$ca_r2['id']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/hide.png\" alt=\"hidden\" title=\"".ucfirst(l('show'))."\" /></a>\n";
            }
            echo "</span>\n";
            echo "<span class=\"title\" title=\"Publicatie op ".strftime(_DATEFORM, strtotime($ca_r2['date']))."\"><a href=\""._SITE."index.php?action=admin_article&amp;id=".$ca_r2['id']."\" tabindex=\"".tabindex()."\">".ucfirst($ca_r2['title'])."</a></span>\n";
            if ($ca_r2['published'] == 2) echo "<span class=\"small\">(".ucfirst(l('future_post')).")</span>\n";
            if ($ca_r2['published'] == 0) echo "<span class=\"small\">(".ucfirst(l('unpublished')).")</span>\n";
            echo "</p>\n";
          }
          echo "</div>\n";
        }
        echo "</div>\n";
      }
      echo "</div>\n";
    }
    echo "      </div>\n    </fieldset>\n";
  } elseif ($contents == 'page_view') {
    $sql = "SELECT id, title, seftitle, date, published, artorder, visible, default_page FROM "._PRE.'articles'." WHERE position = 3 ORDER BY artorder ASC, date DESC ";
    $query = mysqli_query($dblink,$sql) or die(mysqli_error());
    $num_rows = mysqli_num_rows($query);
    echo "    <fieldset class=\"\">\n";
    echo "<h2>".ucfirst(l('pages'))."</h2>\n";
    if ($num_rows == 0) {
      echo '<p>'.l('article_not_exist').'</p>';
    }
    while ($r = mysqli_fetch_array($query)) {
      if($r['id'] == s('display_page')) {
        $homepage = $r;
      } else if(!is_numeric($r['default_page'])) {
        $defaultpages[$r['id']] = $r;
      } else {
        $subpages[$r['id']] = $r;
      }
    }
    if(!empty($homepage)) {
      if(!empty($defaultpages)) {
        unset($defaultpages[$homepage['id']]);
        array_unshift( $defaultpages, $homepage );
      } else {
        $defaultpages[$homepage['id']]=$homepage;
      }
    }
    if(!empty($defaultpages)) {
      foreach($defaultpages as $defpagearray) {
        $catSEF = cat_rel($defpagearray['id'],'seftitle');
        if($catSEF != "") $catSEF .= "/";
        if ($defpagearray['visible'] == 'NO' || $defpagearray['published'] != 1) {
          echo "<p class=\"cat unpublished\">\n";
        } else {
          echo "<p class=\"cat\">\n";
        }
        echo "<input type=\"text\" name=\"page_".$defpagearray['id']."\" value=\"".$defpagearray['artorder']."\" size=\"1\" tabindex=\"".tabindex()."\" />\n";
        echo "<span class=\"inlinetoolbar\">\n";
        echo "<a href=\""._SITE.$catSEF.$defpagearray['seftitle']."/\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/art_view.png\" alt=\"view\" title=\"".ucfirst(l('view_art'))."\" /></a>\n";
        echo "<a href=\""._SITE."index.php?action=admin_article&amp;id=".$defpagearray['id']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/art_edit.png\" alt=\"edit\" title=\"".ucfirst(l('edit_art'))."\" /></a>\n";
        if ($defpagearray['visible'] == 'YES') {
          echo "<a href=\""._SITE."index.php?action=process&amp;task=hide&amp;item=scs-pages&amp;id=".$defpagearray['id']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/show.png\" alt=\"visible\" title=\"".ucfirst(l('hide'))."\" /></a>\n";
        } else {
          echo "<a href=\""._SITE."index.php?action=process&amp;task=show&amp;item=scs-pages&amp;id=".$defpagearray['id']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/hide.png\" alt=\"hidden\" title=\"".ucfirst(l('show'))."\" /></a>\n";
        }
        echo "</span>\n";
        echo "<span class=\"title\" title=\"Publicatie op ".strftime(_DATEFORM, strtotime($defpagearray['date']))."\"><a href=\""._SITE."index.php?action=admin_article&amp;id=".$defpagearray['id']."\" tabindex=\"".tabindex()."\">".ucfirst($defpagearray['title'])."</a></span>\n";
        if ($defpagearray['published'] == 2) echo "<span class=\"small\">(".ucfirst(l('future_post')).")</span>\n";
        if ($defpagearray['published'] == 0) echo "<span class=\"small\">(".ucfirst(l('unpublished')).")</span>\n";
        echo "</p>\n";
        if(!empty($subpages)) {
          foreach($subpages as $subpagearray) {
            if($subpagearray['default_page'] == $defpagearray['id']) {
          
              $catSEF = cat_rel($subpagearray['id'],'seftitle');
              if($catSEF != "") $catSEF .= "/";
              if ($subpagearray['visible'] == 'NO' || $subpagearray['published'] != 1) {
                echo "<p class=\"cat sub unpublished\">\n";
              } else {
                echo "<p class=\"cat sub\">\n";
              }
              echo "<input type=\"text\" name=\"page_".$subpagearray['id']."\" value=\"".$subpagearray['artorder']."\" size=\"1\" tabindex=\"".tabindex()."\" />\n";
              echo "<span class=\"inlinetoolbar\">\n";
              echo "<a href=\""._SITE.$catSEF.$subpagearray['seftitle']."/\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/art_view.png\" alt=\"view\" title=\"".ucfirst(l('view_art'))."\" /></a>\n";
              echo "<a href=\""._SITE."index.php?action=admin_article&amp;id=".$subpagearray['id']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/art_edit.png\" alt=\"edit\" title=\"".ucfirst(l('edit_art'))."\" /></a>\n";
              if ($subpagearray['visible'] == 'YES') {
                echo "<a href=\""._SITE."index.php?action=process&amp;task=hide&amp;item=scs-pages&amp;id=".$subpagearray['id']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/show.png\" alt=\"visible\" title=\"".ucfirst(l('hide'))."\" /></a>\n";
              } else {
                echo "<a href=\""._SITE."index.php?action=process&amp;task=show&amp;item=scs-pages&amp;id=".$subpagearray['id']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/hide.png\" alt=\"hidden\" title=\"".ucfirst(l('show'))."\" /></a>\n";
              }
              echo "</span>\n";
        
              echo "<span class=\"title\" title=\"Publicatie op ".strftime(_DATEFORM, strtotime($subpagearray['date']))."\"><a href=\""._SITE."index.php?action=admin_article&amp;id=".$subpagearray['id']."\" tabindex=\"".tabindex()."\">".ucfirst($subpagearray['title'])."</a></span>\n";
              if ($subpagearray['published'] == 2) echo "<span class=\"small\">(".ucfirst(l('future_post')).")</span>\n";
              if ($subpagearray['published'] == 0) echo "<span class=\"small\">(".ucfirst(l('unpublished')).")</span>\n";
              echo "</p>\n";
            }
          }
        }
      }
    }
    echo "      \n    </fieldset>\n";
  } elseif ($contents == 'agenda_view') {
    $sql = "SELECT id, title, seftitle, date, published, visible, default_page FROM "._PRE."articles WHERE position = 4 ORDER BY date DESC ";
    $query = mysqli_query($dblink,$sql);
    $num_rows = mysqli_num_rows($query);
    $month_names = explode(',', ucfirst(l('month_names')));
    $day_names = explode(',', ucfirst(l('day_names')));
    $day_names_short = explode(',', ucfirst(l('day_names_short')));
    echo "    <fieldset class=\"agenda\">\n";
    echo "<h2>".ucfirst(l('agenda'))."</h2>\n";
    if ($num_rows == 0) {
      echo '<p>'.l('article_not_exist').'</p>';
    } else {
      $jaar = 0;
      $maand = "";
      while ($r = mysqli_fetch_array($query)) {
        $dedatum = strtotime($r['date']);
        if ( strftime('%Y', $dedatum) < $jaar || $jaar == 0) {
          $jaar = strftime('%Y', $dedatum);
          echo "<h3 class=\"floatright\">".$jaar."</h3>\n";
          $maand = "";
        }
        if ( strftime('%m', $dedatum) < $maand || $maand == "" ) {
          $maand = strftime('%m', $dedatum);
          echo "<h4 style=\"clear:left\">".ucfirst($month_names[$maand-1])."</h4>\n";
        }
        $catSEF = cat_rel($r['id'],'seftitle');
        if($catSEF != "") $catSEF .= "/";
        if ($r['visible'] == 'NO' || $r['published'] != 2) {
          echo "<p class=\"cat unpublished\">\n";
        } else {
          echo "<p class=\"cat\">\n";
        }
        $dag = strftime('%d', $dedatum);
        $dayname = strftime('%w', $dedatum);
        echo "<span class=\"dagvdmaand\">".strftime('%e', $dedatum)."</span><span class=\"dagvdweek\">".$day_names_short[strftime('%w', $dedatum)]."</span>\n";
        echo "<span class=\"inlinetoolbar\">\n";
        echo "  <a href=\""._SITE."agenda/".$jaar."/".$month_names[$maand-1]."/".$dag."/".$r['seftitle']."/\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/art_view.png\" alt=\"view\" title=\"".ucfirst(l('view_art'))."\" /></a>\n";
        echo "  <a href=\""._SITE."index.php?action=admin_article&amp;id=".$r['id']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/art_edit.png\" alt=\"edit\" title=\"".ucfirst(l('edit_art'))."\" /></a>\n";
        if ($r['visible'] == 'YES') {
          echo "  <a href=\""._SITE."index.php?action=process&amp;task=hide&amp;item=scs-events&amp;id=".$r['id']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/show.png\" alt=\"visible\" title=\"".ucfirst(l('hide'))."\" /></a>\n";
        } else {
          echo "  <a href=\""._SITE."index.php?action=process&amp;task=show&amp;item=scs-events&amp;id=".$r['id']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/hide.png\" alt=\"hidden\" title=\"".ucfirst(l('show'))."\" /></a>\n";
        }
        echo "</span>\n";
  
        echo "<span class=\"title\" title=\"Publicatie op ".strftime(_DATEFORM, strtotime($r['date']))."\"><a href=\""._SITE."index.php?action=admin_article&amp;id=".$r['id']."\" tabindex=\"".tabindex()."\">".ucfirst($r['title'])."</a></span>\n";
        if ($r['visible'] == 'NO') echo "<span class=\"small\">(".ucfirst(l('hidden')).")</span>\n";
        if ($r['published'] == 0) echo "<span class=\"small\">(".ucfirst(l('unpublished')).")</span>\n";
        echo "</p>\n";
      }
    }
    echo "      \n    </fieldset>\n";
  }
  echo "  </div>\n</form>\n</div>\n";
}
function buttons(){
  echo "<div class=\"clearer\"></div>";
  echo "<p>".ucfirst(l('formatting')).":<br class=\"clearer\" />";
  $formatting = array(
    'strong' => '',
    'em' => 'key',
    'underline' => 'key',
    'del' => 'key',
    'p' => '',
    'br' => ''
  );
  foreach ($formatting as $key => $var) {
    $css = $var == 'key' ? $key :'buttons';
    echo "<input type=\"button\" name=\"".$key."\" title=\"".l($key)."\" class=\"".$css."\" onclick=\"tag('".$key."')\" value=\"".l($key.'_value')."\" tabindex=\"".tabindex()."\" />";
  }
  echo "</p><br class=\"clearer\" /><p>".ucfirst(l('insert')).": <br class=\"clearer\" />";
  $insert = array('img', 'link', 'include', 'func','intro');
  foreach ($insert as $key) {
    echo "<input type=\"button\" name=\"".$key."\" title=\"".l($key)."\" class=\"buttons\" onclick=\"tag('".$key."')\" value=\"".l($key.'_value')."\" tabindex=\"".tabindex()."\" />";
  }
  echo "<br class=\"clearer\" /></p>";
}
function edit_comment() {
  $commentid = $_GET['commentid'];
  $dblink = connect_to_db();
  $query = mysqli_query($dblink,'SELECT id,articleid,name,url,comment,approved FROM '._PRE.'comments WHERE id='.$commentid);
  $r = mysqli_fetch_array($query);
  $articleTITLE = retrieve('title', 'articles', 'id', $r['articleid']);
  echo html_input('form', '', '', '', '', 'cms', '', '', '', '', '', '', 'post', 'index.php?action=process&amp;task=editcomment&amp;id='.$commentid, '');
  echo '<div>';
     echo '<h2>'.ucfirst(l('edit_comment')).' (<strong> '.$articleTITLE.'</strong> )</h2>';
  echo html_input('textarea', 'editedcomment', 'ec', stripslashes($r['comment']), ucfirst(l('comment')), '', '', '', '', '', '2', '100', '', '', '');
  echo html_input('text', 'name', 'n', $r['name'], ucfirst(l('name')), '', '', '', '', '', '', '', '', '', '');
  echo html_input('text', 'url', 'url', $r['url'], ucfirst(l('url')), '', '', '', '', '', '', '', '', '', '');
  echo html_input('checkbox', 'approved', 'a', '', ucfirst(l('approved')), '', '', '', '', $r['approved'] == 'True' ? 'ok' : '', '', '', '', '', '');
  echo '</div><p>';   echo html_input('hidden', 'id', 'id', $r['articleid'], '', '', '', '', '', '', '', '', '', '', '');
  echo html_input('submit', 'submit_text', 'submit_text', ucfirst(l('edit')), '', 'button', '', '', '', '', '', '', '', '', '')." ";
  echo html_input('hidden', 'commentid', 'commentid', $r['id'], '', '', '', '', '', '', '', '', '', '', '');
  echo html_input('submit', 'delete_text', 'delete_text', ucfirst(l('delete')), '',
    'button', 'onclick="javascript: return pop()"', '', '', '', '', '', '', '', '');
  echo '</p></form>';
}
function form_groupings() {
  $dblink = connect_to_db();
  if (s('enable_extras') == 'YES') {
    if (isset($_GET['id']) && is_numeric($_GET['id']) && !is_null($_GET['id'])) {
      $extraid = $_GET['id'];
      $query = mysqli_query($dblink,'SELECT id,name,seftitle,description FROM '._PRE.'extras WHERE id='.$extraid);
      $r = mysqli_fetch_array($query);
      $frm_action = _SITE.'index.php?action=process&amp;task=admin_groupings&amp;id='.$extraid;
      $frm_add_edit = ucfirst(l('edit'));
      $frm_name = $r['name'];
      $frm_sef_title = $r['seftitle'];
      $frm_description = $r['description'];
      $frm_task = 'edit_groupings';
      $frm_submit = ucfirst(l('edit_button'));     } else {
      $frm_action = _SITE.'index.php?action=process&amp;task=admin_groupings';
      $frm_add_edit = ucfirst(l('add_groupings'));
      $frm_name = $_POST['name'];
      $frm_sef_title = $_POST['name'] == '' ? cleanSEF($_POST['name']) : cleanSEF($_POST['seftitle']);
      $frm_description = '';
      $frm_task = 'add_groupings';
      $frm_submit = ucfirst(l('add_groupings'));
    }
    echo html_input('form', '', '', '', '', 'cms', '', '', '', '', '', '', 'post', $frm_action, '');
    echo '<div>';
         echo '<h2>'.$frm_add_edit.'</h2>';
    echo html_input('text', 'name', 't', $frm_name, ucfirst(l('name')), '',
      'onchange="genSEF(this,document.forms[\'post\'].seftitle)"',
      'onkeyup="genSEF(this,document.forms[\'post\'].seftitle)"', '', '', '', '', '', '', '');
    echo html_input('text', 'seftitle', 's', $frm_sef_title, ucfirst(l('extra_title')), '', '', '', '', '', '', '', '', '', '');
    echo html_input('text', 'description', 'desc', $frm_description, ucfirst(l('description')), '', '', '', '', '', '', '', '', '', '');
    echo '</div><p>';     echo html_input('hidden', 'task', 'task', 'admin_groupings', '', '', '', '', '', '', '', '', '', '', '');
    echo html_input('submit', $frm_task, $frm_task, $frm_submit, '', 'button', '', '', '', '', '', '', '', '', '');
    if (!empty($extraid)) {
      echo '&#160;&#160;';
      echo html_input('hidden', 'id', 'id', $extraid, '', '', '', '', '', '', '', '', '', '', '');
      if ($extraid != 1) {
        echo html_input('submit', 'delete_groupings', 'delete_groupings', ucfirst(l('delete')), '',
        'button', 'onclick="javascript: return pop()"', '', '', '', '', '', '', '', '');
      }
    }
    echo '</p></form>';
  }
}
function admin_groupings() {
  $dblink = connect_to_db();
  if (s('enable_extras') == 'YES') {
    if (stats('extras','') > 0) {
      $add = ' - <a href="admin_groupings/" title="'.ucfirst(l('add_new')).'" tabindex="'.tabindex().'">'.ucfirst(l('add_new')).'</a>';
    } else {
      $add = '';
    }
    echo '<div>';
    echo '<h2>'.ucfirst(l('groupings')).$add.'</h2>';   $result = mysqli_query($dblink,'SELECT id,name,description FROM '._PRE.'extras ORDER BY id ASC');
    if (!$result || !mysqli_num_rows($result)) {
      echo '<p>'.ucfirst(l('group_not_exist')).'</p>';
    } else {
      while ($r = mysqli_fetch_array($result)) {
        echo '<p><strong>'.$r['name'].'</strong> '.ucfirst(l('divider')).'<a href="'._SITE.'index.php?action=admin_groupings&amp;id='.$r['id'].'" title="'.$r['description'].'" tabindex="'.tabindex().'">'.ucfirst(l('edit')).'</a></p>';
      }
    }
    echo '</div>';
  }
}

function checkGET($key) {
  if (array_key_exists($key,$_GET)) return $_GET[$key];
}
function checkPOST($key) {
  if (array_key_exists($key,$_POST)) return $_POST[$key];
}

function checkSESSION($key,$subarray='') {
  if(empty($subarray)) {
    if (array_key_exists($key,$_SESSION)) return $_SESSION[$key];
  } else {
    if (array_key_exists($key,$_SESSION[$subarray])) return $_SESSION[$subarray][$key];
  }
}

function processing() {
  if (!_ADMIN) {
    echo (notification(1,ucfirst(l('error_not_logged_in')),'home'));
  } else {
    $dblink = connect_to_db();
    $action = clean(cleanXSS($_GET['action']));
    $task = clean(cleanXSS($_GET['task']));
    $id = clean(cleanXSS(checkGET('id')));
    $commentid = cleanXSS(checkPOST('commentid'));
    $approved = cleanXSS(checkPOST('approved')) == 'on' ? 'True' : '';
    $name = clean(cleanXSS(entity(checkPOST('name'))));
    $category = !empty(clean(cleanXSS(checkPOST('define_category')))) ? clean(cleanXSS(checkPOST('define_category'))) : 0;
    $subcat = clean(cleanXSS(entity(checkPOST('subcat'))));
    $page = clean(cleanXSS(checkPOST('define_page')));
    $def_extra = clean(cleanXSS(checkPOST('define_extra')));
    $description = clean(cleanXSS(entity(checkPOST('description'))));
    $title = clean(cleanXSS(entity(checkPOST('title'))));
    $seftitle = clean(cleanXSS(checkPOST('seftitle')));
    $url = clean(cleanXSS(checkPOST('url')));
    $comment = clean(cleanXSS(entity(checkPOST('editedcomment'))));
    $text = clean(cleanXSS(entity(checkPOST('text'))));
    $default_page = clean(cleanXSS(checkPOST('default_page')));
    $date = date('Y-m-d H:i:s');
    $description_meta = clean(cleanXSS(entity(checkPOST('description_meta'))));
    $keywords_meta = clean(cleanXSS(entity(checkPOST('keywords_meta'))));
    $show_author = clean(cleanXSS(entity(checkPOST('show_author'))));
    $author = clean(cleanXSS(entity(checkPOST('author'))));
    $display_title = clean(cleanXSS(checkPOST('display_title'))) == 'on' ? 'YES' : 'NO';
    $display_info = clean(cleanXSS(checkPOST('display_info'))) == 'on' ? 'YES' : 'NO';
    $commentable = clean(cleanXSS(checkPOST('commentable'))) == 'on' ? 'YES' : 'NO';
    $freez = clean(cleanXSS(checkPOST('freeze'))) == 'on' ? 'YES' : 'NO';
    if ($freez == 'YES' && $commentable == 'YES') {
      $commentable = 'FREEZ';
    }
    $position = clean(cleanXSS(checkPOST('position')));
    if($position == "" || $position <= 0) $position = 1;
    $publish_article = clean(cleanXSS(checkPOST('publish_article'))) == 'on' ? 1 : 0;
    $social = clean(cleanXSS(checkPOST('social_buttons'))) == 'on' ? 'YES' : 'NO';
    $show_in_subcats = clean(cleanXSS(checkPOST('show_in_subcats'))) == 'on' ? 'YES' : 'NO';
    $show_on_home = clean(cleanXSS(checkPOST('show_on_home'))) == 'on' ? 'YES' : 'NO';
    $publish_category = clean(cleanXSS(checkPOST('publish'))) == 'on' ? 'YES' : 'NO';
    $fpost_enabled = false;
    $fposting = clean(cleanXSS(checkPOST('fposting')));
    if ($fposting == 'on') {
      $fpost_enabled = true;
      $date = clean(cleanXSS(checkPOST('date')));
      $date_array = explode("-",$date);
      $date = $date_array[2].'-'.str_pad($date_array[1], 2, '0', STR_PAD_LEFT).'-'.str_pad($date_array[0], 2, '0', STR_PAD_LEFT).' 08:00:00';
      $nowdate = new DateTime('yesterday');
      if (date($nowdate->format('Y-m-d').' 08:00:00') <= $date) {
        $publish_article = 2;
      } else {
        $publish_article = 1;
      }
    } else if ($fposting != 'on' && $position == 1){
      $publish_article = 1;
    }
    switch ($task) {
      case  'save_settings':
        if ( null !== clean(cleanXSS(checkPOST('save'))) ) {
          $website_title = clean(cleanXSS(checkPOST('website_title')));
          $home_sef = clean(cleanXSS(checkPOST('home_sef')));
          $website_description = clean(cleanXSS(checkPOST('website_description')));
          $website_keywords = clean(cleanXSS(checkPOST('website_keywords')));
          $website_email = clean(cleanXSS(checkPOST('website_email')));
          $contact_subject = clean(cleanXSS(checkPOST('contact_subject')));
          $company_contact = clean(cleanXSS(checkPOST('company_contact')));
          $company_phone = clean(cleanXSS(checkPOST('company_phone')));
          $login_url = clean(cleanXSS(checkPOST('login_url')));
          $language = clean(cleanXSS(checkPOST('language')));
          $charset = clean(cleanXSS(checkPOST('charset')));
          $date_format = clean(cleanXSS(checkPOST('date_format')));
          $article_limit = clean(cleanXSS(checkPOST('article_limit')));
          $rss_limit = clean(cleanXSS(checkPOST('rss_limit')));
          $overview_pagename = clean(cleanXSS(checkPOST('overview_pagename')));
          $show_overview_in_menu = clean(cleanXSS(checkPOST('show_overview_in_menu')));
          $overview_menuname = clean(cleanXSS(checkPOST('overview_menuname')));
          $display_page = clean(cleanXSS(checkPOST('display_page')));
          $display_new_on_home = clean(cleanXSS(checkPOST('display_new_on_home')));
          $display_social_buttons = clean(cleanXSS(checkPOST('display_social_buttons')));
          $display_pagination = clean(cleanXSS(checkPOST('display_pagination')));
          $num_categories = clean(cleanXSS(checkPOST('num_categories')));
          $show_cat_names = clean(cleanXSS(checkPOST('show_cat_names')));
          $approve_comments = clean(cleanXSS(checkPOST('approve_comments')));
          $mail_on_comments = clean(cleanXSS(checkPOST('mail_on_comments')));
          $comments_order = clean(cleanXSS(checkPOST('comments_order')));
          $comment_limit = clean(cleanXSS(checkPOST('comment_limit')));
          $word_filter_enable = clean(cleanXSS(checkPOST('word_filter_enable')));
          $word_filter_file = clean(cleanXSS(checkPOST('word_filter_file')));
          $word_filter_change = clean(cleanXSS(checkPOST('word_filter_change')));
          $enable_extras = clean(cleanXSS(checkPOST('enable_extras'))) == 'on' ? 'YES' : 'NO';
          $enable_comments = clean(cleanXSS(checkPOST('enable_comments'))) == 'on' ? 'YES' : 'NO';
          $comment_repost_timer = clean(cleanXSS(checkPOST('comment_repost_timer')));
          $comment_repost_timer = is_numeric($comment_repost_timer) ? $comment_repost_timer : '15';
          $freeze_comments = clean(cleanXSS(checkPOST('freeze_comments'))) == 'on' ? 'YES' : 'NO';
          $facebook_admin = clean(cleanXSS(checkPOST('facebook_admin')));
          $show_author_on = clean(cleanXSS(checkPOST('show_author_on')));
          $show_title_on = clean(cleanXSS(checkPOST('show_title_on')));
          $show_info_on = clean(cleanXSS(checkPOST('show_info_on')));
          $show_agenda = clean(cleanXSS(checkPOST('show_agenda')));
          $previewlines = clean(cleanXSS(checkPOST('previewlines')));
          $ufield = array(
            'website_title' => $website_title,
            'home_sef' => $home_sef,
            'website_description' => $website_description,
            'website_keywords' => $website_keywords,
            'website_email' => $website_email,
            'contact_subject' => $contact_subject,
            'company_contact' => $company_contact,
            'company_phone' => $company_phone,
            'login_url' => $login_url,
            'language' => $language,
            'charset' => $charset,
            'date_format' => $date_format,
            'article_limit' => $article_limit,
            'rss_limit' => $rss_limit,
            'overview_pagename' => $overview_pagename,
            'show_overview_in_menu' => $show_overview_in_menu,
            'overview_menuname' => $overview_menuname,
            'display_page' => $display_page,
            'comments_order' => $comments_order,
            'comment_limit' => $comment_limit,
            'word_filter_file' => $word_filter_file,
            'word_filter_change' => $word_filter_change,
            'display_new_on_home' => $display_new_on_home,
            'display_social_buttons' => $display_social_buttons,
            'display_pagination' => $display_pagination,
            'num_categories' => $num_categories,
            'show_cat_names' => $show_cat_names,
            'approve_comments' => $approve_comments,
            'mail_on_comments' => $mail_on_comments,
            'word_filter_enable' => $word_filter_enable,
            'enable_extras' => $enable_extras,
            'enable_comments' => $enable_comments,
            'freeze_comments' => $freeze_comments,
            'comment_repost_timer' => $comment_repost_timer,
            'facebook_admin' => $facebook_admin,
            'show_author_on' => $show_author_on,
            'show_title_on' => $show_title_on,
            'show_info_on' => $show_info_on,
            'show_agenda' => $show_agenda,
            'previewlines' => $previewlines
          );
          while (list($key, $value) = each($ufield)) {
            mysqli_query($dblink,"UPDATE "._PRE.'settings'." SET VALUE = '$value' WHERE name = '$key' LIMIT 1");
          }
          echo notification(0,'','scs-settings');
        }
        break;
      case  'changeup':
        if (isset($_POST['submit_pass'])) {
          $user = checkUserPass($_POST['uname']);
          $pass1 = checkUserPass($_POST['pass1']);
          $pass2 = checkUserPass($_POST['pass2']);
          if ($user && $pass1 && $pass2 && $pass1 === $pass2) {
            $uname = md5($user);
            $pass = md5($pass2);
            $query = "UPDATE "._PRE.'settings'." SET VALUE=";
            mysqli_query($dblink,$query."'$uname' WHERE name='username' LIMIT 1");
            mysqli_query($dblink,$query."'$pass' WHERE name='password' LIMIT 1");
            echo notification(0,'','administration');
          } else {
            die(notification(2,ucfirst(l('pass_mismatch')),'scs-settings'));
          }
        }
        break;
      case  'admin_groupings':
        switch (true) {
          case  (empty($name)):
            echo notification(1,ucfirst(l('err_TitleEmpty')).ucfirst(l('errNote')));
            form_groupings();
            break;
          case  (empty($seftitle)):
            echo notification(1,ucfirst(l('err_SEFEmpty')).ucfirst(l('errNote')));
            form_groupings();
            break;
          case (check_if_unique('group_name', $name, $id, '', '')):
            echo notification(1,ucfirst(l('err_TitleExists')).ucfirst(l('errNote')));
            form_groupings();
            break;
          case (check_if_unique('group_seftitle', $seftitle, $id, '', '')):
            echo notification(1,ucfirst(l('err_SEFExists')).ucfirst(l('errNote')));
            form_groupings();
            break;
          case (cleancheckSEF($seftitle) == 'notok'):
            echo notification(1,ucfirst(l('err_SEFIllegal')).ucfirst(l('errNote')));
            form_groupings();
            break;
          default:
              switch (true) {
              case  (isset($_POST['add_groupings'])):
                mysqli_query($dblink,"INSERT INTO "._PRE.'extras'."(name, seftitle, description)
                  VALUES('$name', '$seftitle', '$description')");
                break;
              case  (isset($_POST['edit_groupings'])):
                mysqli_query($dblink,"UPDATE "._PRE.'extras'." SET
                  name = '$name',
                  seftitle = '$seftitle',
                  description = '$description'
                  WHERE id = '$id' LIMIT 1");
                break;
              case  (isset($_POST['delete_groupings'])):
                mysqli_query($dblink,"DELETE FROM "._PRE.'extras'." WHERE id = '$id' LIMIT 1");
                break;
              }
          echo notification(0,'','groupings');
        }
        break;
      case  'scs-category':
      case  'admin_subcategory':
        switch (true) {
          case  (empty($name)):
            echo notification(1,ucfirst(l('err_TitleEmpty')).l('errNote'));
            unset($_SESSION[_SITE.'temp']);
            form_categories();
            break;
          case  (empty($seftitle)):
            echo notification(1,ucfirst(l('err_SEFEmpty')).l('errNote'));
            unset($_SESSION[_SITE.'temp']);
            form_categories();
            break;
          case  (isset($_POST['add_category']) && check_if_unique('subcat_name', $name, '', $subcat, '')):
            echo notification(1,ucfirst(l('err_TitleExists')).l('errNote'));
            unset($_SESSION[_SITE.'temp']);
            form_categories();
            break;
          case  (isset($_POST['add_category']) && check_if_unique('subcat_seftitle', $seftitle, '', $subcat, '')):
            echo notification(1,ucfirst(l('err_SEFExists')).l('errNote'));
            unset($_SESSION[_SITE.'temp']);
            form_categories();
            break;
          case  (isset($_POST['edit_category']) && $subcat == 0 && check_if_unique('cat_name_edit', $name, $id, '', '')):
            echo notification(1,ucfirst(l('err_TitleExists')).l('errNote'));
            unset($_SESSION[_SITE.'temp']);
            form_categories();
            break;
          case  (isset($_POST['edit_category']) && $subcat == 0 && check_if_unique('cat_seftitle_edit', $seftitle, $id, '', '')):
            echo notification(1,ucfirst(l('err_SEFExists')).l('errNote'));
            unset($_SESSION[_SITE.'temp']);
            form_categories();
            break;
          case  (isset($_POST['edit_category']) && $subcat != 0 && check_if_unique('subcat_name_edit', $name, $id, $subcat, '')):
            echo notification(1,ucfirst(l('err_TitleExists')).l('errNote'));
            unset($_SESSION[_SITE.'temp']);
            form_categories();
            break;
          case  (isset($_POST['edit_category']) && $subcat != 0 && check_if_unique('subcat_seftitle_edit', $seftitle, $id, $subcat, '')):
            echo notification(1,ucfirst(l('err_SEFExists')).l('errNote'));
            unset($_SESSION[_SITE.'temp']);
            form_categories();
            break;
          case  (cleancheckSEF($seftitle) == 'notok'):
            echo notification(1,ucfirst(l('err_SEFIllegal')).l('errNote'));
            unset($_SESSION[_SITE.'temp']);
            form_categories();
            break;
          case  ($subcat==$id):
            echo notification(1,ucfirst(l('errNote')));
            unset($_SESSION[_SITE.'temp']);
            form_categories();
            break;
          default:
            $sub = !empty($subcat) ? ' WHERE subcat = '.$subcat : ' WHERE subcat = 0';
            $curr_catorder = retrieve('catorder','categories','id',$id);
            $orderquery = mysqli_query($dblink,'SELECT COALESCE(MAX(catorder),0) FROM '._PRE.'categories'.$sub);
            $ordernr = mysqli_fetch_array($orderquery);
            $catorder = $curr_catorder=="" ? $ordernr[0] + 1 : $curr_catorder;
            $_SESSION[_SITE.'temp']['description'] = preg_replace('/(?:\s\s+|\n|\t)/', ' ', $_POST['description']);
            $_SESSION[_SITE.'temp']['description'] = str_replace('<p></p>','',$_SESSION[_SITE.'temp']['description']);
            $_SESSION[_SITE.'temp']['description'] = str_replace(array(">","</","\r\n "),array(">\r\n","\r\n</","\r\n"),$_SESSION[_SITE.'temp']['description']);
            $description = mysqli_real_escape_string($dblink,$_SESSION[_SITE.'temp']['description']);
            $description = entity($description);
            switch(true) {
              case (isset($_POST['add_category'])):
                $opslaancheck = mysqli_fetch_array(mysqli_query($dblink,"SELECT name FROM "._PRE."categories ORDER BY id DESC LIMIT 1"));
                if ($opslaancheck[0] != $name) {
                  mysqli_query($dblink,"INSERT INTO "._PRE."categories(name, seftitle, description, published, catorder, subcat) VALUES('$name', '$seftitle', '$description', '$publish_category', '$catorder','$subcat')") or die();
                }
                break;
              case (isset($_POST['edit_category'])):
                if(isset($_POST['catorder'])) {
                  $catorder = $_POST['catorder'];
                } else {
                  $catorder = $catorder;
                }
                mysqli_query($dblink,"UPDATE "._PRE.'categories'." SET
                  name = '$name',
                  seftitle = '$seftitle',
                  description = '$description',
                  published = '$publish_category',
                  subcat='$subcat',
                  catorder='$catorder'
                  WHERE id = '$id' LIMIT 1");
                break;
              case  (isset($_POST['delete_category'])):
                $any_subcats = retrieve('COUNT(id)','categories','subcat',$id);
                $any_articles = retrieve('COUNT(id)','articles','category',$id);
                if ($any_subcats > 0 || $any_articles > 0) {
                  echo "<div id=\"errorpopup\" class=\"center\">\n";
                  echo notification(1,ucfirst(l('warn_catnotempty')),'');
                  echo "  <p>\n";
                  echo "    <a class=\"btn\" href=\""._SITE."administration/\" title=\"".ucfirst(l('administration'))."\" tabindex=\"".tabindex()."\">".ucfirst(l('administration'))."</a> ".l('or');
                  echo " <a class=\"btn\" href=\""._SITE."index.php?action=process&amp;task=delete_category_all&amp;id=".$id."\" onclick=\"javascript: return pop('x')\" title=\"".ucfirst(l('administration'))."\">".ucfirst(l('empty_cat'))."</a>\n";
                  echo "  </p>\n</div>\n";
                  $no_success = true;
                } else { delete_cat($id); }
                break;
            }
            unset($_SESSION[_SITE.'temp']);
            $success = isset($no_success) ? '' : notification(0,'','index.php?action=scs-category&id='.$id);
            echo $success;
        }
        break;
      case  'reorder':
        if (isset($_POST['reorder'])) {
          switch ($_POST['order']){
            case  'scs-articles':
            case  'extra_contents':
            case  'scs-pages':
              $table = 'articles';
              $order_type = 'artorder';
              $remove = 'page_';
              break;
            case  'scs-categories':
              $table = 'categories';
              $order_type = 'catorder';
              $remove = 'cat_';
              break;
          }
          foreach ($_POST as $key => $value){
            $type_id = str_replace($remove,'',$key);
            $value = clean(cleanXSS(trim($value)));             $key = clean(cleanXSS(trim($value)));
            if ($key != 'reorder' && $key != 'order' && $key != $table && $key != ucfirst(l('order_content')) && $key != $_POST['order']){
              $query = "UPDATE "._PRE.$table." SET $order_type = '$value' WHERE id = '$type_id' LIMIT 1;";
              mysqli_query($dblink,$query) or die(mysqli_error());
            }
          }
          echo notification(0,ucfirst(l('reordered').'.'),$_POST['order']);
        }
        break;
      case  'admin_article':
        $_SESSION[_SITE.'temp']['title'] = cleanXSS($_POST['title']);
        $_SESSION[_SITE.'temp']['seftitle'] = cleanXSS($_POST['seftitle']);
        $_SESSION[_SITE.'temp']['show_author'] = $position==1?cleanXSS(checkPOST('show_author')):'NO';
        $_SESSION[_SITE.'temp']['text'] = preg_replace('/(?:\s\s+|\n|\t)/', ' ', $_POST['text']);
        $_SESSION[_SITE.'temp']['text'] = str_replace('<p></p>','',$_SESSION[_SITE.'temp']['text']);
        $_SESSION[_SITE.'temp']['text'] = str_replace(array(">","</","\r\n "),array(">\r\n","\r\n</","\r\n"),$_SESSION[_SITE.'temp']['text']);
        switch($position) {
          case  1:
            $back_to_new = "scs-article";
            $back_to_overview = "scs-articles";
            break;
          case  2:
            $back_to_new = "extra_new";
            $back_to_overview = "scs-extras";
            break;
          case  3:
            $back_to_new = "scs-page";
            $back_to_overview = "scs-pages";
            break;
          case  4:
            $back_to_new = "scs-event";
            $back_to_overview = "scs-events";
            break;
        }
        switch (true) {
          case  (empty($title)):
            echo notification(1,ucfirst(l('err_TitleEmpty')).l('errNote'));
            form_articles($back_to_new);
            unset($_SESSION[_SITE.'temp']);
            break;
          case  (empty($seftitle)):
            echo notification(1,ucfirst(l('err_SEFEmpty')).l('errNote'));
            $_SESSION[_SITE.'temp']['seftitle'] = $_SESSION[_SITE.'temp']['title'];
            form_articles($back_to_new);
            unset($_SESSION[_SITE.'temp']);
            break;
          case  (cleancheckSEF($seftitle) == 'notok'):
            echo notification(1,ucfirst(l('err_SEFIllegal')).l('errNote'));
            form_articles($back_to_new);
            unset($_SESSION[_SITE.'temp']);
            break;
          case  ($position == 1 && $_POST['article_category'] != $category && isset($_POST['edit_article'])
              && check_if_unique('article_title', $title, $category, '', '')):
            echo notification(1,ucfirst(l('err_TitleExists')).l('errNote'));
            form_articles($back_to_new);
            unset($_SESSION[_SITE.'temp']);
            break;
          case  ($position == 1 && $_POST['article_category'] != $category && isset($_POST['edit_article'])
              && check_if_unique('article_seftitle', $seftitle, $category, '', '')):
            echo notification(1,ucfirst(l('err_SEFExists')).l('errNote'));
            form_articles($back_to_new);
            unset($_SESSION[_SITE.'temp']);
            break;
          case  ($position != 4 && !isset($_POST['delete_article'])/* && !isset($_POST['edit_article'])*/ && check_if_unique('article_title', $title, $category, '', checkPOST('id')) && !empty($_POST['id']) ):
            echo notification(1,ucfirst(l('err_TitleExists')).l('errNote'));
            form_articles($back_to_new);
            unset($_SESSION[_SITE.'temp']);
            break;
          case  ($position != 4 && !isset($_POST['delete_article']) && !isset($_POST['edit_article']) && check_if_unique('article_seftitle', $seftitle, $category, '', '')):
            echo notification(1,ucfirst(l('err_SEFExists')).l('errNote'));
            form_articles($back_to_new);
            unset($_SESSION[_SITE.'temp']);
            break;
          default:
            $sub = !empty($category) ? ' AND category = '.$category : '';
            $curr_artorder = retrieve('artorder','articles','id',$id);
            $orderquery = mysqli_query($dblink,'SELECT COALESCE(MAX(artorder),0) FROM '._PRE.'articles WHERE position='.$position.$sub);
            $ordernr = mysqli_fetch_array($orderquery);
            $text = mysqli_real_escape_string($dblink,$_SESSION[_SITE.'temp']['text']);
            $text = entity($text);
            $artorder = $curr_artorder=="" ? $ordernr[0] + 1 : $curr_artorder;
            $link = 'index.php?action=admin_article&id='.cleanXSS($_POST['id']);
            switch (true) {
              case  (isset($_POST['add_article'])):
                mysqli_query($dblink,"INSERT INTO "._PRE."articles(title,seftitle,text,date,category,position,extraid,page_extra,displaytitle,displayinfo,commentable,published,description_meta,keywords_meta,show_author,author,show_on_home,socialbuttons,show_in_subcats,artorder,default_page) VALUES('$title','$seftitle','$text', '$date', '$category','$position', '$def_extra', '$page', '$display_title','$display_info', '$commentable', '$publish_article', '$description_meta', '$keywords_meta', '$show_author', '$author', '$show_on_home', '$social', '$show_in_subcats', '$artorder', '$default_page')");
                break;
              case  (isset($_POST['edit_article'])):
                $category = $position == 3 ? 0 : $category;
                $old_pos = retrieve('position','articles','id',$id);
                $deze_checken = mysqli_fetch_array(mysqli_query($dblink,"SELECT title,text FROM "._PRE."articles WHERE id='$id'"));
                $modified = ( $deze_checken[0] != $title || $deze_checken[1] != $text ) ? ", mod_date = NOW()" : "";
                $future = $fpost_enabled == true ? $future = "date = '".$date."'," : "";
                mysqli_query($dblink,"UPDATE "._PRE."articles SET title='$title',seftitle='$seftitle',text='$text',$future category='$category',position='$position',extraid='$def_extra',page_extra='$page',displaytitle='$display_title',displayinfo='$display_info',commentable='$commentable',published='$publish_article',description_meta='$description_meta',keywords_meta='$keywords_meta',show_author='$show_author',author='$author',show_on_home='$show_on_home',socialbuttons='$social',show_in_subcats='$show_in_subcats',artorder='$artorder',default_page='$default_page'".$modified." WHERE id='$id' LIMIT 1") or die(mysqli_error());
                break;
              case (isset($_POST['delete_article'])):
                if ($position == 3) {
                  $chk_extra_query = "SELECT id FROM "._PRE.'articles'."
                    WHERE position = 2 AND category = -3 AND  page_extra = $id";
                  $chk_extra_sql = mysqli_query($dblink,$chk_extra_query);
                  if ($chk_extra_sql) {
                    while ($xtra = mysqli_fetch_array($chk_extra_sql)) {
                      $xtra_id = $xtra['id'];
                      mysqli_query($dblink,"UPDATE "._PRE.'articles'." SET category = '0',page_extra = ''  WHERE id = '$xtra_id'");
                    }
                  }
                }
                $link = $back_to_overview;
                mysqli_query($dblink,"DELETE FROM "._PRE.'articles'." WHERE id = '$id'");
                mysqli_query($dblink,"DELETE FROM "._PRE.'comments'." WHERE articleid = '$id'");
                if ($id == s('display_page')) {
                  mysqli_query($dblink,"UPDATE "._PRE.'settings'." SET VALUE = 0 WHERE name = 'display_page'");
                }
                break;
            }
          echo notification(0,'',$link);
          unset($_SESSION[_SITE.'temp']);
        }
        break;
      case  'editcomment':
        $articleID = retrieve('articleid', 'comments', 'id', $commentid);
        $articleSEF = retrieve('seftitle', 'articles', 'id', $articleID);
        $articleCAT = retrieve('category','articles','seftitle',$articleSEF);
        $postCat = cat_rel($articleCAT, 'seftitle');
        $link = $postCat.'/'.$articleSEF;
        if (isset($_POST['submit_text'])) {
          mysqli_query($dblink,"UPDATE "._PRE.'comments'." SET
            name = '$name',
            url = '$url',
            comment = '$comment',
            approved = '$approved'
            WHERE id = $commentid");
        } else if (isset($_POST['delete_text'])) {
          mysqli_query($dblink,"DELETE FROM "._PRE.'comments'." WHERE id = '$commentid'");
        }
        echo notification(0,'',$link);
        break;
      case  'deletecomment':
        $commentid = cleanXSS($_GET['commentid']);
        $articleid = retrieve('articleid', 'comments', 'id', $commentid);
        $articleSEF = retrieve('seftitle', 'articles', 'id', $articleid);
        $articleCAT = retrieve('category','articles','id', $articleid);
        $postCat = cat_rel($articleCAT, 'seftitle');
        $link = $postCat.'/'.$articleSEF;
        mysqli_query($dblink,"DELETE FROM "._PRE.'comments'." WHERE id = '$commentid'");
        echo notification(0,'', $link);
        global $uri;
        $uri='<script type="text/javascript">setTimeout(function(){document.location.href="'._SITE.$postCat.'/'.$articleSEF.'";},500);</script>';
        break;
      case  'delete_category_all':
        $art_query = mysqli_query($dblink,"SELECT id FROM "._PRE.'articles'." WHERE category = '$id'");
        while ($rart = mysqli_fetch_array($art_query)) {
          mysqli_query($dblink,"DELETE FROM "._PRE.'comments'." WHERE articleid = '$rart[id]'");
        }
        mysqli_query($dblink,"DELETE FROM "._PRE.'articles'." WHERE category = '$id'");
        $sub_query = mysqli_query($dblink,"SELECT id FROM "._PRE.'categories'." WHERE subcat = '$id'");
        while ($rsub = mysqli_fetch_array($sub_query)) {
          $art_query = mysqli_query($dblink,"SELECT id FROM "._PRE.'articles'." WHERE category = '$rsub[id]'");
          while ($rart = mysqli_fetch_array($art_query)) {
            mysqli_query($dblink,"DELETE FROM "._PRE.'comments'." WHERE articleid = '$rart[id]'");
          }
          mysqli_query($dblink,"DELETE FROM "._PRE.'articles'." WHERE category = '$rsub[id]'");
        }
        mysqli_query($dblink,"DELETE FROM "._PRE.'categories'." WHERE subcat = '$id'"); delete_cat($id);
        echo notification(0,'', 'scs-categories');
        break;
      case  'hide':
      case  'show':
        $id = cleanXSS($_GET['id']);
        $item = cleanXSS($_GET['item']);
        $back = cleanXSS(checkGET('back'));
        $no_yes = $task == 'hide' ? 'NO' : 'YES';
        switch ($item) {
          case  'scs-articles':
            $link = empty($back) ? 'scs-articles' : $back;
            break;
          case  'extra_contents':
            $link = empty($back) ? 'extra_contents' : $back;
            break;
          case  'scs-pages':
            $link = empty($back) ? 'scs-pages' : $back;
            break;
          case  'scs-events':
            $link = empty($back) ? 'scs-events' : $back;
            break;
          default:
            $link = $back;
        }
        mysqli_query($dblink,"UPDATE "._PRE."articles SET visible = '".$no_yes."' WHERE id = '".$id."'");
        echo notification(0,ucfirst(l('please_wait')),$link);
      break;
    }
  }
}
function cat_rel($var, $column) {
  $categoryid = $var;
  $dblink = connect_to_db();
  $join_result = mysqli_query($dblink,"SELECT parent.".$column." FROM "._PRE."categories AS child INNER JOIN "._PRE."categories AS parent ON parent.id = child.subcat WHERE child.id = ".$categoryid."");
  while ($j = mysqli_fetch_array($join_result)) {
    $parent = $j[$column].'/';
  }
  $subresult = mysqli_query($dblink,"SELECT ".$column." FROM "._PRE."categories WHERE id = ".$categoryid."");
  while ($c = mysqli_fetch_array($subresult)) {
    $child = $c[$column];
  }
  return (!empty($parent)?$parent:'').(!empty($child)?$child:'');
}
function populate_retr_cache() {
  global $retr_cache_cat_id, $retr_cache_cat_sef;
  $dblink = connect_to_db();
  $result = mysqli_query($dblink,'SELECT id, seftitle, name FROM '._PRE.'categories');
  while ($r = mysqli_fetch_array($result)) {
    $retr_cache_cat_id[$r['id']] = $r['seftitle'];
    $retr_cache_cat_sef[$r['seftitle']] = $r['name'];
  }
}
$retr_init = FALSE;
function retrieve($column, $table, $field, $value) {
  $dblink = connect_to_db();
  if (is_null($value)) return null;
  $retrieve="";
  if ($table == 'categories') {
    global $retr_cache_cat_id, $retr_cache_cat_sef, $retr_init;
    if (!$retr_init) {
      populate_retr_cache();
      $retr_init = TRUE;
    }
    if ($column == 'name') {
      return $retr_cache_cat_sef[$value];
    } else if ($column == 'seftitle') {
      return $retr_cache_cat_id[$value];
    }
  }
  $result = mysqli_query($dblink,"SELECT $column FROM "._PRE."$table WHERE $field = '$value'");
  while ($r = mysqli_fetch_array($result)) {
    $retrieve = $r[$column];
  }
  return $retrieve;
}

function notification($error = 0, $note = '', $link = '') {
  $error_shortname = $note;
  $use_renderer = false;
  switch($note) {
    case  l('logout'):
      $title_switch = ucfirst(l('logout'));
    break;
    case  '404':
      $title_switch = ucfirst(l('error_404_title'));
      $note  = ucfirst(l('error_404'));
    break;
    case  '104':
      $title_switch = ucfirst(l('104_confirm_title'));
      $note  = ucfirst(l('104_confirm'));
    break;
    case  l('contact_sent'):
      $title_switch = ucfirst(l('contact_sent_header'));
    break;
    case  l('login'):
      $title_switch = ucfirst(l('logged_in'));
    break;
    case 'db_tables_error':
      $title_switch = ucfirst(l('dberror_title'));
    break;
    default:
      $title_switch = ucfirst(l('operation_completed'));
  }
  if($error == 0) {
    $title = $title_switch;
  } else {
    if($error == 1) {
      $title = ucfirst(l('admin_error'));
    } else {
      $title = ucfirst(l('warning'));
    }
  }
  $note = (!$note || empty($note) || $note=='logout') ? "" : "<p>".ucfirst($note)."</p>\n";
  switch(true) {
    case (!$link):
      if(strpos($note,l('errNote'))) {
        $goto = "<p>".ucfirst(l('returned'))."</p>\n<script type=\"text/javascript\">setTimeout(function(){window.history.back();},5000);</script>\n";
      } else {
        if($error_shortname=='db_tables_error') {
          $goto = "";
          $note = renderer($title_switch, ucfirst(l($error_shortname)), 'XHTML');
          $use_renderer = true;
        } elseif($error_shortname!='404') {
          $title = ucfirst(l('warning'));
          $goto = "";
        }
      }
      break;
    case ($link == 'home'):
      if ($title != ucfirst(l('contact_sent_header')) || $title != '404') $goto = "<p>".ucfirst(l('returned'))."</p>\n<script type=\"text/javascript\">setTimeout(function(){document.location.href='"._SITE."index.php';},2000);</script>\n";
      break;
    case  ($link != 'home'):
      $goto = "<script type=\"text/javascript\">setTimeout(function(){document.location.href='"._SITE.$link."';},0);</script>\n";
      break;
  }
  if($use_renderer === false) {
    echo "<div id=\"lead\" class=\"page\">\n<div id=\"main\" class=\"webpage notification\">\n";
    if ($error == 2) {
      $_SESSION[_SITE.'fatal'] = $note == "" ? "" : "<h1>".$title."</h1>\n".$note.$goto;
      echo "<script type=\"text/javascript\">setTimeout(function(){document.location.href='"._SITE.$link."';},0);</script>\n";
    } else {
      if($title==ucfirst(l('contact_sent_header'))) {
        echo "<h1 id=\"cstart\">".$title."</h1>\n".$note."\n";
      } elseif($error_shortname=='404') {
        echo "<h1 id=\"cstart\">".$title."</h1>\n".$note."\n";
        echo sitemap();
      } elseif($error_shortname == '104') {
        echo "<div id=\"errorpopup\" class=\"center\">\n<h1 id=\"cstart\">".$title."</h1>\n".$note."<button onclick=\"document.getElementById('errorpopup').parentNode.removeChild(document.getElementById('errorpopup'));\">".ucfirst(l('close'))."</button></div>\n";
      } else {
        if(strpos($note,l('errNote'))) {
          echo "<div id=\"errorpopup\" class=\"center\">\n<h1 id=\"cstart\">".$title."</h1>\n".$note."<button onclick=\"document.getElementById('errorpopup').parentNode.removeChild(document.getElementById('errorpopup'));\">".ucfirst(l('close'))."</button></div>\n";
        } else {
          
          echo "<h1 id=\"cstart\">".$title."</h1>\n".$note.$goto;
        }
      }
    }
    echo "</div>\n</div>\n";
  } else {
    echo $note;
  }
}
function renderer($title, $content, $lang) {
  switch($lang) {
    case 'XHTML':
      $c = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
      $c .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.w3.org/MarkUp/SCHEMA/xhtml11.xsd\" xml:lang=\"en-US\">\n";
      $c .= "  <head>\n";
      $c .= "    <base href=\"https://web2.werkzien.nl\" />\n";
      $c .= "    <title>Simple CS</title>\n";
      $c .= "    " . csscrush_tag("/css/styles.css");
      $c .= "  </head>\n";
      $c .= "  <body>\n";
      $c .= "    <div id=\"body\">\n";
      $c .= "      <div id=\"lead\" class=\"page\">\n";
      $c .= "        <div id=\"main\" class=\"webpage notification\">\n";
      $c .= "          <h1>" . $title . "</h1>\n";
      $c .= "          <p>" . $content . "</p>\n";
      $c .= "        </div>\n";
      $c .= "      </div>\n";
      $c .= "    </div>\n";
      $c .= "  </body>\n";
      $c .= "</html>";
      break;
    default:
      $c = $content;
  }
  return $c;
}
function strip($text) {
  $search = array('/\[include\](.*?)\[\/include\]/', '/\[func\](.*?)\[\/func\]/', '/\[break\]/', '/</', '/>/');
  $replace = array('', '', '', '<', '>');
  $output = preg_replace($search, $replace, $text);
  $output = stripslashes(strip_tags($output, '<a><img><h1><h2><h3><h4><h5><ul><li><ol><p><hr><br><b><i><strong><em><blockquote>'));
  return entity($output);
}
function br2nl($text){
    $text = str_replace('\r\n','',str_replace("<br />","\n",preg_replace('/<br\\\\s*?\\/??>/i', "\\n", $text)));
    return $text;
}
function send_email($send_array) {
  foreach ($send_array as $var => $value) { $$var = $value; }
     $body = isset($status) ? $status."\n" : '';
     if (isset($message)) {
     $text = ucfirst(l('message')).': '."\n".br2nl($message)."\n";
     }
     if (isset($comment)) {
       $text = ucfirst(l('comment')).': '."\n".br2nl($comment)."\n";
     }
     $header = "MIME-Version: 1.0\n";
     $header .= "Content-type: text/plain; charset=".s('charset')."\n";
     $header .= "From: $name <$email>\nReply-To: $name <$email>\nReturn-Path: <$email>\n";
     $body .= isset($name) ? ucfirst(l('name')).': '.$name."\n" : '';
     $body .= isset($email) ? ucfirst(l('email')).': '.$email."\n" : '';
     $body .= isset($url) && $url!='' ? ucfirst(l('url')).': '.$url."\n\n" : '';
     $body .= $text."\n";
     mail($to,$subject,$body,$header);
}
function checkUserPass($input) {
  $output = clean(cleanXSS($input));
  $output = strip_tags($output);
  if (ctype_alnum($output) === true && strlen($output) > 3 && strlen($output) < 14) {
    return $output;
  } else {
    return null;
  }
}
function mathCaptcha() {
  $x = rand(1, 9);
  $y = rand(1, 9);
  if (!isset($_SESSION[_SITE.'mathCaptcha-digit'])) {
      $_SESSION[_SITE.'mathCaptcha-digit'] = $x + $y;
      $_SESSION[_SITE.'mathCaptcha-digit-x'] = $x;
      $_SESSION[_SITE.'mathCaptcha-digit-y'] = $y;
  }
  $math  = "  <p class=\"mathcaptcha\">\n";
  $math .= "    <label for=\"calc\">".ucfirst(l('math_captcha'))."</label>\n";
  $math .= "    <span class=\"calc\">: ".$_SESSION[_SITE.'mathCaptcha-digit-x']." + ".$_SESSION[_SITE.'mathCaptcha-digit-y']." = </span><input type=\"text\" name=\"calc\" id=\"calc\" tabindex=\"".tabindex()."\" />\n";
  $math .= "  </p>\n";
  return $math;
}
function checkMathCaptcha() {
  $result = false;
  $testNumber = isset($_SESSION[_SITE.'mathCaptcha-digit']) ? $_SESSION[_SITE.'mathCaptcha-digit'] : 'none';
  unset($_SESSION[_SITE.'mathCaptcha-digit']);
  if (is_numeric($testNumber) && is_numeric($_POST['calc']) && ($testNumber == $_POST['calc'])) {
    $result = true;
  }
  return $result;
}
function check_category($category) {
  $main_menu = explode(',', l('cat_listSEF'));
  if (in_array($category, $main_menu)) {
    return true;
  } else {
    return false;
  }
}
function cleanSEF($string) {
  $string = str_replace(' ', '-', $string);
  $string = preg_replace('/[^0-9a-zA-Z-_]/', '', $string);
  $string = str_replace('-', ' ', $string);
  $string = preg_replace('/^\s+|\s+$/', '', $string);
  $string = preg_replace('/\s+/', ' ', $string);
  $string = str_replace(' ', '-', $string);
  if(is_array($string)) $string = implode($string);
  return strtolower($string);
}
function cleancheckSEF($string) {
    $ret = !preg_match('/^[a-z0-9-_]+$/i', $string) ? 'notok' : 'ok';
    return $ret;
}
function cleanWords($txt) {
  if (strtolower(s('word_filter_enable'))=='on' && file_exists(s('word_filter_file'))) {
    $badword_list=s('word_filter_file');
    $bad_words=explode("\n", file_get_contents($badword_list));
    $replacement=" ".s('word_filter_change')." ";
    $n_words=count($bad_words);
    for($x=0;$x<$n_words;$x++) {
      $txt=preg_replace('~(^|[^a-z0-9])('.$bad_words[$x].'?)($|[^a-z0-9])~iU',$replacement,$txt);
    }
  }
  return $txt;
}
function check_if_unique($what, $text, $not_id = 'x', $subcat, $thisid) {
  $text = clean($text);
  $dblink = connect_to_db();
  switch ($what) {
    case  'article_seftitle':
      $sql = _PRE."articles WHERE seftitle = '".$text."'".(!empty($not_id) ? " AND category = '".$not_id."'" : "");
      break;
    case  'article_title':
      $sql = _PRE."articles WHERE title = '".$text."'".(!empty($not_id) ? " AND category = '".$not_id."'" : "");
      break;
    case  'subcat_seftitle':
      $sql = _PRE."categories WHERE seftitle = '".$text."' AND subcat = '".$subcat."'";
      break;
    case  'subcat_name':
      $sql = _PRE."categories WHERE name = '".$text."' AND subcat = '".$subcat."'";
      break;
    case  'cat_seftitle_edit':
      $sql = _PRE."categories WHERE seftitle = '".$text."' AND id != '".$not_id."'";
      break;
    case  'cat_name_edit':
      $sql = _PRE."categories WHERE name = '".$text."' AND id != '".$not_id."'";
      break;
    case  'subcat_seftitle_edit':
      $sql = _PRE."categories WHERE seftitle = '".$text."' AND subcat = '".$subcat."' AND id != '".$not_id."'";
      break;
    case  'subcat_name_edit':
      $sql = _PRE."categories WHERE name = '".$text."' AND subcat = '".$subcat."' AND id != '".$not_id."'";
      break;
    case  'group_seftitle':
      $sql = _PRE."extras WHERE seftitle = '".$text."'".(!empty($not_id) ? " AND id != '".$not_id."'" : "");
      break;
    case  'group_name':
      $sql = _PRE."extras WHERE name = '".$text."'".(!empty($not_id) ? " AND id != '".$not_id."'" : "");
      break;
  }
  $rows = mysqli_fetch_array(mysqli_query($dblink,'SELECT COUNT(id) FROM '.$sql));
  if ($rows[0] == 0) {
    return false;
  } else if(!empty($thisid)) {
    $therow = mysqli_fetch_array(mysqli_query($dblink,'SELECT id FROM '.$sql));
    if($therow[0]==$thisid) return false;
    else return true;
  } else {
    return true;
  }
}
function update_articles() {
  $last_date = s('last_date');
  $updatetime = !empty($last_date) ? strtotime($last_date) : time();
  $dif_time = time() - $updatetime;
  if ($dif_time > 1200 || empty($last_date)) {
    $dblink = connect_to_db();
    mysqli_query($dblink,'UPDATE '._PRE.'articles SET published=1 WHERE published=2 AND date <= NOW()-INTERVAL 1 DAY');
    mysqli_query($dblink,'UPDATE '._PRE.'settings SET value=NOW() WHERE name=\'last_date\'');
  }
}
function bc() { /* Breadcrumb */
  $excluded_dirs=array("html");
  $replacers=array(
    "weblog"=>"Blog",
    "ons-inhuren"=>"Contact",
    "de-sprong-in-het-onbekende"=>"Wat we doen"
  );
  $tabindex=20;
  $url=array_filter(explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
  $bcs = "        <p><a class=\"bc\" href=\"/\" tabindex=\"".$tabindex."\">Home</a>";
  if(array_filter($excluded_dirs,"komtievoor")) array_shift($url);
  if(end($url)=="index.php") array_pop($url);
  $lastitem=end($url);
  $dir = "";
    foreach ($url as $item=>$bc) {
      $txt=array_key_exists($bc,$replacers)?ucfirst($replacers[str_replace(".php","",$bc)]):ucfirst(str_replace(array(".php","_","-"),array(""," "," "),$bc));
      if($bc!=$lastitem) {
        $dir .= $url[$item]."/";
        $ref=_HOST."/".$dir;
        $bcs .= "<a class=\"bc\" href=\"".$ref."\" title=\"Terug naar $txt\" tabindex=\"".$tabindex++."\">$txt</a>";
      } else $bcs .= "";
    }
  echo $bcs."</p>\n";
}

class tabs {
  private $key;
  function __construct($key) {
    $this->key = $key;
  }
  public function callback($matches) {
    return "<a tabindex=\"".$this->key++."\" ";
  }
}

function captcha($math=FALSE) {
  if(!$math) {
    $operands=array("+","-");
    $_SESSION['operand']=$operands[array_rand($operands)];
    $_SESSION['num1']=mt_rand(1, 10);
    $_SESSION['num2']=mt_rand(1, 10);
    if ($_SESSION['operand']=="+") {
      $_SESSION['result']=$_SESSION['num1']+$_SESSION['num2'];
    } else {
      if ($_SESSION['num1']<$_SESSION['num2']) {
        $_SESSION['result']=$_SESSION['num2']-$_SESSION['num1'];
        $_SESSION['num1']=$_SESSION['num2'];
        $_SESSION['num2']=$_SESSION['num1']-$_SESSION['result'];
      } else {
        $_SESSION['result']=$_SESSION['num1']-$_SESSION['num2'];
      }
    }
    $sol=($_SESSION['operand']=="-")? $_SESSION['num1']-$_SESSION['num2']:$_SESSION['num1']+$_SESSION['num2'];
    return $sol;
  } else {
    return $_SESSION['num1']." ".$_SESSION['operand']." ".$_SESSION['num2'];
  }
}
function sitemap() {
  echo "      <ul class=\"sitemap\">\n";
  echo "        <li><a href=\"/\">".ucfirst(s('home_sef'))."</a></li>\n";
  if(s('show_agenda') == 'on') {
    echo "      <li><a href=\"agenda\">Agenda</a></li>\n";
  }
  echo "        <li><a href=\"".s('overview_menuname')."\">".ucfirst(s('overview_menuname'))."</a>\n";
  echo weblogItemlisting(10);
  echo "        </li>\n";
  global $categorySEF;
  $defaultpages = [];
  $subpages = [];
  $qwr = !_ADMIN ? ' AND visible=\'YES\'' : '';
  $class = empty($categorySEF) ? ' class="current"' : '';
  $tabindex = 20;
  $link = connect_to_db();
  $query = "SELECT id, seftitle, title, visible, published, default_page FROM "._PRE."articles WHERE position = 3".$qwr." AND id <> '".s('display_page')."' ORDER BY artorder ASC, id";
  $result = mysqli_query($link, $query);
  while ($r = mysqli_fetch_array($result)) {
    if($r['id'] == s('display_page')) {
      $homepage = $r;
    } else if(!is_numeric($r['default_page'])) {
      $defaultpages[$r['id']] = $r;
    } else {
      $subpages[$r['id']] = $r;
    }
  }
  if(!empty($homepage)) {
    unset($defaultpages[$homepage['id']]);
    array_unshift ( $defaultpages, $homepage );
  }
  foreach($defaultpages as $defpagearray) {
    $catSEF = cat_rel($defpagearray['id'],'seftitle');
    if($catSEF != "") $catSEF .= "/";
    $title = stripslashes(htmlspecialchars(entity($defpagearray['title']), ENT_QUOTES, 'UTF-8', FALSE));
    $class = ($categorySEF == $defpagearray['seftitle'])? ' class="current"' : '';
    if ($defpagearray['visible'] == 'NO' || $defpagearray['published'] != 1) {
      if(_ADMIN) {
        if ($defpagearray['id'] != s('display_page')) {
          echo "        <li class=\"unpublished\"><a".$class." href=\"".$defpagearray['seftitle']."\" tabindex=\"".$tabindex++."\">".$title."</a>";
        }
      }
    } else {
      echo "        <li><a".$class." href=\"".$defpagearray['seftitle']."\" tabindex=\"".$tabindex++."\">".$title."</a>";
    }
    $sublist = '';
    foreach($subpages as $subpagearray) {
      if($subpagearray['default_page'] == $defpagearray['id']) {
        if($subpagearray['visible'] == 'NO' || $subpagearray['published'] != 1) {
          if(_ADMIN) {
            $sublist = '<ul>';
            break;
          }
        } else {
          $sublist = '<ul>';
          break;
        }
      }
    }
    if($sublist=='<ul>') {
      echo "\n          ".$sublist."\n";
      foreach($subpages as $subpagearray) {
        if($subpagearray['default_page'] == $defpagearray['id']) {
          $title = stripslashes(htmlspecialchars(entity($subpagearray['title']), ENT_QUOTES, 'UTF-8', FALSE));
          $class = ($categorySEF == $subpagearray['seftitle'])? ' class="current"' : '';
          if ($subpagearray['visible'] == 'NO' || $subpagearray['published'] != 1) {
            if(_ADMIN) {
              if ($subpagearray['id'] != s('display_page')) {
                echo "            <li class=\"unpublished\"><a".$class." href=\"".$subpagearray['seftitle']."\" tabindex=\"".$tabindex++."\">".$title."</a></li>\n";
              }
            }
          } else {
            echo "            <li><a".$class." href=\"".$subpagearray['seftitle']."\" tabindex=\"".$tabindex++."\">".$title."</a></li>\n";
          }
        }
      }
      echo "          </ul>\n        </li>\n";
    } else {
      if($defpagearray['visible'] == 'NO' || $defpagearray['published'] != 1) {
        if(_ADMIN) {
          if ($defpagearray['id'] != s('display_page')) {
            echo "</li>\n";
          }
        }
      } else {
        echo "</li>\n";
      }
    }
  }
  echo "        <li><a href=\"contact\">Contact</a></li>\n";
  echo "      </ul>\n";
}

function weblogItemlisting() {
  $dblink = connect_to_db();
  $art_query = "SELECT title, seftitle, date FROM articles WHERE position = 1 AND published = 1 AND visible = 'YES' AND show_on_home = 'YES'";
  $cat_query = "SELECT id, name, seftitle, description, subcat FROM categories WHERE published = 'YES' AND subcat = 0 AND name!='agenda' AND seftitle!='eer-en-roem' ORDER BY catorder,id";
  $cat_result = mysqli_query($dblink,$cat_query);
  $wival = "";
  if (mysqli_num_rows($cat_result) == 0) {
    $wival .= "        <li>Geen artikelen gevonden</li>\n";
  } else {
    while ($c = mysqli_fetch_array($cat_result)) {
      $category_title = $c['seftitle'];
      $catid = $c['id'];
      $query = $art_query.' AND category = '.$catid.' ORDER BY id DESC';
      $result = mysqli_query($dblink,$query);
      $arts = mysqli_num_rows($result);
      if ( $arts > 0 ) {
        $wival .= "          <ul>\n";
        $ccnt = 1;
        while ($r = mysqli_fetch_array($result)) {
          $wival .= "            <li><a tabindex=\"".tabindex()."\" class=\"weblogItemlisting-art\" href=\""._HOST."/".$category_title."/".$r['seftitle']."/\">".$r['title']."</a></li>\n";
          $ccnt++;
        }
        $wival .= "          </ul>\n";
      } 
      $subcat_result = mysqli_query($dblink,"SELECT id, name, seftitle, description, subcat FROM categories WHERE published = 'YES' AND subcat = '".$c['id']."' ORDER BY catorder ASC");
      $subcats = mysqli_num_rows($subcat_result);
      if ( $subcats > 0 ) {
        $wival .= "          <ul>\n";
        while ($s = mysqli_fetch_array($subcat_result)) {
          $subcat_title = $s['seftitle'];
          $subcat_name = $s['name'];
          $subcatid = $s['id'];
          $query = $art_query." AND category = '".$subcatid."' ORDER BY id DESC";
          $artresult = mysqli_query($dblink,$query);
          $subarts = mysqli_num_rows($artresult);
          $wival .= "            <li class=\"subcat\"><a class=\"weblogItemlisting-subcat\" href=\"".$category_title."/".$subcat_title."/\">" . $subcat_name . "</a>";
          if ( $subarts > 0 ) {
            $wival .= "\n              <ul>\n"; 
            while ($r = mysqli_fetch_array($artresult)) {
              $wival .= "                <li><em><a tabindex=\"".tabindex()."\" class=\"weblogItemlisting-subart\" href=\"".$category_title."/".$subcat_title."/".$r['seftitle']."/\">".$r['title']."</a></em></li>\n";
            }
            $wival .= "              </ul>\n            "; 
          }
          $wival .= "</li>\n";
        }
        $wival .= "          </ul>\n";
      }
    }
  }
  return $wival;
}
function komtievoor($var) {
  $check_uri=htmlspecialchars( $_SERVER["REQUEST_URI"]);
  return strpos($check_uri,$var);
}
function isiegelijk($var) {
  $check_uri = explode('/',ltrim($_SERVER["REQUEST_URI"],'/'));
  if ($check_uri[0]==$var) return true;
  return false;
}
function backendMode() {
  $cat_listSEF_ar = explode(",", l('cat_listSEF'));
  return (count(array_filter($cat_listSEF_ar,"komtievoor")) > 0);
}
function d_format() {
  $format = '%A %e %B %Y';
  if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
    $format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
  }
  return $format;
}
function strip_tags_content($text, $tags = '', $invert = FALSE) { 
  preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags); 
  $tags = array_unique($tags[1]); 
  if(is_array($tags) AND count($tags) > 0) { 
    if($invert == FALSE) return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text); 
    else return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text); 
  } 
  elseif($invert == FALSE) return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text); 
  return $text; 
} 

// CLEANXSS
$XSS_cache = array();
$ra1 = array('applet', 'body', 'bgsound', 'base', 'basefont', 'embed', 'frame', 'frameset', 'head', 'html',
             'id', 'iframe', 'ilayer', 'layer', 'link', 'meta', 'name', 'object', 'script', 'style', 'title', 'xml');
$ra2 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script',
            'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base',
            'onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy',
            'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint',
            'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick',
            'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged',
            'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave',
            'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus',
            'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload',
            'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover',
            'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange',
            'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit',
            'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart',
            'onstop', 'onsubmit', 'onunload');
$tagBlacklist = array_merge($ra1, $ra2);
function cleanASCII($str, $replace=array(), $delimiter='-') {
  if( !empty($replace) ) {
    $str = str_replace((array)$replace, ' ', $str);
  }
  $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
  $clean = preg_replace("#[^a-zA-Z0-9/_|+ -]#", '', $clean);
  $clean = strtolower(trim($clean, '-'));
  $clean = preg_replace("#[/_|+ -]+#", $delimiter, $clean);
  return $clean;
}
function cleanXSS($val) {
  if ($val != "") {
    global $XSS_cache;
    if (!empty($XSS_cache) && array_key_exists($val, $XSS_cache)) return $XSS_cache[$val];
    $source = html_entity_decode($val, ENT_QUOTES, 'ISO-8859-1');
    while($source != filterTags($source)) {
      $source = filterTags($source);
    }
    $source = nl2br($source);
    $XSS_cache[$val] = $source;
    return $source;
  }
  return $val;
}
//FILTER TAGS
function filterTags($source) {
  global $tagBlacklist;
  $preTag = NULL;
  $postTag = $source;
  $tagOpen_start = strpos($source, '<');
  while($tagOpen_start !== FALSE) {
    $preTag .= substr($postTag, 0, $tagOpen_start);
    $postTag = substr($postTag, $tagOpen_start);
    $fromTagOpen = substr($postTag, 1);
    $tagOpen_end = strpos($fromTagOpen, '>');
    if ($tagOpen_end === false) break;
    $tagOpen_nested = strpos($fromTagOpen, '<');
    if (($tagOpen_nested !== false) && ($tagOpen_nested < $tagOpen_end)) {
      $preTag .= substr($postTag, 0, ($tagOpen_nested+1));
      $postTag = substr($postTag, ($tagOpen_nested+1));
      $tagOpen_start = strpos($postTag, '<');
      continue;
    }
    $tagOpen_nested = (strpos($fromTagOpen, '<') + $tagOpen_start + 1);
    $currentTag = substr($fromTagOpen, 0, $tagOpen_end);
    $tagLength = strlen($currentTag);
    if (!$tagOpen_end) {
      $preTag .= $postTag;
      $tagOpen_start = strpos($postTag, '<');
    }
    $tagLeft = $currentTag;
    $attrSet = array();
    $currentSpace = strpos($tagLeft, ' ');
    if (substr($currentTag, 0, 1) == '/') {
      $isCloseTag = TRUE;
      list($tagName) = explode(' ', $currentTag);
      $tagName = substr($tagName, 1);
    } else {
      $isCloseTag = FALSE;
      list($tagName) = explode(' ', $currentTag);
    }
    if ((!preg_match('/^[a-z][a-z0-9]*$/i',$tagName)) || (!$tagName) || (is_array($tagBlacklist) && in_array(strtolower($tagName), $tagBlacklist))) {
      $postTag = substr($postTag, ($tagLength + 2));
      $tagOpen_start = strpos($postTag, '<');
      continue;
    }
    while ($currentSpace !== FALSE) {
      $fromSpace = substr($tagLeft, ($currentSpace+1));
      $nextSpace = strpos($fromSpace, ' ');
      $openQuotes = strpos($fromSpace, '"');
      $closeQuotes = strpos(substr($fromSpace, ($openQuotes+1)), '"') + $openQuotes + 1;
      if (strpos($fromSpace, '=') !== FALSE) {
        if (($openQuotes !== FALSE) && (strpos(substr($fromSpace, ($openQuotes+1)), '"') !== FALSE))
          $attr = substr($fromSpace, 0, ($closeQuotes+1));
          else $attr = substr($fromSpace, 0, $nextSpace);
      } else $attr = substr($fromSpace, 0, $nextSpace);
      if (!$attr) $attr = $fromSpace;
        $attrSet[] = $attr;
        $tagLeft = substr($fromSpace, strlen($attr));
        $currentSpace = strpos($tagLeft, ' ');
    }
    $postTag = substr($postTag, ($tagLength + 2));
    $tagOpen_start = strpos($postTag, '<');
  }
  $preTag .= $postTag;
  return $preTag;
}
?>
