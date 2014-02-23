ALTER TABLE post ADD COLUMN comment_date INTEGER DEFAULT NULL;

CREATE TRIGGER comment_update_date AFTER UPDATE ON comment FOR EACH ROW
BEGIN
	UPDATE post SET comment_date = (SELECT MAX(comment_date) FROM comment WHERE comment.post_id = post.id);
END;

CREATE TRIGGER comment_insert_date AFTER INSERT ON comment FOR EACH ROW
BEGIN
	UPDATE post SET comment_date = (SELECT MAX(comment_date) FROM comment WHERE comment.post_id = post.id);
END;

UPDATE comment SET id = id;
