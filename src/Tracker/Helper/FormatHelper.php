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

	public function convert_codebase_minutes($minutes, $hide_zeroes = false) {
		// Divide Minutes by 60 to get hours
		$hours = intval($minutes / 60);
		// Get a timestamp of the hours by * 60 to convert to mins and * 60 again to get seconds
		$hours_timestamp = ($hours * 60) * 60;
		// Times Total minutes spend on items by 60 to get a timestamp of time spent
		$seconds_timestamp = $minutes * 60;
		// Take the hours from the seconds in order to calculate remaining minutes into seconds
		$minutes_timestamp = $seconds_timestamp - $hours_timestamp;
		// Divide the minutes again to get minutes instead of a timestamp
		$minutes = $minutes_timestamp / 60;
		if($hide_zeroes) {
			if($hours == 0) {
				// If we have no hours then just show us minutes
				$overall_time = $minutes.' mins';
			} else {
				// Output a time in hours and minutes
				$overall_time = $hours.' hrs '.$minutes.' mins';
			}
		} else {
			$overall_time = $hours.' hrs '.$minutes.' mins';
		}
		// Convert hours into working days since a working day is 7 hours.
		$days = intval($hours / 7);
		// Give one hour as timestamp
		$one_hour = 3600;
		// Times it by 7 to get one day (since its 7 hours)
		$one_day = $one_hour * 7;
		// Get our total days spent by timesing a single day by how many days we have
		$total_days = $one_day * $days;
		// Get a representation of how many days we have
		$current_days = ($one_hour * 7) * $days;
		// Take our days spent from our whole timestamp to calculate remaining hours
		$remaining_hours = $seconds_timestamp - $current_days;
		// Because the gmdate function outputs 12 for some reason when it doesn't have enough hours we just set it to 0
		// if our timestamp is less than one hour
		if($remaining_hours < 3600) {
			$calculated_hours = 0;
		} else {
			// Convert the timestamp of hours into a remaining hours section
			$calculated_hours = gmdate('g', $remaining_hours);
		}
		if($days == 0) {
			$overall_time .= ' ('.$calculated_hours.'h '.$minutes.'m)';
		} else {
			$overall_time .= ' ('.$days.'d '.$calculated_hours.'h '.$minutes.'m)';	
		}
		return $overall_time;
	}
}