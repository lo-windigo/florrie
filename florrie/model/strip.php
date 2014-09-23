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

require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/model.php';


class StripModel extends BaseModel {


	//----------------------------------------
	// Class Constants
	//----------------------------------------
	const DEFAULT_PATH = '/strips/';
	const SLUG_DATE   = 'Y-m-d-his';


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
	public function addStrip($stripObj) {

		// Prepare the strip query
		// TODO: Better auto-calculate the item order!!!
		$q = <<<Q
INSERT INTO strips (
	display, img, posted, slug, title, item_order
)
VALUES (
	:display, :img, :posted, :slug, :title,
	(SELECT IFNULL(MAX(s.item_order), 0)+1 FROM strips s)
)
Q;

		// Prepare a slug for this strip
		if(empty($stripObj->title)) {

			// Default to an ugly date slug if needed
			$stripObj->slug = strtolower(date(self::SLUG_DATE));
		}
		else {

			$slug = strtolower($stripObj->title);

			$slug = str_replace(' ', '-', $slug);

			$cleanSlug = '';

			// Filter out any non-alphanumeric characters
			for($i = 0, $j = strlen($slug); $i < $j; $i++) {

				if(ctype_alnum($slug[$i])) {

					$cleanSlug .= $slug[$i];
				}
			}

			// If there's nothing left of the title after cleaning, start over
			if(empty($cleanSlug)) {

				$stripObj->title = '';

				$this->addStrip($stripObj);

				return;
			}

			$stripObj-> slug = $cleanSlug;
		}


		// Prepare posted date
		if(!empty($stripObj->posted) && $stripObj->posted instanceof DateTime) {

			$stripObj->posted = $stripObj->posted->format(self::MYSQL_DATE);
		}
		else {

			$stripObj->posted = date(self::MYSQL_DATE);
		}


		// Now that we're pretty certain we can procede, prepare the statement
		//	and bind data
		$statement = $this->db->prepare($q);

		foreach(array('display', 'img', 'posted', 'slug', 'title') as $col) {

			$statement->bindValue(':'.$col, $stripObj->$col);
		}

		$statement->execute();
	}


	//----------------------------------------
	// Delete a strip
	//----------------------------------------
	public function delStrip($strip) {

		$q = 'DELETE FROM strips WHERE id = :id';

		$statement = $this->db->prepare($q);

		if(is_object($strip) {

			$strip = $strip->id;
		}

		$statement->bindValue(':id', $strip);
		$statement->execute();
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
