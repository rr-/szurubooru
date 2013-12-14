CREATE UNIQUE INDEX idx_uq_postscore_post_id_user_id ON postscore(post_id, user_id);
CREATE UNIQUE INDEX idx_uq_crossref_post_id_post2_id ON crossref(post_id, post2_id);

