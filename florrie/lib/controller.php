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



abstract class Controller {

	const MODEL_PATH = '/florrie/model/';


	public $db, $config, $templateDir;


	public function __construct($dbConfig) {

		// Include & initialize the Twig templating library
		require_once 'twig/lib/Twig/Autoloader.php';
		Twig_Autoloader::register();

		// Get a database connection
		$options = array(
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

		if(empty($dbConfig['dsn']) || empty($dbConfig['user']) ||
			empty($dbConfig['pass'])) {

			//throw new exception('Database dbConfiguration values not present');
		}
		else {

			$db = new PDO($dbConfig['dsn'], $dbConfig['user'],
				$dbConfig['pass'], $options);
		}
	}


	// Get a model object
	public function loadModel($name) {

		$modulePath = $_SERVER['DOCUMENT_ROOT'].self::CONFIG_PATH.
			strtolower($name).'.php';

		if(file_exists($modulePath)) {
			
			throw new ServerException('Module does not exist: '.$name);
		}

		// Create a new module object
		require_once $modulePath;

		return new $name($this->db);
	}


	// Render a page
	public function render($templateName, $values = array('comicName' => 'COMIC')) {

		// Check to make sure the template dir is valid
		if(realpath($this->templateDir) === false) {
			
			throw new exception(get_class($this).' Template directory not set');
		}

		// Set up the template system 
		$loader = new Twig_Loader_Filesystem($this->templateDir);
		$twig = new Twig_Environment($loader);
// TODO: Figure out "cache"
//			, array(
//			'cache' => '/path/to/compilation_cache',
//		)); 

		// Load the template requested, and display it
		$template = $twig->loadTemplate($templateName.'.html');
		$template->display($values);
	}
}
?>
