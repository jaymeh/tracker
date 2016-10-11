<?php

namespace Tracker\Helper;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class TogglApiHelper {
	private $site_base_url = 'https://www.toggl.com/api/v8';
	
	function __construct() {
		$error = $this->getConfigData();
		if($error !== true) {
			echo $error;
			exit;
		}
	}

	private function call($endpoint) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->site_base_url.$endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, "$this->api_key:api_token");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$json_string = curl_exec($ch);

		$response = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if($response !== 200) {
			$error = trim($xml_data);
			return $error;
		}

		$array = json_decode($json_string,TRUE);

		return $array;
	}

	private function post($endpoint, $data) {
		// Convert data to json
		if(is_array($data)) {
			$data = json_encode($data);
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->site_base_url.$endpoint);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, "$this->api_key:api_token");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    		'Content-Type: application/json',
    	));

		$json_string = curl_exec($ch);

		$error_handler = $this->checkErrors($ch, $json_string);

		if(isset($error_handler['error_code'])) {
			return $error_handler;
		}

		$decoded_json = $error_handler;

		return $decoded_json;
	}

	public function workspaces() {
		$workspace = $this->call('/workspaces');

		if(!is_array($workspace)) {
			return $workspace;
		}

		if(count($workspace)) {
			return $workspace;
		} else {
			// No Workspace found. Handle the error.
			return array();
		}
	}

	public function createClient($client_name, $workspace_id) {
		$data = array('client' => array('name' => $client_name, 'wid' => intval($workspace_id)));

		$response_data = $this->post('/clients', $data);

		return $response_data;
	}

	public function createProject($project_name, $workspace_id, $client_id, $template_id) {
		// Call the api and push data
		// {"project":{"name":"An awesome project","wid":777,"template_id":10237,"is_private":true,"cid":123397}}
		$endpoint = '/projects';

		$project_data = array(
			'project' => array(
				'name' => $project_name,
				'wid' => $workspace_id,
				'template_id' => $template_id,
				'is_private' => false,
				'cid' => $client_id,
			),
		);

		$project_data = json_encode($project_data);

		$project_response = $this->post($endpoint, $project_data);

		return $project_response;
	}

	public function getProjectByClient($client_id) {
		$endpoint = '/clients/'.$client_id.'/projects';

		$projects = $this->call($endpoint);

		if(!is_array($projects)) {
			return $projects;
		}

		if(count($projects)) {
			return $projects;
		} else {
			// No Projects found. We could handle an error here but its better to just
			// send back an empty array so we can check its length
			return array();
		}
	}

	public function times($start_date, $end_date, $workspace_id = false) {
		$endpoint = '/time_entries?start_date='.urlencode($start_date).'&end_date='.urlencode($end_date);

		$time_items = $this->call($endpoint);

		// Because the API Call doesn't allow us to filter based on workspace_id
		// I will do it myself :D
		foreach($time_items as $key => $time_item) {
			if($workspace_id !== false) {
				if(isset($time_item['wid']) && $time_item['wid'] !== $workspace_id) {
					unset($time_items[$key]);
				}
			}
		}

		return $time_items;
	}

	public function getProjectById($project_id) {
		$endpoint = '/projects/'.$project_id;

		$project = $this->call($endpoint);

		if(isset($project['data'])) {
			$project = $project['data'];
		}
		
		return $project;
	}
	
	private function checkErrors($ch, $json_string) {
		// Get the response code
		if(curl_errno($ch)) {
			return array('error_code' => '500', 'error_message' => 'Invalid response came back from curl. Please check the command.');
		}

		switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
		    case 200:  # OK
		    	return json_decode($json_string, TRUE);
		      	break;
		    default:
		    	return array('error_code' => $http_code, 'error_message' => trim($json_string));
		}
	}

	public function getConfigData() {
		$user = exec('whoami');

		$directory = '/Users/'.$user.'/.tracker/';

		if(!file_exists($directory)) {
    		// Try to create directory
	    	$error = 'Failed to find directory: '.$directory.'. Please create this using the configure command';
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

			$this->api_key = $value['toggl_api_key']; // Codebase api key

			return true;
		}

		return $error;
	}
}