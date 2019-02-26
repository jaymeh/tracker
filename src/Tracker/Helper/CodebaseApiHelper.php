<?php

namespace Tracker\Helper;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class CodebaseApiHelper {
	private $site_base_url = 'https://api3.codebasehq.com';

	function __construct() {
		$error = $this->getConfigData();
		if($error !== true) {
			echo $error;
			exit;
		}
	}

	/**
	 * Calls the api
	 * @param  string $endpoint The endpoint of the api containing a trailing slash.
	 * (The final path used for API call e.g. /projects).
	 * @return array           	Returns a php array of items given back from the api
	 */
	private function call($endpoint, $options = false) {
		$ch = curl_init();

		// Might not need this we will see if we should delete it at some point
		if($options) {
			$call_url = $this->site_base_url.$endpoint.'?'.$options;
		} else {
			$call_url = $this->site_base_url.$endpoint;
		}

		$headers = array();
		$headers[] = 'Accept: application/json';
		$headers[] = 'Content-Type: application/json';

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $call_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		// curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, "$this->api_user:$this->api_key");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$data = curl_exec($ch);

		$array = json_decode($data,TRUE);

		return $array;
	}

	private function post($endpoint, $data) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->site_base_url.$endpoint);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $this->api_user.':'.$this->api_key);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    		'Content-Type: application/xml',
    	));

		$xml_data = curl_exec($ch);

		$xml = simplexml_load_string($xml_data);
		$json = json_encode($xml);
		$array = json_decode($json,TRUE);

		return $array;
	}

	public function projects($include_archived = false) {
		$projects = $this->call('/projects');

		if(!is_array($projects)) {
			return $projects;
		}

		if(count($projects)) {
			foreach($projects as $key => $project) {
				$project = $project['project'];
				if(!$include_archived) {
					if($project['status'] == 'archived') {
						unset($projects['project'][$key]);
					} 
				}
			}

			return $projects;
		}
	}

	public function getProjectByName($name) {
		$project = $this->call('/'.$name);

		if(!is_array($project)) {
			return $project;
		}

		if(count($project)) {	
			return $project;
		}
	}

	public function createTimeSession($project, $time, $note, $ticket_id = false) {
		$endpoint = '/'.$project.'/time_sessions';

		$xml_data = new \SimpleXMLElement('<?xml version="1.0"?><time-session></time-session>');

		$data_array = array();

		// Check that the time entry exists?
		if(!isset($time['start'])) {
			return false;
		}

		if(!$start_timestamp = strtotime($time['start'])) {
			return false;
		}

		if(!isset($time['duration'])) {
			return false;
		}

		$data_array['minutes'] = intval(round($time['duration'] / 60));
		
		if($note !== false) {
			$data_array['summary'] = $note;
		}

		$data_array['session-date'] = date('Y-m-d', $start_timestamp);

		if($ticket_id !== false) {
			$data_array['ticket-id'] = $ticket_id;
			$data_array['summary'] = trim(substr($data_array['summary'], strpos($data_array['summary'], "]") + 1));
		}

		$data_array = array_flip($data_array);

		if($data_array == NULL) {
			return false;
		}

		array_walk_recursive($data_array, array ($xml_data, 'addChild'));

		$post_data = $xml_data->asXML();
		$posted = $this->post($endpoint, $post_data);

		$posted = true;

		return $posted;
	}

	public function checkTicketId($ticket_string) {
		// Just strip touch out. In future I will put something in here
		// to grab it as a json string and parse it.
		$ticket_id = str_replace('touch:', '', $ticket_string);
		$ticket_id = trim($ticket_id);
		$ticket_id = intval($ticket_id);

		return $ticket_id;
	}

	public function getConfigData() {
		$user = exec('whoami');

		$directory = '/Users/'.$user.'/.tracker/';

		if(!file_exists($directory)) {
    		// Try to create directory
	    	$directory_create = mkdir($directory, 0777, true);

	    	if(!$directory_create) {
	    		$error = 'Failed to create directory inside: '.$directory.'. Please check this is writeable by: '.$user;
	    		return $error;
	    	}
    	}

    	$file = $directory.'config.yml';

    	$error = false;

    	try {
    		if(!file_get_contents($file)) {
    			return 'Could not find configuration file in '.$file.'. Please check that the configure command has been run.';
    		}
		    $value = Yaml::parse(file_get_contents($file));
		} catch (ParseException $e) {
		    $error = printf("Unable to parse the YAML string: %s", $e->getMessage());
		}

		if(!$error) {
			if(!isset($value) || !is_array($value)) {
				return 'Cannot parse config file. Please check that this has been created.';
			}

			$this->api_user = $value['cb_api_user']; // Codebase api username
			$this->api_key = $value['cb_api_key']; // Codebase api key

			return true;
		}

		return $error;
	}

	public function getTimeSessions($project, $dateFrom, $dateTo) {
		$dateFromFormatted = $dateFrom->format('Y-m-d');
		$dateToFormatted = $dateTo->format('Y-m-d');
		$today = date('Y-m-d');

		// Replace this section and use the following endpoint
		// http://api3.codebasehq.com/profile
		// This wasn't in the API docs
		$user = $this->getCurrentUser();
		$userId = $user['id'];

		// Today was used
		if($today == $dateFromFormatted && $today == $dateToFormatted) {
			$time_string = '/'.$project.'/time_sessions/day';
		// Yesterday was used
		} else if($dateFromFormatted == $dateToFormatted) {
			$time_string = '/'.$project.'/time_sessions?from='.$dateToFormatted;
		// Custom date was used (FIX THIS AT SOME POINT)
		} else {
			$modifiedTo = clone $dateTo;
			$modifiedTo->modify('+1 days');
			$modifiedFrom = clone $dateFrom;
			$dateFromFormatted = $modifiedFrom->format('Y-m-d');
			$dateToFormatted = $modifiedTo->format('Y-m-d');

			$time_string = '/'.$project.'/time_sessions?to='.$dateToFormatted.'&from='.$dateFromFormatted;
		}

		$time_sessions = $this->call($time_string);
		$new_times = array();

		foreach($time_sessions as $time_session) {
			if($time_session['time_session']['user_id'] == $userId) {
				$new_times[] = $time_session['time_session'];
			}
		}

		return $new_times;
	}

	public function getCurrentUser() {
		$user = $this->call('/profile');

		if(!isset($user['user'])) {
			return false;
		}

		$user = $user['user'];

		return $user;
	}
}
