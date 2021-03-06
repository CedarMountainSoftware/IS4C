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



if (!function_exists("addItem")) include("additem.php");
if (!function_exists("truncate2")) include_once("lib.php");        // apbw 03/24/05 Wedge Printer Swap Patch
if (!function_exists("lastpage")) include("listitems.php");
if (!function_exists("blueLine")) include("session.php");

include_once("temptrans.php");

function memberID($member_number) {
//    new memberID function now searches for staff (staff != 0) and for sister orgs. (memType = 6)
//    to allow cashier to select which personNum to apply to current transaction.    
//     Else function defaults to personNum 1.                                        ~jb    2007-07-22

    $query = "select * from members where CardNo = '" . $member_number . "'";//added to account for non-active members        

    
    $db = pDataConnect();
    $result = sql_query($query, $db);
    $num_rows = sql_num_rows($result);

    if ($num_rows == 1) {
        $row = sql_fetch_array($result);
        $_SESSION["memID"] = $row["id"];
        setMember($_SESSION["memID"]);
        lastpage();
    } 
    elseif ($num_rows > 1) {
        while($row = mysql_fetch_assoc($result)){
        $_SESSION["memID"] = $row["id"];
            if (($row["staff"] != 0) || ($row["memType"] == 6)) {
                $_SESSION["idSearch"] = $member_number;
                maindisplay("memlist.php");
            }
            else {
                setMember($_SESSION["memID"]);
                break;
            }
        }
        lastpage();        
    }
    else {
        $_SESSION["memberID"] = "0";
        $_SESSION["memType"] = 0;
        $_SESSION["percentDiscount"] = 0;
        $_SESSION["memMsg"] = "";

        maindisplay("memsearch.php");
    }     

    mysql_free_result($result);
}

function setMember($id) {
    $conn = pDataConnect();
    //$query = "SELECT * FROM custdata WHERE id = " . $id;
    $query = "SELECT * FROM members WHERE id = " . $id;   //added to move from custdata to member table to catch non-active members
    $result = sql_query($query, $conn);
    $num_rows = sql_num_rows($result);        


    $row = sql_fetch_array($result);

    //added by sjg ---- 2-19-2014 to catch non-active members and display message
    if($row["Active"] == 0)
    {
	//the members Active status is set to '0', display message and exit.
	msgscreen("Status Inactive - Please see membership office to make arrangements.");	
    }


    $_SESSION["memMsg"] = blueLine($row);
    $_SESSION["memberID"] = $row["CardNo"];
    $_SESSION["memType"] = $row["memType"];
    $_SESSION["Type"] = $row["Type"];
    $_SESSION["percentDiscount"] = $row["Discount"];
    $_SESSION["memID"] = $row["id"];

    if (($_SESSION["Type"] == "PC") || ($_SESSION['Type'] == "pc")) {
        $_SESSION["isMember"] = 1;
    }
    else {
        $_SESSION["isMember"] = 0;
    }

    $_SESSION["isStaff"] = $row["staff"];
    $_SESSION["SSI"] = $row["SSI"];
    $_SESSION["discountcap"] = $row["MemDiscountLimit"];

    if ($_SESSION["isStaff"] == 3 || $_SESSION["isStaff"] == 6) {
        $_SESSION["memMsg"] .= " - WM: " . $_SESSION["SSI"] . "hrs";
    }

    $conn2 = tDataConnect();
    $query = "update localtemptrans set card_no = '".$_SESSION["memberID"]."'";
    sql_query($query, $conn2);

    if ($_SESSION["store"] == "wedge") {
        if ($_SESSION["isMember"] == 0 && $_SESSION["percentDiscount"] == 10) {
            sql_query("update localtemptrans set percentDiscount = 0", $conn);
        }
        elseif ($_SESSION["isStaff"] != 1 && $_SESSION["percentDiscount"] == 15) {
            sql_query("update localtemptrans set percentDiscount = 0", $conn);
        }
    }
    elseif ($_SESSION["discountEnforced"] != 0 && $_SESSION["tenderTotal"] == 0) {
        if ($_SESSION["percentDiscount"] > 0) {
            discountnotify($_SESSION["percentDiscount"]);
        }
        sql_query("update localtemptrans set percentDiscount = " . $_SESSION["percentDiscount"] . ", memType = " . nullwrap($_SESSION["memType"]) . ", staff = " . nullwrap($_SESSION["isStaff"]), $conn2);
    }

    if ($_SESSION["discountEnforced"] == 0 && $_SESSION["tenderTotal"] == 0) {
        sql_query("update localtemptrans set percentDiscount = 0", $conn2);
    }

    sql_close($conn2);

    if ($_SESSION["isStaff"] == 0) {
        $_SESSION["staffSpecial"] = 0;
    }

    if ($_SESSION["unlock"] != 1) {
        ttl();
    }

    $_SESSION["unlock"] = 0;

    if ($_SESSION["mirequested"] == 1) {
        $_SESSION["mirequested"] = 0;
        $_SESSION["runningTotal"] = $_SESSION["amtdue"];
        tender("MI", $_SESSION["runningTotal"] * 100);
    }
}

