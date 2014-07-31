ALTER TABLE tag ADD COLUMN creation_date INTEGER DEFAULT NULL;
ALTER TABLE tag ADD COLUMN update_date INTEGER DEFAULT NULL;

DROP TRIGGER post_tag_insert;
CREATE TRIGGER post_tag_insert AFTER INSERT ON post_tag FOR EACH ROW
BEGIN
	UPDATE post SET tag_count = tag_count + 1 WHERE post.id = NEW.post_id;
	UPDATE tag SET update_date = UNIX_TIMESTAMP() WHERE tag.id = NEW.tag_id;
END;

CREATE TRIGGER tag_insert BEFORE INSERT ON tag FOR EACH ROW
BEGIN
	SET NEW.creation_date = UNIX_TIMESTAMP();
	SET NEW.update_date = UNIX_TIMESTAMP();
END;
