UPDATE post SET file_hash = orig_name WHERE type = 3;

CREATE TRIGGER post_tag_update AFTER UPDATE ON post_tag FOR EACH ROW
BEGIN
	UPDATE post SET tag_count = tag_count + 1 WHERE post.id = new.post_id;
	UPDATE post SET tag_count = tag_count - 1 WHERE post.id = old.post_id;
END;

CREATE TRIGGER favoritee_update AFTER UPDATE ON favoritee FOR EACH ROW
BEGIN
	UPDATE post SET fav_count = fav_count + 1 WHERE post.id = new.post_id;
	UPDATE post SET fav_count = fav_count - 1 WHERE post.id = old.post_id;
END;

CREATE TRIGGER comment_update AFTER UPDATE ON comment FOR EACH ROW
BEGIN
	UPDATE post SET comment_count = comment_count + 1 WHERE post.id = new.post_id;
	UPDATE post SET comment_count = comment_count - 1 WHERE post.id = old.post_id;
END;

ALTER TABLE usertoken RENAME TO user_token;

DROP TRIGGER post_score_update;
ALTER TABLE postscore RENAME TO post_score;

CREATE TRIGGER post_score_update AFTER UPDATE ON post_score FOR EACH ROW
BEGIN
	UPDATE post SET score = post.score + new.score WHERE post.id = new.post_id;
	UPDATE post SET score = post.score - old.score WHERE post.id = old.post_id;
END;