function checkstatus($num) {
    if (!$num) {
        $num = 0;
    }

    $query = "select * from localtemptrans where trans_id = ".$num;

    $db = tDataConnect();
    $result = sql_query($query, $db);

    $num_rows = sql_num_rows($result);

    if ($num_rows > 0) {
        $row = sql_fetch_array($result);
        $_SESSION["voided"] = $row["voided"];
        $_SESSION["scaleprice"] = $row["unitPrice"];
        $_SESSION["discountable"] = $row["discountable"];
        $_SESSION["discounttype"] = $row["discounttype"];
        $_SESSION["caseprice"] = $row["unitPrice"];

        if ($row["trans_status"] == "V") {
            $_SESSION["transstatus"] = "V";
        }

        // added by apbw 6/04/05 to correct voiding of refunded items 
        if ($row["trans_status"] == "R") {
            $_SESSION["refund"] = 1;
        }
    }
    sql_close($db);
}

function tender($right, $strl, $noprint = 0) {
    $tender_upc = "";
    $dollar = $_SESSION["dollarOver"];

    if ($_SESSION["LastID"] == 0) {
        boxMsg("transaction in progress");
    }
    elseif ($strl > 999999) {
        xboxMsg("tender amount of " . truncate2($strl/100) . "<br />exceeds allowable limit");
    }
    elseif ($right == "WT") {
        xboxMsg("WIC tender not applicable");
    }
    elseif ($right == "CK" && $_SESSION["ttlflag"] == 1 && ($_SESSION["isMember"] != 0 || $_SESSION["isStaff"] != 0) && (($strl/100 - $_SESSION["amtdue"] - 0.005) > $dollar) && ($_SESSION["cashOverLimit"] == 1)) {
        boxMsg("member or staff check tender cannot exceed total purchase by over $" . $dollar . ".00");
    }
    elseif ((($right == "CC" || $right == "TB") && $strl/100 > ($_SESSION["amtdue"] + 0.005)) && $_SESSION["refundTotal"] == 0) {
        xboxMsg("credit card tender cannot exceed purchase amount");
    }
	elseif (($right == "CC" || $right == "TB") && $_SESSION['ccAddPercent'] > 0 && ! $_SESSION["CardFeeTotal"] > 0) {
		xboxMsg("card payment requires adding card fee. (CRD)");
	}
    elseif ((($right == "FS") && $strl/100 > ($_SESSION["fsEligible"] + 0.005)) && $_SESSION["refundTotal"] == 0) {
        xboxMsg("EBT food tender cannot exceed eligible amount");
    }
	elseif ($right == "DS" && $strl / 100 > ( ( $_SESSION['fsEligible'] / 2 ) + 0.005 ) ) {
	xboxMsg("Discount cannot exceed half of eligible EBT amount.");
	}
    elseif($right == "EF" && truncate2($strl/100) > $_SESSION["fsEligible"]) {
        xboxMsg("no way!");
    }
    else {
        getsubtotals();

        if ($_SESSION["ttlflag"] == 1 && ($right == "CX" || $right == "MI")) {            // added ttlflag on 2/28/05 apbw 
            $charge_ok = chargeOk();
            if ($right == "CX" && $charge_ok == 1) {
                $charge_ok = 1;
            }
            elseif ($right == "MI" && $charge_ok == 1) {
                $charge_ok = 1;
            }
            else $charge_ok = 0;
        }

        $strl = $strl / 100;
        if ($_SESSION["ttlflag"] == 0) {
            boxMsg("transaction must be totaled before tender can be accepted");
        }
        elseif (($right == "FS" || $right == "EF" || $right == "DS" ) && $_SESSION["fntlflag"] == 0) {
            boxMsg("eligble amount must be totaled before foodstamp tender can be accepted");
        }
        elseif ($right == "EF" && $_SESSION["fntlflag"] == 1 && $_SESSION["fsEligible"] + 10 <= $strl) {
            xboxMsg("Foodstamp tender cannot exceed elible amount by over $10.00");
        }
        elseif ($right == "CX" && $charge_ok == 0) {
            xboxMsg("member " . $_SESSION["memberID"] . "<br />is not authorized<br />to make corporate charges");
        }
        // added by apbw on 2/15/05 -- prevents biz charge accts from tendering staff charges
        elseif ($right == "MI" && $_SESSION["isStaff"] == 0) {    // apbw 2/15/05 SCR
            xboxMsg("member " . $_SESSION["memberID"] . "<br />is not authorized<br />to make employee charges");    // apbw 2/15/05 SCR
        }    // apbw 2/15/05 SCR
        elseif ($right == "MI" && $charge_ok == 0 && $_SESSION["availBal"] < 0) {
            xboxMsg("member " . $_SESSION["memberID"] . "<br /> has $" . $_SESSION["availBal"] . " available.");
        }
        elseif ($right == "MI" && $charge_ok == 1 && $_SESSION["availBal"] < 0) {
            xboxMsg("member " . $_SESSION["memberID"] . "<br />is over limit");
        }
            elseif ($right == "MI" && $charge_ok == 0) {
            xboxMsg("member " . $_SESSION["memberID"] . "<br />is not authorized to make employee charges");
        }
        elseif ($right == "MI" && $charge_ok == 1 && ($_SESSION["availBal"] + $_SESSION["memChargeTotal"] - $strl) <= 0) {
            xboxMsg("member " . $_SESSION["memberID"] . "<br /> has exceeded charge limit");
        }
        elseif ($right == "MI" && $charge_ok == 1 && (ABS($_SESSION["memChargeTotal"])+ $strl) >= ($_SESSION["availBal"] + 0.005) && $_SESSION["store"] == "WFC") {
            $memChargeRemain = $_SESSION["availBal"];
            $memChargeCommitted = $memChargeRemain + $_SESSION["memChargeTotal"];
            xboxMsg("available balance for charge <br />is only $" . $memChargeCommitted . ".<br /><b><font size = 5>$" . number_format($memChargeRemain,2) . "</font></b><br />may still be used on this purchase.");
        }
        elseif(($right == "MI" || $right == "CX") && truncate2($_SESSION["amtdue"]) < truncate2($strl)) {
            xboxMsg("charge tender exceeds purchase amount");
        }
        //insert credit card programming......    
        else {
            $query = "select * from tenders where tendercode = '" . $right . "'";

            $db = pDataConnect();
            $result = sql_query($query, $db);

            $num_rows = sql_num_rows($result);

            if ($num_rows == 0) {
                inputUnknown();
            }
            else {
                $row = sql_fetch_array($result);
                $tender_code = $right;
                $tendered = -1 * $strl;                
                if($tender_code == "CC" && $_SESSION["CCintegrate"] == 1) {
                    $tender_upc = $_SESSION["troutd"];
                }
                $tender_desc = $row["TenderName"];                
                $_SESSION["tenderamt"] = $strl;
                $unit_price = 0;

                if ($tender_code == "FS" && $strl > $_SESSION["fsEligible"] && $strl < $_SESSION["subtotal"]) {
                    $unit_price = -1 * $_SESSION["fsEligible"];
                }
                elseif ($tender_code == "FS") {
                    $unit_price = -1 * $strl;
                }
                if ($strl - $_SESSION["amtdue"] > 0) {
                    $_SESSION["change"] = $strl - $_SESSION["amtdue"];
                }
                else {
                    $_SESSION["change"] = 0;
                }

//                if ($right == "CK" && $_SESSION["msgrepeat"] == 0) {
//                   $_SESSION["boxMsg"] = "<br />insert check<br />press [enter] to endorse<p><font size='-1'>[clear] to cancel</font></p>";
//                    $_SESSION["endorseType"] = "check";
//                    boxMsgscreen();

//                }
//				  else
				{

		if($tender_code=='DS' && empty($tender_upc) && $_SESSION['EBT']!='0' ){ 
			//shameless kludge: store EBT number as UPC for Double Snap item
			error_log("*************************** using EBT as UPC ".$_SESSION['EBT']);
			$tender_upc=$_SESSION['EBT'];
		}

                    addItem($tender_upc, $tender_desc, "T", $tender_code, "", 0, 0, 0, $unit_price, $tendered, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '');
                    $_SESSION["msgrepeat"] = 0;
                    $_SESSION["TenderType"] = $tender_code;            /***added by apbw 2/1/05 SCR ***/



			// if there was a DS Discount already applied, and the EBT tender was less than the full amount...
			// adjust the DS discount down to match the EBT amount tendered
			if ($right == "FS" &&
				abs($unit_price) < $_SESSION["fsEligible"]  &&
				abs($_SESSION['dsTendered']) > abs($unit_price) 
				) {
				$dsadjust = ($_SESSION['dsTendered'] - $unit_price) * -1;
				addItem("", "DoubleSnap Adjust", "T", "DS", "", 0, 0, 0, $dsadjust, $dsadjust, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '');
			}
			



                    /*** session tender type set by apbw 2/28/05 ***/
                    if ($_SESSION["TenderType"] == "MI" || $_SESSION["TenderType"] == "CX") {
                        $_SESSION["chargetender"] = 1;                            // apbw 2/28/05 SCR
                    }                                                    // apbw 2/28/05 SCR

                    getsubtotals();

                    if ($right == "FS") {
                        $fs = -1 * $_SESSION["fsEligible"];
                        $fs_ones = (($fs * 100) - (($fs * 100) % 100))/100;
                        $fs_change = $fs - $fs_ones;
    
                        if ($fs_ones > 0) {
                            addfsones($fs_ones);
                        }

                        if ($fs_change > 0) {
                            addchange($fs_change);
                        }
                        getsubtotals();
                    }


                    if ($_SESSION["amtdue"] <= 0.005) {
                        $_SESSION["change"] = -1 * $_SESSION["amtdue"];
                        $cash_return = $_SESSION["change"];

                        if ($right != "FS") {
                            addchange($cash_return);
                        }


                        if ($right == "CK" && $cash_return > 0) {
                            $_SESSION["cashOverAmt"] = 1; // apbw/cvr 3/5/05 cash back beep
                        }

/* gdg 22Jun2016
Commenting out next two lines does appear to keep the transaction live. The amount due $0.00.
Can run fntl again.
*/
                        $_SESSION["End"] = 1;
                        printReceiptfooter();
                    }
                    else {
			if ($right == "DS") {
				// dsTendered now comes from the subtotals database view
//				$_SESSION['dstendered'] += $strl;

    				getsubtotals();
				$_SESSION["fntlflag"] = 1;
				setglobalvalue("fntlflag", 1);
				addItem("", "Foodstamps Remaining Total:", "" , "", "D", 0, 0, 0, truncate2($_SESSION["fsEligible"]), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, '');
				// sometimes we don't print the items yet if the calling function has more stuff to do first
				// if $noprint == 1 the lastpage() function should get called later 
				if (!$noprint)

					lastpage();
			} else {


                        $_SESSION["change"] = 0;
                        $_SESSION["fntlflag"] = 0;
                        ttl();
                        lastpage();
			}
                    }
                }
            }
        }
    }
}

