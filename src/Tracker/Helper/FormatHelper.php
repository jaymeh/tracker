<?php

namespace Tracker\Helper;

class FormatHelper {
    public function get_string_between($string, $start = '{', $end = '}'){
	    $string = ' ' . $string;
	    $ini = strpos($string, $start);
	    if ($ini == 0) { return false; }
    	$ini += strlen($start);
    	$len = strpos($string, $end, $ini) - $ini;
	    return substr($string, $ini, $len);
	}

	public function delete_all_between($string, $start, $end) {
	  $startPos = strpos($string, $start);
	  $endPos = strpos($string, $end);
	  if ($startPos === false || $endPos === false) {
	    return $string;
	  }

	  $textToDelete = substr($string, $startPos, ($endPos + strlen($end)) - $startPos);

	  return str_replace($textToDelete, '', $string);
	}
}