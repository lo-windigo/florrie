<?php
/*
	Comic Strip Controller
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



class StripModel {

	public function __construct($db) {

		// Save the database connection for later
		$this->db = $db;
	}


	public function addStrip($img, $title = null, $episode = null) {

		// TODO: Add a strip!
	}


	public function delStrip($id) {

		// TODO: Delete a strip!
	}


	// Return the very first strip
	public function getFirst() {

		$q = <<<Q
SELECT
	s.id AS id, s.img AS img, s.posted AS posted
FROM strips s
LEFT OUTER JOIN episodes e
ON s.episode = e.id
ORDER BY e.item_order, s.item_order
LIMIT 0, 1
Q;

		$statement = $this->db->prepare($q);
		$statement->execute();

		return $statement->fetch();
	}


	// Get the most recent strip
	public function getLatest() {

		$q = <<<Q
SELECT
	s.id AS id, s.img AS img, s.item_order as item_order, s.posted AS posted
FROM strips s
LEFT OUTER JOIN episodes e
ON s.episode = e.id
ORDER BY e.item_order DESC, s.item_order DESC
LIMIT 0, 1
Q;

		$statement = $this->db->prepare($q);
		$statement->execute();

		return $statement->fetch();
	}


	public function getStrip($id) {

		if(empty($id)) {

			throw new exception('No strip ID specified');
		}

		$q = <<<Q
SELECT
	id, img, item_order, posted
FROM strips
WHERE id = :id
Q;

		$statement = $this->db->prepare($q);
		$statement->bindValue(':id', $id);
		$statement->execute();

		return $statement->fetch();
	}


	public function getEpisodeStrips($episode = null) {

		// TODO
		$q = <<<Q
SELECT
	id, img, item_order, posted
FROM strips
Q;

		$statement = $this->db->prepare($q);
		$statement->bindValue(':episode', $episode);
		$statement->execute();

		$statement->fetchAll();
	}
}
?>