//-------------------------------------------------------

function deptkey($price, $dept, $byweight = 0) {
    $intvoided = 0;

    if ($_SESSION["quantity"] == 0 && $_SESSION["multiple"] == 0) {
            $_SESSION["quantity"] = 1;
    }
        
    if (is_numeric($dept) && is_numeric($price) && strlen($price) >= 1 && strlen($dept) >= 1) {
        $strprice = $price;
        $strdept = $dept;
        $price = $price/100;
//        $dept = $dept/10;

        if ($_SESSION["casediscount"] > 0 && $_SESSION["casediscount"] <= 100) {
            $case_discount = (100 - $_SESSION["casediscount"]) / 100;
            $price = $case_discount * $price;
        }

		$quantity = $_SESSION['quantity'];

		if ($byweight == 1) {
			$hitareflag = 0;

			$quantity = $_SESSION["weight"] - $_SESSION["tare"];

			if ($quantity <= 0) {
				$hitareflag = 1;
			}

			$_SESSION["tare"] = 0;
		}

		if ($hitareflag == 1) {
			boxMsg("item weight must be greater than tare weight");
		}

        $total = $price * $quantity;
        $intdept = $dept;

        $query = "select * from departments where dept_no = " . $intdept;
        $db = pDataConnect();
        $result = sql_query($query, $db);

        $num_rows = sql_num_rows($result);

        if ($num_rows == 0) {
            boxMsg("department unknown");
            $_SESSION["quantity"] = 1;
        }
        elseif ($_SESSION["mfcoupon"] == 1) {
            $row = sql_fetch_array($result);
            $_SESSION["mfcoupon"] = 0;
            $query2 = "select department, sum(total) as total from localtemptrans where department = "
                . $dept . " group by department";

            $db2 = tDataConnect();
            $result2 = sql_query($query2, $db2);

            $num_rows2 = sql_num_rows($result2);
            if ($num_rows2 == 0) {
                boxMsg("no item found in<br />" . $row["dept_name"]);
            }
            else {
                $row2 = sql_fetch_array($result2);
                if ($price > $row2["total"]) {
                    boxMsg("coupon amount greater than department total");
                }
                else {
                    addItem("", $row["dept_name"]." Coupon", "I", "CP", "C", $dept, 0, 1, -1 * $price, -1 * $price, -1 * $price, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, $intvoided, 0, '');
                    $_SESSION["ttlflag"] = 0;
                    $_SESSION["ttlrequested"] = 0;
                    goodBeep();
                    lastpage();
                }
            }
        }
        else {
            $row = sql_fetch_array($result);
            if (!$row["dept_limit"]) {
                $deptmax = 0;
            }
            else {
                $deptmax = $row["dept_limit"];
            }

            if (!$row["dept_minimum"]) {
                $deptmin = 0;
            }
            else {
                $deptmin = $row["dept_minimum"];
            }
            $tax = $row["dept_tax"];

            if ($row["dept_fs"] != 0) {
                $foodstamp = 1;
            }
            else {
                $foodstamp = 0;
            }

            $deptDiscount = $row["dept_discount"];

            if ($_SESSION["toggleDiscountable"] == 1) {
                $_SESSION["toggleDiscountable"] = 0;
                if  ($deptDiscount == 0) {
                    $deptDiscount = 1;
                }
                else {
                    $deptDiscount = 0;
                }
            }

            if ($_SESSION["togglefoodstamp"] == 1) {
                $foodstamp = ($foodstamp + 1) % 2;
                $_SESSION["togglefoodstamp"] = 0;
            }

            // Hard coding starts
            if ($dept == 606) {
                $price = -1 * $price;
                $total = -1 * $total;
            }

            // Hard coding ends
            if ($_SESSION["ddNotify"] != 0 &&  $_SESSION["itemPD"] == 10) {
                    $_SESSION["itemPD"] = 0;
                    $deptDiscount = 7;
                    $intvoided = 22;
            }

            //------------- Find EQUITY payments and force member# entry       ~joel 2006-12-12
            if ($_SESSION["store"] == "acg") {
                if (($dept == 45) && ($_SESSION["member"] != 0)) {            // dp 45 = member equity
                    maindisplay("memsearch.php");
                }
                if ($dept == 41) {                                            // dp 41 = bottle deposit
                    $price = -1 * $price;
                    $total = -1 * $total;
                }
            }

            if ($deptmax > 0 && $price > $deptmax && $_SESSION["msgrepeat"] == 0) {
                $_SESSION["boxMsg"] = "$" . $price . " is greater than department limit<p>"
                    . "<font size='-1'>[clear] to cancel, [enter] to proceed</font></p>";
                boxMsgscreen();
            }
            elseif ($price < $deptmin && $_SESSION["msgrepeat"] == 0) {
                $_SESSION["boxMsg"] = "$" . $price . " is lower than department minimum<p>"
                    . "<font size='-1'>[clear] to cancel, [enter] to proceed</font></p>";
                boxMsgscreen();
            }
            else {
                if ($_SESSION["casediscount"] > 0) {
                    addcdnotify();
                    $_SESSION["casediscount"] = 0;
                }
            
                if ($_SESSION["toggletax"] == 1) {
                    $tax = ($tax + 1) % 2;
                    $_SESSION["toggletax"] = 0;
                }

                addItem($price."DP".$dept, $row["dept_name"], "D", " ", " ", $dept, 0, $quantity, $price, $total, $price, $byweight, $tax, $foodstamp, 0, 0, $deptDiscount, 0, $quantity, 0, 0, 0, 0, 0, $intvoided, 0, '');
                $_SESSION["ttlflag"] = 0;
                $_SESSION["ttlrequested"] = 0;
                goodBeep();
                lastpage();
                $_SESSION["msgrepeat"] = 0;
            }
        }
    }
    else {
        inputUnknown();
        $_SESSION["quantity"] = 1;
    }
    $_SESSION["quantity"] = 0;
    $_SESSION["itemPD"] = 0;
}

