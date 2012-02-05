<?php
/*******************************************************************************

    Copyright 2011 Missoula Food Co-op, Missoula, Montana.

    This file is part of Fannie.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require_once('../src/formlib.inc');
require_once('../src/htmlparts.php');

$page_title = "Reporting";
$header = "Product Sales Report";
// include ('../src/header.html');

$html='<!DOCTYPE HTML>
<html>
<head>';

$html .= '<script type="text/javascript" src="../lib/jquery.js"></script>';
$html .= '<script type="text/javascript" src="../lib/jquery-ui.js"></script>';
$html .= <<<SCRIPT
<script language="Javascript">
$(document).ready(function() {
	$('#startdate').datepicker({dateFormat: 'yy-mm-dd'});
	$('#enddate').datepicker({dateFormat: 'yy-mm-dd'});
});

</script>

<link type="text/css" rel="stylesheet" href="../lib/jquery-ui.css" />

SCRIPT;
	
$html.=head();
	
// $html .= '<script src="../src/CalendarControl.js" language="javascript"></script>';

$html.='
	<title>IS4C - Product Sales</title>
</head>
<body>';
	
$html.=body();


if (isset($_POST['submit'])) {
	// do report
	// show how many of each product sold within given dates
	// dlog.upc
	$startdate = $_REQUEST['startdate'];
	$enddate = $_REQUEST['enddate'];

	$link = mysql_connect("localhost", "backend", "is4cbackend");
	if (!$link) {
		echo "couldn't connect to is4c_log.";
		exit;
	}
	$success = mysql_select_db('is4c_log', $link);
	if (!$success) {
		echo "Couldn't select log db: " . mysql_error();
		exit;
	}

/*
	$link = mysql_connect("localhost", "backend", "ils4cbackend");
	if (!$link) {
		echo "couldn't connect to is4c_op.";
		exit;
	}
	$success = mysql_select_db('is4c_op', $link);

	if (!$success) {
		echo "Couldn't select op db: " . mysql_error();
		exit;
	} */

	$ourwhere = "";
	$ourwhere .= " trans_status <> 'D' AND trans_status <> 'X' ";
	if ($startdate && $enddate) {
		$ourwhere .= " AND `datetime` >= '" . mysql_real_escape_string($startdate) . " 00:00:00' AND `datetime` <= '" . mysql_real_escape_string($enddate) . " 23:59:59'";
	}
	$query = "SELECT di.upc AS upcnum, count(di.upc) AS cnt, prod.description AS proddesc, vendor_name, ccat.title AS custcattitle FROM dtransactions di LEFT JOIN is4c_op.products prod ON di.upc = prod.upc LEFT JOIN is4c_op.vendors vend ON prod.vendor_id = vend.vendor_id LEFT JOIN is4c_op.custcategories ccat ON di.upc >= range_start AND di.upc <= range_end AND showit = true WHERE " . $ourwhere . " AND prod.description IS NOT NULL group by di.upc order by vendor_name DESC, ccat.title ASC, prod.description ASC";

	error_log("prodsales.php running query: " . $query);
	$res = mysql_query($query, $link);
	if (!$res) {
		echo "error: " . mysql_error() . "<br />\n";
	}

	$html .= "<h2>Product Sales Report</h2>";

	if ($startdate && $enddate) {
		$html .= "<h3>$startdate - $enddate</h3>";
	} else {
		$html .= "<h3>All Records</h3>";
	}

	$firstvendor = true;
	$firstcat = true;
	
	$html .= "<table >";
	// $html .= '<tr><th align="left">UPC</th><th align="left">Product</th><th align="left">Sales</th><th align="left">Vendor</th><th align="left">Category</th></tr>';
	while ($row = mysql_fetch_assoc($res)) {
		error_log("got row: " . var_export($row, true));

		$upc = $row['upcnum'];
		$cnt = $row['cnt'];
		$description = $row['proddesc'];
		$vendor = $row['vendor_name'];
		$custcat = $row['custcattitle'];

		
		if ($vendor != $lastvendor || $firstvendor) {
			if ($vendor != "")
				$html .= "<tr><td colspan=\"4\" align=\"center\"><b>$vendor</b></td></tr>";
			else 
				$html .= "<tr><td colspan=\"4\" align=\"center\"><b>UNKNOWN VENDOR</b></td></tr>";

			$html .= '<tr><th align="left">UPC</th><th align="left">Product</th><th align="left">Sales</th><th align="left">Vendor</th></tr>';
			$firstvendor = false;
			$firstcat = true;
			$lastcustcat = "";
		}

		if ($custcat != "" && ($custcat != $lastcustcat || $firstcat)) {
			$html .= "<tr><td colspan=\"4\" align=\"left\"><b>$custcat</b></td></tr>";
			$firstcat = false;
		}



		if (is_numeric($upc)) {
			$html .= tablerow($upc, $description, $cnt, $vendor);
		}

		$lastvendor = $vendor;
		$lastcustcat = $custcat;
	}

	$html .= "</table>";

} else {
	$html .= startform();
	$html .= "<table>";
	$html .= tablerow("Start Date :", textbox("startdate", "", "14", "20", array("onclick" => "showCalendarControl(this)")). " (YYYY-MM-DD)");
	$html .= tablerow("End Date :", textbox("enddate", "", "14", "20", array("onclick" => "showCalendarControl(this)")). " (YYYY-MM-DD)");
	$html .= hiddeninput("submit", "1");
	$html .= "</table>";
	$html .= '<input type="submit" value="Run Report" />';
	$html .= endform();

}

$html.=foot();
	
$html.='
	</body>
</html>';


echo $html;

?>
