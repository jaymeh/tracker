<?php

namespace Tracker\Helper;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class CodebaseApiHelper {
	private $site_base_url = 'https://api3.codebasehq.com';

	function __construct() {
		$this->get_config_data();
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

		curl_setopt($ch, CURLOPT_URL, $call_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, "$this->api_user:$this->api_key");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$xml_data = curl_exec($ch);

		// Build some kind of error handling here.
		var_dump($xml_data);
		die;

		$xml = simplexml_load_string($xml_data);
		$json = json_encode($xml);
		$array = json_decode($json,TRUE);

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

		if(count($projects)) {
			foreach($projects['project'] as $key => $project) {
				if(!$include_archived) {
					if($project['status'] == 'archived') {
						unset($projects['project'][$key]);
					} 
				}
			}

			return $projects['project'];
		}
	}

	public function getProjectByName($name) {
		$project = $this->call('/'.$name);

		if(count($project)) {	
			return $project;
		}
	}

	public function createTimeSession($project, $time, $note, $ticket_id = false) {
		$endpoint = '/'.$project.'/time_sessions';

		$xml_data = new \SimpleXMLElement('<?xml version="1.0"?><time-session></time-session>');

		$data_array = array();

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
			unset($data_array['summary']);
		}

		$data_array = array_flip($data_array);

		if($data_array == NULL) {
			return false;
		}

		array_walk_recursive($data_array, array ($xml_data, 'addChild'));

		$post_data = $xml_data->asXML();

		$posted = $this->post($endpoint, $post_data);

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

	public function get_config_data() {
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
}