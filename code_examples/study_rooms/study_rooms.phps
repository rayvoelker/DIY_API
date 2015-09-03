<?php
//replace all the non alphanumeric characters from the callback function name
if(isset( $_GET['callback'] )) {
	$callback = preg_replace("/[^a-zA-Z0-9\s]/", "", $_GET['callback']);
}

/*
include file (sqlstudycar.php) supplies the following
arguments as the example below illustrates :
	$username = "username";
	$password = "password";

	$dsn = "pgsql:"
		. "host=sierra-db.school.edu;"
		. "dbname=iii;"
		. "port=1032;"
		. "sslmode=require;"
		. "charset=utf8;"
*/

//reset all variables needed for our connection
$username = null;
$password = null;
$dsn = null;
$connection = null;

require_once($_SERVER['DOCUMENT_ROOT'] . '/../includes/sql/sqlstudycar.php');

$time_start = microtime(true);

try {
	//$connection = new PDO($dsn, $username, $password);
	$connection = new PDO($dsn, $username, $password);
}

catch ( PDOException $e ) {
	$connection = null;
	echo "problem connecting to database...\n";
	error_log('PDO Exception: '.$e->getMessage());
	$connection = null;
	exit(1);
}

//set output to utf-8
$connection->query('SET NAMES UNICODE');

$sql = '
SELECT
i.item_status_code,
c.checkout_gmt,
c.due_gmt,
p.best_title,
p.best_title_norm

FROM
sierra_view.item_record									i

LEFT OUTER JOIN sierra_view.checkout					c
ON (i.record_id = c.item_record_id)

LEFT OUTER JOIN sierra_view.bib_record_item_record_link	l
ON (i.record_id = l.item_record_id)

JOIN sierra_view.bib_record								b
ON (l.bib_record_id = b.record_id)

JOIN sierra_view.bib_record_property					p
ON (b.record_id = p.bib_record_id)

WHERE
(i.itype_code_num = 69 OR i.itype_code_num = 71) 
AND i.is_suppressed = FALSE
AND b.is_suppressed = FALSE

ORDER BY
p.best_title_norm ASC
';

$statement = $connection->prepare($sql);
$statement->execute();
$row = $statement->fetchAll(PDO::FETCH_ASSOC);

//we want to return a better status than what item_status_code gives us
foreach ($row as &$value) {
    if( ($value['item_status_code'] != '-') || ($value[due_gmt] != null) )
		$value['available'] = false;
	else
		$value['available'] = true;
}

$num_results = count($row);

//set some extra info into the first result... 
//  be careful, as this actaully creates a single result, even though there may be 
//  no actual results from the query
$row[0]['num_results'] = $num_results;
$row[0]['queryTime'] = ( microtime(true) - $time_start );
$row[0]['created'] = time();
$row[0]['query'] = $sql;

if(isset ($callback)) {
	header('Content-Type: text/javascript; charset=utf8');
	echo $callback . '(' . json_encode($row) . ')';
}
else {
	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf8'); 
	echo json_encode($row);
}

$row = null;
$statement = null;
$connection = null;

?>