// re-wrote the queries to resolve insert statement errors -- apbw 7/01/05
function ttl($voided = 0) {
    // set_error_handler("prehkeys_dataError");

    $_SESSION["ttlrequested"] = 1;

	// the co-op has requested the "enter ebt" box be removed for guests

// if(	$_SESSION['memberID'] == 1

//	&& $_SESSION["isMember"] == 0 
//	&& strlen($_SESSION['EBT'])<4 
//	&& $_SESSION['fsEligible'] == 1


// ) {
// may need session fsEligible
//	maindisplay("enter_ebt.php");
//   	$_SESSION["repeat"] = 0; //from end of ttl()
//	exit; //don't blow past here
// }

    
    if ($_SESSION["memberID"] == "0") {
	//FNTL membersearch comes from here. Doesn't return here after search though. Goes to memlist.php
	//and several other files before this ttl() function is called again, but with memberID set.
        maindisplay("memsearch.php");
    }
    else {
        if ($_SESSION["isMember"] == 1) {
            $query = "INSERT into localtemptrans (datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no) select datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no from memdiscountadd";
        }
        else {
            $query = "INSERT into localtemptrans (datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no) select datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no from memdiscountremove";
        }

        if ($_SESSION["isStaff"] != 0) {
            $query2 = "INSERT into localtemptrans (datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no) select datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no from staffdiscountadd";
        }
        else {
            $query2 = "INSERT into localtemptrans (datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no) select datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no from staffdiscountremove";
        }

        $mconn = tDataConnect();
        $result = sql_query($query, $mconn);
        $result2 = sql_query($query2, $mconn);

        $_SESSION["ttlflag"] = 1;
        setglobalvalue("ttlflag", 1);
        getsubtotals();

        if ($_SESSION["percentDiscount"] > 0) {
            addItem("", $_SESSION["percentDiscount"] . "% Discount", "C", "", "D", 0, 0, 0, truncate2(-1 * $_SESSION["transDiscount"]), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, '');
        }
        $amtDue = str_replace(",", "", $_SESSION["amtdue"]);

        addItem("", "Subtotal " . truncate2($_SESSION["subtotal"]) . ", Tax ".truncate2($_SESSION["taxTotal"]), "C", "", "D", 0, 0, 0, $amtDue, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 0, '');
    
        if ($_SESSION["fntlflag"] == 1 && !$voided) {
            addItem("", "Foodstamps Eligible", "", "", "D", 0, 0, 0, truncate2($_SESSION["fsEligible"]), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, '');
		$dseligible = getDSDiscountEligible();

		if ($dseligible > 0) {
			$insert_id = addItem("", "Discount Eligible", "", "", "D", 0, 0, 0, truncate2($dseligible),  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, '');
			if($insert_id>0){
        			//$result = sql_query("UPDATE localtemptrans SET ebt='".$_SESSION['EBT']."' WHERE trans_id=".$insert_id." limit 1", $mconn);
			}
			tender("DS", $dseligible*100, 1);
		}
        }
    }

    $_SESSION["repeat"] = 0;
}

