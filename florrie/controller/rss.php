<?php
/*
	Main "Home" Controller
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



class Feeds {

	// Constructor
	public function __construct() {

		$this->templateDir = $_SERVER['DOCUMENT_ROOT'].'/templates/default/';
		$this->model = $this->loadModel('Strip');

		// Save the config for later
		$this->config = $config;
	}


	// Index page
	public function index() {

	}


	// RSS feed
	public function rss() {

		// Send the appropriate headers
		header('Content-Type: application/rss+xml');

		$strips = $this->model->getStrips();

		$this->render('archive', array('strips' => $strips));
	}


	// Render a feed and pass it appropriate variables
	public function render($templateName, $data = array()) {

		// Check to make sure the template dir is valid
		if(realpath($this->templateDir) === false) {
			
			throw new ServerErrorException(get_class($this).' Template directory not set');
		}

		// Set up the template system 
		$loader = new Twig_Loader_Filesystem($this->templateDir);
		$twig = new Twig_Environment($loader);
// TODO: Figure out "cache"
//			, array(
//			'cache' => '/path/to/compilation_cache',
//		)); 

		// Load the template requested, and display it
		$template = $twig->loadTemplate('page-'.$templateName.'.html');
		$template->display(array_merge($this->config, $data));
	}


}
?>
