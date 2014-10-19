<div id="tag-view">
	<div class="header">
		<h1><%= tagName %></h1>
	</div>

	<form class="edit">
		<% if (privileges.canChangeName) { %>
			<div class="form-row">
				<label class="form-label" for="tag-name">Name:</label>
				<div class="form-input">
					<input maxlength="200" type="text" name="name" id="tag-name" placeholder="New tag name" value="<%= tag.name %>"/>
				</div>
			</div>
		<% } %>

		<div class="form-row">
			<label class="form-label" for="tag-implications">Implications:</label>
			<div class="form-input">
				<% if (privileges.canChangeImplications) { %>
					<input maxlength="200" type="text" name="implications" id="tag-implications" placeholder="some tag&hellip;" value="<%= _.pluck(tag.implications, 'name').join(' ') %>"/>
					<p><small>Added automatically when tagging with <strong><%= tagName %></strong>.</small></p>
				<% } else { %>
					<%= _.pluck(tag.implications, 'name').join(' ') || '-' %></p>
				<% } %>
			</div>
		</div>

		<div class="form-row">
			<label class="form-label" for="tag-suggestions">Suggestions:</label>
			<div class="form-input">
				<% if (privileges.canChangeSuggestions) { %>
					<input maxlength="200" type="text" name="suggestions" id="tag-suggestions" placeholder="some tag&hellip;" value="<%= _.pluck(tag.suggestions, 'name').join(' ') %>"/>
					<p><small>Suggested when tagging with <strong><%= tagName %></strong>.</small></p>
				<% } else { %>
					<%= _.pluck(tag.suggestions, 'name').join(' ') || '-' %>
				<% } %>
			</div>
		</div>

		<% if (privileges.canChangeCategory) { %>
			<div class="form-row">
				<label class="form-label" for="tag-category">Category:</label>
				<div class="form-input">
					<% _.each(_.extend({'default': 'default'}, _.object(tagCategories, tagCategories)), function(v, k) { %>
						<input name="category" type="radio" value="<%= k %>" id="category-<%= k %>" <% print(tag.category === k ? 'checked="checked"' : '') %>>
						<label for="category-<%= k %>">
							<% print(tag.category === k ? v + ' (current)' : v) %>
						</label>
						<br/>
					<% }) %>
				</div>
			</div>
		<% } %>

		<div class="form-row">
			<label class="form-label" for="tag-ban">Ban:</label>
			<div class="form-input">
				<% if (privileges.canBan) { %>
					<input name="ban" type="checkbox" id="ban" <% print(tag.banned ? 'checked="checked"' : '') %>>
					<label for="ban">
						Prevent tag from being used
					</label>
				<% } else { %>
					<%= tag.banned ? 'This is banned and cannot cannot be used in posts.' : 'This tag is not banned and can be used in posts.' %>
				<% } %>
			</div>
		</div>

		<% if (_.any(privileges)) { %>
			<div class="form-row">
				<label class="form-label"></label>
				<div class="form-input">
					<button type="submit">Update</button>
				</div>
			</div>
		<% } %>
	</form>

	<div class="post-list">
		<h3>Example usages</h3>

		<ul>
		</ul>

		<a href="#/posts/query=<%= tagName %>">Search for more</a>
	</div>
</div>
