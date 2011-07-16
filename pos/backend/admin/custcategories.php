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
	require_once($_SERVER["DOCUMENT_ROOT"].'/src/htmlparts.php');
	require_once($_SERVER["DOCUMENT_ROOT"].'/src/formlib.inc');
	require_once($_SERVER["DOCUMENT_ROOT"]."/define.conf");

	function db_quote($str) {
		return "'" . mysql_real_escape_string($str) . "'";
	}

	$html='<!DOCTYPE HTML>
<html>
	<head>';
	
	$html.=head();
	
	$html.='
		<title>IS4C - Custom Categories</title>
	</head>
	<body>';
	
	$html.=body();

	$action = $_REQUEST['action'];

	$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
	mysql_select_db('is4c_op',$link);


	$html.='<div id="page_panel">';
	$html .= "<h2>Custom Items Categories</h2>";

	// If the Save button was pressed, loop through all the categories and put the new data in the database
	if ($_REQUEST['Save']) {

		// load 'em and loop through em
		$query = "SELECT id, range_start, range_end, title, showit FROM custcategories ORDER BY range_start ASC ";
		$res = mysql_query($query, $link);
		$categories = array();
		while ($row = mysql_fetch_assoc($res)) {
			$categories[] = $row;
		}


		foreach ($categories as $category) {
			$range_start = $_REQUEST['start_'.$category['id']];
			$range_end = $_REQUEST['end_'.$category['id']];
			$title = $_REQUEST['title_'.$category['id']];
			$showit = $_REQUEST['showit_'.$category['id']];
			$deleteit = $_REQUEST['delete_'.$category['id']];
			if ($deleteit) {
				$sql = "DELETE FROM custcategories WHERE id = " . $category['id'];
				$res = mysql_query($sql, $link);
			} else {
				if (is_numeric($range_start) && is_numeric($range_end)) {
					$sql = "UPDATE custcategories SET range_start=". $range_start.
						", range_end=" . $range_end .
						", title='" . mysql_real_escape_string($title) . "'".
						", showit=" . ($showit ? "1" : "0") .
						" WHERE id = " . $category['id'];
					$res = mysql_query($sql, $link);
				} else {
					echo "Warning: Did not save '" . $title . "' because range entered not a number.<br />";
				}
			}
		}

		if (is_numeric($_REQUEST['start_new']) &&
			is_numeric($_REQUEST['end_new']) &&
			$_REQUEST['title_new'] != "")  {
			$sql = "INSERT INTO custcategories (range_start, range_end, title, showit)" .
				" VALUES (".$_REQUEST['start_new'].','.
				$_REQUEST['end_new'].','.
				"'".mysql_real_escape_string($_REQUEST['title_new'])."',".
				($_REQUEST['showit_new']?1:0).")";
			$res = mysql_query($sql, $link);
		}
	}


	// load 'em fresh from the database to show!
	$query = "SELECT id, range_start, range_end, title, showit FROM custcategories ORDER BY range_start ASC ";
	$res = mysql_query($query, $link);
	$categories = array();
	while ($row = mysql_fetch_assoc($res)) {
		$categories[] = $row;
	}
	
	$html .= startform();
	$html .= "<table>";
	$html .= "<tr><th colspan=\"2\">PLU Range</th><th>Title</th><th>Active?</th><th>Delete?</th></tr>";
	foreach ($categories as $category) {
		$id = $category['id'];
		$html .= "<tr>" .
			"<td>" . textbox("start_".$id, $category['range_start'], 6, 20) . "</td>" .
			"<td>" . textbox("end_".$id, $category['range_end'], 6, 20) . "</td>" .
			"<td>" . textbox("title_".$id, $category['title'], 30, 200) . "</td>" .
			"<td>" . checkbox("showit_".$id, $category['showit']) . "</td>" .
			"<td>" . checkbox("delete_".$id, $category['delete']) . "</td>" .
			"</tr>";
	}

	$html .= '<tr><td colspan="4">' .  submitbox("Save", "Save") .  '</td>'.'</tr>';
	$html .= '<tr><td colspan="4">' . '<br /><hr />Add New:' . '</td>'.'</tr>';
	$html .= "<tr>" .
		"<td>" . textbox("start_new", "", 6, 20) . "</td>" .
		"<td>" . textbox("end_new", "", 6, 20) . "</td>" .
		"<td>" . textbox("title_new", "", 30, 200) . "</td>" .
		"<td>" . checkbox("showit_new", "") . "</td>" .
		"<td>" . "" . "</td>" .
		"</tr>";

	$html .= "</table>";
	$html .= submitbox("Save", "Save");
	$html .= endform();
	$html .= '</div>';
	
	$html.=foot();
	
	$html.='
	</body>
</html>';

	print_r($html);


?>
