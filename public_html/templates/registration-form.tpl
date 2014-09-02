<div id="registration-form">
	<p>
		Registered users can view more content,<br/>
		upload files and add posts to favorites.
	</p>

	<div class="messages"></div>

	<form method="post" class="form-wrapper">
		<div class="form-row">
			<label for="registration-user" class="form-label">User name:</label>
			<div class="form-input">
				<input autocomplete="off" type="text" name="user" id="registration-user" placeholder="e.g. darth_vader" value=""/>
			</div>
		</div>

		<div class="form-row">
			<label for="registration-password" class="form-label">Password:</label>
			<div class="form-input">
				<input autocomplete="off" type="password" name="password1" id="registration-password" placeholder="e.g. &#x25cf;&#x25cf;&#x25cf;&#x25cf;&#x25cf;&#x25cf;&#x25cf;&#x25cf;" value=""/>
			</div>
		</div>

		<div class="form-row">
			<label for="registration-password-confirm" class="form-label">Password (repeat):</label>
			<div class="form-input">
				<input autocomplete="off" type="password" name="password2" id="registration-password-confirm" placeholder="e.g. &#x25cf;&#x25cf;&#x25cf;&#x25cf;&#x25cf;&#x25cf;&#x25cf;&#x25cf;" value=""/>
			</div>
		</div>

		<div class="form-row">
			<label for="registration-email" class="form-label">E-mail address:</label>
			<div class="form-input">
				<input autocomplete="off" type="text" name="email" id="registration-email" placeholder="e.g. vader@empire.gov" value=""/>
			</div>
		</div>

		<div class="form-row">
			<label class="form-label"></label>
			<div class="form-input">
				<button class="submit" type="submit">Register</button>
			</div>
		</div>
	</form>

	<p id="email-info">
		Your e-mail will be used to show your <a href="http://gravatar.com/">Gravatar</a>.<br/>
		Leave blank for random Gravatar.
	</p>
</div>
