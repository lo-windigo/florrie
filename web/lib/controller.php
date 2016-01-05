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


// Include the exception classes
require_once __DIR__.'/../../florrie/lib/model.php';


abstract class Controller {

	const TEMPLATE_EXT = '.html';
	const TEMPLATE_PRE = 'page-';


	public $db, $config, $themeDir;


	//----------------------------------------
	// Set up a basic controller
	//----------------------------------------
	public function __construct() {

		$this->config = Florrie::getConfig();
		$this->db = Florrie::getDB();
		$this->initTemplates();
	}


	//----------------------------------------
	// Set up the templating system
	//----------------------------------------
	protected function initTemplates() {

		// Include & initialize the Twig templating library
		require_once 'twig/lib/Twig/Autoloader.php';
		Twig_Autoloader::register();

		// If there is a theme present, use that folder.
		// Use basename to prevent directory traversal.
		if(!empty($this->config['florrie']) &&
			!empty($this->config['florrie']['theme'])) {

			# TODO This might need to be refactored due to the move
			$templatePath = WebController::THEMES.
				basename($this->config['florrie']['theme']).'/';
			$templateDir = __DIR__.'/../'.$templatePath;
				

			if(is_dir($templateDir)) {

				$this->themeDir = $templateDir;
				$this->config['florrie']['themedir'] = $templatePath; 
			}
		}
	}


	//----------------------------------------
	// Render a page and pass it appropriate variables
	//----------------------------------------
	protected function render($templateName, $data = array()) {

		// Set up the template system 
		$loader = new Twig_Loader_Filesystem(__DIR__.'/../'.WebController::TEMPLATES);

		// Check to make sure the template dir is valid
		if(!empty($this->themeDir) && realpath($this->themeDir) !== false) {

			$loader->prependPath(realpath($this->themeDir));
		}

		$twig = new Twig_Environment($loader);
		// Can be enabled for caching templates
		//	, array(
		//	'cache' => '/path/to/compilation_cache',
		//)); 

		// Load the template requested, and display it
		$template = $twig->loadTemplate(self::TEMPLATE_PRE.$templateName.
			self::TEMPLATE_EXT);

		$template->display(array_merge($this->config, $data));
	}


	//----------------------------------------
	// Route a request to a controller function, based on the URI data
	//----------------------------------------
	public function route($uriArray = false) {

		// TODO: Check $uriArray for variable type, not just emptiness
		// If there is no additional URI data, show the main index
		if(empty($uriArray)) {

			return $this->index();
		}

		// Verify we were sent in a URI array
		if(!is_array($uriArray)) {

			throw new ServerException('Controller: Cannot route, URI was not sent in as array');
		}

		$view = array_shift($uriArray);

		if(!method_exists($this, $view)) {

			throw new NotFoundException('Controller: No route for this URI');
		}

		call_user_func_array(array($this, $view), $uriArray);
	}
}
?>
