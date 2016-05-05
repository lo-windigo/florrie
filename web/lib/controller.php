<?php
/*
	Abstract Controller Class
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


// Include Florrie core
require_once __DIR__.'/../../florrie/florrie.php';


abstract class Controller {

	//----------------------------------------
	// Template prefix and extensions
	//----------------------------------------
	const TEMPLATE_EXT = '.html';
	const TEMPLATE_PRE = 'page-';


	public $themeDir;



	//----------------------------------------
	// Initialize a basic controller
	//----------------------------------------
	public static function initialize() {

		// Initialize Florrie
		Florrie::initialize();
		self::initTemplates();
	}


	//----------------------------------------
	// Set up the templating system
	//----------------------------------------
	protected static function initTemplates() {

		// Include & initialize the Twig templating library
		if( ! ( include_once 'twig/lib/Twig/Autoloader.php' ) ) {

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

				self::themeDir = $templateDir;
				$config['florrie']['themedir'] = $templatePath; 
			}
		}
	}


	//----------------------------------------
	// Render a page and pass it appropriate variables
	//----------------------------------------
	protected function render($templateName, $data = array()) {

		// Set up the template system 
		$loader = new Twig_Loader_Filesystem(__DIR__.'/../'.Controller::TEMPLATES);

		// Check to make sure the template dir is valid
		if(!empty(self::themeDir) && realpath(self::themeDir) !== false) {

			$loader->prependPath(realpath(self::themeDir));
		}

		$twig = new Twig_Environment($loader);
		// Can be enabled for caching templates
		//	, array(
		//	'cache' => '/path/to/compilation_cache',
		//)); 

		// Load the template requested, and display it
		$template = $twig->loadTemplate(self::TEMPLATE_PRE.$templateName.
			self::TEMPLATE_EXT);

		$template->display(array_merge(self::config, $data));
	}


	//----------------------------------------
	// Route a request to a controller function, based on the URI data
	//----------------------------------------
	public static function route($uriArray = false) {

		// TODO: Check $uriArray for variable type, not just emptiness
		// If there is no additional URI data, show the main index
		if(empty($uriArray)) {

			return self::index();
		}

		// Verify we were sent in a URI array
		if(!is_array($uriArray)) {

			throw new ServerException('Controller: Cannot route, URI was not sent in as array');
		}

		$view = array_shift($uriArray);

		if(!method_exists(self, $view)) {

			throw new NotFoundException('Controller: No route for this URI');
		}

		call_user_func_array(array(self, $view), $uriArray);
	}
}
?>
