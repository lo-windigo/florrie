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
	static public function init() {

		try {

			// shift the controller type off of the URI variables
			$uri = self::parseURI();
			$type = array_shift($uri);

			// If Florrie hasn't been installed yet, we should probably address that
			if(!self::installed() && $type != 'install') {

				// Start the Florrie install procedure; redirect to the
				//	installation page
				header('Location: /install', true, 307);
				return;
			}

			// Get controller object, and route the request
			$controller = self::getController($type);
			$controller->route($uri);
		}
		// Handle any errors that may have occurred
		catch (exception $e) {

			$controller = self::getController('error');

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

			require_once self::CONTROLLER.'main.php';

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

		// Sanitize the URL
		$uri = filter_input(INPUT_GET, 'p', FILTER_SANITIZE_URL);

		// trim the leading/trailing slashes
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
}
?>
