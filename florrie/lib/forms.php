<?php
/*
	Form Functions
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


require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/error.php';


//----------------------------------------
// Process form data and store the result
//----------------------------------------
function processFormInput(&$formData) {

	// Error state
	$error = false;

	// Check for CSRF value, if required
	if(array_key_exists('csrf', $formData) && (
	   empty($_POST['csrf']) ||
	   $_POST['csrf'] != $_SESSION['csrf'])) {

		throw new FormException('CSRF value not correct');	
	}
	else {

		unset($formData['csrf']);
	}

	// Process all form fields
	foreach($formData as $index => &$value) {

		$input = filter_input(INPUT_POST, $index, FILTER_SANITIZE_STRING);

		// If no value was submitted and no default exists, raise an 
		// error
		if(($input === null || $input === false) && empty($value)) {

			$error = true;
			//if($error === false) {

			//	$error = '';
			//}

			//$error .= $index.' ';
		}
		else if(!empty($input)) {

			$value = $input;
		}
	}

	// If there was an error during processing, throw an exception
	if($error) {

		$e = new FormException('There were issues with the information you provided');

		// Include form data for re-display/individual errors
		$e->formData = $formData;

		throw $e;
	}
}


//----------------------------------------
// Check for a form submission
//----------------------------------------
function submitted() {

		$submitted = filter_input(INPUT_POST, 'submitted');

		// Process form data if it has been submitted
		return ($submitted !== null);
}
