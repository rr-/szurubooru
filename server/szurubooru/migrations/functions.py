from alembic_utils.pg_function import PGFunction

get_pool_posts_around = PGFunction.from_sql("""
CREATE OR REPLACE FUNCTION public.get_pool_posts_around(
  P_POOL_ID int,
  P_POST_ID int
)
  RETURNS TABLE (
    ORD int,
    POOL_ID int,
    POST_ID int,
    DELTA int
  )
  LANGUAGE PLPGSQL
AS $$
BEGIN
  RETURN QUERY WITH main AS (
    SELECT * FROM pool_post WHERE pool_post.pool_id = P_POOL_ID AND pool_post.post_id = P_POST_ID
  ),
    around AS (
      (SELECT pool_post.ord,
              pool_post.pool_id,
              pool_post.post_id,
              1 as delta,
              main.ord AS target_ord,
              main.pool_id AS target_pool_id
         FROM pool_post, main
        WHERE pool_post.ord > main.ord
          AND pool_post.pool_id = main.pool_id
        ORDER BY pool_post.ord ASC LIMIT 1)
        UNION
        (SELECT pool_post.ord,
                pool_post.pool_id,
                pool_post.post_id,
                -1 as delta,
                main.ord AS target_ord,
                main.pool_id AS target_pool_id
           FROM pool_post, main
          WHERE pool_post.ord < main.ord
            AND pool_post.pool_id = main.pool_id
          ORDER BY pool_post.ord DESC LIMIT 1)
    )
      SELECT around.ord, around.pool_id, around.post_id, around.delta FROM around;
END
$$
""")
