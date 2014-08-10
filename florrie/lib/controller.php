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

	const MODEL_PATH = '/florrie/model/';
	const TEMPLATE_EXT = '.html';
	const TEMPLATE_PRE = 'page-';



	public $db, $config, $templateDir;


	public function __construct($config) {

		//----------------------------------------
		// Set up the templating system
		//----------------------------------------

		// Include & initialize the Twig templating library
		require_once 'twig/lib/Twig/Autoloader.php';
		Twig_Autoloader::register();

		$this->templateDir = $_SERVER['DOCUMENT_ROOT'].'/templates/';
		$this->config = $config;

		// If there is a theme present, use that folder.
		// Use basename to prevent directory traversal.
		if(empty($config['florrie']) || empty($config['florrie']['theme']) &&
			file_exists($this->templateDir.basename($config['florrie']['theme']))) {

			$this->templateDir .= 'default';
		}
		else {

			$this->templateDir .= basename($config['florrie']['theme']);
		}

		$this->templateDir .= '/';

		// Get a database connection
		$options = array(
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

		if(empty($config['data']) || empty($config['data']['dsn']) ||
			empty($config['data']['user']) || empty($config['data']['pass'])) {

			throw new ServerException('Database configuration values not present');
		}
		else {

			$this->db = new PDO($config['data']['dsn'], $config['data']['user'],
				$config['data']['pass'], $options);
		}
	}


	// Get a model object
	protected function loadModel($name) {

		$modulePath = $_SERVER['DOCUMENT_ROOT'].self::MODEL_PATH.
			strtolower($name).'.php';
		$name .= 'Model';

		if(!file_exists($modulePath)) {
			
			throw new ServerException('Module does not exist: '.$name);
		}

		// Create a new module object, and return it
		require_once $modulePath;

		return new $name($this->db);
	}


	// Render a page and pass it appropriate variables
	protected function render($templateName, $data = array()) {

		// Check to make sure the template dir is valid
		if(realpath($this->templateDir) === false) {
			
			throw new ServerException(get_class($this).' Template directory not set');
		}

		// Set up the template system 
		$loader = new Twig_Loader_Filesystem($this->templateDir);
		$twig = new Twig_Environment($loader);
// TODO: Figure out "cache"
//			, array(
//			'cache' => '/path/to/compilation_cache',
//		)); 

		// Load the template requested, and display it
		$template = $twig->loadTemplate($this::TEMPLATE_PRE.$templateName.
			$this::TEMPLATE_EXT);

		$template->display(array_merge($this->config, $data));
	}


	// Route a request to a controller function, based on the URI data
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
