<div class="messages"></div>

<form class="form-wrapper account-settings">
	<% if (canChangeAvatarStyle) { %>
		<div class="form-row">
			<label class="form-label">User picture:</label>
			<div class="form-input">
				<%
					var avatarStyles = {
						gravatar: 'Gravatar',
						manual: 'Custom',
						blank: 'Blank',
					};
				%>
				<% _.each(avatarStyles, function(v, k) { %>
					<input <% print(user.avatarStyle == k ? 'checked="checked"' : '') %> type="radio" name="avatar-style" id="account-settings-avatar-<%= k %>" value="<%= k %>"/>
					<label for="account-settings-avatar-<%= k %>">
						<%= v %>
					</label>
				<% }) %>
			</div>
		</div>

		<div class="form-row avatar-content">
			<label class="form-label"></label>
			<div class="form-input">
				<input type="file" name="avatar-content" id="account-settings-avatar-content"/>
			</div>
		</div>
	<% } %>

	<% if (canChangeName) { %>
		<div class="form-row">
			<label class="form-label" for="account-settings-name">Name:</label>
				<div class="form-input">
				<input autocomplete="off" type="text" name="userName" id="account-settings-name" placeholder="New name&hellip;" value="<%= user.name %>"/>
			</div>
		</div>
	<% } %>

	<% if (canChangeEmailAddress) { %>
		<div class="form-row">
			<label class="form-label" for="account-settings-email">E-mail:</label>
			<div class="form-input">
				<input autocomplete="off" type="text" name="email" id="account-settings-email" placeholder="New e-mail&hellip;" value="<%= user.email %>"/>
				<% if (user.emailUnconfirmed) { %>
					<br/>
					<span class="account-settings-email-unconfirmed">(unconfirmed) <%= user.emailUnconfirmed %></span>
				<% } %>
			</div>
		</div>
	<% } %>

	<% if (canChangePassword) { %>
		<div class="form-row">
			<label class="form-label" for="account-settings-password">New password:</label>
			<div class="form-input">
				<input autocomplete="off" type="password" name="password" id="account-settings-password" placeholder="New password&hellip;" value=""/>
			</div>
		</div>

		<div class="form-row">
			<label class="form-label" for="account-settings-password-confirmation"></label>
			<div class="form-input">
				<input autocomplete="off" type="password" name="passwordConfirmation" id="account-settings-password-confirmation" placeholder="New password&hellip; (repeat)" value=""/>
			</div>
		</div>
	<% } %>

	<% if (canBan) { %>
		<div class="form-row">
			<label class="form-label" for="account-settings-ban">Ban:</label>
			<div class="form-input">
				<input name="ban" type="checkbox" id="ban" <% print(user.banned ? 'checked="checked"' : '') %>>
				<label for="ban">
					Enabled
				</label>
			</div>
		</div>
	<% } %>


	<% if (canChangeAccessRank) { %>
		<div class="form-row">
			<label class="form-label" for="account-settings-access-rank">Access rank:</label>
			<div class="form-input">
				<%
					var accessRanks = {
						anonymous: 'Anonymous',
						restrictedUser: 'Restricted user',
						regularUser: 'Regular user',
						powerUser: 'Power user',
						moderator: 'Moderator',
						administrator: 'Administrator'
					};
				%>
				<% _.each(accessRanks, function(v, k) { %>
					<input name="access-rank" type="radio" value="<%= k %>" id="access-rank-<%= k %>" <% print(user.accessRank == k ? 'checked="checked"' : '') %>>
					<label for="access-rank-<%= k %>">
						<% print(user.accessRank == k ? v + ' (current)' : v) %>
					</label>
					<br/>
				<% }) %>
			</div>
		</div>
	<% } %>

	<div class="form-row">
		<label class="form-label"></label>
		<div class="form-input">
			<button type="submit">Update settings</button>
		</div>
	</div>
</form>
