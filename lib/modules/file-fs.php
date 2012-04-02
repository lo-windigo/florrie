<?php
/*
	Florrie Filesystem Module
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



// FTP-based Filesystem Interaction Class
class FilesystemPHP extends Filesystem
{
	// Constructor
	// Purpose: None (yet!)
	public function __construct($config)
	{
		return;
	}



	// DeleteFile() - Delete a file from the filesystem
	public function DeleteFile($folder, $filename)
	{
		(unlink("../../{$folder}/{$filename}")) or
			throw new exception('Could not delete file');
	}



	// SaveFile()
	//	Saves an uploaded file to a specific folder via filesystem functions
	// Arguments:
	//	file	 - File (string) to be saved
	//	folder	 - Existing folder where the file should be moved
	//	filename - New name of the file
	function SaveFile($file, $folder, $filename)
	{
		// Open the file for writing
		($fp = fopen("../../{$folder}/{$filename}", 'w+')) or
			throw new exception('Could not open file for writing');

		// Write data
		($bytes = fwrite($fp, $file)) or
			throw new exception('Cannot write data'); 
			
		return $bytes;
	}
}
?>
