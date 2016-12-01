<?php
/*
	Main Controller
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

class Main extends WebController {

	// Index page
	public function index() {
		
		$strip = Florrie::loadModel('Strip');
		$latest = $strip->getLatest();

		$this->render('index', array('strip' => $latest));
	}
}
?>
