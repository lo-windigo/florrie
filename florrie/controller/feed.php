<?php
/*
	Syndication Feed Controller
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


class Feed extends Controller {

	// Override the usual html template extension
	const TEMPLATE_EXT = '.xml';
	const TEMPLATE_PRE = 'feed-';

	// Constructor
	public function __construct($config) {

		parent::__construct($config);

		$this->model = $this->loadModel('Strip');
	}


	// Atom feed
	public function atom() {

		// Send the appropriate headers
		header('Content-Type: application/atom+xml');

		$strips = $this->model->getStrips();

		$this->render('atom', array('strips' => $strips));
	}


	// Index page
	public function index() {

		// TODO: Maybe list the available feeds?
		throw new NotFoundException('No Feed Index');
	}


	// RSS feed
	public function rss() {

		// Send the appropriate headers
		header('Content-Type: application/rss+xml');

		$strips = $this->model->getStrips();

		$this->render('rss', array('strips' => $strips));
	}
}
?>
