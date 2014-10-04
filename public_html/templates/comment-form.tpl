<form class="comment-form">

	<h1><%= title %></h1>

	<div class="preview"></div>

	<div class="form-row text">
		<div class="input-wrapper">
			<textarea name="text" cols="50" rows="3"><% if (typeof(text) !== 'undefined') { print(text) } %></textarea>
		</div>
	</div>

	<div class="form-row">
		<button type="submit" name="sender" value="preview">Preview</button>&nbsp;
		<button type="submit" name="sender" value="submit">Submit</button>
	</div>
</form>
