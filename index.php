<?
session_start();
require_once("css-crush/CssCrush.php");
require_once("php/params.php");
require_once("php/helpers.php");
require_once("scs-main.php");
doctype();
title();
?>
  <body>
    <div id="body">
<?
/* Set tabindex start for the body, so skip all items that are visible above the body (menu, etc.) */
if(tabindex(10)) center();
?>
    </div>
    <div id="mainmenubar">
<?
if (constant("_ADMIN")==0 || frontendMode()===true) pages();
?>
    </div>
  </body>
</html>