function getMaxDSDailyDiscount(){
	$ret = 20.00; // not sure if this is supposed to come from DB.

	$ds =0;
	//need backend database since lane db can be cleaned out at end of shift, not end of day.
	//plus, user might have used a different lane.
	$db = mDataConnect();
	
//	if($_SESSION['memberID']==99999 && $_SESSION['isMember']==0){
	if($_SESSION['memberID']==1 ){

		// non-members assume each guest is different so no check for previous transactions today
		$ds = 0;

//	$query = sprintf("SELECT SUM(total) AS todaysum FROM is4c_log.dtransactions ".
//			 "WHERE card_no='%s' AND upc ='%s' AND trans_subtype='DS' AND trans_status !='X' ". 
//			 "AND DATE(`datetime`)=DATE(now())", mysql_real_escape_string($_SESSION["memberID"], $db), mysql_real_escape_string($_SESSION["EBT"], $db));



	}else{
		$query = sprintf("SELECT SUM(total) AS todaysum FROM is4c_log.dtransactions ".
			 "WHERE card_no='%s' AND trans_subtype='DS' AND trans_status !='X' ". 
			 "AND DATE(`datetime`)=DATE(now())", mysql_real_escape_string($_SESSION["memberID"], $db));

		$result = sql_query($query, $db);

		$num_rows = sql_num_rows($result);
		if ($num_rows == 1) {
			$row = sql_fetch_array($result);
			$ds = abs($row["todaysum"]);
			error_log("TODAY'S DOUBLE SNAP sum:$ds");
		}
	}

	$ret = $ret - $ds;
	if($ret < 0.0) $ret = 0.0; //don't want to charge for the discount or whatever
	return $ret;
}

