ALTER TABLE user DROP COLUMN email_token;

CREATE TABLE usertoken
(
	id INTEGER PRIMARY KEY AUTO_INCREMENT,
	user_id INTEGER,
	token VARCHAR(32),
	used BOOLEAN,
	expires INTEGER --TIMESTAMP
);
CREATE INDEX idx_fk_usertoken_user_id ON usertoken(user_id);
