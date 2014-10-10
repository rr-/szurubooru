<div id="tag-view">
	<div class="header">
		<h1><%= tagName %></h1>
	</div>

	<% if (_.any(privileges)) { %>
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
				<label class="form-label"></label>
				<div class="form-input">
					<button type="submit">Update</button>
				</div>
			</div>
		</form>
	<% } %>

	<div class="post-list">
		<h3>Example usages</h3>

		<ul>
		</ul>

		<a href="#/posts/query=<%= tagName %>">Search for more</a>
	</div>
</div>
