<% var permaLink = (window.location.origin + '/' + window.location.pathname + '/data/posts/' + post.name).replace(/([^:])\/+/g, '$1/') %>

<div id="post-current-search-wrapper">
	<div id="post-current-search">
		<div class="left">
			<a class="enabled">
				<i class="fa fa-chevron-left"></i>
				Next
			</a>
		</div>

		<div class="search">
			<a href="#/posts/query=<%= query.query %>;order=<%= query.order %>">
				Current search: <%= query.query || '-' %>
			</a>
		</div>

		<div class="right">
			<a class="enabled">
				Previous
				<i class="fa fa-chevron-right"></i>
			</a>
		</div>
	</div>
</div>

<div id="post-view-wrapper">
	<div id="sidebar">
		<ul class="essential">
			<li>
				<a class="download" href="<%= permaLink %>">
					<i class="fa fa-download"></i>
					<br/>
					<%= post.contentExtension + ', ' + formatFileSize(post.originalFileSize) %>
				</a>
			</li>

			<% if (isLoggedIn) { %>
				<li>
					<% if (hasFav) { %>
						<a class="delete-favorite" href="#">
							<i class="fa fa-heart"></i>
						</a>
					<% } else { %>
						<a class="add-favorite" href="#">
							<i class="fa fa-heart-o"></i>
						</a>
					<% } %>
				</li>

				<li>
					<a class="score-up <% print(ownScore === 1 ? 'active' : '') %>" href="#">
						<% if (ownScore === 1) { %>
							<i class="fa fa-thumbs-up"></i>
						<% } else { %>
							<i class="fa fa-thumbs-o-up"></i>
						<% } %>
					</a>
				</li>

				<li>
					<a class="score-down <% print(ownScore === -1 ? 'active' : '') %>" href="#">
						<% if (ownScore === -1) { %>
							<i class="fa fa-thumbs-down"></i>
						<% } else { %>
							<i class="fa fa-thumbs-o-down"></i>
						<% } %>
					</a>
				</li>
			<% } %>
		</ul>

		<h1>Tags (<%= _.size(post.tags) %>)</h1>
		<ul class="tags">
			<% _.each(post.tags, function(tag) { %>
				<li class="tag-category-<%= tag.category %>">
					<a href="#/posts/query=<%= tag.name %>">
						<%= tag.name %>
						<span class="usages"><%= (tag.usages) %></span>
					</a>
				</li>
			<% }) %>
		</ul>

		<h1>Details</h1>

		<div class="author-box">
			<% if (post.user.name) { %>
				<a href="#/user/<%= post.user.name %>">
			<% } %>

			<img width="40" height="40" class="author-avatar"
				src="/data/thumbnails/40x40/avatars/<%= post.user.name || '!' %>"
				alt="<%= post.user.name || 'Anonymous user' %>"/>

			<span class="author-name">
				<%= post.user.name || 'Anonymous user' %>
			</span>

			<% if (post.user.name) { %>
				</a>
			<% } %>

			<br/>

			<span class="date"><%= formatRelativeTime(post.uploadTime) %></span>
		</div>

		<ul class="other-info">

			<li>
				Rating:
				<span class="safety-<%= post.safety %>">
					<%= post.safety %>
				</span>
			</li>

			<% if (post.originalFileSize) { %>
				<li>
					File size:
					<%= formatFileSize(post.originalFileSize) %>
				</li>
			<% } %>

			<% if (post.contentType == 'image') { %>
				<li>
					Image size:
					<%= post.imageWidth + 'x' + post.imageHeight %>
				</li>
			<% } %>

			<% if (post.lastEditTime !== post.uploadTime) { %>
				<li>
					Edited:
					<%= formatRelativeTime(post.lastEditTime) %>
				</li>
			<% } %>

			<% if (post.featureCount > 0) { %>
				<li>
					Featured: <%= post.featureCount %> <%= post.featureCount < 2 ? 'time' : 'times' %>
					<small>(<%= formatRelativeTime(post.lastFeatureTime) %>)</small>
				</li>
			<% } %>

			<% if (post.source) { %>
				<li><!--
					--><% var link = post.source.match(/^(\/\/|https?:\/\/)/); %><!--
					-->Source:&nbsp;<!--
					--><% if (link) { %><!--
						--><a href="<%= post.source %>"><!--
					--><% } %><!--
						--><%= post.source.trim() %><!--
					--><% if (link) { %><!--
						--></a><!--
					--><% } %><!--
				--></li>
			<% } %>

			<li>
				Score: <%= post.score %>
			</li>
		</ul>

		<% if (_.any(postFavorites)) { %>
			<p>Favorites:</p>

			<ul class="favorites">
				<% _.each(postFavorites, function(user) { %>
					<li>
						<a href="#/user/<%= user.name %>">
							<img class="fav-avatar"
								src="/data/thumbnails/25x25/avatars/<%= user.name || '!' %>"
								alt="<%= user.name || 'Anonymous user' %>"/>
						</a>
					</li>
				<% }) %>
			</ul>
		<% } %>

		<% if (_.any(post.relations)) { %>
			<h1>Related posts</h1>
			<ul class="related">
				<% _.each(post.relations, function(relatedPost) { %>
					<li>
						<a href="#/post/<%= relatedPost.id %>">
							<%= relatedPost.idMarkdown %>
						</a>
					</li>
				<% }) %>
			</ul>
		<% } %>

		<% if (_.any(privileges) || _.any(editPrivileges) || post.contentType === 'image') { %>
			<h1>Options</h1>

			<ul class="operations">
				<% if (_.any(editPrivileges)) { %>
					<li>
						<a class="edit" href="#">
							Edit
						</a>
					</li>
				<% } %>

				<% if (privileges.canDeletePosts) { %>
					<li>
						<a class="delete" href="#">
							Delete
						</a>
					</li>
				<% } %>

				<% if (privileges.canFeaturePosts) { %>
					<li>
						<a class="feature" href="#">
							Feature
						</a>
					</li>
				<% } %>

				<% if (privileges.canViewHistory) { %>
					<li>
						<a class="history" href="#">
							History
						</a>
					</li>
				<% } %>

				<% if (post.contentType === 'image') { %>
					<li>
						<a href="http://iqdb.org/?url=<%= permaLink %>">
							Search on IQDB
						</a>
					</li>

					<li>
						<a href="https://www.google.com/searchbyimage?&image_url=<%= permaLink %>">
							Search on Google Images
						</a>
					</li>
				<% } %>
			</ul>
		<% } %>

	</div>

	<div id="post-view">
		<div class="messages"></div>

		<div class="post-edit-wrapper">
			<%= postEditTemplate({post: post, privileges: editPrivileges}) %>
		</div>

		<%= postContentTemplate({post: post}) %>

		<% if (privileges.canViewHistory) { %>
			<div class="post-history-wrapper">
				<%= historyTemplate({
					history: postHistory,
					formatRelativeTime: formatRelativeTime
				}) %>
			</div>
		<% } %>

		<div id="post-comments-target">
		</div>
	</div>
</div>
