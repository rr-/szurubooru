<div id="login-form">
	<p>
		If you don't have an account yet,<br/>
		<a href="#/register">click here</a> to create a new one.
	</p>

	<div class="messages"></div>

	<form class="form-wrapper">
		<div class="form-row">
			<label class="form-label" for="login-user">User name:</label>
			<div class="form-input">
				<input autocomplete="off" type="text" name="user" id="login-user"/>
			</div>
		</div>

		<div class="form-row">
			<label class="form-label" for="login-password">Password:</label>
			<div class="form-input">
				<input autocomplete="off" type="password" name="password" id="login-password"/>
			</div>
		</div>

		<div class="form-row">
			<label class="form-label"></label>
			<div class="form-input">
				<button type="submit">Log in</button>
				&nbsp;
				<label>
					<input type="checkbox" name="remember"/>
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
