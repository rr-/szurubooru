<div class='post-content post-type-<%= ctx.post.type %>'>
    <% if (['image', 'animation'].includes(ctx.post.type)) { %>

        <img alt='<%= ctx.post.id %>' src='<%= ctx.post.contentUrl %>'/>

    <% } else if (ctx.post.type === 'flash') { %>

        <object width='<%= ctx.post.canvasWidth %>' height='<%= ctx.post.canvasHeight %>' data='<%= ctx.post.contentUrl %>'>
            <param name='wmode' value='opaque'/>
            <param name='movie' value='<%= ctx.post.contentUrl %>'/>
        </object>

    <% } else if (ctx.post.type === 'video') { %>

        <% if ((ctx.post.flags || []).includes('loop')) { %>
            <video id='video' controls loop='loop'>
        <% } else { %>
            <video id='video' controls>
        <% } %>

            <source type='<%= ctx.post.mimeType %>' src='<%= ctx.post.contentUrl %>'/>

            Your browser doesn't support HTML5 videos.
        </video>

    <% } else { console.log(new Error('Unknown post type')); } %>

    <div class='post-overlay'>
    </div>
</div>
