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


if(!class_exists('Florrie')) {
class Florrie {

	// Class Constants:
	//	BASE    - Installed directory
	//	CONFIG	- Configuration File
	const CONFIG     = '/config/config.xml';
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
		if(($type !== null && !ctype_alpha($type)) ||
			($action !== null && !ctype_alpha($action))) {

			// Funny business
			throw new exception('Invalid controller type');
		}


		try {
			// Get controller object
			$controller = $this->getController($type);

			// If there is no action, show the main index
			if($action === null) {

				$controller->Index();
			}
			// Otherwise, check and see if this controller supports
			//  this action
			else if(method_exists($controller, $action)) {

				call_user_func(array($controller, $action));
			}
		}
		catch (NotFoundException $e) {
			// TODO: Handle a 404
		}
	}



	// ReadConfig
	// Purpose:	Get the configuration file, if present, and store for later
	protected function readConfig() {

		// Skip this step if data is already present
		if(!empty($this->config) && is_array($this->config)) {

			return;
		}

		// Check to see if the configuration file exists
		$configFile = realpath($_SERVER['DOCUMENT_ROOT'].self::CONFIG);

		if(!file_exists($configFile)) {

			throw new exception('Configuration file not present!');
		}


		// If this is the first time getting the config, try to parse the
		//	configuration file. The second argument returns a multidimensional
		//	array based on sections
		//$config = parse_ini_file($configFile, true);

		// If we failed to get the configuration, throw an exception
		if(empty($config)) {

			throw new exception('Unable to parse "'.basename(self::CONFIG).'".');
		}

		$this->config = $config;
	}



	// GetController
	// Purpose: Return the appropriate controller object
	public function getController($type) {

		// Get the controller path
		$cPath = $_SERVER['DOCUMENT_ROOT'].self::CONTROLLER;

		// Check the standard Florrie controllers
		if(file_exists($cPath.$type.'.php')) {

			return include $cPath.$type.'.php';
		}

		// Plugins! TODO
//		if(file_exists(self::CONTROLLER.$type.'.php')) {
//
//			return include self::CONTROLLER.$type.'.php';
//		}

		// Default to the main controller
		if($type === null) {

			// Set up a main controller, and serve up the index
			return include $cPath.'main.php';
		}

		throw new NotFoundException();
	}



	// GetPlugins
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


	// Installed
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
		}

		return $installed;
	}



	// RouteRequest
	// Purpose: Take a HTTP request, and route it to the correct controller
	public function routeRequest() {


		throw new NotFoundException();
	}
}}



//----------------------------------------
// Custom Exception Types
//----------------------------------------

// An exception thrown in a HTTP404 case.
if(!class_exists('NotFoundException')) {
	class NotFoundException extends exception {}
}




//----------------------------------------
// Return an instance of the application
//----------------------------------------

return new Florrie();
?>
