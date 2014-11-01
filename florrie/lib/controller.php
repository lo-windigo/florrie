<?php
/*
	Abstract Controller Class
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


abstract class Controller {

	const TEMPLATE_EXT = '.html';
	const TEMPLATE_PRE = 'page-';



	public $db, $config, $themeDir;


	//----------------------------------------
	// Set up a basic controller
	//----------------------------------------
	public function __construct($config = null) {

		if($config === null) {

			$config = array();
		}

		$this->config = $config;

		$this->initDB();
		$this->initTemplates();
	}


	//----------------------------------------
	// Initialize the database connection
	//----------------------------------------
	protected function initDB($config = false) {

		// Verify that we have some configuration values
		if(!$config) {

			if(empty($this->config)) {

				throw new ServerException('No configuration provided for database initialization');
			}

			$config = $this->config;
		}

		// Check the configuration values are present
		if(empty($config['data']) ||
			empty($config['data']['dsn']) ||
			empty($config['data']['user']) ||
		 	empty($config['data']['pass'])) {

			throw new ServerException('Database configuration values not present');
		}
		else {

			// Attempt to create a connection
			// - Fetch results as objects
			// - Throw exceptions when errors occur
			$options = array(
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

			$this->db = new PDO($config['data']['dsn'], $config['data']['user'],
				$config['data']['pass'], $options);
		}
	}


	//----------------------------------------
	// Set up the templating system
	//----------------------------------------
	protected function initTemplates() {

		// Include & initialize the Twig templating library
		require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/twig/lib/Twig/Autoloader.php';
		Twig_Autoloader::register();

		// If there is a theme present, use that folder.
		// Use basename to prevent directory traversal.
		if(!empty($this->config['florrie']) &&
			!empty($this->config['florrie']['theme'])) {

			$templatePath = Florrie::THEMES.basename($this->config['florrie']['theme']).'/';
			$templateDir = $_SERVER['DOCUMENT_ROOT'].$templatePath;
				

			if(is_dir($templateDir)) {

				$this->themeDir = $templateDir;
				$this->config['florrie']['themedir'] = $templatePath; 
			}
		}
	}


	//----------------------------------------
	// Get a model object
	//----------------------------------------
	protected function loadModel($name) {

		$modulePath = $_SERVER['DOCUMENT_ROOT'].Florrie::MODELS.
			strtolower($name).'.php';
		$name .= 'Model';

		if(!file_exists($modulePath)) {
			
			throw new ServerException('Module does not exist: '.$name);
		}

		// Create a new module object, and return it
		require_once $modulePath;

		return new $name($this->db);
	}


	//----------------------------------------
	// Render a page and pass it appropriate variables
	//----------------------------------------
	protected function render($templateName, $data = array()) {

		// Set up the template system 
		$loader = new Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'].
			Florrie::TEMPLATES);

		// Check to make sure the template dir is valid
		if(!empty($this->themeDir) && realpath($this->themeDir) !== false) {

			$loader->prependPath(realpath($this->themeDir));
		}

		$twig = new Twig_Environment($loader);
// Can be enabled for caching templates
//			, array(
//			'cache' => '/path/to/compilation_cache',
//		)); 

		// Load the template requested, and display it
		$template = $twig->loadTemplate(self::TEMPLATE_PRE.$templateName.
			self::TEMPLATE_EXT);

		$template->display(array_merge($this->config, $data));
	}


	//----------------------------------------
	// Route a request to a controller function, based on the URI data
	//----------------------------------------
	public function route($uriArray = array()) {

		// If there is no additional URI data, show the main index
		if(empty($uriArray)) {

			$this->index();
		}
		// Otherwise, check and see if this controller supports
		//  this action
		else {

			$value = array_shift($uriArray);

			if(method_exists($this, $value)) {
				call_user_func(array($this, $value));
			}
			else {

				throw new NotFoundException('Controller does not have a good way to handle this URI');
			}
		}
	}
}
?>
