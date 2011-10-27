<?php
/*******************************************************************************

    Copyright 2011 Missoula Food Co-op, Missoula, Montana.

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
$header = "Custom Items Report";
// include ('../src/header.html');

$html='<!DOCTYPE HTML>
<html>
<head>';
	
$html.=head();
	
// $html .= '<script src="../src/CalendarControl.js" language="javascript"></script>';

$html.='
	<title>IS4C - Custom Item PLU codes</title>
</head>
<body>';
	
$link = mysql_connect("localhost", "backend", "is4cbackend");
if (!$link) {
	echo "couldn't connect to is4c_op.";
	exit;
}
$success = mysql_select_db('is4c_op', $link);

if (!$success) {
	echo "Couldn't select op db: " . mysql_error();
	exit;
} 

if ($_POST['doreport']) {


	$saveeditactive = $_REQUEST['saveeditactive'] ? 1 : 0;
	if ($saveeditactive) {
		foreach ($_REQUEST as $param => $value) {
			if (preg_match("/^editupc_(\d+)$/", $param, $matches)) {
				$thisupc  = $matches[1];
				$thisactive = $_REQUEST['activecheck_' . $thisupc] ? 1 : 0;
				$thisprice = $_REQUEST['itemprice_' . $thisupc];
				// echo "update upc: $thisupc; thisactive = $thisactive, price = $thisprice " .  " <br />\n";
				
				$query = "UPDATE products SET inUse = " . $thisactive . ", normal_price = " . mysql_real_escape_string($thisprice) . " WHERE upc = " . mysql_real_escape_string($thisupc);
				$res = mysql_query($query, $link);
				if (!$res) {
					echo "error: " . mysql_error() . "<br />\n";
				}
			}
		}
	}




	$sections = array();

	$catwhere = ($_REQUEST['categoryprint'] != 0) ? (" WHERE id = " . $_REQUEST['categoryprint'] ) : "";

	$query = "SELECT id, range_start, range_end, title FROM custcategories $catwhere ORDER BY " . ($_POST['sortorder'] == "numeric" ? "range_start" : " title ") . " ASC";
	$res = mysql_query($query, $link);
	if (!$res) {
		echo "error: " . mysql_error() . "<br />\n";
	}
	while ($row = mysql_fetch_assoc($res)) {
//		$sections[] = array($row['range_start'], $row['range_end'], $row['title']);
		$sections[] = $row;
	}

	$editactive = $_REQUEST['editactive'] ? 1 : 0;


	$sectionitems = array();
	foreach ($sections as $section) {
		$query = "SELECT  upc, description, inUse, normal_price from products " .
			"where upc >= " . $section['range_start'] . " AND upc <= " . $section['range_end'] .
			(!$editactive ? " AND inUse = 1" : "") .
			" ORDER BY " . ($_POST['sortorder'] == "numeric" ? "upc asc" : "description asc");
		$res = mysql_query($query, $link);
		if (!$res) {
			echo "error: " . mysql_error() . "<br />\n";
		}

		$custitems = array();
		while ($row = mysql_fetch_assoc($res)) {
			$upc = $row['upc'];
			$desc = $row['description'];

			$custitems[$upc] = array("desc" => $desc, "active" => $row['inUse'], "price" => $row['normal_price']);
		}
		$sectionitems[] = $custitems;
	}

	$html .= "<h2>Custom Item PLU Codes</h2>";
	for ($idx = 0; $idx < count($sections); $idx++) {
		if (count($sectionitems[$idx]) > 0) {
			$html .= "<h3>" . $sections[$idx]['title'] . "</h3>";

			if ($editactive) {
				$html .= startform();
				$html .= hiddeninput("saveeditactive", 1);
				$html .= hiddeninput("sortorder", $_REQUEST['sortorder']);
				$html .= hiddeninput("categoryprint", $_REQUEST['categoryprint']);
				$html .= hiddeninput("editactive", 1);
				$html .= hiddeninput("doreport", 1);
			}

			$custitems = $sectionitems[$idx];
			$html .= "<table border=\"1\" cellpadding=\"5\" cellspacing=\"5\">";

			if ($editactive) {
				$html .= "<tr>";
				$html .= "<th>PLU</th><th>Description</th><th>Active</th><th>Price</th>";
				$html .= "</tr>";
			}

			foreach ($custitems as $upc => $data) {
				$desc = $data['desc'];
				$isactive = $data['active'];
				$itemprice = $data['price'];
				$dispupc = preg_replace("/^0*/", "", $upc);
				$html .= "<tr>" .
					"<td align=\"right\">" . 
					"<a href=\"/item/?a=search&q=".$upc."&t=upc\" style=\"text-decoration: none; color: black\">$dispupc</a>".
					"</td>" .
					"<td>" .
					$desc .
					"</td>".
					( $editactive ?
					(
						"<td>" .
						hiddeninput("editupc_" . $upc, 1) .
						checkbox("activecheck_" . $upc, $isactive) .
						"</td>".

						"<td>" .
						textbox("itemprice_" . $upc, $itemprice, 7, 15) .
						"</td>"
					)
					:"") .
					"</tr>";
			}
			$html .= "</table>";

			if ($editactive) {
				$html .= '<input type="submit" value="Save" />';
				$html .= endform();
			}

		}

	}


$html.=foot();
$html .= "<a href=\"/\">Return to Backend</a>";
$html .= "<br /><br /><br />";
} else {

	$html.=body();


	$query = "SELECT id, range_start, range_end, title, showit FROM custcategories ORDER BY range_start ASC ";
	$res = mysql_query($query, $link);
	$catselect = array();
	$catselect[0] = " -- All Categories -- ";
	while ($row = mysql_fetch_assoc($res)) {
		$catselect[$row['id']] = $row['title'] . " ( " . $row['range_start'] . ' - ' . $row['range_end'] . " ) ";
	}

	$html .= startform();
	$html .= "<table>";
	$html .= tablerow("Sort order:", selectbox("sortorder", "", array("numeric" => "numeric", "alphabetic" => "alphabetic")));
	$html .= tablerow("Category: ", selectbox("categoryprint", "", $catselect));
	$html .= tablerow("Edit Active: ", checkbox("editactive", 0));
	$html .= hiddeninput("doreport", "1");
	$html .= "</table>";
	$html .= '<input type="submit" value="Run Report" />';
	$html .= endform();
}
	
$html.='
	</body>
</html>';


echo $html;

?>
