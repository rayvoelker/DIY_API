<?php
header("Content-Type: application/json;charset=utf-8");
//header("Access-Control-Allow-Origin: *");
/*
// 
// Ray Voelker
// University of Dayton Libraries
// 300 College Park Dayton, OH 45419-1360
// rvoelker1@udayton.edu
// ray.voelker@gmail.com
//  If you have any questions or comments 
//  about this script, page or feature, please
//  feel free to contact me.
//
*/
// sanitize the input
ini_set('default_mimetype','Content-Type: application/json');

if ( isset($_GET['location']) )  {
	header("Content-Type: application/json");
	// ensure that the barcode value is formatted somewhat sanely
	if(strlen($_GET['location']) < 2 OR strlen($_GET['location']) > 3) {
		//we don't expect barcodes to be longer than 12 alpha-numeric characters
		//although, 99.9 % of our scannable barcodes are 10 digit, I'm leaving some breathing room
		die();
	}
	// barcodes are ONLY alpha-numeric ... strip anything that isn't this.
	$location = $_GET['location'];
}
else{
	//send an empty object and then quit the script
	echo "{}";
	die();
}
/*
include file (item_barcode.php) supplies the following
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
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/sierra_api.php');

$dbconn = pg_connect($dsn)
    or die('Could not connect: ' . pg_last_error());
$sql = "
SELECT
CAST(SUM(i.price) AS MONEY) AS value,
COUNT(c.id) AS circ_count,
(CAST(SUM(i.price) AS MONEY) / COUNT(c.id)) as cost_per_circ,
localtimestamp - interval '7 days' AS start_time,
localtimestamp AS end_time
FROM
sierra_view.circ_trans c
JOIN
sierra_view.item_record i
ON
c.item_record_id = i.id
WHERE
c.op_code IN ('o', 'r')
AND
c.transaction_gmt >= (localtimestamp - interval '7 days')
AND
i.location_code LIKE '" . $location . "%'";

$result = pg_query($sql) or die('Query failed: ' . pg_last_error());
$row = pg_fetch_array($result, null, PGSQL_ASSOC);

echo json_encode($row);
// Free resultset
pg_free_result($result);


// Closing connection
pg_close($dbconn);

?>
