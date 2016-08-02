<?php
/*******************************************************************************

    Copyright 2001, 2004, 2008 Wedge Community Co-op

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
/*------------------------------------------------------------------------------
additem.php is called by the following files:

as include:
    login3.php
    authenticate3.php
    prehkeys.php
    upcscanned.php
    authenticate.php

additem.php is the bread and butter of IS4C. addItem inserts the information
stream for each item scanned, entered or transaction occurence into localtemptrans.
Each of the above follows the following structure for entry into localtemptrans:
    $strupc, 
    $strdescription, 
    $strtransType, 
    $strtranssubType, 
    $strtransstatus, 
    $intdepartment, 
    $dblquantity, 
    $dblunitPrice, 
    $dbltotal, 
    $dblregPrice, 
    $intscale, 
    $inttax, 
    $intfoodstamp, 
    $dbldiscount, 
    $dblmemDiscount, 
    $intdiscountable, 
    $intdiscounttype, 
    $dblItemQtty, 
    $intvolDiscType, 
    $intvolume, 
    $dblVolSpecial, 
    $intmixMatch, 
    $intmatched, 
    $intvoided

Additionally, additem.php inserts entries into the activity log when a cashier 
signs in
-------------------------------------------------------------------------------*/

if (!function_exists("pDataConnect")) include("connect.php");
if (!function_exists("tDataConnect")) include("connect.php");
if (!function_exists("nullwrap")) include("lib.php");
if (!function_exists("truncate2")) include ("lib.php");


//-------insert line into localtemptrans with standard insert string--------------

function addItem($strupc,$strdescription, $strtransType, $strtranssubType, $strtransstatus,
    $intdepartment,$dblcost, $dblquantity, $dblunitPrice, $dbltotal, $dblregPrice, $intscale,
    $inttax, $intfoodstamp, $dbldiscount, $dblmemDiscount, $intdiscountable, $intdiscounttype, 
    $dblItemQtty, $intvolDiscType, $intvolume, $dblVolSpecial, $intmixMatch, $intmatched,
    $intvoided, $intnumflag, $strcharflag, $doublesnap = 0){

	error_log("starting addItem");

    $dbltotal = str_replace(",", "", $dbltotal);        
    $dbltotal = number_format($dbltotal, 2, '.', '');
    $dblunitPrice = str_replace(",", "", $dblunitPrice);
    $dblunitPrice = number_format($dblunitPrice, 2, '.', '');

    if ($_SESSION["refund"] == 1) {
        $dblquantity = (-1 * $dblquantity);
        $dbltotal = (-1 * $dbltotal);
        $dbldiscount = (-1 * $dbldiscount);
        $dblmemDiscount = (-1 * $dblmemDiscount);

        if ($strtransstatus != "V" && $strtransstatus != "D") {
            $strtransstatus = "R" ;
        }
        $_SESSION["refund"] = 0;
    }
    elseif ($_SESSION["void"] == 1) {
        $dblquantity = (-1 * $dblquantity);
        $dbltotal = (-1 * $dbltotal);
        $strtransstatus = "V";
        $_SESSION["void"] = 0;
    }

    $intregisterno = $_SESSION["laneno"];
    $intempno=$_SESSION["CashierNo"];
    $inttransno = $_SESSION["transno"];
    $strCardNo = $_SESSION["memberID"];
    $memType = $_SESSION["memType"];
    $staff = $_SESSION["isStaff"];

    $db = tDataConnect();

    if ($_SESSION["DBMS"] == "mssql") {
        $datetimestamp = strftime("%m/%d/%y %H:%M:%S %p", time());
    }
    else {
        $datetimestamp = strftime("%Y-%m-%d %H:%M:%S", time());
    }

    $_SESSION["datetimestamp"] = $datetimestamp;
    $_SESSION["LastID"] = $_SESSION["LastID"] + 1;
    
    $trans_id = $_SESSION["LastID"];

    $strqinsert = "INSERT into localtemptrans (datetime, register_no, emp_no, trans_no, upc, description, trans_type, "
                ."trans_subtype, trans_status, department, quantity, unitPrice, total, regPrice, scale, tax, "
              ."foodstamp, discount, memDiscount, discountable, discounttype, ItemQtty, volDiscType, volume, "
              ."VolSpecial, mixMatch, matched, voided, memType, staff, card_no, doublesnap) "
              ."values (" 
              
              ."'".$datetimestamp."', "
              .$intregisterno.", "
              .$intempno.", "
              .nullwrap($inttransno).", "
              ."'".nullwrap($strupc)."', "
              ."'".$strdescription."', "
              ."'".nullwrap($strtransType)."', "
              ."'".nullwrap($strtranssubType)."', "
              ."'".nullwrap($strtransstatus)."', "
              .nullwrap($intdepartment).", "
              .nullwrap($dblquantity).", "
              .nullwrap($dblunitPrice).", "
              .nullwrap($dbltotal).", "
              .nullwrap($dblregPrice).", "
              .nullwrap($intscale).", "
              .nullwrap($inttax).", "
              .nullwrap($intfoodstamp).", "
              .nullwrap($dbldiscount).", "
              .nullwrap($dblmemDiscount).", "
              .nullwrap($intdiscountable).", "
              .nullwrap($intdiscounttype).", "
              .nullwrap($dblItemQtty).", "
              .nullwrap($intvolDiscType).", "
              .nullwrap($intvolume).", "
              .nullwrap($dblVolSpecial).", "
              .nullwrap($intmixMatch).", "
              .nullwrap($intmatched).", "
              .nullwrap($intvoided).", "
            .nullwrap($memType).", "
            .nullwrap($staff).", "
            ."'".(string) $strCardNo."', " .
		nullwrap($doublesnap) .
		") ";

	error_log("addItem insert query: " . $strqinsert);


    sql_query($strqinsert, $db);
    sql_close($db);

    if ($strtransType == "I" || $strtransType == "D") {
        goodBeep();
        if ($intscale == 1) {
            $_SESSION["screset"] = "rePoll";
        }
        elseif ($_SESSION["weight"] != 0) {
            $_SESSION["screset"] = "rePoll";
        }
        $_SESSION["repeatable"] = 1;
    }

    $_SESSION["msgrepeat"] = 0;
    $_SESSION["toggletax"] = 0;
    $_SESSION["togglefoodstamp"] = 0;
    $_SESSION["SNR"] = 0;
    $_SESSION["wgtRequested"] = 0;
    $_SESSION["nd"] = 0;
    $_SESSION["bdaystatus"] = 99;
    $_SESSION["ccAmtEntered"] = 0;
    $_SESSION["ccAmt"] = 0;

	error_log("finished addItem");

}

