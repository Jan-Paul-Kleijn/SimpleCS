<?
define('SERVER', db('dbhost'));
define('USER', db('dbuname'));
define('PASS', db('dbpass'));
define('DB', db('dbname'));

class Connection{
    /**
     * @var Resource 
     */
    var $mysqli = null;

    function __construct(){
        try{
            if(!$this->mysqli){
                $this->mysqli = new MySQLi(SERVER, USER, PASS, DB);
                if(!$this->mysqli)
                    throw new Exception('Could not create connection using MySQLi', 'NO_CONNECTION');
            }
        }
        catch(Exception $ex){
            echo "ERROR: ".$e->getMessage();
        }
    }
}
?>
