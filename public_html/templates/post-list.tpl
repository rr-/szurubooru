<div class="post-list">
	<form class="search">
		<input type="text" name="query" placeholder="Search query..."/>
		<button type="submit" name="search">Search</button>

		<% if (privileges.canMassTag) { %>
			<button name="mass-tag">Mass tag</button>
		<% } %>
	</form>

	<p class="mass-tag-info">Tagging with <span class="mass-tag"><%= massTag %></span></p>

	<div class="pagination-target">
		<div class="wrapper">
			<ul class="posts">
			</ul>
		</div>
	</div>
</div>
