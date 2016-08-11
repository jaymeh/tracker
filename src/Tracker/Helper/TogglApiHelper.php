<?php

namespace Tracker\Helper;

class TogglApiHelper {
	private $api_key = '8c5147af10d78d87f033d709a2b9f234';
	private $site_base_url = 'https://www.toggl.com/api/v8';
	private $toggl_workspace_id = '';

	private function call($endpoint) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->site_base_url.$endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, "$this->api_key:api_token");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$json_string = curl_exec($ch);

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

	public function me() {
		$me = $this->call('me');
	}

	public function workspaces() {
		$workspace = $this->call('/workspaces');

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

		if(count($projects)) {
			return $projects;
		} else {
			// No Projects found. We could handle an error here but its better to just
			// send back an empty array so we can check its length
			return array();
		}
	}

	public function importProjects($projects) {

	}

	private function importProject($project) {

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
}