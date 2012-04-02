<?php
/*
	Florrie Filesystem Module (FTP Alternative)
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
class FilesystemFTP extends Filesystem
{
	// FTP server connection
	private $ftp;
	

	
	// Constructor
	// Purpose: Connect to the FTP server, and save the connection
	public function __construct($config)
	{
		// Get the connection details from the configuration array
		die('Unfinished: FTP configuration details');

		// Attempt to connect to the FTP server
		($ftp = ftp_connect(FTP_SERVER)) or
			throw new exception('Cannot connect to FTP server: bad response');

		// Login using the appropriate credentials
		(ftp_login($ftp, FTP_USER, FTP_PASS)) or
			throw new exception('Cannot connect to FTP server: bad login');
			
		// Save the FTP connection for later
		$this->ftp = $ftp;
	}



	// Connected()
	// Purpose: Test the connection to the FTP server
	protected function Connected()
	{
		return ($this->ftp);
	} 



	// DeleteFile() - Delete a file from the filesystem using FTP
	public function DeleteFile($folder, $filename)
	{
		$this->Connected() or return false;

		(ftp_delete($ftp, $folder.'/'.$filename)) or
			throw new exception('Unable to delete file');
			
		return true;
	}



	// SaveFile()
	//	Save a file to the filesystem via FTP
	// Arguments:
	//	file	 - File to be saved (string)
	//	folder	 - Existing folder where the file should be moved
	//	filename - New name of the file
	public function SaveFile($file, $folder, $filename)
	{
		$this->Connected();

		// Check to make sure the file resource is a resource (is open)
		is_resource($file) or
			throw new exception('Papidackus... this should be finished.');

		// Try to upload the file
		die('FTP Save needs to be modified to accept a string!!');
		$result = ftp_fput($ftp, $folder.'/'.$filename, $file, FTP_BINARY);

		ftp_close($ftp);					// Close connection

		return $result;						// Here's hoping!
	}
}
?>
