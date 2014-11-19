<%
var reprValue = function(value) {
	if (typeof(value) === 'string' || value instanceof String) {
		return value;
	}
	return JSON.stringify(value);
};

var showDifference = function(className, difference) {
	_.each(difference, function(value, key) {
		if (!Array.isArray(value)) {
			value = [value];
		}
		_.each(value, function(v) {
			%><li class="<%= className %> difference-<%= key %>"><%= key + ':' + reprValue(v) %></li><%
		});
	});
};
%>

<table class="history">
	<tbody>
		<% _.each(history, function( historyEntry) { %>
			<tr>
				<td class="time">
					<%= formatRelativeTime(historyEntry.time) %>
				</td>

				<td class="user">
					<% var userName = historyEntry.user && historyEntry.user.name || '' %>

					<% if (userName) { %>
						<a href="#/user/<%= userName %>">
					<% } %>

					<img width="20" height="20" class="author-avatar"
						src="/data/thumbnails/20x20/avatars/<%= userName || '!' %>"
						alt="<%= userName || 'Anonymous user' %>"/>

					<%= userName || 'Anonymous user' %>

					<% if (userName) { %>
						</a>
					<% } %>
				</td>

				<td class="subject">
					<% if (historyEntry.type === 0) { %>
						<a href="#/post/<%= historyEntry.primaryKey %>">
							@<%= historyEntry.primaryKey %>
						</a>
					<% } else if (historyEntry.type === 1) { %>
						<a href="#/tag/<%= historyEntry.data.name %>">
							#<%= historyEntry.data.name %>
						</a>
					<% } else { %>
						?
					<% } %>
				</td>

				<td class="difference">
					<% if (historyEntry.operation == 2) { %>
						deleted
					<% } else { %>
						<% if (historyEntry.operation == 0) { %>
							added
						<% } else { %>
							changed
						<% } %>

						<% if (historyEntry.dataDifference) { %>
							<ul><!--
								--><% showDifference('addition', historyEntry.dataDifference['+']) %><!--
								--><% showDifference('removal', historyEntry.dataDifference['-']) %><!--
							--></ul>
						<% } %>
					<% } %>
				</td>
			</tr>
		<% }) %>
	</tbody>
</table>