//---------------------------------- insert tax line item --------------------------------------
function addtax() {
    addItem("TAX", "Tax", "A", "", "", 0, 0, 0, 0, $_SESSION["taxTotal"], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '');
}

// add 2% credit card fee
function addCardCharge() {
	if ($_SESSION["ttlflag"] == 0) {
		boxMsg("transaction must be totaled before card fee can be added.");
	} else {
		$_SESSION["CardFeeTotal"] = $_SESSION["amtdue"] * $_SESSION['ccAddPercent'];
		addItem("CardFee", "Card Fee", "A", "CF", "", 0, 0, 0, 0, $_SESSION["CardFeeTotal"], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '');
		// re-total, show it
		ttl();
		lastpage();
	}
}

//---------------------------------- insert tender line item -----------------------------------
function addtender($strtenderdesc, $strtendercode, $dbltendered) {
    addItem("", $strtenderdesc, "T", $strtendercode, "", 0, 0, 0, 0, $dbltendered, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '');
}

//---------------------------------- insert foodstamps line item ------------------------------
function addfstender($strtenderdesc, $strtendercode, $dbltendered) {
    addItem("", $strtenderdesc, "T", $strtendercode, "", 0, 0, 0, truncate2($_SESSION["fsEligible"]), $dbltendered, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '');
}

//--------------------------------- insert change line item ------------------------------------
function addchange($dblcashreturn) {
    addItem("", "Change", "T", "CA", "", 0, 0, 0, 0, $dblcashreturn, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 8, 0, '');
}

//-------------------------------- insert foods stamp change item ------------------------------
function addfsones($intfsones) {
    addItem("", "FS Change", "T", "FS", "", 0, 0, 0, 0, $intfsones, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 8, 0, '');
}

//---------------------------------- insert End of Shift  --------------------------$
function addEndofShift() {
    addItem("ENDOFSHIFT", "End of Shift", "S", "", "", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '');
}

