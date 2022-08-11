//comes handy when api call
ob_flush();
ob_start();
var_dump($_POST);
file_put_contents("dump.txt", ob_get_flush());
