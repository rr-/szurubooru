CREATE TABLE crossref
(
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	post_id INTEGER,
	post2_id INTEGER,
	FOREIGN KEY(post_id) REFERENCES post(id) ON DELETE CASCADE ON UPDATE SET NULL,
	FOREIGN KEY(post2_id) REFERENCES post(id) ON DELETE CASCADE ON UPDATE SET NULL
);
CREATE INDEX idx_fk_crossref_post_id ON crossref(post_id);
CREATE INDEX idx_fk_crossref_post2_id ON crossref(post2_id);
