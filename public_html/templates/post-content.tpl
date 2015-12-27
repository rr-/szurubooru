<%
    var postContentUrl = '/data/posts/' + post.name;
    var width;
    var height;
    if (post.contentType === 'image' || post.contentType === 'animation' || post.contentType === 'flash') {
        width = post.imageWidth;
        height = post.imageHeight;
    } else {
        width = 800;
        height = 450;
    }
%>

<div class="post-content post-type-<%= post.contentType %>">
    <div class="post-notes-target">
    </div>

    <div
        class="object-wrapper"
        data-width="<%= width %>"
        data-height="<%= height %>"
        style="max-width: <%= width %>px">

        <% if (post.contentType === 'image' || post.contentType === 'animation') { %>

            <img alt="<%= post.name %>" src="<%= postContentUrl %>"/>

        <% } else if (post.contentType === 'youtube') { %>

            <iframe src="//www.youtube.com/embed/<%= post.contentChecksum %>?wmode=opaque" allowfullscreen></iframe>

        <% } else if (post.contentType === 'flash') { %>

            <object
                    type="<%= post.contentMimeType %>"
                    width="<%= width %>"
                    height="<%= height %>"
                    data="<%= postContentUrl %>">
                <param name="wmode" value="opaque"/>
                <param name="movie" value="<%= postContentUrl %>"/>
            </object>

        <% } else if (post.contentType === 'video') { %>

            <% if (post.flags.loop) { %>
                <video id="video" controls loop="loop">
            <% } else { %>
                <video id="video" controls>
            <% } %>

                <source type="<%= post.contentMimeType %>" src="<%= postContentUrl %>"/>

                Your browser doesn't support HTML5 videos.
            </video>

        <% } else { console.log(new Error('Unknown post type')) } %>

        <div class="padding-fix" style="padding-bottom: calc(100% * <%= height %> / <%= width %>)"></div>
    </div>

</div>
