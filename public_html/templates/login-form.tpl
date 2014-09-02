<div id="login-form">
	<p>
		If you don't have an account yet,<br/>
		<a href="#/register">click here</a> to create a new one.
	</p>

	<div class="messages"></div>

	<form method="post" class="form-wrapper">
		<div class="form-row">
			<label for="login-user" class="form-label">User name:</label>
			<div class="form-input">
				<input autocomplete="off" type="text" name="user" id="login-user"/>
			</div>
		</div>

		<div class="form-row">
			<label for="login-password" class="form-label">Password:</label>
			<div class="form-input">
				<input autocomplete="off" type="password" name="password" id="login-password"/>
			</div>
		</div>

		<div class="form-row">
			<label class="form-label"></label>
			<div class="form-input">
				<button class="submit" type="submit">Log in</button>
				&nbsp;
				<input type="hidden" name="remember" value="0"/>
				<label class="checkbox-wrapper">
					<input type="checkbox" name="remember" value="1"/>
					<span></span>
					Remember me
				</label>
			</div>
		</div>
	</form>

	<div class="help">
		<p>Problems logging in?</p>
		<ul>
			<li><a href="#/password-reset">I don't remember my password</a></li>
			<li><a href="#/activate-account">I haven't received activation e-mail</a></li>
			<li><a href="#/register">I don't have an account</a></li>
		</ul>
	</div>
</div>
