<?php
/*
	Florrie Core Classes
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



// FlorrieData: A base class for building florrie objects
abstract class FlorrieObject {

	public $id;


	public function __construct($id) {

		// Get an existing object if an ID was passed in
		if($id !== false) {

			// TODO: Match up the "get data" part
			$data = GetDataOrSomething();

			// Go through each value, and assign it to a data member
			//  if that data member exists
			array_walk($data, function($val, $var, $object) {

				if(property_exists($object, $var)) {
					$object->$var = $val;
				}
			}, $this);
		}	
	}



	public function Save() {
	}
}



// An orderable object
abstract class FlorrieOrderable extends FlorrieObject {

	public $index;


	public function MoveIndex($index = false) {

		// TODO: All objects after this one should be re-indexed

		// TODO: Increment all of the indexes >= the new one back
		//  by one to make room for this object

		// Save this object, with this order
		$this->index = $index;
		$this->Save();
	}
}



// Strip: the basic strip object
class FlorrieStrip implements FlorrieObject {

	public $date, $file, $episode, $alt, $title;


	public function __construct($id = false) {
		parent::__construct($id);
	}
}



// Episode: A collection of strips. Optional.
class FlorrieEpisode implementes FlorrieObject {

	public $title, $desc;


	public function __construct($id = false) {
		parent::__construct($id);
	}
}
?>
