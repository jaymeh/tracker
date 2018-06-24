<?php

namespace Tracker\Helper;

use Symfony\Component\Yaml\Yaml;

class ConfigHelper {
	protected $codebase_user;
	protected $codebase_password;
	protected $toggl_api_key;
	protected $workspace_id;
	protected $parsed_config;

	public function __construct() {
		$user = exec('whoami');

		$directory = $_SERVER['HOME'] . '/.tracker/';

		if(!file_exists($directory)) {
    		// Try to create directory
	    	$directory_create = mkdir($directory, 0777, true);

	    	if(!$directory_create) {
	    		throw new Exception('Failed to create directory inside: '.$directory.'. Please check this is writable by: '.$user);
	    	}
    	}

    	$file = $directory.'config.yml';

    	try {
    		if(!file_get_contents($file)) {
    			throw new Exception('Could not find configuration file in '.$file.'. Please check that the configure command has been run.');
    		}
		    $this->parsed_config = Yaml::parse(file_get_contents($file), Yaml::PARSE_OBJECT);
		} catch (ParseException $e) {
			throw new Exception("Unable to parse the string: " . $e->getMessage());
		}
	}

	public function getCodebaseUser() {
		return isset($this->parsed_config['cb_api_user']) ?: null;
	}

	public function getCodebaseApiKey() {
		return isset($this->parsed_config['cb_api_key']) ?: null;
	}

	public function getTogglApiKey() {
		return isset($this->parsed_config['toggl_api_key']) ?: null;
	}

	public function getTogglWorkspaceId() {
		return isset($this->parsed_config['toggl_workspace_id']) ?: null;
	}
}
