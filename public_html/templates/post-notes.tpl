<div class="post-notes">
	<% _.each(notes, function(note) { %>
		<div class="post-note"
			style="left: <%= note.left %>px;
				top: <%= note.top %>px;
				width: <%= note.width %>px;
				height: <%= note.height %>px">

			<div class="text-wrapper">
				<div class="text">
					<%= note.text %>
				</div>
			</div>

		</div>
	<% }) %>
</div>
