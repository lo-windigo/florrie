<?php
/*
	Main Application - Florrie
	By Jacob Hume

	This file is part of Florrie.

	Florrie is free software: you can redistribute it and/or modify it
	under the terms of the GNU Affero General Public License as published
	by the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	Florrie is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with Florrie.  If not, see <http://www.gnu.org/licenses/>.
*/


// Include the exception classes
require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/error.php';


class Florrie {

	// Class Constants:
	//	BASE    - Installed directory
	//	CONFIG	- Configuration File
	const CONFIG     = '/florrie.cfg';
	const CONTROLLER = '/florrie/controller/';

	public $config, $urls;


	// Constructor
	// Purpose:	Set up all of the basic stuff required to run the comic
	public function __construct() {

		// If Florrie hasn't been installed yet, we should probably address that
		if(!$this->installed()) {

			// TODO: Florrie install procedure
			die('IOU: One installer. - Windigo');
		}


		//----------------------------------------
		// Handle requests
		//----------------------------------------

		// Sanitize the URL
		$uri = filter_input(INPUT_GET, 'u', FILTER_SANITIZE_URL);

		// Trim off the leading slash, if present
		if(strlen($uri) > 0) {
	
			$uri = substr($uri, 1);
		}

		// Burst into an array and remove the controller type
		$uriArray = explode('/', $uri);
		$type = array_shift($uriArray);

		try {

			// Get controller object, and route the request
			$controller = $this->getController($type);
			$controller->route($uriArray);
		}
		// Handle any errors that may have occurred
		catch (exception $e) {

			if(get_class($e) === 'NotFoundException') {

				// TODO: Properly handle a 404
				echo '404: '.$e->getMessage();
			}
			else if(get_class($e) === 'ServerErrorException') {

				// TODO: Properly handle a server error
				echo '500: '.$e->getMessage();
			}
			else if(get_class($e) === 'ServerErrorException') {
				// TODO: Properly handle DB connection errors
				echo 'DB error: '.$e->getMessage();
			}
			else {

				// TODO
				echo 'Other: '.$e->getMessage();
			}
		}
	}



	// getController
	// Purpose: Return the appropriate controller object
	public function getController($controller) {

		// Get the controller path
		$cPath = $_SERVER['DOCUMENT_ROOT'].self::CONTROLLER;

		// If no controller was specified, use the main controller
		if(empty($controller)) {

			require $cPath.'main.php';

			return new Main($this->config['florrie']);
		}

		// Check the standard Florrie controllers
		if(file_exists($cPath.strtolower($controller).'.php')) {

			require_once $cPath.strtolower($controller).'.php';

			// Send in a config array
			if(empty($this->config[$controller])) {
				$config = $this->config['florrie'];
			}
			else {
				$config = array_merge($this->config[$controller],
					$this->config['florrie']);
			}

			// Return the new controller
			return new $controller($config);
		}

		// Plugins! TODO
//		if(file_exists(self::CONTROLLER.$controller.'.php')) {
//
//			return include self::CONTROLLER.$controller.'.php';
//		}

		// Default to 404
		throw new NotFoundException('Unknown controller/type. Controller: "'.
			$controller.'"');
	}



	// getPlugins
	// Purpose:	Get the configuration file, if present, and return the
	//	configuration array
	// Return:	void, but an exception may be thrown
	public function getPlugins()
	{
		// Work Ongoing!
		//	Love,
		//	- Windigo
		return;
	}


	// installed
	// Purpose:	Check to see if Florrie's installed or not
	public function installed()
	{
		$installed = false;

		try
		{
			// Attempt to get required components, like configuration files
			$this->readConfig();
			$this->getPlugins();

			// If all of these checks occur without issue, then it must be installed!
			$installed = true;
		}
		catch (exception $e)
		{
			// Log stuff
			$installed = false;
		}

		return $installed;
	}



	// parse an XML element, recursively, into an array
	protected function parseConfig($node) {

		// If we have children, we will need to start an array and fill it with 
		//   the child nodes' values, recursively
		$values = array();

		foreach($node->childNodes as $child) {

			// Recurse through this child if it's an XML element
			if($child->nodeType == XML_ELEMENT_NODE) {
				$values[$child->nodeName] = $this->parseConfig($child);
			}
		}


		// If there are no child elements on this node, return its value
		if(empty($values)) {
			return $node->nodeValue;
		}

		// Otherwise, return the child node's values
		return $values;
	}



	// readConfig
	// Purpose:	Get the configuration file, if present, and store for later
	protected function readConfig() {

		// Check to see if the configuration file exists
		$configFile = $_SERVER['DOCUMENT_ROOT'].self::CONFIG;

		if(!file_exists($configFile)) {

			throw new exception('Configuration file not present!');
		}


		// If this is the first time getting the config, try to parse the
		//	configuration file. The second argument returns a multidimensional
		//	array based on sections
		$configDoc = new DOMDocument();
		$configDoc->load($configFile);

		// If we failed to get the configuration, throw an exception
		if($configDoc === false) {

			throw new exception('Unable to parse "'.basename(self::CONFIG).'".');
		}

		// Get the base configuration node
		$configNode = $configDoc->documentElement;

		// Parse the configuration file into an associative array
		$config = $this->parseConfig($configNode);

		// Save the configuration values for later
		$this->config = $config;
	}
}
?>
