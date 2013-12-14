ALTER TABLE post ADD COLUMN tag_count INTEGER NOT NULL DEFAULT 0;
ALTER TABLE post ADD COLUMN fav_count INTEGER NOT NULL DEFAULT 0;
ALTER TABLE post ADD COLUMN comment_count INTEGER NOT NULL DEFAULT 0;

UPDATE post SET tag_count = (SELECT COUNT(*) FROM post_tag WHERE post_id = post.id);
UPDATE post SET fav_count = (SELECT COUNT(*) FROM favoritee WHERE post_id = post.id);
UPDATE post SET comment_count = (SELECT COUNT(*) FROM comment WHERE post_id = post.id);

CREATE TRIGGER post_tag_insert AFTER INSERT ON post_tag FOR EACH ROW
BEGIN
	UPDATE post SET tag_count = tag_count + 1 WHERE post.id = new.post_id;
END;

CREATE TRIGGER post_tag_delete BEFORE DELETE ON post_tag FOR EACH ROW
BEGIN
	UPDATE post SET tag_count = tag_count - 1 WHERE post.id = old.post_id;
END;

CREATE TRIGGER favoritee_insert AFTER INSERT ON favoritee FOR EACH ROW
BEGIN
	UPDATE post SET fav_count = fav_count + 1 WHERE post.id = new.post_id;
END;

CREATE TRIGGER favoritee_delete BEFORE DELETE ON favoritee FOR EACH ROW
BEGIN
	UPDATE post SET fav_count = fav_count - 1 WHERE post.id = old.post_id;
END;

CREATE TRIGGER comment_insert AFTER INSERT ON comment FOR EACH ROW
BEGIN
	UPDATE post SET comment_count = comment_count + 1 WHERE post.id = new.post_id;
END;

CREATE TRIGGER comment_delete BEFORE DELETE ON comment FOR EACH ROW
BEGIN
	UPDATE post SET comment_count = comment_count - 1 WHERE post.id = old.post_id;
END;
