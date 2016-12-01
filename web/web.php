<?php
/*
	Web Interface
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


// Include the main florrie module
require_once __DIR__.'/../florrie/florrie.php';


//----------------------------------------
// Main class - kicks things off, starts the party
//----------------------------------------
class WebModule {

	//----------------------------------------
	// Class Constants
	//
	//  CONTROLLER   - Main controllers
	//  TEMPLATES    - System templates
	//  THEMES       - User-installable, customizeable templates
	//----------------------------------------
	const CONTROLLER = '/controller/';
	const TEMPLATES  = '/templates/';
	const THEMES     = '/themes/';


	public static $themeDir;

	//----------------------------------------
	// Get the appropriate controller object
	//----------------------------------------
	public static function getController($controller) {

		// Get the controller path
		$cPath = __DIR__.self::CONTROLLER;

		// If no controller was specified, use the main controller
		if(empty($controller)) {

			require $cPath.'main.php';

			return new Main();
		}

		// Check the standard Florrie controllers
		if(file_exists($cPath.strtolower($controller).'.php')) {

			require_once $cPath.strtolower($controller).'.php';

			// Return the new controller
			return new $controller();
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
	// Handle a web request
	//----------------------------------------
	public static function initialize() {

		// shift the controller type off of the URI variables
		$uri = self::parseURI();
		$type = array_shift($uri);


		// Set up the template system
		try {
			self::initTemplates();
		}
		catch (InitException $e) {

			if(Florrie::DEBUG) {
				echo $e;
			}

			// TODO: Any nicer way to do this?
			die('Cannot load template library. Did you use "git submodule init"?');
		}


		try {
			// Initialize Florrie
			Florrie::initialize();

			// Get controller object, and route the request
			$controller = self::getController($type);
		}
		// If Florrie is not installed, redirect to the installer
		catch (AuthException $e) {

			// TODO: This is wrong.
			// Get controller object, and route the request
			$controller = self::getController($type);
		}
		// If Florrie is not installed, redirect to the installer
		catch (NotInstalledException $e) {

			// If Florrie hasn't been installed yet, we should probably address that
			if($type != 'install') {

				// Start the Florrie install procedure; redirect to the
				//	installation page
				header('Location: /install', true, 307);
				return;
			}

			$controller = self::getController('install');
			$controller->index();
		}
		// Handle the generic error cases (404, 500, etc)
		catch (exception $e) {

			$controller = self::getController('error');

			if(get_class($e) === 'NotFoundException') {

				// Handle a 404
				$controller->notFound($e);
			}
			else if(get_class($e) === 'ServerException') {

				// Handle a server error
				$controller->serverError($e);
			}
			else if(get_class($e) === 'DBException' ||
				get_class($e) === 'PDOException') {

				// Properly handle DB connection errors
				$controller->dbError($e);
			}
			else {

				// Properly handle unexpected errors
				$controller->unknownError($e);
			}
		}

		if(!empty($controller) && $controller instanceof WebController) {
			$controller->route($uri);
		}
		else {
			// TODO: log, explain, etc
			echo 'No controller available.';
		}
	}


	//----------------------------------------
	// Set up the templating system
	//----------------------------------------
	protected static function initTemplates() {

		// Include & initialize the Twig templating library
		if(!(include_once __DIR__.'/lib/twig/lib/Twig/Autoloader.php')) {

			throw new InitException('Twig libraries not successfully loaded');
		}

		Twig_Autoloader::register();

		// If there is a theme present, use that folder.
		// Use basename to prevent directory traversal.
		$config = Florrie::getConfig();

		if(!empty($config['florrie']) && !empty($config['florrie']['theme'])) {

			# TODO This might need to be refactored due to the move
			$templatePath = Controller::THEMES.
				basename($config['florrie']['theme']).'/';
			$templateDir = __DIR__.'/../'.$templatePath;
				

			if(is_dir($templateDir)) {

				self::$themeDir = $templateDir;
				$config['florrie']['themedir'] = $templatePath; 
			}
		}
	}


	//----------------------------------------
	// Split the URI into usable chunks
	//----------------------------------------
	protected static function parseURI() {

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
		$themesPath = __DIR__.self::THEMES;

		// Fetch installed themes by looping through the theme directory
		$themesDir = dir($themesPath);

		while(false !== ($dir = $themesDir->read())) {

			// Filter out any hidden directories, and the dot entries (. and ..)
			if(!(substr($dir, 0, 1) === '.')) {

				$themeDir = $themesPath.$dir;
				$themeInfoFile = $themeDir.'/theme.ini';

				if(is_dir($themeDir) && file_exists($themeInfoFile)) {

					$theme = parse_ini_file($themeInfoFile);

					if(!empty($theme['name'])) {

						// TODO: Decent escaping and stuff
						$theme['name'] = htmlentities($theme['name']);
						$theme['dir'] = $dir;

						$themes[] = $theme;
					}
				}
			}
		}

		$themesDir->close();

		return $themes;
	}
}
?>
