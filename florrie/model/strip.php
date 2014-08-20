<?php
/*
	Comic Strip Model
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


/* TODO
 *
 *	Add a slug field for file naming and URL resolution
 *
 */

class StripModel {


	//----------------------------------------
	// Class Constants
	//----------------------------------------
	const DEFAULT_PATH = '/strips/';
	const MYSQL_DATE   = 'Y-m-d H:i:s';


	//========================================
	// Public methods
	//========================================


	//----------------------------------------
	// Constructor
	//----------------------------------------
	public function __construct($db) {

		// Save the database connection for later
		$this->db = $db;
	}


	//----------------------------------------
	// Add a strip to the database
	//----------------------------------------
	public function addStrip($img, $title = null, $episode = null) {

		// TODO: Add a strip!
	}


	//----------------------------------------
	// Delete a strip
	//----------------------------------------
	public function delStrip($id) {

		// TODO: Delete a strip!
	}


	//----------------------------------------
	// Return the very first strip
	//----------------------------------------
	public function getFirst() {

		// Get the bulk of the strip data
		$q = <<<Q
SELECT
	id, img, item_order, posted, title
FROM strips
WHERE posted < NOW()
ORDER BY item_order
LIMIT 0, 1
Q;

		$statement = $this->db->prepare($q);
		$statement->execute();

		if($strip = $statement->fetch()) {
			return $this->prepareStripData($strip);
		}
		
		return false;
	}


	//----------------------------------------
	// Return a random strip
	//----------------------------------------
	public function getRandom() {

		// Get the bulk of the strip data
		$q = <<<Q
SELECT
	id, img, item_order, posted, title
FROM strips
WHERE posted < NOW()
ORDER BY RAND()
LIMIT 0, 1
Q;

		$statement = $this->db->prepare($q);
		$statement->execute();

		if($strip = $statement->fetch()) {
			return $this->prepareStripData($strip);
		}
		
		return false;
	}


	//----------------------------------------
	// Get the most recent strip
	//----------------------------------------
	public function getLatest() {

		$q = <<<Q
SELECT
	id, img, item_order, posted, title
FROM strips
WHERE posted < NOW()
ORDER BY item_order DESC
LIMIT 0, 1
Q;

		$statement = $this->db->prepare($q);
		$statement->execute();

		$strip = $statement->fetch();

		return $this->prepareStripData($strip);
	}


	//----------------------------------------
	// Get a specific strip
	//----------------------------------------
	public function getStrip($id) {

		if(empty($id)) {

			throw new exception('No strip ID specified');
		}

		$q = <<<Q
SELECT
	id, img, item_order, posted, title
FROM strips

WHERE
	id = :id AND
	posted < NOW()
Q;

		$statement = $this->db->prepare($q);
		$statement->bindValue(':id', $id);
		$statement->execute();

		$strip = $statement->fetch();

		return $this->prepareStripData($strip);
	}


	//----------------------------------------
	// Get all strips
	//----------------------------------------
	public function getStrips() {

		$q = <<<Q
SELECT
	id, img, item_order, posted, title
FROM strips
WHERE posted < NOW()
Q;

		$statement = $this->db->prepare($q);
		$statement->execute();

		if(!($strips = $statement->fetchAll())) {

			return array();
		}

		// Format all strips before they're returned
		array_walk($strips, function(&$strip, $index, $stripModel) {

			$strip = $stripModel->prepareStripData($strip);

		}, $this);

		return $strips;
	}



	//========================================
	// Protected (internal) methods
	//========================================

	//----------------------------------------
	// Massage some of the strip data to get
	//   it ready for being displayed
	//----------------------------------------
	protected function prepareStripData($strip) {

		// Supply a sensible default if strip is empty
		if(empty($strip)) {

			$strip = new stdClass();

			$strip->id = -1;
			$strip->item_order = -1;
			$strip->img = false;
			$strip->posted = new DateTime();
			$strip->next = $strip->prev = null;
			$strip->title = 'Uh oh...';
		}
		else {

			if(!empty($strip->posted)) {
				$strip->posted = dateTime::createFromFormat(self::MYSQL_DATE, $strip->posted);
			}

			if(!empty($strip->img)) {
				$strip->img = self::DEFAULT_PATH.$strip->img;
			}

			// Get the "previous" and "next" strip IDs
			$strip->next = $this->getNextID($strip->item_order);
			$strip->prev = $this->getPrevID($strip->item_order);
		}

		return $strip;
	}


	//----------------------------------------
	// Get the next strip id
	//----------------------------------------
	protected function getNextID($order) {

		$q = <<<Q
SELECT id
FROM strips
WHERE
	item_order > :order AND
	posted < NOW()
ORDER BY item_order
LIMIT 0, 1
Q;

		$statement = $this->db->prepare($q);
		$statement->bindValue(':order', $order);
		$statement->execute();

		$next = $statement->fetch();

		if(isset($next->id)) {
			return $next->id;
		}

		return null;
	}


	//----------------------------------------
	// Get the previous strip id
	//----------------------------------------
	protected function getPrevID($order) {

		$q = <<<Q
SELECT id
FROM strips
WHERE
	item_order < :order AND
	posted < NOW()
ORDER BY item_order DESC
LIMIT 0, 1
Q;

		$statement = $this->db->prepare($q);
		$statement->bindValue(':order', $order);
		$statement->execute();

		$prev = $statement->fetch();

		if(isset($prev->id)) {
			return $prev->id;
		}

		return null;
	}
}
?>
