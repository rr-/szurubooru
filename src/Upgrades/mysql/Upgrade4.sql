ALTER TABLE post ADD COLUMN score INTEGER NOT NULL DEFAULT 0;

UPDATE post SET score = 0;

CREATE TABLE post_score
(
	id INTEGER PRIMARY KEY AUTO_INCREMENT,
	post_id INTEGER,
	user_id INTEGER,
	score INTEGER,
	FOREIGN KEY(post_id) REFERENCES post(id) ON DELETE CASCADE ON UPDATE SET NULL,
	FOREIGN KEY(user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE SET NULL
);
CREATE INDEX idx_fk_post_score_post_id ON post_score(post_id);
CREATE INDEX idx_fk_post_score_user_id ON post_score(user_id);

CREATE TRIGGER post_score_update AFTER UPDATE ON post_score FOR EACH ROW
BEGIN
	UPDATE post SET score = post.score - old.score + new.score WHERE post.id = new.post_id;
END;

CREATE TRIGGER post_score_insert AFTER INSERT ON post_score FOR EACH ROW
BEGIN
	UPDATE post SET score = post.score + new.score WHERE post.id = new.post_id;
END;

CREATE TRIGGER post_score_delete BEFORE DELETE ON post_score FOR EACH ROW
BEGIN
	UPDATE post SET score = post.score - old.score WHERE post.id = old.post_id;
END;
