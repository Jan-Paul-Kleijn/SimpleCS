<? 
error_reporting(E_ALL);
ini_set('display_errors', '1');
$r = explode('/',$_GET['request']);
switch($r[0]) {
  case 'artistry' :
    include ("artistry.php");
    break;
  case 'gordianpage' :
    include ("loadbatch.php");
    break;
  default :
    echo "API not found with GET-request <strong>".$_GET['request']."</strong>";
}
?>