function discountLastItem($percent) {
	error_log("discountLastItem called with arg: " . $percent);
    $db = tDataConnect();

	// get the amount of the last item scanned or entered
	$mquery = "SELECT max(trans_id) as last_id FROM localtemptrans";
    $res = sql_query($mquery, $db);
	$errors = 1;
	if ($row = mysql_fetch_assoc($res)) {
		$last_id = $row['last_id'];
		if ($last_id > 0) {
			$chkquery = "SELECT description, total, department, upc FROM localtemptrans WHERE trans_id = $last_id";
			$res2 = sql_query($chkquery, $db);

			if ($row2 = mysql_fetch_assoc($res2)) {
				$itemupc = $row2['upc'];
				$itemdesc = $row2['description'];
				$discountAmt = $row2['total'] * ($percent / 100);
				$department = $row2['department'];
				$errors = 0;

			} else {
				$errors = 1;
				boxMsg("You must scan an item before a discount can be applied. [1]");
			}
		}
	} else {
		$errors = 1;
		boxMsg("You must scan an item before a discount can be applied. [2]");
	}

    sql_close($db);

	if (!$errors) {

		// add a line item to discount the specified percentage
		addItem("DISCOUNT", "** $percent% Item Discount ** [$itemupc] $itemdesc", "I", "ID", "", $department, 0, 1, truncate2(-1 * $discountAmt), truncate2(-1 * $discountAmt), 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 25, 0, '');
		lastpage();
	} else {
		boxMsg("Error processing discount");
	}
}

//-------------------------------- insert deli discount (Wedge specific) -----------------------
function addscDiscount() {

    if ($_SESSION["scDiscount"] != 0) {
        addItem("DISCOUNT", "** 10% Deli Discount **", "I", "", "", 0, 0, 1, truncate2(-1 * $_SESSION["scDiscount"]), truncate2(-1 * $_SESSION["scDiscount"]), 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 2, 0, '');
    }
}

function addStaffCoffeeDiscount() {
    if ($_SESSION["staffCoffeeDiscount"] != 0) {
        addItem("DISCOUNT", "** Coffee Discount **", "I", "", "", 0, 0, 1, truncate2(-1 * $_SESSION["staffCoffeeDiscount"]), truncate2(-1 * $_SESSION["staffCoffeeDiscount"]), 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 2, 0, '');
    }
}

//------------------------------- insert discount line -----------------------------------------
function adddiscount($dbldiscount) {
    $strsaved = "** YOU SAVED $".truncate2($dbldiscount)." **";
    addItem("", $strsaved, "I", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, '');
}

function addmemspecialmsg() {
        $strsaved = "** Member Special **";
        addItem("", $strsaved, "I", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, '');
}

//------------------------------ insert Food Stamp Tax Exempt line -----------------------------
function addfsTaxExempt() {
    getsubtotals();
    addItem("FS Tax Exempt", " Fs Tax Exempt ", "C", "", "D", 0, 0, 0, $_SESSION["fsTaxExempt"], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 17, 0, '');
}

//------------------------------ insert 'discount applied' line --------------------------------

function discountnotify($strl) {
    if ($strl == 10.01) {
        $strL = 10;
    }
    addItem("", "** ".$strl."% Discount Applied **", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 0, '');
}

//------------------------------- insert discount line -----------------------------------------
function addpercentDiscount() {
    addtender($_SESSION["percentDiscount"]."% Discount", "PD", truncate2($_SESSION["transDiscount"]));
}

//------------------------------- insert tax exempt statement line -----------------------------
function addTaxExempt() {
    addItem("", "** Order is Tax Exempt **", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10, 0, '');
    $_SESSION["TaxExempt"] = 1;
    setglobalvalue("TaxExempt", 1);
}

//------------------------------ insert reverse tax exempt statement ---------------------------
function reverseTaxExempt() {
    addItem("", "** Tax Exemption Reversed **", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10, 0, '');
    $_SESSION["TaxExempt"] = 0;
    setglobalvalue("TaxExempt", 0);
}

//------------------------------ insert case discount statement --------------------------------
function addcdnotify() {
    addItem("", "** ".$_SESSION["casediscount"]."% Case Discount Applied", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 0, '');
}

//------------------------------ insert manufacturer coupon statement --------------------------
function addCoupon($strupc, $intdepartment, $dbltotal) {
    addItem($strupc, " * Manufacturers Coupon", "I", "MC", "C", $intdepartment, 0, 1, $dbltotal, $dbltotal, $dbltotal, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, '');    
}

//------------------------------ insert tare statement -----------------------------------------
function addTare($dbltare) {
    $_SESSION["tare"] = $dbltare/100;
    addItem("", "** Tare Weight ".$_SESSION["tare"]." **", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 0, '');
}

