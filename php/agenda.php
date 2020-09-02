<?
function agenda($mode="full",$tipo="normal") {
  include('config.php');
  $mysqli = new mysqli(config('dbhost'), config('dbuname'), config('dbpass'), config('dbname'));
  if ($mysqli->connect_errno) {
      echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
  }
  $isUser = (_ADMIN!==true)?"YES":"NO";
  $limit = 10;
  $showfulltext = 0;
  $months=array('januari','februari','maart','april','mei','juni','juli','augustus','september','oktober','november','december');
  $getcat=str_replace("category=", "", $_SERVER['QUERY_STRING']);
  $trail = explode("/",$getcat);
  array_shift($trail);
  if(is_array($trail)) $trail = array_filter($trail);
  
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
        if (!$queryStatement->bind_param("sssi", $isUser, strftime('%Y-%m-%d', $year_start), strftime('%Y-%m-%d', $year_start), $limit)) {
          echo "Binding parameters failed: (" . $queryStatement->errno . ") " . $queryStatement->error;
        }
        break;
      case "maand":
        if (!$queryStatement = $mysqli->prepare("SELECT * FROM articles WHERE position=4 AND (articles.visible='YES' OR articles.visible=?) AND (articles.published=1 OR articles.published=2) AND articles.date >= ? AND articles.date < DATE_ADD(?, INTERVAL 1 MONTH) ORDER BY articles.date DESC LIMIT ?")) {
          echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
        }
        $maandnr=array_search($trail[1],$months)!==false?array_search($trail[1],$months)+1:$trail[1];
        $month_start = strtotime("01-".str_pad($maandnr, 2, '0', STR_PAD_LEFT)."-".$trail[0]);
        if (!$queryStatement->bind_param("sssi", $isUser, strftime('%Y-%m-%d', $month_start), strftime('%Y-%m-%d', $month_start), $limit)) {
          echo "Binding parameters failed: (" . $queryStatement->errno . ") " . $queryStatement->error;
        }
        break;
      case "dag":
        if (!$queryStatement = $mysqli->prepare("SELECT * FROM articles WHERE position=4 AND (articles.visible='YES' OR articles.visible=?) AND (articles.published=1 OR articles.published=2) AND articles.seftitle = ? AND articles.date >= ? AND articles.date < DATE_ADD(?,INTERVAL 1 DAY) ORDER BY articles.date DESC LIMIT ?")) {
          echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
        }
        $maandnr=array_search($trail[1],$months)!==false?array_search($trail[1],$months)+1:$trail[1];
        $dagnr_sql = strtotime($trail[2]."-".str_pad($maandnr, 2, '0', STR_PAD_LEFT)."-".$trail[0]);
        if (!$queryStatement->bind_param("ssssi", $isUser, end($trail), strftime('%Y-%m-%d', $dagnr_sql), strftime('%Y-%m-%d', $dagnr_sql), $limit)) {
          echo "Binding parameters failed: (" . $queryStatement->errno . ") " . $queryStatement->error;
        }
        $showfulltext = 1;
        break;
      default:
    }
  } else {
//echo "<script type=\"text/javascript\">console.log('".$limit."')</script>";
//echo mysqli_num_rows($testresult);

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
  $q = $queryStatement->get_result();
  if(mysqli_num_rows($q)>0) { 
    $t=0;
    $day=0;
    $month="";
    $year=0;
    $year_incr = false;
    $month_incr = false;
    $day_incr = false;
    while($r=$q->fetch_assoc()) {
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
        $visiblity = "<a href=\""._SITE."?action=process&amp;task=hide&amp;item=snews_agenda&amp;id=".$r['id']."&amp;back="._HOST.'/'.ltrim($_SERVER['REQUEST_URI'],'/')."\" tabindex=\"".tabindex(400)."\"><img class=\"icon\" src=\"images/icons/show.png\" alt=\"visible\" title=\"hide\" /></a>\n";
        $vis=" published";
      } else {
        $visiblity = "<a href=\""._SITE."?action=process&amp;task=show&amp;item=snews_agenda&amp;id=".$r['id']."&amp;back="._HOST.'/'.ltrim($_SERVER['REQUEST_URI'],'/')."\" tabindex=\"".tabindex(400)."\"><img class=\"icon\" src=\"images/icons/hide.png\" alt=\"hidden\" title=\"show\" /></a>\n";
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
              echo $divopen.$dateline.$titlelink."<p>\n".entity(trim(substr(strip_tags(trim($text),"<br><a><img>"),0,$shorten)))."\n</p>\n";
            }
/*
            $local_link = "agenda/".$year."/".$month."/".$day."/".$seftitle;
            $social_link = _SITE.$local_link;
            if ($type=="dag") {
              switch(TRUE) {
                case  ($r['socialbuttons']=="YES" && $r['commentable'] == 'YES') :
                  if ($_POST['geen_koekie'] != "NOCOOKIE" && $_COOKIE["jsCookieCheck"] != "NOCOOKIE") {
                    socializer($social_link);
                  }
                  disqus();
                  break;
                case  ($r['socialbuttons']=="YES" || $r['commentable'] == 'YES') :
                  if ($r['socialbuttons']=="YES" && $_POST['geen_koekie'] != "NOCOOKIE") {
                    socializer($social_link);
                  } else {
                    disqus();
                  }
                  break;
              }
              if ($r['socialbuttons']=="YES" || $r['commentable'] == 'YES') {
                if ($_POST['geen_koekie'] != "NOCOOKIE") {
                  echo "      <script type=\"text/javascript\">checkCookie();</script>\n";
                } else {
                  echo "      <script type=\"text/javascript\">setCookie('jsCookieCheck','NOCOOKIE',365);</script>\n";
                }
              }
            }
*/
            $edit_link = "            <a href=\""._SITE."?action=admin_article&amp;id=".$r['id']."\" tabindex=\"".tabindex()."\"><img class=\"icon\" src=\"images/icons/art_edit.png\" alt=\"edit\" title=\"edit article\" /></a>\n";
            $edit_link.= "            ".$visiblity;
            echo "        </div>\n";
          } elseif($mode=="menu") {
            if ($month_incr == true) {
              echo "          <h3><a href=\"agenda/".strftime('%Y',$date)."/".$month."\">".ucfirst($month)."</a></h3>\n";
            }
            echo "          <div class=\"".$vis."\"><span class=\"agenda_".$mode." day\"><a href=\"agenda/".$year."/".$month."/".$day."/".$seftitle."\"><span class=\"numberday\">".ltrim(strftime('%d',$date),'0')."</span> <span class=\"textday\">".$title."</span></a></span></div>\n";
            if ( ($t + 1)==$tipo ) {
              $c=mysql_query("SELECT COUNT(*) FROM articles WHERE position=4 AND show_on_home='YES'".$show_hidden.$past_and_future_posts);
              if ( $c[0] > s('article_limit') ) {
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
              echo "      <p>\n".entity(trim(substr(strip_tags(trim($text),"<br>"),0,$shorten)))."\n</p>\n";
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
      echo "        </div>\n      </div>\n";
    } elseif($mode=="full") {
      echo "<button name=\"loadbatch\" id=\"loadbatch\" type=\"button\" value=\"\" data-lastinlist=\"".$date."\"></button>";
    }
  } else {
    if($mode=="menu") {
      echo "<h2>(".ucfirst(l('none_yet')).")</h2>\n";
    } else {
      echo "<h1 id=\"cstart\">(".ucfirst(l('none_yet')).")</h1>\n";
    }
  }    
  $queryStatement->close();
  $mysqli->close();
}
function tabindex($interval=1) {
  static $c = 0;
  $c=$c+$interval;
  return $c;
}
function entity($text) {
  $to_ncr = array(
    '//"' => '"',
    '<b>' => '<strong>',
    '</b>' => '</strong>',
    '<i>' => '<em>',
    '</i>' => '</em>',
    "".'"'.">\r\n" => "\">",
    "<em>\r\n" => "<em>",
    "<strong>\r\n" => "<strong>",
    "\r\n</strong>\r\n" => '</strong> ',
    "\r\n</span>\r\n</strong>" => '</span></strong>', 
    "</strong> \r\n</span>\r\n" => '</strong> </span>', 
    "</strong> </span><br />" => '</strong></span><br />', 
    " <br />" => "<br />",
    "\r\n</td>" => '</td>',
    "\r\n</tr>" => '</tr>',
    "\r\n</thead>" => '</thead>',
    "\r\n</tbody>" => '</tbody>',
    "\r\n</table>" => '</table>',
    '<p>  </p>' => '',
    "\r\n</em>\r\n" => "</em> ",
    "\r\n</abbr>\r\n" => "</abbr> ",
    "\r\n</a>\r\n" => "</a> ",
    " ." => ".",
    " ," => ",",
    " :" => ":",
    " ;" => ";",
    " ?" => "?",
    " !" => "!",
    " )" => ")",
    " &quot;" => " &#8220;",
    ">\r\n&quot;" => ">\r\n&#8220;",
    "&quot; " => "&#8221; ",
    "&quot;\r\n<" => "&#8221;\r\n<",
    " &#39;" => " &#8216;",
    ">\r\n&#39;" => ">\r\n&#8216;",
    "&#39; " => "&#8217; ",
    "&#39;\r\n<" => "&#8217;\r\n<",
    "&#39;" => "&#8217;",
    "\r\n\r\n" => "\r\n",
    '&Aacute;' => '&#193;',
    '&aacute;' => '&#225;',
    '&Acirc;' => '&#194;',
    '&acirc;' => '&#226;',
    '&acute;' => '&#180;',
    '&AElig;' => '&#198;',
    '&aelig;' => '&#230;',
    '&Agrave;' => '&#192;',
    '&agrave;' => '&#224;',
    '&alefsym;' => '&#8501;',
    '&Alpha;' => '&#913;',
    '&alpha;' => '&#945;',
    '&and;' => '&#8743;',
    '&ang;' => '&#8736;',
    '&Aring;' => '&#197;',
    '&aring;' => '&#229;',
    '&asymp;' => '&#8776;',
    '&Atilde;' => '&#195;',
    '&atilde;' => '&#227;',
    '&Auml;' => '&#196;',
    '&auml;' => '&#228;',
    '&bdquo;' => '&#8222;',
    '&Beta;' => '&#914;',
    '&beta;' => '&#946;',
    '&brkbar;' => '&#166;',
    '&brvbar;' => '&#166;',
    '&bull;' => '&#8226;',
    '&cap;' => '&#8745;',
    '&Ccedil;' => '&#199;',
    '&ccedil;' => '&#231;',
    '&cedil;' => '&#184;',
    '&cent;' => '&#162;',
    '&Chi;' => '?',
    '&chi;' => '?',
    '&circ;' => '?',
    '&clubs;' => '?',
    '&cong;' => '?',
    '&copy;' => '&#169;',
    '&crarr;' => '?',
    '&cup;' => '?',
    '&curren;' => '&#164;',
    '&dagger;' => '?',
    '&Dagger;' => '?',
    '&dArr;' => '?',
    '&darr;' => '?',
    '&deg;' => '&#176;',
    '&Delta;' => '?',
    '&delta;' => '?',
    '&diams;' => '?',
    '&die;' => '&#168;',
    '&divide;' => '&#247;',
    '&Eacute;' => '&#201;',
    '&eacute;' => '&#233;',
    '&Ecirc;' => '&#202;',
    '&ecirc;' => '&#234;',
    '&Egrave;' => '&#200;',
    '&egrave;' => '&#232;',
    '&empty;' => '?',
    '&emsp;' => '?',
    '&ensp;' => '?',
    '&Epsilon;' => '?',
    '&epsilon;' => '?',
    '&equiv;' => '?',
    '&Eta;' => '?',
    '&eta;' => '?',
    '&ETH;' => '&#208;',
    '&eth;' => '&#240;',
    '&Euml;' => '&#203;',
    '&euml;' => '&#235;',
    '&euro;' => '&#8364;',
    '&exist;' => '?',
    '&fnof;' => '/',
    '&forall;' => '?',
    '&frac12;' => '&#189;',
    '&frac14;' => '&#188;',
    '&frac34;' => '&#190;',
    '&frasl;' => '&#47;',
    '&Gamma;' => '?',
    '&gamma;' => '?',
    '&ge;' => '?',
    '&hArr;' => '?',
    '&harr;' => '?',
    '&hearts;' => '?',
    '&hellip;' => '?',
    '&hibar;' => '&#175;',
    '&Iacute;' => '&#205;',
    '&iacute;' => '&#237;',
    '&Icirc;' => '&#206;',
    '&icirc;' => '&#238;',
    '&iexcl;' => '&#161;',
    '&Igrave;' => '&#204;',
    '&igrave;' => '&#236;',
    '&image;' => '?',
    '&infin;' => '?',
    '&int;' => '?',
    '&Iota;' => '?',
    '&iota;' => '?',
    '&iquest;' => '&#191;',
    '&isin;' => '?',
    '&Iuml;' => '&#207;',
    '&iuml;' => '&#239;',
    '&Kappa;' => '?',
    '&kappa;' => '?',
    '&Lambda;' => '?',
    '&lambda;' => '?',
    '&lang;' => '?',
    '&laquo;' => '&#171;',
    '&lArr;' => '?',
    '&larr;' => '?',
    '&lceil;' => '?',
    '&ldquo;' => '&#8220;',
    '&le;' => '?',
    '&lfloor;' => '?',
    '&lowast;' => '?',
    '&loz;' => '?',
    '&lrm;' => '?',
    '&lsaquo;' => '?',
    '&lsquo;' => '&#8216;',
    '&macr;' => '&#175;',
    '&mdash;' => '&#8212;',
    '&micro;' => '&#181;',
    '&middot;' => '&#183;',
    '&minus;' => '-',
    '&Mu;' => '?',
    '&mu;' => '?',
    '&nabla;' => '?',
    '&nbsp;' => '&#160;',
    '&ndash;' => '&#8212;',
    '&ne;' => '?',
    '&ni;' => '?',
    '&not;' => '&#172;',
    '&notin;' => '?',
    '&nsub;' => '?',
    '&Ntilde;' => '&#209;',
    '&ntilde;' => '&#241;',
    '&Nu;' => '?',
    '&nu;' => '?',
    '&Oacute;' => '&#211;',
    '&oacute;' => '&#243;',
    '&Ocirc;' => '&#212;',
    '&ocirc;' => '&#244;',
    '&OElig;' => '?',
    '&oelig;' => '?',
    '&Ograve;' => '&#210;',
    '&ograve;' => '&#242;',
    '&oline;' => '?',
    '&Omega;' => '?',
    '&omega;' => '?',
    '&Omicron;' => '?',
    '&omicron;' => '?',
    '&oplus;' => '?',
    '&or;' => '?',
    '&ordf;' => '&#170;',
    '&ordm;' => '&#186;',
    '&Oslash;' => '&#216;',
    '&oslash;' => '&#248;',
    '&Otilde;' => '&#213;',
    '&otilde;' => '&#245;',
    '&otimes;' => '?',
    '&Ouml;' => '&#214;',
    '&ouml;' => '&#246;',
    '&para;' => '&#182;',
    '&part;' => '?',
    '&permil;' => '?',
    '&perp;' => '?',
    '&Phi;' => '?',
    '&phi;' => '?',
    '&Pi;' => '?',
    '&pi;' => '?',
    '&piv;' => '?',
    '&plusmn;' => '&#177;',
    '&pound;' => '&#163;',
    '&prime;' => '?',
    '&Prime;' => '?',
    '&prod;' => '?',
    '&prop;' => '?',
    '&Psi;' => '?',
    '&psi;' => '?',
    '&radic;' => '?',
    '&rang;' => '?',
    '&raquo;' => '&#187;',
    '&rArr;' => '?',
    '&rarr;' => '?',
    '&rceil;' => '?',
    '&rdquo;' => '&#8221;',
    '&real;' => '?',
    '&reg;' => '&#174;',
    '&rfloor;' => '?',
    '&Rho;' => '?',
    '&rho;' => '?',
    '&rlm;' => '?',
    '&rsaquo;' => '&#187;',
    '&rsquo;' => '&#8217;',
    '&sbquo;' => '&#8218;',
    '&Scaron;' => '?',
    '&scaron;' => '/',
    '&sdot;' => '?',
    '&sect;' => '&#167;',
    '&Sigma;' => '?',
    '&sigma;' => '?',
    '&sigmaf;' => '?',
    '&sim;' => '?',
    '&spades;' => '?',
    '&sub;' => '?',
    '&sube;' => '?',
    '&sum;' => '?',
    '&sup;' => '?',
    '&sup1;' => '&#185;',
    '&sup2;' => '&#178;',
    '&sup3;' => '&#179;',
    '&supe;' => '?',
    '&szlig;' => '&#223;',
    '&Tau;' => '?',
    '&tau;' => '?',
    '&there4;' => '?',
    '&Theta;' => '?',
    '&theta;' => '?',
    '&thetasym;' => '?',
    '&thinsp;' => '?',
    '&THORN;' => '&#222;',
    '&thorn;' => '&#254;',
    '&tilde;' => '?',
    '&times;' => '&#215;',
    '&trade;' => '&#8482;',
    '&Uacute;' => '&#218;',
    '&uacute;' => '&#250;',
    '&uArr;' => '?',
    '&uarr;' => '?',
    '&Ucirc;' => '&#219;',
    '&ucirc;' => '&#251;',
    '&Ugrave;' => '&#217;',
    '&ugrave;' => '&#249;',
    '&uml;' => '&#168;',
    '&upsih;' => '?',
    '&Upsilon;' => '?',
    '&upsilon;' => '?',
    '&Uuml;' => '&#220;',
    '&uuml;' => '&#252;',
    '&weierp;' => '?',
    '&Xi;' => '?',
    '&xi;' => '?',
    '&Yacute;' => '&#221;',
    '&yacute;' => '&#253;',
    '&yen;' => '&#165;',
    '&Yuml;' => '/',
    '&yuml;' => '&#255;',
    '&Zeta;' => '?',
    '&zeta;' => '?',
    '&zwj;' => '*',
    '&zwnj;' => '*'
  );
  return str_replace( array_keys($to_ncr), array_values($to_ncr), $text );
}
