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
$header = "Custom Items Report";
// include ('../src/header.html');

$html='<!DOCTYPE HTML>
<html>
<head>';
	
$html.=head();
	
// $html .= '<script src="../src/CalendarControl.js" language="javascript"></script>';

$html.='
	<title>IS4C - Custom Items</title>
</head>
<body>';
	
$html.=body();


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

	$query = "SELECT  upc, description from products where upc < 99999 ORDER BY upc asc";
//	echo $query . "<br \>\n";
	$res = mysql_query($query, $link);
	if (!$res) {
		echo "error: " . mysql_error() . "<br />\n";
	}

	$html .= "<h2>Custom Items</h2>";

	
	$html .= "<table >";
	$html .= "<tr><th>UPC</th><th>Product</th></tr>";
	while ($row = mysql_fetch_assoc($res)) {
		$upc = $row['upc'];
		$desc = $row['description'];

		$html .= tablerow("<a href=\"/item/?a=search&q=".$upc."&t=upc\">$upc</a>", $desc);
	}

	$html .= "</table>";


$html.=foot();
	
$html.='
	</body>
</html>';


echo $html;

?>
