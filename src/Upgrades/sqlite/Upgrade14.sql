--merge multiple triggers into singular ones

DROP TRIGGER comment_update;
DROP TRIGGER comment_update_date;

CREATE TRIGGER comment_update AFTER UPDATE ON comment FOR EACH ROW
BEGIN
	UPDATE post SET comment_count = comment_count + 1 WHERE post.id = new.post_id;
	UPDATE post SET comment_count = comment_count - 1 WHERE post.id = old.post_id;
	UPDATE post SET comment_date = (SELECT MAX(comment_date) FROM comment WHERE comment.post_id = post.id);
END;

DROP TRIGGER comment_insert;
DROP TRIGGER comment_insert_date;

CREATE TRIGGER comment_insert AFTER INSERT ON comment FOR EACH ROW
BEGIN
	UPDATE post SET comment_count = comment_count + 1 WHERE post.id = new.post_id;
	UPDATE post SET comment_date = (SELECT MAX(comment_date) FROM comment WHERE comment.post_id = post.id);
END;

DROP TRIGGER favoritee_update;
DROP TRIGGER favoritee_update_date;

CREATE TRIGGER favoritee_update AFTER UPDATE ON favoritee FOR EACH ROW
BEGIN
	UPDATE post SET fav_count = fav_count + 1 WHERE post.id = new.post_id;
	UPDATE post SET fav_count = fav_count - 1 WHERE post.id = old.post_id;
	UPDATE post SET fav_date = (SELECT MAX(fav_date) FROM favoritee WHERE favoritee.post_id = post.id);
END;

DROP TRIGGER favoritee_insert;
DROP TRIGGER favoritee_insert_date;

CREATE TRIGGER favoritee_insert AFTER INSERT ON favoritee FOR EACH ROW
BEGIN
	UPDATE post SET fav_count = fav_count + 1 WHERE post.id = new.post_id;
	UPDATE post SET fav_date = (SELECT MAX(fav_date) FROM favoritee WHERE favoritee.post_id = post.id);
END;

DROP TRIGGER favoritee_delete;
DROP TRIGGER favoritee_delete_date;

CREATE TRIGGER favoritee_delete AFTER DELETE ON favoritee FOR EACH ROW
BEGIN
	UPDATE post SET fav_count = fav_count - 1 WHERE post.id = old.post_id;
	UPDATE post SET fav_date = (SELECT MAX(fav_date) FROM favoritee WHERE favoritee.post_id = post.id);
END;
