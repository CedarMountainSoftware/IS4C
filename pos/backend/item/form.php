<?php
	function form($backoffice) {
		if (isset($backoffice['product_detail'])) {
			require_once($_SERVER["DOCUMENT_ROOT"]."/lib/table_departments.php");
				$departments_result=get_departments(&$backoffice);
			require_once($_SERVER["DOCUMENT_ROOT"]."/lib/table_subdepts.php");
				$subdepartments_result=get_subdepartments(&$backoffice);
			require_once($_SERVER["DOCUMENT_ROOT"]."/lib/table_vendors.php");
				$vendors_result=get_vendors(&$backoffice);

			
				$departments = array();
				$subdepartments = array();
				$subdept_ids = array();

				$vendors = array();

				// put the departments / subdepartment data in array to initialize javascript data
				while ($row=mysql_fetch_array($departments_result)) {
					$departments[$row['dept_no']] = $row['dept_name'];
					$subdept_ids[$row['dept_no']] = array();
				}

				while ($row=mysql_fetch_array($subdepartments_result)) {
					$subdepartments[$row['subdept_no']] = $row['subdept_name'];
					$subdept_ids[$row['dept_ID']][] = $row['subdept_no'];
				}

				while ($row=mysql_fetch_array($vendors_result)) {
					$vendors[$row['vendor_id']] = $row['vendor_name'];
				}


			$html='
			<script language="Javascript">
			<!--
				Departments = Object();
				Subdepartments = Object();
				Subdept_Ids = Object();
				';
			foreach ($departments as $dept_id => $dept_name) {
				$html .= "Departments[$dept_id] = '" . $dept_name . "';\n";
				$html .= "Subdept_Ids[$dept_id] = Array();\n";
				$n = 0;
				foreach ($subdept_ids[$dept_id] as $subid) {
					$html .= "Subdept_Ids[$dept_id][$n] = $subid;\n";
					$n++;
				}
			}
			foreach ($subdepartments as $subdept_id => $subdept_name) {
				$html .= "Subdepartments[$subdept_id] = '" . $subdept_name . "';\n";
			}


			$html .= '

				function switchDept(dept_id) {
					var subselect = document.getElementById("edit_subdepartment");
					var oldval = subselect.options[subselect.selectedIndex].value;
					subselect.options.length = 0;
					for (var idx = 0; idx < Subdept_Ids[dept_id].length; idx++) {
						var isselected = (oldval == Subdept_Ids[dept_id][idx]) ? true : false;
						subselect.options[idx] = new Option(Subdepartments[Subdept_Ids[dept_id][idx]], Subdept_Ids[dept_id][idx], isselected, isselected);
					}

				}
			// -->
			</script>

			<form action="./" method="post" name="edit">
				<div class="edit_column">
					<fieldset>
						<legend>Core</legend>
						<input name="a" id="a" type="hidden" value="update"/>
						<input name="edit_id" type="hidden" value="'.$backoffice['product_detail']['id'].'"/>
						<div class="edit_row">
							<input readonly name="edit_upc" type="text" value="'.$backoffice['product_detail']['upc'].'"/>
						</div>
						<div class="edit_row">
							<span class="note">Last modified: '.$backoffice['product_detail']['modified'].'</span>
						</div>
						<div class="edit_row">
							<span class="note">Last sold: Unavailable</span>
						</div>
						</fieldset>
					<fieldset>
						<legend>Sorting</legend>
						<div class="edit_row">
							<label for="edit_description"><span class="accesskey">D</span>escription</label>
							<input accesskey="d" id="edit_description" name="edit_description" onkeyup="valid_description(this)" type="text" value="'.$backoffice['product_detail']['description'].'"/>
						</div>
						<div class="edit_row">
							<label for="edit_department">Dep<span class="accesskey">a</span>rtment</label>
							<select accesskey="a" id="edit_department" name="edit_department" size=1 onchange="switchDept(this.options[this.selectedIndex].value)" >';
				foreach ($departments as $dept_no => $dept_name) {
					$html.='
								<option '.($dept_no==$backoffice['product_detail']['department']?'selected ':'').'value="'.$dept_no.'">'.$dept_name.'</option>';
				}
				
				$html.='
							</select>
						</div>
						<div class="edit_row">
							<label for="edit_subdepartment">Subdepartme<span class="accesskey">n</span>t</label>
							<select accesskey="n" id="edit_subdepartment" name="edit_subdepartment" size=1>';
				
				foreach ($subdepartments as $subdept_id => $subdept_name) {
					if (in_array($subdept_id, $subdept_ids[$backoffice['product_detail']['department']] )) {
					$html.='
								<option '.($subdept_id==$backoffice['product_detail']['subdept']?'selected ':'').'value="'.$subdept_id.'">'.$subdept_name.'</option>';
					}
				}
				
				$html.='
							</select>
						</div>
						<div class="edit_row">
							<label for="edit_vendor">Vendor</label>
							<select id="edit_vendor" name="edit_vendor" size=1>';
							$html .= '<option value=""> -- Unset -- </option>';
				foreach ($vendors as $vendor_id => $vendor_name) {
					$html.='
								<option '.($vendor_id==$backoffice['product_detail']['vendor_id']?'selected ':'').'value="'.$vendor_id.'">'.$vendor_name.'</option>';
				}
				
				$html.='
							</select>
						</div>
					</fieldset>
					<fieldset>
						<legend>Pricing</legend>
						<div class="edit_row">
							<label for="edit_price"><span class="accesskey">P</span>rice</label>
							<input accesskey="p" id="edit_price" name="edit_price" onkeyup="valid_price(this)" type="text" value="'.money_format("%!.2n", $backoffice['product_detail']['normal_price']).'"/>
						</div>
					</fieldset>
				</div>
				<div class="edit_column">
					<fieldset>
						<legend>Attributes</legend>
						<div class="edit_subcolumn">
							<label for="edit_tax"><span class="accesskey">T</span>ax</label>
							<input accesskey="t" id="edit_tax" name="edit_tax" onkeyup="valid_tax(this)" type="text" value="'.$backoffice['product_detail']['tax'].'"/>
							<label for="edit_tareweight">Tar<span class="accesskey">e</span></label>
							<input accesskey="e" id="edit_tareweight" name="edit_tareweight" onkeyup="valid_tareweight(this)" type="text" value="'.$backoffice['product_detail']['tareweight'].'"/>
							<label for="edit_size"><span class="accesskey">S</span>ize</label>
							<input accesskey="s" id="edit_size" name="edit_size" onkeyup="valid_size(this)" type="text" value="'.$backoffice['product_detail']['size'].'"/>
							<label for="edit_unitofmeasure"><span class="accesskey">U</span>nit</label>
							<input accesskey="u" id="edit_unitofmeasure" name="edit_unitofmeasure" onkeyup="valid_unitofmeasure(this)" type="text" value="'.$backoffice['product_detail']['unitofmeasure'].'"/>
							<label for="edit_deposit">Dep<span class="accesskey">o</span>sit</label>
							<input accesskey="o" id="edit_deposit" name="edit_deposit" onkeyup="valid_deposit(this)" type="text" value="'.money_format("%!.2n", $backoffice['product_detail']['deposit']).'"/>
						</div>
						<div class="edit_subcolumn">
							<label for="edit_foodstamp"><span class="accesskey">F</span>oodstamp</label>
							<input accesskey="f" '.($backoffice['product_detail']['foodstamp']?'checked ':'').'id="edit_foodstamp" name="edit_foodstamp" type="checkbox"/>
							<label for="edit_weighed"><span class="accesskey">W</span>eighed</label>
							<input accesskey="w" '.($backoffice['product_detail']['scale']?'checked ':'').'id="edit_weighed" name="edit_scale" type="checkbox"/>
							<label for="edit_advertised">Adve<span class="accesskey">r</span>tised</label>
							<input accesskey="r" '.($backoffice['product_detail']['advertised']?'checked ':'').'id="edit_advertised" name="edit_advertised" type="checkbox"/>
							<label for="edit_discount">D<span class="accesskey">i</span>scount</label>
							<input accesskey="i" '.($backoffice['product_detail']['discount']?'checked ':'').'id="edit_discount" name="edit_discount" type="checkbox"/>
							<label for="edit_wicable">WI<span class="accesskey">C</span></label>
							<input accesskey="c" '.($backoffice['product_detail']['wicable']?'checked ':'').'id="edit_wicable" name="edit_wicable" type="checkbox"/>
							<br /><label for="edit_alcohol">Alcoho<span class="accesskey">l</span></label>
							<input accesskey="l" '.($backoffice['product_detail']['alcohol']?'checked ':'').'id="edit_alcohol" name="edit_alcohol" type="checkbox"/>
							<label for="edit_inuse">Acti<span class="accesskey">v</span>e</label>
							<input accesskey="v" '.($backoffice['product_detail']['inUse']?'checked ':'').'id="edit_inuse" name="edit_inuse" type="checkbox"/>
						</div>
					</fieldset>
					<fieldset>
						<legend>Actions</legend>
						<input disabled type="button" value="Clone"/>
						<input type="button" name="action" value="Delete"
						onclick="if(confirm(\'Are you sure you want to delete this item?\')) { document.getElementById(\'a\').value = \'delete\'; this.form.submit(); }" />
						<input disabled type="button" value="Reset"/>
						<input type="submit" name="action" value="Save"/>
					</fieldset>
				</div>
			</form>';
		} else {
			$html='
			<!-- Some default message? -->';
		}

		return $html; 		
	}
?>
