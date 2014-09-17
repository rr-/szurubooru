<ul>
	<%
		var links = [['home', '#/home', 'Home', 'fa-home']];
		if (canListPosts) {
			links.push(['posts', '#/posts', 'Posts', 'fa-th']);
			if (canUploadPosts) {
				links.push(['upload', '#/upload', 'Upload', 'fa-upload']);
			}
			links.push(['comments', '#/comments', 'Comments', 'fa-comments']);
		}
		if (canListTags) {
			links.push(['tags', '#/tags', 'Tags', 'fa-tags']);
		}
		if (canListUsers) {
			links.push(['users', '#/users', 'Users', 'fa-users']);
		}
		if (!loggedIn) {
			links.push(['login', '#/login', 'Login', 'fa-sign-in']);
			links.push(['register', '#/register', 'Register', 'fa-file-text-o']);
		} else {
			links.push(['my-account', '#/user/' + user.name, 'Account', 'fa-user']);
			links.push(['logout', '#/logout', 'Logout', 'fa-sign-out']);
		}
		links.push(['help', '#/help', 'Help', 'fa-question-circle']);
	%>

	<% _.each(links, function(link) { %><!--
		--><% var className = link[0], target=link[1], title=link[2], iconClassName=link[3] %><!--
		--><li class="<%= className %>">
			<a class="big-button" href="<%= target %>">
				<i class="fa <%= iconClassName %>"></i><br/>
				<%= title %>
			</a>
		</li><!--
	--><% }) %>
</ul>
