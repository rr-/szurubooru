CREATE TABLE user
(
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	name TEXT,
	pass_salt TEXT,
	pass_hash TEXT,
	staff_confirmed INTEGER,
	email_unconfirmed TEXT,
	email_confirmed TEXT,
	email_token TEXT,
	join_date INTEGER,
	access_rank INTEGER,
	settings TEXT
);

CREATE TABLE tag
(
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	name TEXT
);

CREATE TABLE post_tag
(
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	tag_id INTEGER,
	post_id INTEGER,
	FOREIGN KEY(tag_id) REFERENCES tag(id) ON DELETE CASCADE ON UPDATE SET NULL,
	FOREIGN KEY(post_id) REFERENCES post(id) ON DELETE CASCADE ON UPDATE SET NULL
);
CREATE INDEX idx_fk_post_tag_post_id ON post_tag(post_id);
CREATE INDEX idx_fk_post_tag_tag_id ON post_tag(tag_id);
CREATE UNIQUE INDEX idx_uq_post_tag_tag_id_post_id ON post_tag(tag_id, post_id);

CREATE TABLE favoritee
(
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	post_id INTEGER,
	user_id INTEGER,
	FOREIGN KEY(user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE SET NULL,
	FOREIGN KEY(post_id) REFERENCES post(id) ON DELETE CASCADE ON UPDATE SET NULL
);
CREATE INDEX idx_fk_favoritee_post_id ON favoritee(post_id);
CREATE INDEX idx_fk_favoritee_user_id ON favoritee(user_id);
CREATE UNIQUE INDEX idx_uq_favoritee_post_id_user_id ON favoritee(post_id, user_id);

CREATE TABLE comment
(
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	post_id INTEGER,
	commenter_id INTEGER,
	comment_date INTEGER,
	text TEXT,
	FOREIGN KEY(post_id) REFERENCES post(id) ON DELETE CASCADE ON UPDATE SET NULL,
	FOREIGN KEY(commenter_id) REFERENCES user(id) ON DELETE SET NULL ON UPDATE SET NULL
);
CREATE INDEX idx_fk_comment_commenter_id ON comment(commenter_id);
CREATE INDEX idx_fk_comment_post_id ON comment(post_id);

CREATE TABLE post
(
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	type INTEGER,
	name TEXT,
	orig_name TEXT,
	file_hash TEXT,
	file_size INTEGER,
	mime_type TEXT,
	safety INTEGER,
	hidden INTEGER,
	upload_date INTEGER,
	image_width INTEGER,
	image_height INTEGER,
	uploader_id INTEGER,
	source TEXT,
	FOREIGN KEY(uploader_id) REFERENCES user(id) ON DELETE SET NULL ON UPDATE SET NULL
);
CREATE INDEX idx_fk_post_uploader_id ON post(uploader_id);

CREATE TABLE property
(
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	prop_id INTEGER,
	value TEXT
);
