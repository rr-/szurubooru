ALTER TABLE favoritee ADD COLUMN fav_date INTEGER DEFAULT NULL;
ALTER TABLE post ADD COLUMN fav_date INTEGER DEFAULT NULL;

CREATE TRIGGER favoritee_update_date AFTER UPDATE ON favoritee FOR EACH ROW
BEGIN
	UPDATE post SET fav_date = (SELECT MAX(fav_date) FROM favoritee WHERE favoritee.post_id = post.id);
END;

CREATE TRIGGER favoritee_insert_date AFTER INSERT ON favoritee FOR EACH ROW
BEGIN
	UPDATE post SET fav_date = (SELECT MAX(fav_date) FROM favoritee WHERE favoritee.post_id = post.id);
END;

CREATE TRIGGER favoritee_delete_date AFTER DELETE ON favoritee FOR EACH ROW
BEGIN
	UPDATE post SET fav_date = (SELECT MAX(fav_date) FROM favoritee WHERE favoritee.post_id = post.id);
END;
