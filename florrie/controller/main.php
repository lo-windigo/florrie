<?php
/*
	Main Controller
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

class Main extends Controller {

	public function __construct($config) {

		parent::__construct($config['data']);

		$this->templateDir = $_SERVER['DOCUMENT_ROOT'].'/templates/';
	}


	// Index page
	public function index() {
		
		$this->render('index');
	}


	public static function notFound() {
		return;
	}
}
?>
