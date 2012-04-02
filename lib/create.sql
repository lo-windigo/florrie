# News table creation statement
CREATE TABLE news
(
	news VARCHAR(65000) NOT NULL,
	slug VARCHAR(255) NOT NULL,
	posted DATETIME NOT NULL,
	poster VARCHAR(15) NOT NULL,
	id INT NOT NULL AUTO_INCREMENT,
	PRIMARY KEY(id)
);


# Episode table creation statement
CREATE TABLE episodes
(
	title VARCHAR(255) NOT NULL,
	id INT NOT NULL AUTO_INCREMENT,
	item_order INT NOT NULL,
	PRIMARY KEY(id)
);

# Comic strip table creation statement
CREATE TABLE strips
(
	img VARCHAR(9),
	episode INT NOT NULL,
	back INT,
	forth INT,
	id INT NOT NULL AUTO_INCREMENT,
	item_order INT NOT NULL,
	posted DATETIME NOT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (episode) REFERENCES episodes (id) ON DELETE CASCADE
);

# Characters table creation statement
CREATE TABLE characters
(
	name VARCHAR(255) NOT NULL,
	description VARCHAR(65000) NOT NULL,
	major BOOL DEFAULT FALSE;
	item_order INT NOT NULL,
	id INT NOT NULL AUTO_INCREMENT,
	PRIMARY KEY(id)
);


# Comic strip table creation statement
CREATE TABLE users
(
	user VARCHAR(15) NOT NULL,
	pass VARCHAR(40) NOT NULL,
	salt VARCHAR(40) NOT NULL,
	display VARCHAR(50),
	PRIMARY KEY(user)
);
