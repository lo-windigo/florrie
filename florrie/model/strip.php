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



require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/model.php';


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
	public function __construct($db) {

		// Save the database connection for later
		$this->db = $db;
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

			// TODO: Really need to throw an exception here?
			throw new exception('ID does not return a valid strip');
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
			$q .= ' WHERE posted < NOW() ';
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
			$q .= ' WHERE posted < NOW() ';
		}

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
	// Update a strip in the database
	//----------------------------------------
	public function updateStrip($stripObj) {

		// If we're dealing with an associative array, cast it to an object
		if(is_array($stripObj)) {

			$stripObj = (object)$stripObj;
		}

		// Prepare the strip query
		// TODO: Better auto-calculate the item order!!!
		$q = <<<Q
UPDATE strips
SET
	display = :display,
	img = :img,
	posted = :posted,
	slug = :slug,
	title = :title,
	item_order = :item_order
WHERE
	id = :id
Q;

		// Catch an empty date
		if(empty($stripObj->posted)) {

			// TODO: Is this right?!?
			throw new ServerError('No date in StripObject sent to updateStrip method!');
		}

		// Prepare posted date
		if($stripObj->posted instanceof DateTime) {

			$stripObj->posted = $stripObj->posted->format(self::MYSQL_DATE);
		}
		else {

			$stripObj->posted = date(self::MYSQL_DATE, strtotime($stripObj->posted));
		}


		// Now that we're pretty certain we can procede, prepare the statement
		//	and bind data
		$statement = $this->db->prepare($q);
		$fields = array('display','img','posted','slug','title','item_order','id');

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
