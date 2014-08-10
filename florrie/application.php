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


// Main class - kicks things off, starts the party
class Florrie {

	// Class Constants:
	//  CONFIG	   - Configuration File
	//  CONTROLLER - Directory for main controllers
	const CONFIG     = '/florrie.cfg';
	const CONTROLLER = '/florrie/controller/';


	// Data members:
	//  config - The configuration for this controller
	public $config;


	// Set up all of the basic stuff required to run the comic
	public function __construct() {

		try {

			// If Florrie hasn't been installed yet, we should probably address that
			if(!$this->installed()) {

				// TODO: Florrie install procedure
				die('IOU: One installer. - Windigo');
			}

			// shift the controller type off of the URI variables
			$uri = $this->parseURI();
			$type = array_shift($uri);

			// Get controller object, and route the request
			$controller = $this->getController($type);
			$controller->route($uri);
		}
		// Handle any errors that may have occurred
		catch (exception $e) {

			$controller = $this->getController('error');

			if(get_class($e) === 'NotFoundException') {

				// Handle a 404
				$controller->notFound($e->getMessage());
			}
			else if(get_class($e) === 'ServerException') {

				// Handle a server error
				$controller->serverError($e->getMessage());
				//echo '500: '.$e->getMessage();
			}
			else if(get_class($e) === 'DBException' ||
				get_class($e) === 'DBException') {

				// Properly handle DB connection errors
				$controller->dbError($e->getMessage());
				//echo 'DB error: '.$e->getMessage();
			}
			else {

				// Properly handle DB connection errors
				$controller->unknownError($e->getMessage());
				//echo 'DB error: '.$e->getMessage();
			}
		}
	}


	// Get the appropriate controller object
	public function getController($controller) {

		// Get the controller path
		$cPath = $_SERVER['DOCUMENT_ROOT'].self::CONTROLLER;

		// If no controller was specified, use the main controller
		if(empty($controller)) {

			require $cPath.'main.php';

			return new Main($this->config);
		}

		// Check the standard Florrie controllers
		if(file_exists($cPath.strtolower($controller).'.php')) {

			require_once $cPath.strtolower($controller).'.php';

			// Return the new controller
			return new $controller($this->config);
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


	// Get the configuration file, if present, and return the
	//	configuration array
	public function getPlugins()
	{
		// TODO: Work Ongoing!
		//	Love,
		//	- Windigo
		return;
	}


	// Check to see if Florrie's installed or not
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


	// Parse an XML element, recursively, into an array
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


	// Split the URI into usable chunks
	protected function parseURI() {

			// Sanitize the URL, and trim the leading/trailing slashes
			$uri = filter_input(INPUT_GET, 'u', FILTER_SANITIZE_URL);
			$uri = trim($uri, '/');

			// Burst into an array
			return explode('/', $uri);
	}


	// Read the configuration file, if present, and store for later
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
