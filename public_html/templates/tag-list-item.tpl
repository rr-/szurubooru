<tr class="tag">
	<td class="name tag-category-<%= tag.category %>">
		<a href="#/tag/<%= tag.name %>"><%= tag.name %></a>
	</td>
	<td class="implications">
		<%= _.pluck(tag.implications, 'name').join(' ') || '-' %>
	</td>
	<td class="suggestions">
		<%= _.pluck(tag.suggestions, 'name').join(' ') || '-' %>
	</td>
	<td class="usages">
		<%= tag.usages %>
	</td>
	<td class="banned">
		<% if (tag.banned) { %>
			<i class="fa fa-times"></i>
		<% } else { %>
			<i class="fa fa-check"></i>
		<% } %>
	</td>
</tr>
