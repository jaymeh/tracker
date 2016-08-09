<?php

namespace Tracker\Helper;

class TogglApiHelper {
	private $api_key = '8c5147af10d78d87f033d709a2b9f234';
	private $site_base_url = 'https://www.toggl.com/api/v8';

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

	public function importProjects($projects) {

	}

	private function importProject($project) {

	}
}