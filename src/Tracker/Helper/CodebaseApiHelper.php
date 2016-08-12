<?php

namespace Tracker\Helper;

class CodebaseApiHelper {
	private $api_user = 'creode/jamie-sykes-30';
	private $api_key = 'b9114c3c026dc58212e8e8a44a8c05dc5fcf0f9f';
	private $site_base_url = 'https://api3.codebasehq.com';

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

	public function updateTicketTime($project, $ticket_id, $time) {
		// (/project/tickets/ticket_id/notes)
		/* 
		<ticket-note>
		    <content>Updating the Status</content>
		    <time-added>46</time-added>
		    <changes>
		        <status-id>{NEW_ID}</status-id>
		        <priority-id>{NEW_ID}</priority-id>
		        <category-id>{NEW_ID}</category-id>
		        <assignee-id></assignee-id>
		        <milestone-id></milestone-id>
		        <summary>A new summary</summary>
		    </changes>
		    <upload-tokens type="array">
		      <upload-token>c2959f1c-0297-0af7-4e52-ed0bc3e2fb02</upload-token>
		    </upload-tokens>
		    <private>1</private>
		</ticket-note>
		*/
	}

	public function createTimeSession($project, $time, $note = '', $date = false) {

		/* 
		<time-session>
		  <id type="integer">1234</id>
		  <summary>Worked on the awesome time tracking in Codebase</summary>
		  <minutes type="integer">180</minutes>
		  <session-date type="date">2009-09-05</session-date>
		  <user-id type="integer">1234</user-id>
		  <created-at type="datetime">2013-09-10T18:45:44Z</created-at>
		  <updated-at type="datetime">2013-09-10T18:45:44Z</updated-at>
		</time-session>
		*/
	}
}