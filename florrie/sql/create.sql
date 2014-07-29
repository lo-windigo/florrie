DROP TABLE IF EXISTS users;
#DROP TABLE IF EXISTS characters;
DROP TABLE IF EXISTS strips;
DROP TABLE IF EXISTS episodes;
#DROP TABLE IF EXISTS news;


# News table creation statement
#CREATE TABLE news
#(
#	news VARCHAR(65000) NOT NULL,
#	slug VARCHAR(255) NOT NULL,
#	posted DATETIME NOT NULL,
#	poster VARCHAR(15) NOT NULL,
#	id INT NOT NULL AUTO_INCREMENT,
#	PRIMARY KEY(id)
#);


# Episode table creation statement
CREATE TABLE episodes
(
	title VARCHAR(500) NOT NULL,
	id INT NOT NULL AUTO_INCREMENT,
	item_order INT NOT NULL,
	PRIMARY KEY(id)
);

# Comic strip table creation statement
CREATE TABLE strips
(
	id INT NOT NULL AUTO_INCREMENT,
	title VARCHAR(500),
	img VARCHAR(200) NOT NULL,
	episode INT,
	item_order INT NOT NULL,
	posted DATETIME NOT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (episode) REFERENCES episodes (id) ON DELETE CASCADE
);

# Characters table creation statement
#CREATE TABLE characters
#(
#	name VARCHAR(255) NOT NULL,
#	description VARCHAR(65000) NOT NULL,
#	major BOOL DEFAULT FALSE;
#	item_order INT NOT NULL,
#	id INT NOT NULL AUTO_INCREMENT,
#	PRIMARY KEY(id)
#);


# Comic strip table creation statement
CREATE TABLE users
(
	user VARCHAR(50) NOT NULL,
	pass VARCHAR(40) NOT NULL,
	salt VARCHAR(40) NOT NULL,
	display VARCHAR(50),
	PRIMARY KEY(user)
);