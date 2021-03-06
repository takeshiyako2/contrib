#!/usr/bin/php
<?php
/**
 * Moodle Modules Total
 * Munin plugin to count total modules instances
 *
 * It's required to define a container entry for this plugin in your
 * /etc/munin/plugin-conf.d/moodle.conf
 *
 * @example Example entry for configuration:
 * [moodle*]
 * env.type mysql
 * env.db moodle
 * env.user mysql_user
 * env.pass mysql_pass
 * env.host localhost
 * env.port 3306
 * env.table_prefix mdl_
 *
 * @author Arnaud Trouvé <ak4t0sh@free.fr>
 * @version 1.0 2014
 *
 */

$dbh = null;
$db = getenv('db');
$type = getenv('type');
$host = getenv('host');
$user = getenv('user');
$pass = getenv('pass');
$table_prefix = getenv('table_prefix');
$port = getenv('port');
if (!$port)
    $port = 3306;


try {
    $dsn = $type . ':host=' . $host . ';port=' . $port . ';dbname=' . $db;
    $dbh = new PDO($dsn, $user, $pass);
} catch (Exception $e) {
    echo "Connection failed\n";
    exit(1);
}
//All users
$data = array();
if (($stmt = $dbh->query("SELECT m.name as modulename, count(cm.id) as moduleinstance FROM {$table_prefix}modules m, {$table_prefix}course_modules cm WHERE cm.module=m.id GROUP BY cm.module ORDER BY m.name ASC")) != false) {
    $data = $stmt->fetchAll(PDO::FETCH_OBJ);
}

if (count($argv) === 2 && $argv[1] === 'config') {
    echo "graph_title Moodle Modules\n";
    echo "graph_args --base 1000 --lower-limit 0\n";
    echo "graph_vlabel modules\n";
    echo "graph_category Moodle\n";
    echo "graph_scale no\n";
    echo "graph_info Displays the sum of module, as well as module instance number by type, in your Moodle site\n";
    echo "graph_total.label total\n";
    $draw = "AREA";
    foreach($data as $entry) {
        echo "modules_".$entry->modulename.".label ".$entry->modulename."\n";
        echo "modules_".$entry->modulename.".min 0\n";
        echo "modules_".$entry->modulename.".draw $draw\n";
        $draw = "STACK";
    }
    exit(0);
}
foreach($data as $entry) {
    echo "modules_".$entry->modulename.".label ".$entry->modulename."\n";
    echo "modules_".$entry->modulename.".value ".$entry->moduleinstance."\n";
}
