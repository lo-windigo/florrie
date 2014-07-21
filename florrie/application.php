<?php
/*
	Controller - Florrie Base Module
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
		}


		//----------------------------------------
		// Handle requests
		//----------------------------------------

		// Sanitize type & actions
		$type   = filter_input(INPUT_GET, 't', FILTER_SANITIZE_URL);
		$action = filter_input(INPUT_GET, 'a', FILTER_SANITIZE_URL);

		// Types and actions should be alphabetic characters
		if((!is_null($type) && !ctype_alpha($type)) ||
			(!is_null($action) && !ctype_alpha($action))) {

			// Funny business
			echo 'Invalid controller type';
		}


		try {
			// Get controller object
			$controller = $this->getController($type);

			// If there is no action, show the main index
			if(is_null($action)) {

				$controller->index();
			}
			// Otherwise, check and see if this controller supports
			//  this action
			else if(method_exists($controller, $action)) {

				call_user_func(array($controller, $action));
			}
			else {

				throw new NotFoundException();
			}
		}
		catch (exception $e) {

			if(get_class($e) === 'NotFoundException') {

				// TODO: Handle a 404
				echo '404';
			}
			else if(get_class($e) === 'ServerErrorException') {

				// TODO: Handle a server error
				echo '500';
			}
			else {

				// TODO
				echo 'Other';
			}
		}
	}



	// getController
	// Purpose: Return the appropriate controller object
	public function getController($controller) {

		// Get the controller path
		$cPath = $_SERVER['DOCUMENT_ROOT'].self::CONTROLLER;

		// Check the standard Florrie controllers
		if(file_exists($cPath.strtolower($controller).'.php')) {

			require_once $cPath.strtolower($controller).'.php';

			return new $controller($this->config['florrie']);
		}

		// Plugins! TODO
//		if(file_exists(self::CONTROLLER.$controller.'.php')) {
//
//			return include self::CONTROLLER.$controller.'.php';
//		}

		// Default to the main controller
		if($controller === null) {

			// Set up a main controller, and serve up the index
			require $cPath.'main.php';

			return new Main($this->config['florrie']);
		}
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
		$configFile = realpath($_SERVER['DOCUMENT_ROOT'].self::CONFIG);

		if(!file_exists($configFile)) {

			throw new exception('Configuration file not present!');
		}


		// If this is the first time getting the config, try to parse the
		//	configuration file. The second argument returns a multidimensional
		//	array based on sections
		$configDoc = DOMDocument::load($configFile);

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
