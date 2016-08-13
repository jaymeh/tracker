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
}