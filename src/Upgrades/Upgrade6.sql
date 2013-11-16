CREATE TABLE user2
(
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	name TEXT,
	pass_salt TEXT,
	pass_hash TEXT,
	staff_confirmed INTEGER,
	email_unconfirmed TEXT,
	email_confirmed TEXT,
	join_date INTEGER,
	access_rank INTEGER,
	settings TEXT,
	banned INTEGER
);

INSERT INTO user2
	(id,
	name,
	pass_salt,
	pass_hash,
	staff_confirmed,
	email_unconfirmed,
	email_confirmed,
	join_date,
	access_rank,
	settings,
	banned)
SELECT
	id,
	name,
	pass_salt,
	pass_hash,
	staff_confirmed,
	email_unconfirmed,
	email_confirmed,
	join_date,
	access_rank,
	settings,
	banned
FROM user;

DROP TABLE user;
ALTER TABLE user2 RENAME TO user;

CREATE TABLE usertoken
(
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	user_id INTEGER,
	token VARCHAR(32),
	used BOOLEAN,
	expires INTEGER --TIMESTAMP
);
CREATE INDEX idx_fk_usertoken_user_id ON usertoken(user_id);
