<?php
header("Content-Type: application/json;charset=utf-8");
//header("Access-Control-Allow-Origin: *");
/*
//Jeremy Goldstein 
//Minuteman Library Network
//jgoldstein@minlib.net
//
// Modified from code produced by
// Ray Voelker
// University of Dayton Libraries
// 300 College Park Dayton, OH 45419-1360
// rvoelker1@udayton.edu
// ray.voelker@gmail.com
// https://github.com/rayvoelker/DIY_API
//  If you have any questions or comments 
//  about this script, page or feature, please
//  feel free to contact me.
//
*/
// sanitize the input
ini_set('default_mimetype','Content-Type: application/json');

if ( isset($_GET['barcode']) )  {
	header("Content-Type: application/json");
	// ensure that the barcode value is formatted somewhat sanely
	if( strlen($_GET['barcode']) > 14 ) {
		//we don't expect barcodes to be longer than 14 numeric characters
		die();
	}
	
	$barcode = $_GET['barcode'];
}
else{
	//send an empty object and then quit the script
	echo "{}";
	die();
}
/*
include file (item_barcode.php) supplies the following
arguments as the example below illustrates :
	$dsn = "host=sierra-db.library.net port=1032 dbname=iii user= password= connect_timeout=5";
*/
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/sierra_api.php');

$dbconn = pg_connect($dsn)
    or die('Could not connect: ' . pg_last_error());
$sql = "
SELECT
upper(p.call_number_norm || COALESCE(' ' || v.field_content, '') ) as call_number_norm,
i.location_code,
i.item_status_code,
b.best_title,
i.last_checkout_gmt,
i.checkout_total,
i.renewal_total,
i.record_creation_date_gmt,
id2reckey(i.id)||'a' as rec_num,
id2reckey(b.bib_record_id)||'a' as brec_num
FROM
sierra_view.phrase_entry				AS e
JOIN
sierra_view.item_record_property		AS p
ON
  e.record_id = p.item_record_id
JOIN sierra_view.item_view			AS i
ON
  i.id = p.item_record_id
-- This JOIN will get the Title and Author from the bib
JOIN
sierra_view.bib_record_item_record_link	AS l
ON
  l.item_record_id = e.record_id
JOIN
sierra_view.bib_record_property			AS b
ON
  l.bib_record_id = b.bib_record_id
  
LEFT OUTER JOIN
sierra_view.varfield					AS v
ON
  (i.id = v.record_id) AND (v.varfield_type_code = 'v')
WHERE
e.index_tag || e.index_entry = 'b' || '" . $barcode . "'";

$result = pg_query($sql) or die('Query failed: ' . pg_last_error());
$row = pg_fetch_array($result, null, PGSQL_ASSOC);

echo json_encode($row);
// Free resultset
pg_free_result($result);


// Closing connection
pg_close($dbconn);

?>
