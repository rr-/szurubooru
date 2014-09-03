<ul class="pager">

	<% _.each(pages, function(page) { %>
		<% if (page == pageNumber) { %>
			<li class="active">
		<% } else { %>
			<li>
		<% } %>
			<a href="<%= link(page) %>">
				<%= page %>
			</a>
		</li>
	<% }); %>

</ul>
