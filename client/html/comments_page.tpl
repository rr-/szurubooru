<div class='global-comment-list'>
    <ul><!--
        --><% for (let post of ctx.results) { %><!--
            --><li><!--
                --><div class='post-thumbnail'><!--
                    --><% if (ctx.canViewPosts) { %><!--
                        --><a href='/post/<%- post.id %>'><!--
                    --><% } %><!--
                        --><%= ctx.makeThumbnail(post.thumbnailUrl) %><!--
                    --><% if (ctx.canViewPosts) { %><!--
                        --></a><!--
                    --><% } %><!--
                --></div><!--
                --><div class='comments-container' data-for='<%- post.id %>'></div><!--
            --></li><!--
        --><% } %><!--
    --></ul>
</div>
