<?php

namespace Tracker\Helper;

class ConfigHelper {
	protected $codebase_user;
	protected $codebase_password;
	protected $toggl_api;
	protected $workspace_id;

	public function __construct() {
		$user = exec('whoami');

		$directory = $_SERVER['HOME'] . '/.tracker/';

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
	}
}