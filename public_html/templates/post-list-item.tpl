<div class="post-small post-type-<%= post.contentType %> ">
	<a class="link"
		href="<%= util.appendComplexRouteParam('#/post/' + post.id, typeof(query) !== 'undefined' ? query : {}) %>"
		title="<%= _.map(post.tags, function(tag) { return '#' + tag.name; }).join(', ') %>">

		<img width="160" height="160" class="thumb" src="/data/thumbnails/160x160/posts/<%= post.name %>" alt="<%= post.idMarkdown %>"/>

		<% if (post.favoriteCount || post.score || post.commentCount) { %>
			<div class="info">
				<ul>
					<% if (post.favoriteCount) { %>
						<li>
							<i class="fa fa-heart"></i>
							<%= post.favoriteCount %>
						</li>
					<% } %>

					<% if (post.score) { %>
						<li>
							<i class="fa fa-thumbs-up"></i>
							<%= post.score %>
						</li>
					<% } %>

					<% if (post.commentCount) { %>
						<li>
							<i class="fa fa-comments"></i>
							<%= post.commentCount %>
						</li>
					<% } %>
				</ul>
			</div>
		<% } %>
	</a>
</div>
