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

include_once("connect.php");
if (!function_exists("returnHome")) include("maindisplay.php");
if (!function_exists("receipt")) include("clientscripts.php");
if (!function_exists("upcscanned")) include("upcscanned.php");

// $_SESSION["away"] = 1;

$dobinput = strtoupper(trim($_POST["dobinput"]));

if (! (strlen($dobinput) == 8)) {
	header("Location: agecheck.php?invalidinput=1");
	exit;
}

$dobmonth = substr($dobinput, 0, 2);
$dobday = substr($dobinput, 2, 2);
$dobyear = substr($dobinput, 4, 4);

$dobtime = mktime(0, 0, 0, $dobmonth, $dobday, $dobyear);


// subtract 21 years from present time
$then = date_sub(new DateTime(), date_interval_create_from_date_string('21 years'));
$thentime = $then->format("U");

error_log("comparing 21 years ago: " . $thentime . "  with dob entered: " . $dobtime);


if ($dobtime <= $thentime) {
	// alll ok
	$_SESSION['carded'] = 1;
	upcscanned($_SESSION['alcentered']);
	gohome();
} else  {
	$_SESSION['errorBox'] = "This customer is not old enough to buy alcohol";
	gohome();
//	boxMsg("This customer is not old enough to buy alcohol.");
//	header("Location:ageinvalid.php");
}


