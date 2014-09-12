<li class="user">
	<a href="#/user/<%= user.name %>">
		<img src="/api/users/<%= user.name %>/avatar/80" alt="<%= user.name %>"/>
	</a>
	<div class="details">
		<h1>
			<a href="#/user/<%= user.name %>">
				<%= user.name %>
			</a>
		</h1>
		<div class="date-joined" title="<%= user.registrationTime %>">
			Joined: <%= formatRelativeTime(user.registrationTime) %>
		</div>
		<div class="date-seen">
			Last seen: <%= formatRelativeTime(user.lastLoginTime) %>
		</div>
	</div>
</li>
