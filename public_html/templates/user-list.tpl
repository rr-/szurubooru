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

	<% _.each(userList, function(user) { %>
		<div class="user">
			User name: <%= user.name %>
		</div>
	<% }); %>

	<div class="pager"></div>
</div>
