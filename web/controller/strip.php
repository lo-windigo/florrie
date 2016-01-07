<?php
/*
	Comic Strip Controller
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


require_once __DIR__.'/../lib/controller.php';


class Strip extends Controller {

	public function __construct() {

		parent::__construct();

		$this->model = Florrie::loadModel('Strip');
	}


	//========================================
	// Views
	//========================================


	//----------------------------------------
	// Show an archive of strips
	//----------------------------------------
	public function archive() {

		$strips = $this->model->getStrips();

		$this->render('archive', array('strips' => $strips));
	}


	//----------------------------------------
	// Show the first strip
	//----------------------------------------
	public function first() {

		$strip = $this->model->getFirst();

		header('Location: /strip/'.$strip->slug, true, 307);
		return;
	}


	//----------------------------------------
	// Index: Render a single strip
	//----------------------------------------
	public function index($slug = false) {

		if($slug === false) {

			throw new NotFoundException('Strip ID not provided');
		}

		// Get the strip and display it
		$strip = $this->model->getStrip($slug);

		$this->render('index', array('strip' => $strip));
	}


	//----------------------------------------
	// Show the latest strip
	//----------------------------------------
	public function latest() {

		$strip = $this->model->getLatest();

		header('Location: /strip/'.$strip->slug, true, 307);
		return;
	}


	//----------------------------------------
	// Show a random strip
	//----------------------------------------
	public function random() {

		$strip = $this->model->getRandom();

		header('Location: /strip/'.$strip->slug, true, 307);
		return;
	}



	//========================================
	// Protected (internal) methods
	//========================================


	//----------------------------------------
	// Route a request to a controller function, based on the URI data
	//----------------------------------------
	public function route($uriArray = array()) {

		// If a strip ID has been sent in, display that
		$value = current($uriArray);

		if($value !== false && !method_exists($this, $value)) {

			$this->index($value);
		}
		else {

			// The parent router can handle everything else
			parent::route($uriArray);
		}
	}
}
?>
