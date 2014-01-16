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
require_once('../src/misc.inc');

$page_title = "Reporting";
$header = "Active Items Report";
// include ('../src/header.html');

$html='<!DOCTYPE HTML>
<html>
<head>';
	
$html.=head();
	
$html.='
	<title>IS4C - Active Items</title>
</head>
<body>';
	
$html.=body();


if (isset($_POST['submit'])) {
	// do report
	// show how many of each product sold within given dates
	// dlog.upc
	$csvexport = $_REQUEST['csvexport'] ? 1 : 0;

	$csvdata = array();

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
	$query = "SELECT prod.upc AS upcnum, prod.description AS proddesc, dept_name, vendor_name, ccat.title AS custcattitle FROM is4c_op.products prod LEFT JOIN is4c_op.departments ON prod.department = departments.dept_no LEFT JOIN is4c_op.vendors vend ON prod.vendor_id = vend.vendor_id LEFT JOIN is4c_op.custcategories ccat ON prod.upc >= range_start AND prod.upc <= range_end AND showit = true WHERE inUse = 1 AND prod.description IS NOT NULL group by prod.upc order by vendor_name DESC, ccat.title ASC, prod.description ASC";

	$res = mysql_query($query, $link);
	if (!$res) {
		echo "error: " . mysql_error() . "<br />\n";
	}

	$html .= "<h2>Active Items Report</h2>";

	$html .= "<h3>All Records</h3>";

	$firstvendor = true;
	$firstcat = true;
	
	$html .= "<table >";
	while ($row = mysql_fetch_assoc($res)) {
		error_log("got row: " . var_export($row, true));

		$upc = $row['upcnum'];
		$description = $row['proddesc'];
		$vendor = $row['vendor_name'];
		$custcat = $row['custcattitle'];
		$dept = $row['dept_name'];

		
		if ($vendor != $lastvendor || $firstvendor) {
			if ($vendor != "")
				$html .= "<tr><td colspan=\"4\" align=\"center\"><b>$vendor</b></td></tr>";
			else 
				$html .= "<tr><td colspan=\"4\" align=\"center\"><b>UNKNOWN VENDOR</b></td></tr>";

			$html .= '<tr><th align="left">UPC</th><th align="left">Product</th><th align="left">Department</th><th align="left">Vendor</th></tr>';
			$firstvendor = false;
			$firstcat = true;
			$lastcustcat = "";
		}

		if ($custcat != "" && ($custcat != $lastcustcat || $firstcat)) {
			$html .= "<tr><td colspan=\"4\" align=\"left\"><b>$custcat</b></td></tr>";
			$firstcat = false;
		}


		if (is_numeric($upc)) {
			if (!$csvexport)
				$html .= tablerow($upc, $description, $dept, $vendor);
			else
				$csvdata[] = array($upc, $description, $dept, $vendor);
		}

		$lastvendor = $vendor;
		$lastcustcat = $custcat;
	}

	if ($csvexport) {
		$filename = "active_items_report_" . $startdate . '_to_' . $enddate . ".csv";
		export_csv($filename, $csvdata);
		exit;
	}

	$html .= "</table>";

} else {
	$html .= "<h2>Active Items Report</h2>";
	$html .= startform();
	$html .= "<table>";
	$html .= tablerow("<label for=\"csvexport\">CSV Export</label>: " , checkbox("csvexport", 0));
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
