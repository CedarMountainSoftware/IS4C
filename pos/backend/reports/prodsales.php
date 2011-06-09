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
	if ($startdate && $enddate) {
		$ourwhere = " tdate >= '" . mysql_real_escape_string($startdate) . "' AND tdate <= '" . mysql_real_escape_string($enddate) . "'";
	}

	$query = "SELECT DISTINCT upc AS upcnum, (SELECT count(upc) FROM dlog di WHERE di.upc = upcnum " . ( $ourwhere ? "AND $ourwhere" : "") . ") AS cnt FROM dlog " . ($ourwhere ? "WHERE $ourwhere" : "");
//	echo $query . "<br \>\n";
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

	
	$html .= "<table >";
	$html .= "<tr><th>UPC</th><th>Product</th><th>Sales</th></tr>";
	while ($row = mysql_fetch_assoc($res)) {
		$upc = $row['upcnum'];
		$cnt = $row['cnt'];

		if (is_numeric($upc)) {
			$q2 = "SELECT is4c_op.products.description FROM is4c_op.products WHERE upc = " . $upc;
			$res2 = mysql_query($q2, $link);
			if (!$res2) {
				echo "error: " . mysql_error() . "<br />\n";
			}

			if ($row2 = mysql_fetch_assoc($res2)) {
				$description = $row2['description'];
				$html .= tablerow($upc, $description, $cnt);
			} else {
				$html .= tablerow($upc, "[product not found]", $cnt);
			}
		}
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
