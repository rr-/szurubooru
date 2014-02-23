INSERT
	INTO post_score(user_id, post_id, score)
	SELECT user_id, favoritee.post_id, 1
	FROM favoritee WHERE NOT EXISTS
	(
		SELECT *
			FROM post_score ps2
			WHERE favoritee.post_id = ps2.post_id
			AND favoritee.user_id = ps2.user_id
	);

UPDATE post_score
	SET score = 1
	WHERE user_id IN
	(
		SELECT user_id
			FROM favoritee
			WHERE favoritee.post_id = post_score.post_id
	);
