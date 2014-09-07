<div id="user-list">
	<ul class="order">
		<li>
			<a data-order="name">Sort A&rarr;Z</a>
		</li>
		<li>
			<a data-order="name,desc">Sort Z&rarr;A</a>
		</li>
		<li>
			<a data-order="registrationTime">Sort old&rarr;new</a>
		</li>
		<li>
			<a data-order="registrationTime,desc">Sort new&rarr;old</a>
		</li>
	</ul>

	<ul class="users">
		<% _.each(userList, function(user) { %>
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
		<% }); %>
	</ul>

	<div class="pager"></div>
</div>
