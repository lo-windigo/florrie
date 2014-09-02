<?php
/*
	Extra Authentication Functions
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


// Create a pre-5.6 version of the hash_equals function
if(!function_exists('hash_equals')) {

	function hash_equals($str1, $str2) {

		if(strlen($str1) != strlen($str2)) {
			return false;
		}

		$equal = ($str1 === $str2);

		// Screw up timing attacks, cheap-o version, pre 5.6.0
		usleep(mt_rand(0, 50000));

		return $equal;
	}
}
?>
