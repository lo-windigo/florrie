<?php
/*
	Florrie Abstract Filesystem Module
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



abstract class Filesystem
{
	abstract function DeleteFile();
	abstract function SaveFile();



	// GetUploadedFile()
	//	Processes a file uploaded via a HTML form
	// Arguments:
	//	index	 - Index of the POST variable that contains the file upload
	//	folder	 - Existing folder where the file should be moved
	//	filename - New name of the file
	function GetUploadedFile($index, $folder, $filename)
	{
		// Check to make sure the form's been filled out
		if(empty($_FILES[$index]) || empty($_FILES[$index]['error']) ||
			empty($_FILES[$index]['tmp_name'])))
		{
			throw new exception('Missing file upload data');
		}

		// If the file uploaded successfully
		($_FILES[$index]['error'] == UPLOAD_ERR_OK &&
			is_uploaded_file($_FILES[$index]['tmp_name'])) or
			throw new exception('File upload failed');

		($formFile = fopen($_FILES[$index]['tmp_name'], 'r')) or
			throw new exception('Cannot access uploaded file');

		// Try to save the file to the filesystem
		$this->SaveFile($formFile, $folder, $filename);
		
		return true;
	}
}
