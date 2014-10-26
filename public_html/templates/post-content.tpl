<% var postContentUrl = '/data/posts/' + post.name + '?x=' + Math.random() /* reset gif animations */ %>

<div class="post-content post-type-<%= post.contentType %>">
	<div class="post-notes-target">
	</div>

	<%  if (post.contentType === 'image') { %>

		<div class="image-wrapper" style="width: <%= post.imageWidth %>px">
			<img alt="<%= post.name %>" src="<%= postContentUrl %>"/>
			<div style="padding-top: calc(100% * <%= post.imageHeight %> / <%= post.imageWidth %>)"></div>
		</div>

	<% } else if (post.contentType === 'youtube') { %>

		<iframe src="//www.youtube.com/embed/<%= post.contentChecksum %>?wmode=opaque" allowfullscreen></iframe>

	<% } else if (post.contentType === 'flash') { %>

		<object
				type="<%= post.contentMimeType %>"
				width="<%= post.imageWidth %>"
				height="<%= post.imageHeight %>"
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

</div>
