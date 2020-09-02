<?
session_start();

require_once('../../php/params.php');
require_once('../../php/helpers.php');
$subdirred = substr_count(dirname(__FILE__), '/') - _HOST_CONSTRUCT;
include_language_file($subdirred);
$mysqli = new mysqli(db('dbhost'), db('dbuname'), db('dbpass'), db('dbname'));
if ($mysqli->connect_errno) {
  echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

$cookie_name = "loadedBatch";
$req = clean($_GET['request']);

$reqArray = explode('/',trim($req,' /'));
$gordianType = end($reqArray);

global $isUser;
$main_overview = false;
$show_all = false;
$limit = s('article_limit');
$showfulltext = _OVERVIEW_FULL_TEXT_PREVIEWS;
$t=0;

if(array_key_exists($cookie_name,$_COOKIE)) {
  $timeStamp = $_COOKIE[$cookie_name];
} else {
  $timeStamp = $reqArray[1];
}
unset($_COOKIE[$cookie_name]);
setcookie($cookie_name, null, -1, '/');
if((int)$timeStamp>1000000) {
//echo (int)$timeStamp . " > " .s('article_limit');
//if(isValidTimeStamp($timeStamp)) {

  $timeNotation = strftime('%Y-%m-%d', $timeStamp);
  $year = strftime('%Y', $timeStamp);
  $month = strftime('%m', $timeStamp);
  $day = strftime('%d', $timeStamp);
  $catorder = "catorder";
  $artorder = "date";

  if(s('overview_menuname') == $gordianType) {
    $main_overview = true;
    if("x".$gordianType == s('overview_pagename')) {
      $show_all = true;
      $catsef = $gordianType;
    } else {
      $gordianType = s('overview_pagename');
    }
  }
  if($show_all === true) {
    if(!$qs = $mysqli->prepare("SELECT
            a.id AS aid,title,a.seftitle AS asef,text,a.date,a.mod_date,a.position,
              displaytitle,a.show_author,a.author,displayinfo,commentable,a.show_on_home,a.visible,a.socialbuttons,a.artorder,
            c.name AS name,c.seftitle AS csef,c.catorder,
            x.name AS xname,x.seftitle AS xsef
          FROM "._PRE."articles AS a
          LEFT OUTER JOIN "._PRE."categories AS c
            ON category = c.id
          LEFT OUTER JOIN "._PRE."categories AS x
            ON c.subcat =  x.id AND x.published ='YES'
         WHERE show_on_home = 'YES'
           AND (a.visible='YES' OR a.visible=?)
           AND position = 1
           AND a.published = 1
           AND c.published = 'YES'
      ORDER BY c.catorder "._OVERVIEW_ORDER.", a.artorder "._OVERVIEW_ORDER."
         LIMIT ?, ?")) echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
    if(!$qs->bind_param("sii", $isUser, $marker, $limit)) {
      echo "Binding parameters failed: (" . $qs->errno . ") " . $qs->error;
    }
  } else {
    if($gordianType == "agenda") {
      if (!$qs = $mysqli->prepare("SELECT * FROM articles WHERE position=4 AND (articles.visible='YES' OR articles.visible=?) AND (articles.published=1 OR articles.published=2) AND articles.date < ? ORDER BY articles.date DESC LIMIT ?")) {
        echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
      }
      if (!$qs->bind_param("ssi", $isUser, $timeNotation, $limit)) {
        echo "Binding parameters failed: (" . $qs->errno . ") " . $qs->error;
      }
    } else {
      if (!$qs = $mysqli->prepare("SELECT id,subcat FROM categories WHERE seftitle=?")) echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
      if (!$qs->bind_param("s", $gordianType)) echo "Binding parameters failed: (" . $qs->errno . ") " . $qs->error;
      if (!$qs->execute()) echo "Execute failed: (" . $qs->errno . ") " . $qs->error;
      $qs->store_result();
      while($categoryInfo = fetchAssocStatement($qs)) {
        $catID = (int)$categoryInfo['id'];
        $subcatID = (int)$categoryInfo['subcat'];
        $catsef = getCat($catID)->seftitle;
      }
      if($subcatID===0) {
        if (!$qs = $mysqli->prepare("SELECT
                a.id AS aid,title,a.seftitle AS asef,text,a.date,a.mod_date,a.position,x.seftitle as subcatname,c.seftitle as catsef,a.category,
                a.displaytitle,a.show_author,a.author,a.displayinfo,a.commentable,a.show_on_home,a.visible,a.socialbuttons,a.artorder
          FROM "._PRE."articles a
          LEFT JOIN (
            "._PRE."categories c INNER JOIN "._PRE."categories x ON x.subcat = c.id
          ) ON x.id = a.category
          WHERE (a.category=? OR x.subcat=?)
            AND (a.visible='YES' OR a.visible=?)
            AND a.published = 1
            AND a.position = 1
            AND a.date < ?
          ORDER BY a.date DESC
          LIMIT ?")) echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
        if (!$qs->bind_param("iissi", $catID, $catID, $isUser, $timeNotation, $limit)) {
          echo "Binding parameters failed: (" . $qs->errno . ") " . $qs->error;
        }
      $uri = $catsef;
      } else {
        if (!$qs = $mysqli->prepare("SELECT
            a.id AS aid,title,a.seftitle AS asef,text,a.date,a.mod_date,a.position,a.category,c.seftitle as catsef,
            a.displaytitle,a.show_author,a.author,a.displayinfo,a.commentable,a.show_on_home,a.visible,a.socialbuttons,a.artorder
              FROM "._PRE."articles AS a
              LEFT JOIN "._PRE."categories c ON c.id = a.category
              WHERE a.category = ?
                AND (a.visible='YES' OR a.visible=?)
                AND a.published = 1
                AND a.position = 1
              ORDER BY a.date DESC
              LIMIT ?")) echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
        if (!$qs->bind_param("isi", $catID, $isUser, $limit)) {
          echo "Binding parameters failed: (" . $qs->errno . ") " . $qs->error;
        }
      }
    }
  }
  if (!$qs->execute()) {
    echo "Execute failed: (" . $qs->errno . ") " . $qs->error;
  }

  $qs->store_result();
  while($r = fetchAssocStatement($qs)) {
    $subcatsef = array_key_exists('subcatsef',$r) ? $r['subcatsef']."/" : "";
    $title=entity($r['title']);
    $infoline = ($r['displayinfo'] == 'YES') ? TRUE : FALSE;
    $seftitle = $r['asef'];
    $text = str_replace("<a ","<a tabindex=\"".tabindex()."\" ", $r['text']);
    $text = $showfulltext == 0?str_replace("</p>","</p><br />",$text):$text;
    if ($r['displaytitle'] == 'NO') {
      $h1 = extract_tags( $text, 'h1', false );
      $h1 = trim($h1[0]['contents']);
      $title = !empty($h1)? $h1 : l('no_title');
    }
    $title = ucfirst(strtolower($title));
    $short_display = strpos($text,'<hr />') - 1;
    $date=strtotime($r['date']);
    $h="h2";
    if(array_key_exists('csef',$r)) {
      if($uri = $r['xsef']) {
        $uri = $r['xsef'].'/'.$r['csef'];
      } else {
        $uri = $r['csef'];
      }
    } elseif(array_key_exists('subcatname',$r)) {
      $uri = $r['catsef']."/".$r['subcatname'];
    } else {
      $uri = $catsef;
    }
    if(strftime('%Y',$date)!=$year) {
      $year=strftime('%Y',$date);
      $year_incr = true;
    } else $year_incr = false;
    if(strftime('%B',$date)!=$month) {
      $month=strftime('%B',$date);
      $month_incr = true;
    } else $month_incr = false;
    if(strftime('%d',$date)!=$day) {
      $day=strftime('%d',$date);
      $day_incr = true;
    } else $day_incr = false;
    if ($r['visible'] == 'YES') {
      $visiblity = "<a href=\""._HOST."/?action=process&amp;task=hide&amp;item=scs_events&amp;id=".$r['aid']."&amp;back=".ltrim($_SERVER['REQUEST_URI'],'/')."\" tabindex=\"".tabindex(400)."\"><img class=\"icon\" src=\"images/icons/show.png\" alt=\"visible\" title=\"".ucfirst(l('hide'))."\" /></a>\n";
      $vis="";
    } else {
      $visiblity = "<a href=\""._HOST."/?action=process&amp;task=show&amp;item=scs_events&amp;id=".$r['aid']."&amp;back=".ltrim($_SERVER['REQUEST_URI'],'/')."\" tabindex=\"".tabindex(400)."\"><img class=\"icon\" src=\"images/icons/hide.png\" alt=\"hidden\" title=\"".ucfirst(l('show'))."\" /></a>\n";
      $vis=" unpublished";
    }
    if($showfulltext === true) 
      $shorttext = entity($text);
    else {  
      $short_display = strpos($text,'<hr />') - 1;
      $shorten = $short_display < 0 ? 320 : $short_display;
      $text = preg_replace("/&#?[a-z0-9]{2,8};/i","",$text); 
      $shorttext = entity(trim(substr(strip_tags(trim($text)),0,$shorten)));
    }
    if($gordianType=="agenda") {
      echo "<div class=\"agenda_item".$vis."\">\n";
      echo "  <span class=\"agenda_full list\"><span class=\"weekday\">".ucfirst(strftime('%A',$date))."</span><span class=\"bigger bold\">".ltrim(strftime('%d',$date),'0')."</span> <span class=\"textmonth\">".ucfirst(strftime('%B',$date))."</span> <span class=\"textyear\">".strftime('%Y',$date)."</span></span>\n";
      echo "  <".$h." id=\"agendapunt-".($t+1)."\"><a href=\"agenda/".$year."/".$month."/".$day."/".$seftitle."\">".$title."</a></".$h.">\n";
      echo "  ".$shorttext."\n";
    } else {
      echo "<div class=\"lead".$vis."\">\n";
      if(_SHOW_DATE_IN_OVERVIEW===true) {
        echo "<p class=\"date\">\n";
        echo "  ".ucfirst($a_date_format);
/* alternative date display
*         echo "    <span class=\"weekday\">".ucfirst(strftime('%A',$date))."</span> <span class=\"bigger bold\">".ltrim(strftime('%d',$date),'0')."</span> <span class=\"textmonth\">".ucfirst(strftime('%B',$date))."</span> <span class=\"textyear\">".strftime('%Y',$date)."</span>\n";
*/
        echo "</p>\n";
      }
      echo "  <".$h." class=\"lead-title\"><a href=\"".$uri."/".$r['asef']."\">".$title."</a></".$h.">\n";
      preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $text, $image);
      if(array_key_exists('src',$image)) {
        $imageSource = $image['src'];
      } else {
        $imageSource = _WEBSITE_DEFAULT_IMAGE; 
      }
      echo "  <div class=\"lead-previewtext\">\n";
      echo "    <span class=\"mask\"><img src=\"".$imageSource."\" class=\"lead-previewimage".((strpos($shorttext,'.') & 1) ?" floatLeft":" floatRight")."\" alt=\"\" /></span>\n";
      echo "    <p>\n".$shorttext."\n</p>\n";
      echo "  </div>\n";
    }
    if(_ADMIN) {
      echo "  <div class=\"edit-control\">\n";
      echo "    <a href=\""._HOST."/?action=admin_article&amp;id=".$r['aid']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/art_edit.png\" alt=\"edit\" title=\"".ucfirst(l('edit_art'))."\" /></a>\n";
      echo "    ".$visiblity."\n";;
      echo "  </div>\n";
    }
    echo "</div>\n";
    $t++;
  }
  if($t<s('article_limit')) {
    echo "<button name=\"loadbatch\" id=\"loadbatch\" type=\"button\" value=\"".$date."\" data-lastinlist=\"".$date."\">Next</button>";
  }
} else {
  $marker = (int)$timeStamp;
  if(s('overview_menuname') == $gordianType) {
    $main_overview = true;
    if("x".$gordianType == s('overview_pagename')) {
      $show_all = true;
      $catsef = $gordianType;
    } else {
      $gordianType = s('overview_pagename');
    }
  }
  if($show_all === true) {
    if(!$qs = $mysqli->prepare("SELECT
            a.id AS aid,title,a.seftitle AS asef,text,a.date,a.mod_date,a.position,
              displaytitle,a.show_author,a.author,displayinfo,commentable,a.show_on_home,a.visible,a.socialbuttons,a.artorder,
            c.name AS name,c.seftitle AS csef,c.catorder,
            x.name AS xname,x.seftitle AS xsef
          FROM "._PRE."articles AS a
          LEFT OUTER JOIN "._PRE."categories AS c
            ON category = c.id
          LEFT OUTER JOIN "._PRE."categories AS x
            ON c.subcat =  x.id AND x.published ='YES'
         WHERE show_on_home = 'YES'
           AND (a.visible='YES' OR a.visible=?)
           AND position = 1
           AND a.published = 1
           AND c.published = 'YES'
      ORDER BY c.catorder "._OVERVIEW_ORDER.", a.artorder "._OVERVIEW_ORDER."
         LIMIT ?, ?")) echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
    if(!$qs->bind_param("sii", $isUser, $marker, $limit)) {
      echo "Binding parameters failed: (" . $qs->errno . ") " . $qs->error;
    }
  } else {
    if(!$iqs = $mysqli->prepare("SELECT id,subcat FROM categories WHERE seftitle=?")) echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
    if(!$iqs->bind_param("s", $gordianType)) echo "Binding parameters failed: (" . $iqs->errno . ") " . $iqs->error;
    if(!$iqs->execute()) echo "Execute failed: (" . $iqs->errno . ") " . $iqs->error;

    $iqs->bind_result($id, $subcat);
    while ($iqs->fetch()) {
      $catq['id'] = $id;
      $catq['subcat'] = $subcat;
    }

    if(count($catq)>0) { 
      $categoryInfo=$catq;
      $catID = (int)$categoryInfo['id'];
      $subcatID = (int)$categoryInfo['subcat'];
      $catsef = getCat($catID)->seftitle;
    }
    if($subcatID===0) { // Query for a main category and its subcategories
      if (!$qs = $mysqli->prepare("SELECT
              a.id AS aid,title,a.seftitle AS asef,text,a.date,a.mod_date,a.position,a.displaytitle,
              a.show_author,a.author,a.displayinfo,a.commentable,a.show_on_home,a.visible,a.socialbuttons,a.artorder,
              c.id AS cid,
              x.name AS subcatname,x.seftitle AS subcatsef,x.id AS xid
        FROM "._PRE."articles a
        LEFT JOIN (
          "._PRE."categories c INNER JOIN "._PRE."categories x ON x.subcat = c.id
        ) ON x.id = a.category
        WHERE (a.category=? OR x.subcat=?)
          AND (a.visible='YES' OR a.visible=?)
          AND a.published = 1
        ORDER BY x.catorder "._OVERVIEW_ORDER.", a.artorder "._OVERVIEW_ORDER."
        LIMIT ?, ?")) echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
      if (!$qs->bind_param("iisii", $catID, $catID, $isUser, $marker, $limit)) {
        echo "Binding parameters failed: (" . $qs->errno . ") " . $qs->error;
      }

      $qs->bind_result($aid,$title,$asef,$text,$date,$mod_date,$position,$displaytitle,$show_author,$author,$displayinfo,$commentable,$show_on_home,$visible,$socialbuttons,$artorder,$cid,$subcatname,$subcatsef,$xid);
      
    } else { // Query for category without subcategories or a subcategory
      if(!$qs = $mysqli->prepare("SELECT
          a.id AS aid,title,a.seftitle AS asef,text,a.date,a.mod_date,a.position,a.category as acat,a.displaytitle,
          a.show_author,a.author,a.displayinfo,a.commentable,a.show_on_home,a.visible,a.socialbuttons,a.artorder,
          c.seftitle AS catsef,c.id AS cid
            FROM "._PRE."articles AS a
            LEFT JOIN "._PRE."categories c ON c.id = a.category
            WHERE a.category = ?
              AND (a.visible='YES' OR a.visible=?)
              AND a.published = 1
              AND a.position = 1
            ORDER BY a.artorder "._OVERVIEW_ORDER."
            LIMIT ?
            OFFSET ?")) echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
      if(!$qs->bind_param("isii", $catID, $isUser, $limit, $marker)) {
        echo "Binding parameters failed: (" . $qs->errno . ") " . $qs->error;
      }
      $qs->bind_result($aid,$title,$asef,$text,$date,$mod_date,$position,$acat,$displaytitle,$show_author,$author,$displayinfo,$commentable,$show_on_home,$visible,$socialbuttons,$artorder,$catsef,$cid);
    }
  }
  if (!$qs->execute()) {
    echo "Execute failed: (" . $qs->errno . ") " . $qs->error;
  }
  $qs->store_result();
  while($r = fetchAssocStatement($qs)) {
    $subcatsef = array_key_exists('subcatsef',$r) ? $r['subcatsef']."/" : "";
    $title=entity($r['title']);
    $infoline = ($r['displayinfo'] == 'YES') ? TRUE : FALSE;
    $seftitle = $r['asef'];
    $text = str_replace("<a ","<a tabindex=\"".tabindex()."\" ", $r['text']);
    $text = $showfulltext == 0?str_replace("</p>","</p>",$text):$text;
    if ($r['displaytitle'] == 'NO') {
      $h1 = extract_tags( $text, 'h1', false );
      $h1 = trim($h1[0]['contents']);
      $title = !empty($h1)? $h1 : l('no_title');
    }
    $title = ucfirst(strtolower($title));
    $short_display = strpos($text,'<hr />') - 1;
    $date=strtotime($r['date']);
    $h="h2";
    if(array_key_exists('csef',$r)) {
      if($uri = $r['xsef']) {
        $uri = $r['xsef'].'/'.$r['csef'];
      } else {
        $uri = $r['csef'];
      }
    } elseif(array_key_exists('catsef',$r)) {
      $parentcatsef = getCat($subcatID)->seftitle;
      $uri = $parentcatsef."/".$catsef;
    } else {
      $uri = $catsef;
    }
    if ($r['visible'] == 'YES') {
      $visiblity = "<a href=\""._HOST."/?action=process&amp;task=hide&amp;item=scs_events&amp;id=".$r['aid']."&amp;back=".ltrim($_SERVER['REQUEST_URI'],'/')."\" tabindex=\"".tabindex(400)."\"><img class=\"icon\" src=\"images/icons/show.png\" alt=\"visible\" title=\"".ucfirst(l('hide'))."\" /></a>\n";
      $vis="";
    } else {
      $visiblity = "<a href=\""._HOST."/?action=process&amp;task=show&amp;item=scs_events&amp;id=".$r['aid']."&amp;back=".ltrim($_SERVER['REQUEST_URI'],'/')."\" tabindex=\"".tabindex(400)."\"><img class=\"icon\" src=\"images/icons/hide.png\" alt=\"hidden\" title=\"".ucfirst(l('show'))."\" /></a>\n";
      $vis=" unpublished";
    }
    if($showfulltext === true) 
      $shorttext = entity($text);
    else {  
      $short_display = strpos($text,'<hr />') - 1;
      $shorten = $short_display < 0 ? 320 : $short_display;
      $text = preg_replace("/&#?[a-z0-9]{2,8};/i","",$text); 
      $shorttext = entity(trim(substr(strip_tags(trim($text)),0,$shorten)));
    }
    echo "<div class=\"lead".$vis."\">\n";
    if(_SHOW_DATE_IN_OVERVIEW===true) {
      echo "<p class=\"date\">\n";
      echo "  ".ucfirst(strftime(_DATEFORM, $date));
/* alternative date display
*         echo "    <span class=\"weekday\">".ucfirst(strftime('%A',$date))."</span> <span class=\"bigger bold\">".ltrim(strftime('%d',$date),'0')."</span> <span class=\"textmonth\">".ucfirst(strftime('%B',$date))."</span> <span class=\"textyear\">".strftime('%Y',$date)."</span>\n";
*/
      echo "</p>\n";
    }
    echo "  <".$h." class=\"lead-title\"><a href=\"".$uri."/".$subcatsef.$r['asef']."\">".$title."</a></".$h.">\n";
    preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $text, $image);
    if(array_key_exists('src',$image)) {
      $imageSource = $image['src'];
    } else {
      $imageSource = _WEBSITE_DEFAULT_IMAGE; 
    }
    echo "  <div class=\"lead-previewtext\">\n";
    echo "    <span class=\"mask\"><img src=\"".$imageSource."\" class=\"lead-previewimage".((strpos($shorttext,'.') & 1) ?" floatLeft":" floatRight")."\" alt=\"\" /></span>\n";
    echo "    <p>\n".$shorttext."\n</p>\n";
    echo "  </div>\n";
    if(_ADMIN) {
      echo "  <div class=\"edit-control\">\n";
      echo "    <a href=\""._HOST."/?action=admin_article&amp;id=".$r['aid']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/art_edit.png\" alt=\"edit\" title=\"".ucfirst(l('edit_art'))."\" /></a>\n";
      echo "    ".$visiblity."\n";
      echo "  </div>\n";
    }
    echo "</div>\n";
    $t++;
  }
  if($t==s('article_limit')) {
    echo "<div><button name=\"loadbatch\" id=\"loadbatch\" type=\"button\" value=\"".($marker+$t)."\" data-lastinlist=\"".($marker+$t)."\">More artworks</button></div>";
  }
}
$qs->close();
$mysqli->close();

function fetchAssocStatement($stmt)
{
    if($stmt->num_rows>0)
    {
        $result = array();
        $md = $stmt->result_metadata();
        $params = array();
        while($field = $md->fetch_field()) {
            $params[] = &$result[$field->name];
        }
        call_user_func_array(array($stmt, 'bind_result'), $params);
        if($stmt->fetch())
            return $result;
    }

    return null;
}
?>

