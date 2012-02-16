<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IS4C.

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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title></title>
    </head>
    <body onLoad='document.refundform.refundamount.focus();'>
<?
if (!function_exists("printfooter")) include("drawscreen.php");
if (!function_exists("pDataConnect")) include("connect.php");


printheaderb();
    echo "<tr><td height='295' width='640' align='center' valign='center'>";
    echo "<table border='0' cellpadding='0' cellspacing='0'>";
    echo "<tr><td bgcolor='#004080' height='150' width='260' valign='center' align='center'>";
    echo "<font face='arial' size='-1' color='white'>";
    echo "<p style=\"text-align: center\">Refund<br />(leave amount blank to cancel)</p>";
    echo "<form action='refundprocess.php' method='post' autocomplete='off' name='refundform'>";
	echo "<table>";
	echo '<tr><td>Amount:</td><td><input type="text" name="refundamount" size="10" /></td></tr>';
	$dptselect = '<select name="dptmt">';
        $query = "select * from departments ";
        $db = pDataConnect();
        $result = sql_query($query, $db);
		while ($row = sql_fetch_array($result)) {
	$dptselect .= '<option value="'.$row['dept_no'].'">'.$row['dept_name'].'</option>';
		}
	$dptselect .= '</select>';
	echo "<tr><td>Department: </td><td>$dptselect</td></tr>";
	echo '<tr><td>Reason:</td><td><input type="text" name="reason" size="20" /></td></tr>';
	echo '<tr><td colspan="2" align="center"><input type="submit" name="Refund" value="Refund"></td></tr>';
	echo '</table></form>';
    echo "";
    echo "</font></center></td></tr></table></td></tr>";
?>
<?php
$_SESSION["scan"] = "noScan";
$_SESSION["beep"] = "noBeep";
printfooter();

?>

    </body>
</html>
