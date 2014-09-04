<form class="browsing-settings">
	<div class="form-row">
		<label class="form-label">Safety:</label>
		<div class="form-input">
			<input type="hidden" name="safety[]" value=""/>
			<label for="browsing-settings-safety-safe">
				<input type="checkbox" id="browsing-settings-safety-safe" name="safety[]" value="1"/>
				Safe
			</label>

			<input type="hidden" name="safety[]" value=""/>
			<label for="browsing-settings-safety-sketchy">
				<input type="checkbox" id="browsing-settings-safety-sketchy" name="safety[]" value="2"/>
				Sketchy
			</label>

			<input type="hidden" name="safety[]" value=""/>
			<label for="browsing-settings-safety-unsafe">
				<input type="checkbox" id="browsing-settings-safety-unsafe" name="safety[]" value="3"/>
				Unsafe
			</label>
		</div>
	</div>

	<div class="form-row">
		<label class="form-label" for="browsing-settings-endless-scrolling">Endless scrolling:</label>
		<div class="form-input">
			<input type="hidden" name="endless-scrolling" value="0"/>
			<label for="browsing-settings-endless-scrolling">
				<input type="checkbox" id="browsing-settings-endless-scrolling" name="endless-scrolling" value="1"/>
				Enabled
			</label>
		</div>
	</div>

	<div class="form-row">
		<label class="form-label" for="browsing-settings-hide-disliked-posts">Hide down-voted:</label>
		<div class="form-input">
			<input type="hidden" name="hide-disliked-posts" value="0"/>
			<label for="browsing-settings-hide-disliked-posts">
				<input type="checkbox" id="browsing-settings-hide-disliked-posts" name="hide-disliked-posts" value="1"/>
				Enabled
			</label>
		</div>
	</div>

	<div class="form-row">
		<label class="form-label"></label>
		<div class="form-input">
			<button class="submit" type="submit">Update settings</button>
		</div>
	</div>
</form>


