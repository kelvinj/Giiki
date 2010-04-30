<?php
define('ROOT', dirname(__FILE__));
define('FU_DB_DEBUG', false);
ini_set('include_path', str_replace('.:', '.:'.ROOT.':', ini_get('include_path')));
include 'giiki/init.php';
app_debug();
