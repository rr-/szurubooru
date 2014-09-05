<form class="account-settings">
	<div class="messages"></div>

	<div class="form-row">
		<label class="form-label" for="account-removal-confirmation">Confirmation:</label>
		<div class="form-input">
			<input type="hidden" name="confirmation" value="0"/>
			<label for="account-removal-confirmation">
				<input type="checkbox" id="account-removal-confirmation" name="confirmation" value="1"/>
				I confirm that I want to delete this account.
			</label>
		</div>
	</div>

	<div class="form-row">
		<label class="form-label"></label>
		<div class="form-input">
			<button class="submit" type="submit">Delete account</button>
		</div>
	</div>
</form>
