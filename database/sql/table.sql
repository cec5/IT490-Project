/*table that stores users*/
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  epoch INT(11) NOT NULL
);

/*table that stores simple info from matches*/
CREATE TABLE matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATETIME NOT NULL,
    home_team VARCHAR(100) NOT NULL,
    away_team VARCHAR(100) NOT NULL,
    score_home TINYINT,
    score_away TINYINT
);