function getDSDiscountEligible() {

	// the amount of match can be up to half of the foodstamp total (including non-DS items)
	// but the only up to the total of DS marked items

	// so for example, if somebody had a total of $30 FS items
	// then up to $15 DS marked produce items could be matched
	// but if they only have $10 in produce then that would be
	// the discount amount

	$dsmax = $_SESSION['dsTotal'];
	$fsmax = ($_SESSION['fsTotal'] / 2);

	$tmp = min($fsmax, $dsmax);


	// daily limit 
	$tmp = min($tmp, getMaxDSDailyDiscount());//appears to work

	// subtract any discounts that were already tendered
	// dsTendered will be negative so we add it
	$tmp = $tmp + $_SESSION['dsTendered'];
	return $tmp;
}

function finalttl() {
    if ($_SESSION["percentDiscount"] > 0) {
        addItem("", "Discount", "C", "", "D", 0, 0, 0, truncate2(-1 * $_SESSION["transDiscount"]), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, '');
    }

    addItem("Subtotal", "Subtotal", "C", "", "D", 0, 0, 0, truncate2($_SESSION["taxTotal"] - $_SESSION["fsTaxExempt"]), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 11, 0, '');

    if ($_SESSION["fsTaxExempt"]  != 0) {
        addItem("Tax", truncate2($_SESSION["fstaxable"])." Taxable", "C", "", "D", 0, 0, 0, truncate2($_SESSION["fsTaxExempt"]), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, '');
    }

    addItem("Total", "Total", "C", "", "D", 0, 0, 0, truncate2($_SESSION["amtdue"]), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 11, 0, '');
}

function fsEligible() {
    getsubtotals();
    if ($_SESSION["fsEligible"] < 0) {
        boxMsg("Foodstamp eligible amount inapplicable<p>Please void out earlier tender and apply foodstamp first</p>");
    }
    else {
        $_SESSION["fntlflag"] = 1;
        setglobalvalue("fntlflag", 1);
        if ($_SESSION["ttlflag"] != 1) {
		ttl();
        } else {
		addItem("", "Foodstamps Eligible", "" , "", "D", 0, 0, 0, truncate2($_SESSION["fsEligible"]), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, '');
		$dseligible = getDSDiscountEligible();

		if ($dseligible > 0)  {
			addItem("", "Discount Eligible", "", "", "D", 0, 0, 0, truncate2($dseligible),  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, '');
			tender("DS", $dseligible*100, 1);
		} 

	}
        lastpage();
    }
}

function percentDiscount($strl) {
    if ($strl == 10.01) $strl = 10;

    if (!is_numeric($strl) || $strl > 100 || $strl < 0) {
        boxMsg("discount invalid");
    }
    else {
        $query = "select sum(total) as total from localtemptrans where upc = '0000000008005' group by upc";

        $db = tDataConnect();
        $result = sql_query($query, $db);

        $num_rows = sql_num_rows($result);
        if ($num_rows == 0) {
            $couponTotal = 0;
        }
        else {
            $row = sql_fetch_array($result);
            $couponTotal = nullwrap($row["total"]);
        }
        if ($couponTotal == 0 || $strl == 0) {
            if ($strl != 0) discountnotify($strl);
            sql_query("update localtemptrans set percentDiscount = " . $strl, $db);
            ttl();
            lastpage();
        }
        else xboxMsg("10% discount already applied");
    }
}

