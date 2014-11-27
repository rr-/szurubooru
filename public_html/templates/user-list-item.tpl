<div class="user">
	<div class="avatar">
		<% if (canViewUsers) { %>
			<a href="#/user/<%= user.name %>">
		<% } %>
			<img width="80" height="80" src="/data/thumbnails/80x80/avatars/<%= user.name %>" alt="<%= user.name %>"/>
		<% if (canViewUsers) { %>
			</a>
		<% } %>
	</div>

	<div class="details">
		<h1>
			<% if (canViewUsers) { %>
				<a href="#/user/<%= user.name %>">
					<%= user.name %>
				</a>
			<% } else { %>
				<%= user.name %>
			<% } %>
		</h1>
		<div class="date-joined" title="<%= formatAbsoluteTime(user.registrationTime) %>">
			Joined: <%= formatRelativeTime(user.registrationTime) %>
		</div>
		<div class="date-seen" title="<%= formatAbsoluteTime(user.lastLoginTime) %>">
			Last seen: <%= formatRelativeTime(user.lastLoginTime) %>
		</div>
	</div>
</div>
