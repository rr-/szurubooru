<% _.each(notes, function(note) { %>
	<div class="post-note"
		style="left: <%= note.left %>px;
			top: <%= note.top %>px;
			width: <%= note.width %>px;
			height: <%= note.height %>px">

		<div class="text-wrapper">
			<%= note.text %>
		</div>

	</div>
<% }) %>