function MADdiscount($strl) {
    if ($strl == 10.01) $strl = 10;

    if (!is_numeric($strl) || $strl > 100 || $strl < 0) {
        boxMsg("discount invalid");
    }
    else {
        $query = "select sum(total) as total from localtemptrans where upc = '0000000008005' group by upc";

        $db = tDataConnect();
        $result = sql_query($query, $db);

        $num_rows = sql_num_rows($result);
        if ($num_rows == 0) $couponTotal = 0;
        else {
            $row = sql_fetch_array($result);
            $couponTotal = nullwrap($row["total"]);
        }
        if ($couponTotal == 0 || $strl == 0) {
            sql_query("update localtemptrans set percentDiscount = ".$strl, $db);
            //    ------------ MAD gets its own ttl() function.  cuz it's special.    --------------
            $_SESSION["ttlrequested"] = 1;
    
            if ($_SESSION["memberID"] == "0") {
                maindisplay("memsearch.php");
            }
            else {
                if ($_SESSION["isMember"] == 1) {
                    $query = "INSERT into localtemptrans (datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no) select datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no from memdiscountadd";
                } 
                else {
                    $query = "INSERT into localtemptrans (datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no) select datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no from memdiscountremove";
                }
                if ($_SESSION["isStaff"] != 0) {
                    $query2 = "INSERT into localtemptrans (datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no) select datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no from staffdiscountadd";
                } 
                else {
                    $query2 = "INSERT into localtemptrans (datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no) select datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no from staffdiscountremove";
                }

                $mconn = tDataConnect();
                $result = sql_query($query, $mconn);
                $result2 = sql_query($query2, $mconn);

                $_SESSION["ttlflag"] = 1;
                setglobalvalue("ttlflag", 1);
                getsubtotals();

                if ($strl > 0) {
                    $discAmt = truncate2(-1 * ($_SESSION["discountableTotal"] * ($_SESSION["MADdiscount"] / 100)));
                    addItem($_SESSION["MADdiscount"] . "MA", $_SESSION["MADdiscount"]."% Member Appreciation Discount", "I", "", "C", 0, 0, 1, $discAmt, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 9, 0, '');
                }
                $amtDue = str_replace(",", "", $_SESSION["amtdue"]);

                addItem("", "Discount Amt: " . $discAmt . ", Tax " . truncate2($_SESSION["taxTotal"]), "C", "", "D", 0, 0, 0, $amtDue, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 0, '');
    
                if ($_SESSION["fntlflag"] == 1) {
                    addItem("", "Foodstamps Eligible", "", "", "D", 0, 0, 0, truncate2($_SESSION["fsEligible"]), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, '');
                }

            }
//    ------------ end MADs special ttl() function ------------------
    
            $_SESSION["repeat"] = 0;
        }
    lastpage();
    }
}

function needBasedDisc($strl) {
    if ($_SESSION["needBasedDisc"] != 0) {
        if (!is_numeric($strl) || $strl > 100 || $strl < 0) {
            boxMsg("discount invalid");
        }
        else {
            $query = "select sum(total) as total from localtemptrans where upc = '0000000008005' group by upc";

            $db = tDataConnect();
            $result = sql_query($query, $db);

            $num_rows = sql_num_rows($result);
            if ($num_rows == 0) $couponTotal = 0;
            else {
                $row = sql_fetch_array($result);
                $couponTotal = nullwrap($row["total"]);
            }
            if ($couponTotal == 0 || $strl == 0) {
                sql_query("update localtemptrans set percentDiscount = " . $strl, $db);

                //    ------------ NBD gets its own ttl() function.  cuz it's special.    --------------
                $_SESSION["ttlrequested"] = 1;

                if ($_SESSION["memberID"] == "0") {
                    maindisplay("memsearch.php");
                }
                else {
                    if ($_SESSION["isMember"] == 1) {
                        $query = "INSERT into localtemptrans (datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no) select datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no from memdiscountadd";
                    } 
                    else {
                        $query = "INSERT into localtemptrans (datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no) select datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no from memdiscountremove";
                    }
                    if ($_SESSION["isStaff"] != 0) {
                        $query2 = "INSERT into localtemptrans (datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no) select datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no from staffdiscountadd";
                    } 
                    else {
                        $query2 = "INSERT into localtemptrans (datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no) select datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no from staffdiscountremove";
                    }

                    $mconn = tDataConnect();
                    $result = sql_query($query, $mconn);
                    $result2 = sql_query($query2, $mconn);

                    $_SESSION["ttlflag"] = 1;
                    setglobalvalue("ttlflag", 1);
                    getsubtotals();

                    if ($strl > 0) {
                        $discountAmt = truncate2(-1 * ($_SESSION["discountableTotal"] * ($_SESSION["needBasedDisc"] / 100)));
                        addItem($_SESSION["needBasedDisc"]."FF", $_SESSION["needBasedDisc"]."% Food For All Discount", "I", "", "C", 0, 0, 1, $discountAmt, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 10, 0, '');                    
                    }
                    $amtDue = str_replace(",", "", $_SESSION["amtdue"]);

                    addItem("", "Discount Amt: ".$discountAmt.", Tax ".truncate2($_SESSION["taxTotal"]), "C", "", "D", 0, 0, 0, $amtDue, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 0, '');

                    if ($_SESSION["fntlflag"] == 1) {
                        addItem("", "Foodstamps Eligible", "", "", "D", 0, 0, 0, truncate2($_SESSION["fsEligible"]), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, '');
                    }
                }
    //    ------------ end NBDs special ttl() function ------------------

                $_SESSION["repeat"] = 0;
            }
            lastpage();
        }
    }
    else {
        inputUnknown();
    }
}

function chargeOk() {

    getsubtotals();

    $conn = pDataConnect();
    $m_conn = mDataConnect();
    $query = "select * from memchargebalance where cardNo = '" . $_SESSION["memberID"] . "'";

    if ($_SESSION["standalone"] == 0) {
        $result = sql_query($query, $m_conn);
    }
    else {
        $result = sql_query($query, $conn);
    }

    $num_rows = sql_num_rows($result);
    $row = sql_fetch_array($result);
    $availBal = $row["availBal"] + $_SESSION["memChargeTotal"];
    
    $_SESSION["balance"] = $row["balance"];
    $_SESSION["availBal"] = number_format($availBal,2,'.','');        
    
    $query2 = "select Balance, MemDiscountLimit, ChargeOk from custdata where id = '" . $_SESSION["memID"] . "'";
    $result2 = sql_query($query2, $conn);
    $row2 = sql_fetch_array($result2);

    if ($row2["ChargeOk"] == 0) {
        $chargeOk = 0;
        $_SESSION["chargeOk"] = 0;
    }
    elseif ($row2["ChargeOk"] == 1) {
        $chargeOk = 1;
        $_SESSION["chargeOk"] = 1;
    }
    return $chargeOk;

}

