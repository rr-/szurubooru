<div id="user-view">
	<div class="messages"></div>

	<img src="/api/users/<%= user.name %>/avatar/50" alt="Avatar"/>
	<%= user.name %>

	<% if (canChangeBrowsingSettings) { %>
		<h2>Browsing settings</h2>
		<div id="browsing-settings-target"></div>
	<% } %>

	<% if (canChangeAccountSettings) { %>
		<h2>Account settings</h2>
		<div id="account-settings-target"></div>
	<% } %>

	<% if (canDeleteAccount) { %>
		<h2>Account removal</h2>
		<div id="account-removal-target"></div>
	<% } %>

</div>
