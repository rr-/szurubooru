<% var postContentUrl = '/data/posts/' + post.name %>

<%  if (post.contentType == 'image') { %>

	<img alt="<%= post.name %>" src="<%= postContentUrl %>"/>

<% } else if (post.contentType == 'youtube') { %>

	<iframe src="//www.youtube.com/embed/<%= post.contentChecksum %>?wmode=opaque" allowfullscreen></iframe>

<% } else if (post.contentType == 'flash') { %>

	<object
			type="application/x-shockwave-flash"
			width="<%= post.imageWidth %>"
			height="<%= post.imageHeight %>"
			data="<%= postContentUrl %>">
		<param name="wmode" value="opaque"/>
		<param name="movie" value="<%= postContentUrl %>"/>
	</object>

<% } else if (post.contentType == 'video') { %>

	<video controls>
		<source src="<%= postContentUrl %>"/>

		Your browser doesn't support HTML5 videos.
	</video>

<% } else { console.log(new Error('Unknown post type')) } %>
