<?php
/*
	Core Functionality
	Copyright © 2015 Jacob Hume

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
require_once __DIR__.'/../florrie/florrie.php';


// Handles all of the web interface to Florrie
class FlorrieWeb extends Florrie {

	//----------------------------------------
	// Class Constants
	//
	//  CONTROLLER - Main controllers
	//  TEMPLATES  - System templates
	//  THEMES     - User-installable, customizeable templates
	//----------------------------------------
	const CONTROLLER = 'controller/';
	const TEMPLATES  = 'templates/';
	const THEMES     = 'themes/';


	// Set up all of the basic stuff required to run the comic
	public function __construct() {

		// Call the parent constructor to set up Florrie
		parent::__construct();

		try {

			// shift the controller type off of the URI variables
			$uri = $this->parseURI();
			$type = array_shift($uri);

			// If Florrie hasn't been installed yet, we should probably address that
			if(!$this->installed() && $type != 'install') {

				// Start the Florrie install procedure; redirect to the
				//	installation page
				header('Location: /install', true, 307);
				return;
			}

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
			}
			else if(get_class($e) === 'DBException' ||
				get_class($e) === 'PDOException') {

				// Properly handle DB connection errors
				$controller->dbError($e->getMessage());
			}
			else {

				// Properly handle unexpected errors
				$controller->unknownError($e->getMessage());
			}
		}
	}


	//----------------------------------------
	// Get the appropriate controller object
	//----------------------------------------
	public function getController($controller) {

		// If no controller was specified, use the main controller
		if(empty($controller)) {

			require self::CONTROLLER.'main.php';

			return new Main($this->config);
		}

		// Check the standard Florrie controllers
		if(file_exists(self::CONTROLLER.strtolower($controller).'.php')) {

			require_once self::CONTROLLER.strtolower($controller).'.php';

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


	//----------------------------------------
	// Split the URI into usable chunks
	//----------------------------------------
	protected function parseURI() {

		// Sanitize the URL, and trim the leading/trailing slashes
		$uri = filter_input(INPUT_GET, 'p', FILTER_SANITIZE_URL);
		$uri = trim($uri, '/');

		// Burst into an array
		return explode('/', $uri);
	}


	//----------------------------------------
	// Get the installed/available themes
	//----------------------------------------
	static public function getThemes() {

		$themes = array();
		$themesPath = __DIR__.'/'.self::THEMES;

		// Fetch installed themes
		$themesDir = dir($themesPath);

		while(false !== ($dir = $themesDir->read())) {

			$themeDir = $themesPath.'/'.$dir;
			$themeInfoFile = $themeDir.'/theme.ini';

			if(is_dir($themeDir) && file_exists($themeInfoFile)) {

				$theme = parse_ini_file($themeInfoFile);

				if(!empty($theme['name'])) {

					$theme['dir'] = $dir;

					// TODO: Escape values
					$themes[] = $theme;
				}
			}
		}

		$themesDir->close();

		return $themes;
	}


	//----------------------------------------
	// Take form input array and convert to multi-dimensional configuration 
	// array, for use with the config file
	//----------------------------------------
	static public function convertToConfigArray($flatConfig) {

		$configArray = array();

		// Recursive function to build multidimensional config arrays
		$builder = function(&$indexes, $value) use (&$builder) {

			$index = array_shift($indexes);

			if(is_null($index)) {

				return $value;
			}

			return array($index => $builder($indexes, $value));
		};

		foreach($flatConfig as $index => $value) {

			$indexes = explode('-', $index);

			$treeValue = $builder($indexes, $value);

			$configArray = array_merge_recursive($configArray, $treeValue);
		}

		return $configArray;
	}


	//----------------------------------------
	// Flatten a multidimensional config array
	//----------------------------------------
	static public function convertToFlatArray($configArray) {

		$flatConfig = array();

		// Recursive function to flatten multidimensional config arrays
		$builder = function(&$flatConfig, $configArray, $flatIndex = false)
			use (&$builder) {

			// Once we get down to the value, add it to the flat array
			// B-B-BASE CASE!
			if(!is_array($configArray)) {

				$flatConfig[$flatIndex] = $configArray;
				return;
			}

			if($flatIndex) {

				$flatIndex .= '-';
			}
			else {

				$flatIndex = '';
			}

			// Process the config array recursively
			foreach($configArray as $index => $subArray) {

				$builder($flatConfig, $subArray, $flatIndex.$index);
			}
		};

		$builder($flatConfig, $configArray);

		return $flatConfig;
	}


	//----------------------------------------
	// Write configuration values to the config file
	//----------------------------------------
	static public function saveConfig($configArray) {

		$configXML = new DOMDocument();
		$configXML->formatOutput = true;

		// Recursive function to build config nodes
		$builder = function($values, &$parent) use (&$builder) {

			// BASE CASE: Set the value of the parent node, and return
			if(!is_array($values)) {

				// This chokes on ampersands. Booo, PHP.
				//$parent->nodeValue = $values;

				$value = $parent->ownerDocument->createTextNode($values);

				$parent->appendChild($value);

				return;
			}

			// Create nodes for each config value, and add it as a child
			foreach($values as $index => $value) {

				$thisNode = $parent->ownerDocument->createElement($index);

				$builder($value, $thisNode);

				$parent->appendChild($thisNode);
			}
		};

		$configNode = $configXML->createElement('config');

		$configNode->appendChild(
			new DOMComment('!!! DO NOT MODIFY DIRECTLY: USE ADMIN SECTION !!!')
		);

		$builder($configArray, $configNode);

		$configXML->appendChild($configNode);

		$configData = $configXML->saveXML();

		// TODO Use file API!
		return (file_put_contents($_SERVER['DOCUMENT_ROOT'].Florrie::CONFIG,
			$configData) > 0);
	}
}
?>
