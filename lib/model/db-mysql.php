<?php
/*
	Florrie MySQL Database Connection Layer
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

class FlorrieMysql extends FlorrieDb
{
	// Configuration indices
	public const CFG_PASS = 'pass';
	public const CFG_PORT = 'port';
	public const CFG_PREFIX = 'prefix';
	public const CFG_SCHEMA = 'schema';
	public const CFG_SERVER = 'server';
	public const CFG_USER = 'user';

	// Default values
	public const DEFAULT_PASS = ''
	public const DEFAULT_PORT = 3306;
	public const DEFAULT_PREFIX = '';
	public const DEFAULT_SERVER = 'localhost';
	public const DEFAULT_SCHEMA = 'florrie';
	public const DEFAULT_USER = 'florrie';
	
	// Error messages
	public const ERR_ADD_EP = 'Cannot add episode: ';
	public const ERR_ADD_EP = 'Cannot add news: ';

	// Data Members
	//	- conn		- Database connection object
	//	- prefix	- A prefix to append to each table name
	public $conn, $prefix;

	// Class Constructor
	// Purpose: Accepts configuration options, and starts up a database
	//	connection
	// Arguments:
	//	- config: An associative array of configuration values.
	//		+ port		- Port the MySQL server listens on
	//		+ server	- Server hostname or IP
	//		+ schema	- Schema where all of our tables are stored
	//		+ prefix	- A prefix that should be prepended to all tables
	//		+ user		- User to access data with
	//		+ pass		- Password for said user
	public function __construct($config)
	{
		$defaults = array(
			self::CFG_PASS => self::DEFAULT_PASS,
			self::CFG_PORT => self::DEFAULT_PORT,
			self::CFG_PREFIX => self::DEFAULT_PREFIX,
			self::CFG_SERVER => self::DEFAULT_SERVER,
			self::CFG_SCHEMA => self::DEFAULT_SCHEMA,
			self::CFG_USER => self::DEFAULT_USER
		);

		// Loop through the configuration values, and any option not present should
		//	be set to it's default value
		foreach($defaults as $option => $default)
		{
			if(empty($config[$option]))
			{
				$config[$option] = $default;
			}
		}

		// Connect to the database
// NOTE: Insert port option!
		$this->conn = mysql_connect(
			$config[self::CFG_SERVER],
			$config[self::CFG_USER],
			$config[self::CFG_PASS],
			$config[self::CFG_SCHEMA]
		);

		// Save a table prefix for later
		$this->prefix = $config[self::CFG_PREFIX];
	}



	// AddEpisode
	// Purpose:	Add an episode to the comic database
	// Return: boolean success value
	public function AddEpisode($episode)
	{
		// Check for a valid database connection
		$this->CheckConnection(self::ERR_ADD_EP);

		// If data hasn't been provided
		if(empty($episode))
		{
			throw new exception(self::ERR_ADD_EP.'episode description empty.');
		}

		$episode = EscapeSQL($episode);

		// Set up the "create new strip" SQL
		$query = "INSERT INTO {$this->prefix}episodes (title, item_order) VALUES ('{$episode}";

		// Handle the "First Episode" scenario
		$q = "SELECT COUNT(*) as num_rows FROM {$this->prefix}episodes";

		if(!($result = mysql_query($q, $db)))
		{
			throw new exception(self::ERR_ADD_EP
				.'cannot get previous episode count. Error: '.
				mysql_error($db));
		}
		
		$numRows = mysql_fetch_assoc($result);
		$numRows = $numRows['num_rows'];

		if($numRows && $numRows > 0)
		{
			// Since you can't reference the same table in the sub-query of an
			//	insert/update query, we have to break this out into it's own query
			//	now. Thanks, MySQL.
			$order = "SELECT MAX(item_order) AS new_order FROM {$this->prefix}episodes";

			if(!($order = mysql_query($order, $db)))
			{
				throw new exception(self::ERR_ADD_EP.
					'cannot get previous maximum order. Error: '.
					mysql_error($db));
			}

			$newOrder = mysql_fetch_assoc($order);
			$newOrder = $newOrder['new_order'] + 1;
			$query .= "', {$newOrder})";
		}
		else
		{
			$query .= "', 1)";
		}


		// Try to insert the new strip
		if(!($result = mysql_query($query, $db)))
		{
			throw new exception(self::ERR_ADD_EP.
				'INSERT query failed. Error: '.
				mysql_error($db));
		}

		// Get the ID if inserted
		if(!($id = mysql_insert_id($db)))
		{
			throw new exception(self::ERR_ADD_EP.
				'cannot get last inserted ID.');
		}
	}



	// AddNews
	// Purpose:	Add a news item (story, post, etc.) that will be displayed under
	//	the news section
	public function AddNews($data)
	{
		// Check the database connection
		$this->CheckConnection(self::ERR_ADD_NEWS);

		// Check to see if we have enough information to proceed.
		if(!isset($data['news'], $data['slug']))
		{
			throw new exception(self::ERR_ADD_NEWS.
				'"slug" or "news" are empty.');
		}

		// Filter the input ahead of time
		$news = FilterInput($data['news']);
		$slug = FilterInput($data['slug']);

		// Handle the "all bad info" case, where all the information provided
		//	was bad
		if(empty($news) || empty($slug))
		{
			throw new exception(self::ERR_ADD_NEWS.
				'all data was filtered out as malicious input');
		}

		// Set up the "create new strip" SQL
		$query = "INSERT INTO {$this->prefix}news (news, slug, posted, poster) VALUES ('".
			EscapeSQL($news)."', '".EscapeSQL($slug).
			"', NOW(), '{$_SESSION['user']}')";

		// Try to insert the news item, and throw an exception if it fails
		if(($result = mysql_query($query, $this->conn)))
		{
			throw new exception(self::ERR_ADD_NEWS.
				'inserting the news item failed. Error: '.
				mysql_error($this->conn));
		}
	}




	public function AddStrip()
	{
		$this->CheckConnection(self::ERR_ADD_NEWS);

		if(!($prev = DefaultStripId()))
		{
			$prev = 'NULL';
		}

		// Try to get the default ID
		if(!($episode_id = DefaultEpisodeId()))
		{
			if(DEBUG){ echo 'AddStrip(), Point 0'; }
			return false;
		}

		// Set up the "create new strip" SQL
		$q = "INSERT INTO {$this->prefix}strips (img, back, episode, item_order) VALUES ('', {$prev}, {$episode_id}";

		if($prev == 'NULL')
		{
			$q .= ", 1)";
		}
		else
		{
			// Since you can't reference the same table in the sub-query of an insert/update query,
			//	we have to break this out into it's own query now. Thanks MySQL.
			$order = "SELECT MAX(item_order) AS new_order FROM {$this->prefix}strips WHERE episode = ".$episode_id;

			if($order = mysql_query($order, $db))
			{
				$newOrder = mysql_fetch_assoc($order);
				$newOrder = $newOrder['new_order'] + 1;
				$q .= ", {$newOrder})";
			}
			else
			{
				if(DEBUG){ echo 'AddStrip(), Point 0.5'; }
				return false;
			}
		}

		if(!($result = mysql_query($q, $db)))
		{
			if(DEBUG){ echo 'AddStrip(), Point 1: ', $q; }
			return false;
		}

		// Get the ID if inserted
		if(!($id = mysql_insert_id($db)))
		{
			if(DEBUG){ echo 'AddStrip(), Point 1.5'; }
			return false;
		}

		// Get the jpeg filename from the ID
		$filename = sprintf('%05d', $id).'.jpg';

		// Try to save the strip through FTP
		if(!SaveFile('strip', 'img', $filename))
		{
			if(DEBUG){ echo 'AddStrip(), Point 2'; }
			return false;
		}

		// Try to delete the old preview
		if(!DeleteFile('img', 'preview.jpg') && DEBUG)
		{
			echo 'AddStrip(), Could not delete old preview... maybe no big deal.';
		}

		// Try to save the strip through FTP
		if(!SaveFile('strip_prev', 'img', 'preview.jpg'))
		{
			if(DEBUG){ echo 'AddStrip(), Point 2.5'; }
			return false;
		}

		// Set up the "update" script to set right the filename
		//	Note: We couldn't set the image name in the database until we
		//		got the ID from inserting the strip into the database that
		//		would be used to create the filename that... *explodes*
		$query = "UPDATE {$this->prefix}strips SET img = '".EscapeSQL($filename)."' WHERE id = ".$id;

		if(!($result = mysql_query($query, $db)))		// Try the query
		{
			if(DEBUG){ echo 'AddStrip(), Point 3: ', $query; }
			return false;
		}

		// Handle the "previous strip" ID, in special cases (between episodes,
		//	first strip, etc.)
	/*	if($prev == 'NULL')
		{
			$q = 'SELECT s.id FROM strips s LEFT OUTER JOIN episodes e ON e.id = s.episode ORDER BY e.item_order, s.item_order ASC';

			if()
			{

			}
		}*/

		$query = "UPDATE {$this->prefix}strips SET forth = {$id} WHERE id = {$prev}";

		if(!($result = mysql_query($query, $db)))	// Update the navigation field
		{
			if(DEBUG){ echo 'AddStrip(), Point 4: ', $query; }
			return false;
		}


		return true;
	}




	public function AddUser()
	{
		if(is_resource($db))
		{
			if(!isset($_POST['user'], $_POST['pass'], $_POST['display']))
			{
				if(DEBUG){ echo 'AddUser() [1]'; }
				return false;
			}

			$user = strtolower(FilterInput($_POST['user']));
			$pass = FilterInput($_POST['pass']);
			$pass = PasswordHash($user, $pass);
			$disp = FilterInput($_POST['display']);

			if(empty($user) || empty($disp) || $pass == sha1($user))
			{
				if(DEBUG){ echo 'AddUser(), [2]'; }
				return false;
			}

			// Set up the "create new strip" SQL
			$query = "INSERT INTO {$this->prefix}users (user, pass, display) VALUES ('".EscapeSQL(strtolower($user));
			$query .= "', '".EscapeSQL($pass)."', '".EscapeSQL($disp)."')";

			if($result = mysql_query($query, $db))		// Try to insert the news
			{
				return true;
			}
			else
			{
				if(DEBUG){ echo 'AddUser(), Point 3: ', $query; }
				return false;
			}
		}
		else
		{
			if(DEBUG){ echo 'AddUser(), DB Connection'; }
			return false;
		}
	}




	public function DeleteEpisode()
	{
		if(isset($_POST['episode']))
		{
			$id = FilterInput($_POST['episode']);
		}
		else
		{
			if(DEBUG) { echo 'DelEpisode() [3]'; }
			return false;
		}

		if(!RemoveOrder('episodes', $id))
		{
			if(DEBUG) { echo 'DelEpisode() [5]'; }
			return false;
		}

		if(DelItem('episodes', $id))
		{
			return true;
		}
		else
		{
			if(DEBUG) { echo 'DelEpisode() [10]'; }
			return false;
		}
	}




	public function DeleteItem()
	{
		if(is_resource($db))
		{
			$q = "DELETE FROM {$this->prefix}{$table} WHERE ID = {$id}";

			if(mysql_query($q, $db))
			{
				return true;
			}
			else
			{
				if(DEBUG) {echo 'Problem in DelItem()';}
				return false;
			}
		}
		else if(DEBUG)
		{
			echo 'Cannot connect to the database: DelItem()';
		}

		return false;
	}




	public function DeleteNews()
	{
		if(isset($_POST['news']))
		{
			$id = FilterInput($_POST['news']);
		}
		else
		{
			if(DEBUG) { echo 'DelNews() [5]'; }
			return false;
		}

		return DelItem('news', $id);
	}




	public function DeleteStrip()
	{
	}




	public function DeleteUser()
	{
	}




	public function GetEpisode()
	{
	}




	public function GetNews()
	{
	}




	public function GetStrip()
	{
	}




	public function GetUser()
	{
	}




	public function UpdateEpisode()
	{
	}




	public function UpdateNews()
	{
	}




	public function UpdateStrip()
	{
	}




	public function UpdateUser()
	{
	}




}

?>
