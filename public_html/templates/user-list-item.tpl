<div class="user">
	<a href="#/user/<%= user.name %>">
		<img width="80" height="80" src="/data/thumbnails/80x80/avatars/<%= user.name %>" alt="<%= user.name %>"/>
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
</div>
