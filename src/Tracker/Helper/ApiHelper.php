<?php

namespace Tracker\Helper;

class ApiHelper {
	private $api_user = 'creode/jamie-sykes-30';
	private $api_key = 'b9114c3c026dc58212e8e8a44a8c05dc5fcf0f9f';
	private $site_base_url = 'http://api3.codebasehq.com';

	/**
	 * Calls the api
	 * @param  string $endpoint The endpoint of the api containing a trailing slash.
	 * (The final path used for API call e.g. /projects).
	 * @return array           	Returns a php array of items given back from the api
	 */
	private function call($endpoint) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->site_base_url.$endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, "$this->api_user:$this->api_key");

		$xml_data = curl_exec($ch);

		$xml = simplexml_load_string($xml_data);
		$json = json_encode($xml);
		$array = json_decode($json,TRUE);

		return $array;
	}

	public function projects() {
		$projects = $this->call('/projects');

		if(count($projects)) {
			var_dump($projects);
		}
	}
}