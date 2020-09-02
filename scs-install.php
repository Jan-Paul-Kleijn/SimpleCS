<?php

require_once "scs-config.php";
require_once "includes/tables-defaults.php";

$obj = new CreateTablesDefaults();

// Create the system tables
$obj->createTables();

// Insert the default settings
$obj->setDefaults();

?>
