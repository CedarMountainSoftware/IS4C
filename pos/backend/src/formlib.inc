<?php


function startform($action = "", $target = "", $onsubmit = "", $multipart = true) {
	global $PHP_SELF;
	if ($action == "") {
		
	}
	$html = "<form method=\"post\" action=\"$action\"" .
		($target != "" ? " target=\"$target\"": "" ) .
		($onsubmit != "" ? " onsubmit=\"$onsubmit\"" : "") .
		($multipart ? " enctype=\"multipart/form-data\"" : "") .

		">\n";
	$html .= hiddeninput("formsubmit", "1");
	return $html;
}

function endform() {
	$html = "</form>\n";
	return $html;
}

function selectbox($name, $value, $options, $params = array()) {
	$html = "<select name=\"$name\" id=\"$name\" " .
		(array_key_exists("onchange", $params) ? (' onchange="' . $params["onchange"] . '"'): "" ) .
		(array_key_exists("class", $params) ? (' class="' . $params["class"] . '"') : "") . 
		(array_key_exists("style", $params) && $params['style'] ? " style=\"" . $params['style'] . "\" " : "") . 
		(array_key_exists("disabled", $params) && $params['disabled'] ? " disabled=\"disabled\" " : "") . 
		(array_key_exists("size", $params) && $params['size'] ? " size=\"" . $params["size"] . "\" " : "") . 
		(array_key_exists("multiple", $params) && $params['multiple'] ? " multiple=\"multiple\" " : "") . 
		">";
	if ((array_key_exists("pick_one", $params) && $params["pick_one"]) || array_key_exists("pick_text", $params))
		$html .= "<option value=\"\">" . (array_key_exists("pick_text", $params) ? $params["pick_text"] : "-- Pick One --") . "</option>";

	foreach ($options as $key => $val) {
		if (is_array($val)) {
			$html .= "<optgroup label=\"" . htmlentities($key) . "\">";
			foreach ($val as $key2 => $txt) {
				$html .= "<option value=\"" . htmlentities($key2) . "\"". ($value == $key2 ? " selected=\"selected\"" : "") . ">" . htmlentities($txt) . "</option>";
			}
			$html .= "</optgroup>";
		} else {
			$html .= "<option value=\"" . htmlentities($key) . "\"". ($value == $key ? " selected=\"selected\"" : "") . ">" . htmlentities($val) . "</option>";
		}
	}
	$html .= "</select>\n";
	return $html;
}

function textbox($name, $value, $size = "", $maxlength = "", $params = array()) {
	$ret = "<input type=\"text\" name=\"" . htmlentities($name) . "\" id=\"" . htmlentities($name) . "\" value=\"" . htmlentities($value) . "\" " . ($size ? (" size=\"" . $size . "\""): "" ). ($maxlength ? (" maxlength=\"" . $maxlength . "\"") : "") . 
		(array_key_exists("class", $params) && $params["class"] ? " class=\"" . $params["class"] . "\" " : "") .
		(array_key_exists("disabled", $params) && $params['disabled'] ? " disabled=\"disabled\" " : "") . 
		(array_key_exists("readonly", $params) && $params['readonly'] ? " readonly=\"readonly\" " : "") . 
		(array_key_exists("onchange", $params) && $params['onchange'] ? " onchange=\"" . $params['onchange'] . "\" " : "") . 
		(array_key_exists("style", $params) && $params['style'] ? " style=\"" . $params['style'] . "\" " : "") . 
		" />";
	return $ret;
}

function textarea($name, $value, $rows, $cols, $params = array()) {
	return "<textarea name=\"" . htmlentities($name) . "\" id=\"" . htmlentities($name) . "\"rows=\"$rows\" cols=\"$cols\" " .
		(array_key_exists("class", $params) ? ('class="' . $params["class"] . '"'): "") .
		(array_key_exists("onchange", $params) ? ('onchange="' . $params["onchange"] . '"'): "") .
		" wrap=auto>" . htmlentities($value) . "</textarea>";
}

function checkbox($name, $checked, $params = array()) {
	return "<input type=\"checkbox\" name=\"". htmlentities($name) . "\" id=\"" . htmlentities($name) . "\" value=\"1\" " . ($checked ? " checked" : "") .
		" class=\"" . (array_key_exists("class", $params) ? $params["class"] : "") . '"'.
		(array_key_exists("onclick", $params) ? " onclick=\"" . $params["onclick"] . "\"" : "").
		(array_key_exists("disabled", $params) && $params['disabled'] ? " disabled=\"disabled\" " : "") .
		" />";
}

function radiobutton($name, $opt_val, $curval) {
	return "<input type=\"radio\" name=\"" . htmlentities($name) . "\" value=\"" . $opt_val . "\" " .
		($opt_val == $curval ? " checked":"");
}



function hiddeninput($name, $value) {
	return "<input type=\"hidden\" name=\"" . htmlentities($name) . "\" id=\"" . htmlentities($name) . "\" value=\"" . htmlentities($value) . "\" />";
}

function fileupload($name) {
	return "<input type=\"file\" name=\"" . htmlentities($name) . "\" />";
}

function submitbox($name, $value, $params = array()) {
	$html = "<input type=\"submit\" name=\"" . htmlentities($name) . "\" value=\"" . htmlentities($value) . "\" " .
		(array_key_exists("class", $params) ? ("class=\"" . $params["class"] . '"') : "") .
		(array_key_exists("onclick", $params) ? ("onclick=\"" . $params["onclick"]) . "\"": "") .
		" />";
		return $html;
}

function datepickinput($name) {
	$html = '<input type="text" name="'.$name.'" id="'.$name.'" size="12" maxlength="10" />' .
		'<a href="javascript:NewCal(\''.$name.'\',\'yyyyMMdd\',false,24)"><img src="/images/cal.gif" width="16" height="16" border="0" alt="Pick a date"></a>';
	return $html;
}

function formlabel($text, $form_element="") {
	$html = '<label for="' . $form_element . '">' . $text . '</label>';
	return $html;
}

function tablerow() {
	$cells = func_get_args();
	$html = "";
	$html .= "<tr>";
	foreach ($cells as $cell) {
		$html .= "<td>";
		$html .= $cell;
		$html .= "</td>";
	}
	$html .= "</tr>";
	return $html;
}

function tableheaderrow() {
	$cells = func_get_args();
	$html .= "<tr>";
	foreach ($cells as $cell) {
		$html .= "<th>";
		$html .= $cell;
		$html .= "</th>";
	}
	$html .= "</tr>";
	return $html;
}

?>
