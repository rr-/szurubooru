ALTER TABLE post ADD COLUMN comment_date INTEGER DEFAULT NULL;

DROP TRIGGER comment_update;

CREATE TRIGGER comment_update AFTER UPDATE ON comment FOR EACH ROW
BEGIN
	UPDATE post SET comment_count = comment_count + 1 WHERE post.id = new.post_id;
	UPDATE post SET comment_count = comment_count - 1 WHERE post.id = old.post_id;
	UPDATE post SET comment_date = (SELECT MAX(comment_date) FROM comment WHERE comment.post_id = post.id);
END;

DROP TRIGGER comment_insert;

CREATE TRIGGER comment_insert AFTER INSERT ON comment FOR EACH ROW
BEGIN
	UPDATE post SET comment_count = comment_count + 1 WHERE post.id = new.post_id;
	UPDATE post SET comment_date = (SELECT MAX(comment_date) FROM comment WHERE comment.post_id = post.id);
END;

UPDATE comment SET id = id;
