<div id="user-view">
	<div class="messages"></div>
	<%= user.name %>

	<h2>Browsing settings</h2>
	<div class="browsing-settings"></div>

	<h2>Account settings</h2>
	<div class="account-settings"></div>

	<% if (canDeleteAccount) { %>
		<h2>Account removal</h2>
		<div class="account-removal"></div>
	<% } %>

</div>
