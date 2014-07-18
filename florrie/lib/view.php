<?php
/*
	Abstract View Class - Florrie Base Module
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


abstract class FlorrieView
{
	// Class Constants
	//	PAGE_REDIRECT	- If defined (and true), a redirect is required
	//	PAGE_URL			- The base URL of this page object


	// Data Members
	public $something;


	// The following functions need to be defined by an implementing class
	abstract public function DisplayPage();



	// Constructor
	public function __construct()
	{
		// Check for redirects
		$this->Redirect();
	}



	// Redirect
	// Purpose:	Redirect to the appropriate page, if defined by the implementing
	//	class; otherwise it doesn't really do anything
	public function Redirect()
	{
		// Make the following checks to see if this view needs to be redirected to
		//	a particular URL: 
		//	- Check to see if the PAGE_REDIRECT constant is defined
		//	- Check to see if we're already on the page in question
		//	- Make sure the page you're redirecting to exists
		if(!empty(self::PAGE_REDIRECT) && !empty(self::PAGE_REDIRECT) &&
			(basename($_SERVER['PHP_SELF']) != basename(self::PAGE_URL)) &&
			(file_exists(self::PAGE_URL)))
		// NOTE: The last check needs some work!
		{
			header('Location: '.self::PAGE_URL, true, 307);
			exit;
		}

		die('^-- Fix this check');
	}
}
?>
