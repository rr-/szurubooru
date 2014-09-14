<ul>
	<%
		var links = [['home', '#/home', 'Home']];
		if (canListPosts) {
			links.push(['posts', '#/posts', 'Posts']);
			if (canUploadPosts) {
				links.push(['upload', '#/upload', 'Upload']);
			}
			links.push(['comments', '#/comments', 'Comments']);
		}
		if (canListTags) {
			links.push(['tags', '#/tags', 'Tags']);
		}
		if (canListUsers) {
			links.push(['users', '#/users', 'Users']);
		}
		if (!loggedIn) {
			links.push(['login', '#/login', 'Login']);
			links.push(['register', '#/register', 'Register']);
		} else {
			links.push(['my-account', '#/user/' + user.name, user.name]);
			links.push(['logout', '#/logout', 'Logout']);
		}
		links.push(['help', '#/help', 'Help']);
	%>

	<% _.each(links, function(link) { %><!--
		--><% var className = link[0], target=link[1], title=link[2] %><!--
		--><li class="<%= className %>">
			<a href="<%= target %>"><%= title %></a>
		</li><!--
	--><% }) %>
</ul>
