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
    <body onLoad='document.form.dobinput.focus();'>
        <table border='0' cellpadding='0' cellspacing='0'>
            <?php
                if (!function_exists("printheaderb")) include("drawscreen.php");
                printheaderb();

            ?>
            <tr>
                <td height='300' width='640' align='center' colspan='2' valign='center'>
                    <table border='0' cellpadding='0' cellspacing='0'>
                        <tr>
                            <td bgcolor='#004080' height='150' width='260' valign='center' align='center'>
                                <center>
                                    <font face='arial' color='white'>
<?php
				if ($_REQUEST['invalidinput']) {
					echo "Invalid date of birth <br /> Please use format MMDDYYYY<br />";
				}
?>
                                        <b>Age Identification</b>
                                        <form name='form' method='post' autocomplete='off' action='dobauth.php'>
                                            <input Type='text' name='dobinput' tabindex='0' onBlur='document.form.dobinput.focus();'>
                                        </form>
                                        <p>
                                            <font face='arial' color='white'>
                                                please enter date of birth (MMDDYYYY)
                                            </font>
                                        </p>
                                    </font>
                                </center>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <?php
            $_SESSION["beep"] = "noScan";
            printfooter();
        ?>
    </body>
