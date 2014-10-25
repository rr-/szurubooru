<div class="post-notes">
	<% _.each(notes, function(note) { %>
		<div class="post-note"
			style="left: <%= note.left %>px;
				top: <%= note.top %>px;
				width: <%= note.width %>px;
				height: <%= note.height %>px">

			<div class="text-wrapper">
				<div class="text">
					<%= formatMarkdown(note.text) %>
				</div>
			</div>

		</div>
	<% }) %>
</div>

<form class="post-note-edit">
	<textarea></textarea>
	<div class="actions"><!--
		--><% if (privileges.canEditPostNotes) { %><!--
			--><button type="submit" name="sender" value="save">Save</button><!--
		--><% } %><!--
		--><button type="submit" name="sender" value="cancel">Cancel</button><!--
		--><% if (privileges.canDeletePostNotes) { %><!--
			--><button type="submit" name="sender" value="remove">Remove</button><!--
		--><% } %><!--
	--></div>
</form>
