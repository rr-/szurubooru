<form class="account-settings">
	<div class="form-row">
		<label class="form-label">User picture:</label>
		<div class="form-input">
			<label for="account-settings-avatar-gravatar">
				<input type="radio" name="avatar-style" id="account-settings-avatar-gravatar" class="avatar-style" value="gravatar"/>
				Gravatar
			</label>
			<label for="account-settings-avatar-manual">
				<input type="radio" name="avatar-style" id="account-settings-avatar-manual" class="avatar-style" value="manual"/>
				Custom
			</label>
			<label for="account-settings-avatar-none">
				<input type="radio" name="avatar-style" id="account-settings-avatar-none" class="avatar-style" value="none"/>
				None
			</label>
		</div>
	</div>

	<div class="form-row">
		<label class="form-label" for="account-settings-avatar-content"></label>
		<div class="form-input">
			<input class="avatar-content" type="file" name="avatar-content" id="account-settings-avatar-content"/>
		</div>
	</div>

	<div class="form-row">
		<label class="form-label" for="account-settings-name">Name:</label>
			<div class="form-input">
			<input type="text" name="name" id="account-settings-name" placeholder="New name&hellip;" value=""/>
		</div>
	</div>

	<div class="form-row">
		<label class="form-label" for="account-settings-email">E-mail:</label>
		<div class="form-input">
			<input type="text" name="email" id="account-settings-email" placeholder="New e-mail&hellip;" value=""/>
		</div>
	</div>

	<div class="form-row">
		<label class="form-label" for="account-settings-password1">New password:</label>
		<div class="form-input">
			<input type="password" name="password1" id="account-settings-password1" placeholder="New password&hellip;" value=""/>
		</div>
	</div>

	<div class="form-row">
		<label class="form-label" for="account-settings-password2"></label>
		<div class="form-input">
			<input type="password" name="password2" id="account-settings-password2" placeholder="New password&hellip; (repeat)" value=""/>
		</div>
	</div>

	<div class="form-row">
		<label class="form-label" for="account-settings-access-rank">Access rank:</label>
		<div class="form-input">
			<select name="access-rank" id="account-settings-access-rank">
			<option value="anonymous">anonymous</option>
			<option value="regular-user">registered</option>
			<option value="power-user">power user</option>
			<option value="moderator">moderator</option>
			<option value="administrator" selected="selected">admin</option>
		</select>
		</div>
	</div>

	<div class="form-row">
		<label class="form-label"></label>
		<div class="form-input">
			<button class="submit" type="submit">Update settings</button>
		</div>
	</div>
</form>
