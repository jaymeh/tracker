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
			$data = array('client' => $data);
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

		// var_dump($ch);
		// 

		/* Check here if there are any errors. If its not a 200 chances are something went wrong. We will send an error code back and a friendly message. Which should already be in the code. Otherwise we just send back the decoded array? We could check for an error
		code variable and if it exists treat it as an error. Ability to skip and carry on since we still want to keep processing.

		Perhaps this could be stored in some kind or an error array that we can output after the fact? Alternatively a prompt to continue with import?

		if(!curl_errno($ch)) {
			// curl_getinfo($ch, CURLINFO_HTTP_CODE)
		} else {
			var_dump(curl_getinfo($ch, CURLINFO_HTTP_CODE));
		} */
	}

	public function me() {
		$me = $this->call('me');
	}

	public function workspaces() {
		$workspace = $this->call('/workspaces');

		$workspace[1] = $workspace[0];

		$workspace[1]['id'] = 58486759;

		if(count($workspace)) {
			return $workspace;
		} else {
			// No Workspace found. Handle the error.
			return array();
		}
	}

	public function createClient($client_name, $workspace_id) {
		$data = array('name' => $client_name, 'wid' => intval($workspace_id));

		$this->post('/clients', $data);
	}

	public function importProjects($projects) {

	}

	private function importProject($project) {

	}
}