//------------------------------- insert MAD coupon statement (WFC specific) -------------------
function addMadCoup() {
        $madCoup = -1 * $_SESSION["madCoup"];
        addItem("MAD Coupon", "Member Appreciation Coupon", "I", "CP", "C", 0, 0, 1, $madCoup, $madCoup, $madCoup, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 17, 0, '');
}

//-------------------------------- insert deposit record --------------------------------------
function addDeposit($quantity, $deposit, $foodstamp) {
        $total = $quantity * $deposit;
        $chardeposit = 100 * $deposit;
        $dept = 0;
        addItem("DEPOSIT" * $chardeposit, "Deposit", "I", "", "", $dept, 0, $quantity, $deposit, $total, $deposit, 0, 0, $foodstamp, 0, 0, 0, 0, $quantity, 0, 0, 0, 0, 0, 0, 0, '');
}

//-------------------------------- insert bottle return record --------------------------------------
function addBottleReturn($quantity, $deposit, $foodstamp) {
        $deposit = -1 * $deposit;
        $total = $quantity * $deposit;
        $chardeposit = -1 * 100 * $deposit;
        $dept = 0;
        addItem("RETURN" * $chardeposit, "Bottle Return", "I", "", "R", $dept, 0, $quantity, $deposit, $total, $deposit, 0, 0, $foodstamp, 0, 0, 0, 0, $quantity, 0, 0, 0, 0, 0, 0, 0, '');
}

// ----------------------------- insert transaction discount -----------------------------------
function addtransDiscount() {
    addItem("DISCOUNT", "Discount", "I", "", "", 0, 0, 1, truncate2(-1 * $_SESSION["transDiscount"]), truncate2(-1 * $_SESSION["transDiscount"]), 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, '');
}

function addactivity($activity) {
    $timeNow = time();

    if ($_SESSION["CashierNo"] > 0 && $_SESSION["CashierNo"] < 256) {
        $intcashier = $_SESSION["CashierNo"];
    }
    else {
        $intcashier = 0;
    }

    if ($_SESSION["DBMS"] == "mssql") {
        $strqtime = "select max(datetime) as maxDateTime, getdate() as rightNow from activitytemplog";
    }
    else {
        $strqtime = "select max(datetime) as maxDateTime, now() as rightNow from activitytemplog";
    }

    $db = tDataConnect();
    $result = sql_query($strqtime, $db);

    $row = sql_fetch_array($result);

    if (!$row || !$row[0]) {
        $interval = 0;
    }
    else {
        $interval = strtotime($row["rightNow"]) - strtotime($row["maxDateTime"]);
    }
        
    $_SESSION["datetimestamp"] = strftime("%Y-%m-%d %H:%M:%S", $timeNow);

    $strq = "insert into activitytemplog values ("
        ."'".nullwrap($_SESSION["datetimestamp"])."', "
        .nullwrap($_SESSION["laneno"]).", "
        .nullwrap($intcashier).", "
        .nullwrap($_SESSION["transno"]).", "
        .nullwrap($activity).", "
        .nullwrap($interval).")";

    $result = sql_query($strq, $db);
    
    sql_close($db);
}


// THESE MUST BE TIMESTAMPS !!
function timeinterval($date1, $date2) {
    $interval = $date2 - $date1;
    return $interval;
}


function fstoggle ($transid) {
	if ($transid) {
		$query = "SELECT foodstamp, description FROM localtemptrans WHERE trans_id = " . $transid;

        $db = tDataConnect();
        $result = sql_query($query, $db);
        $num_rows = sql_num_rows($result);

        if ($num_rows == 0) {
            boxMsg("Item not found");
        }
		else {
            $row = sql_fetch_array($result);
			$newval = $row['foodstamp'] ? 0 : 1;
//			$olddesc = $row['description'];
//			$olddesc = preg_replace("/ \(FS-.*\)/", "", $olddesc);
//			$newdesc = $olddesc . " (" . ($newval ? "FS-enable":"FS-disable") .  ")";
//			$update = "update localtemptrans set foodstamp = $newval, description = '" . mysql_real_escape_string($newdesc) . "' where trans_id = " . $transid;
			$update = "update localtemptrans set foodstamp = $newval where trans_id = " . $transid;

			sql_query($update, $db);

//			$msg = $newval ? "Enabled food stamp elibable" : "Disabled food stamp eligible";

//			boxMsg($msg);
			clearinput();
		}
	}

}
