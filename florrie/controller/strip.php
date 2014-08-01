<?php
/*
	Comic Strip Controller
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


require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/controller.php';


class Strip extends Controller {

	public function __construct($config) {

		parent::__construct($config['data']);

		// TODO: Pull from config
		$this->templateDir = $_SERVER['DOCUMENT_ROOT'].'/templates/default/';
		$this->model = $this->loadModel('Strip');

		// Save the config for later
		$this->config = $config;
	}


	// Show the first strip
	public function first() {

		$strip = $this->model->getFirst();

		$this->render('index', array('strip' => $strip));
	}


	// Index: Render a single strip
	public function index($id) {

		// TODO: Get the strip and display it
		$strip = $this->model->getStrip($id);

		$this->render('index', array('strip' => $strip));
	}


	// Show a random strip
	public function random() {

		$strip = $this->model->getRandom();

		$this->render('index', array('strip' => $strip));
	}


	// Route a request to a controller function, based on the URI data
	public function route($uriArray = array()) {

		// The parent router can handle everything else
		parent::route($uriArray);
	}
}
?>
