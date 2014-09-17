<li class="post post-type-<%= post.contentType %> ">
	<a class="link"
		title="<%= _.map(post.tags, function(tag) { return '#' + tag; }).join(', ') %>"
		href="#/post/<%= post.id %>">

		<img class="thumb" src="/data/thumbnails/160x160/posts/<%= post.name %>" alt="@<%= post.id %>"/>
	</a>
</li>
