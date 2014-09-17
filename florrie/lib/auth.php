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


//----------------------------------------
// Pre-5.6 version of hash_equals
//----------------------------------------
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


//----------------------------------------
// Properly hash a password
// NOTE: Someday could be superceeded with
//	PHP's hash_password
//----------------------------------------
function hashPassword($pass, $salt = false) {

	// If a salt value hasn't been passed in, create one
	if(!$salt) {

		// Here's hoping 10,000-ish rounds will hold 'em.
		$salt = '$6$rounds=9999$';

		// Use OpenSSL functions to generate salt
		if(function_exists('openssl_random_pseudo_bytes')) {

			$rand = openssl_random_pseudo_bytes(17);
		}
		// Fall back to mt_rand if openssl isn't available
		else {

			$rand = sha1(mt_rand(0, mt_getrandmax()));
		}
		
		$salt .= substr(base64_encode($rand), 0, 16).'$';
	}

	return crypt($pass, $salt);
}
?>
