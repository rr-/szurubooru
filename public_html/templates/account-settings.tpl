<form class="account-settings">
	<div class="messages"></div>

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
					<label for="account-settings-avatar-<%= k %>">
						<input <% print(user.avatarStyle == k ? 'checked="checked"' : '') %> type="radio" name="avatar-style" id="account-settings-avatar-<%= k %>" value="<%= k %>"/>
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
				<input type="text" name="userName" id="account-settings-name" placeholder="New name&hellip;" value="<%= user.name %>"/>
			</div>
		</div>
	<% } %>

	<% if (canChangeEmailAddress) { %>
		<div class="form-row">
			<label class="form-label" for="account-settings-email">E-mail:</label>
			<div class="form-input">
				<input type="text" name="email" id="account-settings-email" placeholder="New e-mail&hellip;" value="<%= user.email %>"/>
			</div>
		</div>
	<% } %>

	<% if (canChangePassword) { %>
		<div class="form-row">
			<label class="form-label" for="account-settings-password">New password:</label>
			<div class="form-input">
				<input type="password" name="password" id="account-settings-password" placeholder="New password&hellip;" value=""/>
			</div>
		</div>

		<div class="form-row">
			<label class="form-label" for="account-settings-password-confirmation"></label>
			<div class="form-input">
				<input type="password" name="passwordConfirmation" id="account-settings-password-confirmation" placeholder="New password&hellip; (repeat)" value=""/>
			</div>
		</div>
	<% } %>

	<% if (canChangeAccessRank) { %>
		<div class="form-row">
			<label class="form-label" for="account-settings-access-rank">Access rank:</label>
			<div class="form-input">
				<select name="access-rank" id="account-settings-access-rank">
					<%
						var accessRanks = {
							anonymous: 'Anonymous',
							regularUser: 'Regular user',
							powerUser: 'Power user',
							moderator: 'Moderator',
							administrator: 'Administrator'
						};
					%>
					<% _.each(accessRanks, function(v, k) { %>
						<option <% print(user.accessRank == k ? 'selected="selected"' : '') %> value="<%= k %>"><%= v %></option>
					<% }) %>
				</select>
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
