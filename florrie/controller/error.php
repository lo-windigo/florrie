<?php
/*
	Error Controller
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



require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/controller.php';


class Error extends Controller {

	public function __construct($config = null) {

		if($config === null) {

			$config = array();
		}

		$this->config = $config;

		$this->initTemplates();
	}


	// No index - but we're already in the error controller. HOW CONVENIENT!
	public function index() {

		$this->notFound();
	}


	// Display an error regarding database operations
	public function dbError($msg = false) {

		$this->displayError($msg);
	}


	// Display a standard HTTP 404 status page
	public function notFound($msg = false) {

		$this->render('404', array('msg' => $msg));
	}


	// Display an error regarding an internal execution issue
	public function serverError($msg = false) {

		$this->displayError($msg);
	}


	// Display an error regarding a mysterious issue
	public function unknownError($msg = false) {

		$this->displayError($msg);
	}


	// Generic error message display
	protected function displayError($msg) {

		$data = array();

		if($msg !== false && Florrie::DEBUG) {

			$data = array('msg' => $msg);
		}

		$this->render('error', $data);
	}
}
?>
