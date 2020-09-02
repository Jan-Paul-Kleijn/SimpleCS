<?
/* Create JSON for the artistry carousel */
error_reporting(E_ALL);
ini_set('display_errors', '1');
include ("../../php/params.php");
include ("../../php/drivedb.php");
$trail = explode('/',$_GET['request']);
$trail = array_filter($trail, function($value){return !empty($value) || $value === 0;});
$catsef = end($trail);

$conn = new Connection();
$stmt = $conn->mysqli->prepare("SELECT id, subcat FROM "._PRE."categories WHERE seftitle = ?");
$stmt->bind_param('s', $catsef);
$stmt->execute();
$stmt->bind_result($id, $subcat);

if($stmt->fetch()) {
  $stmt->free_result();
  $stmt->close();
  
  echo '{ "version" : "1.0", "artObjects" : [';

  if($subcat!=0) {
    $stmt = $conn->mysqli->prepare("SELECT seftitle FROM "._PRE."categories WHERE id = ?");
    $stmt->bind_param('s', $subcat);
    $stmt->execute();
    $stmt->bind_result($seftitle_sub);
    $stmt->fetch();
    $stmt->free_result();
    $stmt->close();
  }
  if( _OVERVIEW_ORDERED_BY == 'custom' ) {
    $order_column = "artorder";
  } else {
    $order_column = "date";
  }
  if( _OVERVIEW_ORDER == 'desc' ) {
    $order_type = "desc";
  } else {
    $order_type = "asc";
  }
  $stmt = $conn->mysqli->prepare("SELECT text, seftitle FROM "._PRE."articles WHERE category = ? order by ".$order_column." ".$order_type);
  $stmt->bind_param('i', $id );
  $stmt->execute();
  $stmt->bind_result($text,$seftitle);

  $x=0;
  while($stmt->fetch()) {
    if($text!==NULL && $text!='' && !empty($text)) {
      libxml_use_internal_errors(true);
      $html = new DOMDocument();
      $html -> loadHTML( 
          str_replace(array("\r", "\n"), '', $text),
            LIBXML_NOERROR
            | LIBXML_NOWARNING
            | LIBXML_NOBLANKS
            | LIBXML_NOEMPTYTAG
      );
      $xpath = new DOMXPath($html);
      if($xpath->evaluate("string(//img/@src)") != '') {
        $src = $xpath->evaluate("string(//img/@src)");
        $title = $xpath->evaluate("string(//h1)");
        if($x>0) echo ', ';
        echo '{ "title" : "'.$title.'", "imgsrc" : "'.$src.'", "location" : "'.((!empty($seftitle_sub) && $seftitle_sub!='')? $seftitle_sub . "/" : "" ) . $catsef . "/" . $seftitle.'" }';
        $x++;
      }
      libxml_use_internal_errors(false);
    }
  }
  echo "]}";
} else
  echo "Category not found";

$stmt->free_result();
$stmt->close();
$conn->mysqli->close();
?>
