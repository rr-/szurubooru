<% if (canListComments && comments.length) { %>
	<div class="comments">
		<h1>Comments</h1>
		<ul class="comments">
		</ul>
	</div>
<% } %>

<% if (canAddComments) { %>
	<div class="comment-add">
		<%= commentFormTemplate({title: 'Add comment'}) %>
	</div>
<% } %>
