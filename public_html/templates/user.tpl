<div id="user-view" class="tab-wrapper">
	<div class="messages"></div>

	<div class="top">
		<div class="side">
			<img src="/api/users/<%= user.name %>/avatar/100" alt="Avatar"/>
			<br/>
			<%= user.name %>
		</div>

		<% if ((canChangeBrowsingSettings || canChangeAccountSettings || canDeleteAccount)) { %>

		<ul>
			<li>
				<a class="big-button" href="#/user/<%= user.name %>" data-tab="basic-info">
					Basic information
				</a>
			</li>

			<% if (canChangeBrowsingSettings) { %>
				<li>
					<a class="big-button" href="#/user/<%= user.name %>/browsing-settings" data-tab="browsing-settings">
						Browsing settings
					</a>
				</li>
			<% } %>

			<% if (canChangeAccountSettings) { %>
				<li>
					<a class="big-button" href="#/user/<%= user.name %>/account-settings" data-tab="account-settings">
						Account settings
					</a>
				</li>
			<% } %>

			<% if (canDeleteAccount) { %>
				<li>
					<a class="big-button" href="#/user/<%= user.name %>/account-removal" data-tab="account-removal">
						Account removal
					</a>
				</li>
			<% } %>
		</ul>

		<% } %>
	</div>

	<div class="tab basic-info" data-tab="basic-info">
		<h2>Basic information</h2>

		<table>
			<tr>
				<td>Registered:</td>
				<td><%= formatRelativeTime(user.registrationTime) %></td>
			</tr>

			<tr>
				<td>Seen:</td>
				<td><%= formatRelativeTime(user.lastLoginTime) %></td>
			</tr>
		</table>
	</div>

	<% if (canChangeBrowsingSettings) { %>
		<div class="tab" data-tab="browsing-settings">
			<h2>Browsing settings</h2>
			<div id="browsing-settings-target"></div>
		</div>
	<% } %>

	<% if (canChangeAccountSettings) { %>
		<div class="tab" data-tab="account-settings">
			<h2>Account settings</h2>
			<div id="account-settings-target"></div>
		</div>
	<% } %>

	<% if (canDeleteAccount) { %>
		<div class="tab" data-tab="account-removal">
			<h2>Account removal</h2>
			<div id="account-removal-target"></div>
		</div>
	<% } %>

</div>