function madCoupon(){
    getsubtotals();
    addMadCoup();
    lastpage();

}

function staffCharge($arg) {
    $_SESSION["sc"] = 1;

    $staffID = substr($arg, 0, 4);

    $pQuery = "select * from chargecodeView where chargecode = '" . $arg . "'";
    $pConn = pDataConnect();
    $result = sql_query($pQuery, $pConn);
    $num_rows = sql_num_rows($result);
    $row = sql_fetch_array($result);

    if ($num_rows == 0) {
        xboxMsg("unable to authenticate staff ".$staffID);
        $_Session["isStaff"] = 0;            // apbw 03/05/05 SCR
    }
    else {
        $_SESSION["isStaff"] = 1;            // apbw 03/05/05 SCR
        $_SESSION["memMsg"] = blueLine($row);
        $tQuery = "update localtemptrans set card_no = '" . $staffID . "', percentDiscount = 15";
        $tConn = tDataConnect();

        addscDiscount();        
        discountnotify(15);
        sql_query($tQuery, $tConn);
        getsubtotals();

        ttl();
        $_SESSION["runningTotal"] = $_SESSION["amtdue"];
        tender("MI", $_SESSION["runningTotal"] * 100);
    }
}

function endofShift() {
    $_SESSION["memberID"] = "99999";
    $_SESSION["memMsg"] = "End of Shift";
    addEndofShift();
    getsubtotals();
    ttl();
    $_SESSION["runningtotal"] = $_SESSION["amtdue"];
    tender("CA", $_SESSION["runningtotal"] * 100);
	cleartemptrans();
}

//---------------------------    WORKING MEMBER DISCOUNT    -------------------------- 
function wmdiscount() {
    $sconn = mDataConnect();
    $conn2 = tDataConnect();
        
//    $volQ = "SELECT * FROM is4c_op.volunteerDiscounts WHERE CardNo = " . $_SESSION["memberID"];
    
//    $volR = sql_query($volQ,$sconn);
//    $row = sql_fetch_array($volR);
//    $total = $row["total"];
$total =1000;    

    if ($_SESSION["isStaff"] == 3) {
        if ($_SESSION["discountableTotal"] > $total) {
            $a = $total * .15;                                                                // apply 15% disocunt
            $b = ($_SESSION["discountableTotal"] - $total) * .02 ;                                // apply 2% discount
            $c = $a + $b;
            $aggdisc = number_format(($c / $_SESSION["discountableTotal"]) * 100,2);                // aggregate discount

            $_SESSION["transDiscount"] = $c;
            $_SESSION["percentDiscount"] = $aggdisc;
        }
        elseif ($_SESSION["discountableTotal"] <= $total) {
            $_SESSION["percentDiscount"] = 15;
            $_SESSION["transDiscount"] = $_SESSION["discountableTotal"] * .15;
        }
    }
    elseif ($_SESSION["isStaff"] == 6) {

            if ($_SESSION["discountableTotal"] > $total) {

            $a = $total * .10;                                                                // apply 10% disocunt
            $aggdisc = number_format(($a / $_SESSION["discountableTotal"]) * 100,2);                // aggregate discount

            $_SESSION["transDiscount"] = $a;
            $_SESSION["percentDiscount"] = $aggdisc;
        }
        elseif ($_SESSION["discountableTotal"] <= $total) {

            $_SESSION["percentDiscount"] = 10;
            $_SESSION["transDiscount"] = $_SESSION["discountableTotal"] * .10;
	    //rounding error with the discount, trying to round it out here 
	    //so math later will take care of itself - sjg
            $_SESSION["transDiscount"] = round($_SESSION["transDiscount"],2,PHP_ROUND_HALF_UP);
        }
    }

    sql_query("update localtemptrans set percentDiscount = ".$_SESSION["percentDiscount"], $conn2);

    if ($_SESSION["discountableTotal"] < $total) {

        $a = number_format($_SESSION["discountableTotal"] / 20,2);
        $arr = explode(".",$a);
        if ($arr[1] >= 75 && $arr[1] != 00) {
            $dec = 75;
        }
        elseif ($arr[1] >= 50 && $arr[1] < 75) {
            $dec = 50;
        }
        elseif ($arr[1] >= 25 && $arr[1] < 50) {
            $dec = 25;
        }
        elseif ($arr[1] >= 00 && $arr[1] < 25) {
            $dec = 00;
        }
        $_SESSION["volunteerDiscount"] = $arr[0] . "." . $dec;
    }
    else {
        $_SESSION["volunteerDiscount"] = $total / 20;
    }
}
//------------------------- END WORKING MEMBER DISCOUNT    --------------------------

function prehkeys_dataError($Type, $msg, $file, $line, $context) {
    $_SESSION["errorMsg"] = $Type . " " . $msg . " " . $file . " " . $line . " " . $context;

    if ($Type != 8) {
        $_SESSION["standalone"] = 1;
    }
}

