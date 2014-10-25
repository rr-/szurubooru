<% var postContentUrl = '/data/posts/' + post.name + '?x=' + Math.random() /* reset gif animations */ %>

<div class="post-type-<%= post.contentType %>">
	<div class="post-notes-target">
	</div>

	<%  if (post.contentType === 'image') { %>

		<img alt="<%= post.name %>" src="<%= postContentUrl %>"/>

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
