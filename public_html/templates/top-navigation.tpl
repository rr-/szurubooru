<ul>
	<li class="home">
		<a href="#/home">Home</a>
	</li>

	<% if (canListPosts) { %>
		<li class="posts">
			<a href="#/posts">Posts</a>
		</li>
		<% if (canUploadPosts) { %>
			<li class="upload">
				<a href="#/upload">Upload</a>
			</li>
		<% } %>
		<li class="comments">
			<a href="#/comments">Comments</a>
		</li>
	<% } %>

	<% if (canListTags) { %>
		<li class="tags">
			<a href="#/tags">Tags</a>
		</li>
	<% } %>

	<% if (canListUsers) { %>
		<li class="users">
			<a href="#/users">Users</a>
		</li>
	<% } %>

	<% if (!loggedIn) { %>
		<li class="login">
			<a href="#/login">Login</a>
		</li>
		<li class="register">
			<a href="#/register">Register</a>
		</li>
	<% } else { %>
		<li class="my-account">
			<a href="#/user/<%= user.name %>"><%= user.name %></a>
		</li>
		<li class="logout">
			<a href="#/logout">Logout</a>
		</li>
	<% } %>

	<li class="help">
		<a href="#/help">Help</a>
	</li>
</ul>
