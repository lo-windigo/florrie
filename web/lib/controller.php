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
	public function initDB($config = false) {

		// Verify that we have some configuration values
		if(!$config) {

			if(empty($this->config)) {

				throw new ServerException('No configuration provided for database initialization');
			}

			$config = $this->config;
		}

		// Check the configuration values are present
		if(empty($config['data']) ||
			empty($config['data']['server']) ||
			empty($config['data']['port']) ||
			empty($config['data']['db']) ||
			empty($config['data']['user']) ||
		 	empty($config['data']['pass'])) {

			throw new ServerException('Database configuration values not present');
		}
		else {

			// Compile the db values into a DSN
			// TODO: Database independent? Let people choose?
			$dsn = BaseModel::getDSN(
				$config['data']['db'],
				$config['data']['server'],
				$config['data']['port']);

			// Attempt to create a connection
			// - Fetch results as objects
			// - Throw exceptions when errors occur
			$options = array(
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

			$this->db = new PDO($dsn, $config['data']['user'],
				$config['data']['pass'], $options);

			// Quit it with the safety nets! Let me juggle flaming chainsaws!
			$this->db->exec('SET SQL_SAFE_UPDATES=0');
		}
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
			$templatePath = FlorrieWeb::THEMES.
				basename($this->config['florrie']['theme']).'/';
			$templateDir = __DIR__.'/../'.$templatePath;
				

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

		$modulePath = __DIR__.'/../../florrie/'.Florrie::MODELS.
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
		$loader = new Twig_Loader_Filesystem(__DIR__.'/../'.FlorrieWeb::TEMPLATES);

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
