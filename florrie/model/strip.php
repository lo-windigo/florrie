<?php
/*
	Comic Strip Model
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



require_once __DIR__.'/../lib/model.php';


class StripModel extends BaseModel {


	//----------------------------------------
	// Class Constants
	//----------------------------------------
	const SLUG_DATE   = 'Y-m-d-his';


	//----------------------------------------
	// Data members
	//----------------------------------------
	public $unpublished;


	//========================================
	// Public methods
	//========================================


	//----------------------------------------
	// Constructor
	//----------------------------------------
	public function __construct() {

		parent::__construct();

		$this->unpublished = false;
	}


	//----------------------------------------
	// Add a strip to the database
	//----------------------------------------
	public function addStrip($stripObj) {

		// If we're dealing with an associative array, cast it to an object
		if(is_array($stripObj)) {

			$stripObj = (object)$stripObj;
		}

		// Prepare the strip insertion query
		$q = <<<Q
INSERT INTO strips (
	display, img, posted, slug, title
)
VALUES (
	:display, :img, :posted, :slug, :title
)
Q;


		// Prepare posted date: default to right now
		if(!empty($stripObj->posted) && $stripObj->posted instanceof DateTime) {

			$stripObj->posted = $stripObj->posted->format(self::MYSQL_DATE);
		}
		else {

			$stripObj->posted = date(self::MYSQL_DATE);
		}


		// Prepare the "add strip" statement and bind data
		$statement = $this->db->prepare($q);

		foreach(array('display', 'img', 'posted', 'slug', 'title') as $col) {

			$statement->bindValue(':'.$col, $stripObj->$col);
		}

		$statement->execute();

		// Retrieve the strip object (and new ID)
		$stripObj = $this->getStrip($stripObj->slug);

		// Set the order of this strip to the very last order
		$this->orderBefore($stripObj);
	}


	//----------------------------------------
	// Create a slug from title text
	//----------------------------------------
	static public function createSlug($title = null) {

		// Prepare a slug for this strip
		if(empty($title)) {

			// Default to an ugly date slug if needed
			return strtolower(date(self::SLUG_DATE));
		}

		$slug = strtolower($title);

		$slug = str_replace(' ', '-', $slug);

		$cleanSlug = '';

		// Filter out any non-alphanumeric characters
		for($i = 0, $j = strlen($slug); $i < $j; $i++) {

			if(ctype_alnum($slug[$i]) || $slug[$i] === '-') {

				$cleanSlug .= $slug[$i];
			}
		}

		// If there's nothing left of the title after cleaning, start over
		if(empty($cleanSlug)) {

			return self::createSlug();
		}

		return $cleanSlug;
	}


	//----------------------------------------
	// Delete a strip
	//----------------------------------------
	public function delStrip($stripObj) {

		// Make sure we have a strip object to deal with
		if(is_int($stripObj) || ctype_digit($stripObj)) {

			$stripObj = $this->getStrip($stripObj);
		}
		else if(!(is_object($stripObj) && !empty($stripObj->item_order))) {

			throw new ServerException('ID does not return a valid strip');
		}

		// Remove the strip from the database
		$q = 'DELETE FROM strips WHERE id = :id';

		$statement = $this->db->prepare($q);

		$statement->bindValue(':id', $stripObj->id);

		// TODO: Check for actual deletion?
		$statement->execute();

		// Rebuild the order of the Strip table
		$q =<<<ORDER
UPDATE strips
SET item_order = item_order -1
WHERE item_order > :order
ORDER;

		$statement = $this->db->prepare($q);

		$statement->bindValue(':order', $stripObj->item_order);
		$statement->execute();
	}


	//----------------------------------------
	// Remove this module's database tables
	//----------------------------------------
	public function removeTables() {

		parent::delTable('strips');
	}


	//----------------------------------------
	// Return the very first strip
	//----------------------------------------
	public function getFirst() {

		// Get the bulk of the strip data
		$q = <<<Q
SELECT
	display, id, img, item_order, posted, slug, title
FROM strips
Q;

		// Include unpublished strips, if specified
		if(!$this->unpublished) {
			$q .= ' WHERE posted < NOW() ';
		}

		$q .= <<<Q
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
	display, id, img, item_order, posted, slug, title
FROM strips
Q;

		// Include unpublished strips, if specified
		if(!$this->unpublished) {
			$q .= ' WHERE posted < NOW() ';
		}

		$q .= <<<Q
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
	display, id, img, item_order, posted, slug, title
FROM strips
Q;

		// Include unpublished strips, if specified
		if(!$this->unpublished) {
			$q .= ' WHERE posted < NOW() ';
		}

		$q .= <<<Q
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
	public function getStrip($criteria = false) {

		if($criteria === false) {

			throw new exception('No strip ID specified');
		}


		$q = <<<Q
SELECT
	display, id, img, item_order, posted, slug, title
FROM strips
WHERE
Q;

		// TODO: Figure out how to get around slugs that are only digits!
		if(is_int($criteria) || ctype_digit($criteria)) {

			$q .= ' id = :criteria';
		}
		else {

			$q .= ' slug LIKE :criteria';
		}

		// Include unpublished strips, if specified
		if(!$this->unpublished) {

			$q .= ' AND posted < NOW() ';
		}

		$statement = $this->db->prepare($q);
		$statement->bindValue(':criteria', $criteria);
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
	display, id, img, item_order, posted, slug, title
FROM strips
Q;

		// Include unpublished strips, if specified
		if(!$this->unpublished) {
			$q .= ' WHERE posted < NOW()';
		}

		$q .= "\nORDER BY item_order";

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


	//----------------------------------------
	// Install this module's database tables
	//----------------------------------------
	public function installTables($force = false) {

		parent::installTables($force);

		$q = <<<Q
CREATE TABLE strips
(
	id INT NOT NULL AUTO_INCREMENT,
	title VARCHAR(500),
	slug VARCHAR(100) UNIQUE,
	display TEXT,
	img VARCHAR(200) NOT NULL,
	item_order INT NOT NULL,
	posted DATETIME NOT NULL,
	PRIMARY KEY(id)
)
Q;

		$statement = $this->db->prepare($q);
		$statement->execute();
	}


	//----------------------------------------
	// Change the order of a strip
	//----------------------------------------
	public function orderBefore($stripObj, $target = false) {

		// Note; $stripObj not really validated. TODO

		// if a strip object was sent in, only use the item order
		if(is_object($target) && !empty($target->item_order)) {

			$target = $target->item_order;
		}

		// Make sure the target is either false, or an integer
		if(!isset($target) || (!ctype_digit($target) && !is_int($target) &&
			$target !== false)) {

			$e = 'orderBefore error: invalid "target" order specified. [target: '.
				var_dump($target).']';

			throw new ServerException($e);
		}

		// Remove this strip from the item order
		if(!empty($stripObj->item_order)) {

			$q = <<<Q
UPDATE strips
SET item_order = item_order - 1
WHERE item_order > :order
Q;
			$statement = $this->db->prepare($q);
			$statement->bindValue(':order', $stripObj->item_order);
			$statement->execute();
		}

		// Change order, case 1: no target, stick at the end
		if($target === false) {

			// Calculate the "end of the line" order number
			// NOTE: could be done in one query, but MySQL doesn't like
			//   you querying a table you're updating. This is easier. :P
			$q = <<<Q
SELECT IFNULL(MAX(item_order), 0)+1
FROM strips
Q;
			$statement = $this->db->prepare($q);
			$statement->execute();
			$newOrder = $statement->fetchColumn();

			// Set the order of the strip in question
			$q = <<<Q
UPDATE strips
SET item_order = :new_order
WHERE id = :id
Q;
			$statement = $this->db->prepare($q);
			$statement->bindValue(':id', $stripObj->id);
			$statement->bindValue(':new_order', $newOrder);
			$statement->execute();
		}
		// Change order, case 2: there is a target order
		else
		{
			// Special case: if the target specified is higher than the current order, 
			//	we have to compensate for when we removed the current strip from the 
			//	item order. It has moved the target back by one.
			if($target > $stripObj->item_order) {
				$target--;
			}

			// Push all other strips up in the order
			$q = <<<Q
UPDATE strips
SET item_order = item_order + 1
WHERE item_order >= :order
Q;
			$statement = $this->db->prepare($q);
			$statement->bindValue(':order', $target);
			$statement->execute();

			// Set the order of the current strip
			$q = <<<Q
UPDATE strips
SET item_order = :order
WHERE id = :id
Q;
			$statement = $this->db->prepare($q);
			$statement->bindValue(':id', $stripObj->id);
			$statement->bindValue(':order', $target);
			$statement->execute();
		}
	}


	//----------------------------------------
	// Delete this module's database tables
	//----------------------------------------
	public function tablesExist() {

		return $this->tableExists('strips');
	}


	//----------------------------------------
	// Update a strip in the database
	//----------------------------------------
	public function updateStrip($stripObj) {

		// If we're dealing with an associative array, cast it to an object
		if(is_array($stripObj)) {

			$stripObj = (object)$stripObj;
		}

		// Prepare the strip query
		$q = <<<Q
UPDATE strips
SET
	display = :display,
	img = :img,
	posted = :posted,
	slug = :slug,
	title = :title
WHERE
	id = :id
Q;

		// Prepare posted date
		if($stripObj->posted instanceof DateTime) {

			$stripObj->posted = $stripObj->posted->format(self::MYSQL_DATE);
		}
		else if(empty($stripObj->posted)) {

			$stripObj->posted = date(self::MYSQL_DATE);
		}
		else {

			$stripObj->posted = date(self::MYSQL_DATE, strtotime($stripObj->posted));
		}


		// Now that we're pretty certain we can procede, prepare the statement
		//	and bind data
		$statement = $this->db->prepare($q);
		$fields = array('display','img','posted','slug','title','id');

		foreach($fields as $col) {

			$statement->bindValue(':'.$col, $stripObj->$col);
		}

		$statement->execute();
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

			$strip->display = 'It seems as if there has been an error. We apologize.';
			$strip->id = -1;
			$strip->item_order = -1;
			$strip->img = false;
			$strip->imgPath = false;
			$strip->posted = new DateTime();
			$strip->next = $strip->prev = null;
			$strip->title = 'Uh oh...';
		}
		else {

			$strip->posted = dateTime::createFromFormat(self::MYSQL_DATE, $strip->posted);

			if(!empty($strip->img)) {
				$strip->imgPath = Florrie::STRIPS.$strip->img;
			}

			// Get the "previous" and "next" strip IDs
			$strip->next = $this->getNextSlug($strip->item_order);
			$strip->prev = $this->getPrevSlug($strip->item_order);
		}

		return $strip;
	}


	//----------------------------------------
	// Get the next strip slug
	//----------------------------------------
	protected function getNextSlug($order) {

		$q = <<<Q
SELECT slug
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

		if(isset($next->slug)) {
			return $next->slug;
		}

		return null;
	}


	//----------------------------------------
	// Get the previous strip slug
	//----------------------------------------
	protected function getPrevSlug($order) {

		$q = <<<Q
SELECT slug
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

		if(isset($prev->slug)) {
			return $prev->slug;
		}

		return null;
	}
}
?>
