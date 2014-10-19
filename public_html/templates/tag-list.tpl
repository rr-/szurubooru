<div id="tag-list-wrapper">
	<div id="tag-list">
		<div class="search">
			<form>
				<input type="text" name="query" placeholder="Search tags..."/>
				<button type="submit" name="search">Search</button>
			</form>

			<ul class="order">
				<li>
					<a class="big-button" href="#/tags/order=name,asc">Tags</a>
				</li>
				<li>
					<a class="big-button" href="#/tags/order=creation_time,desc">Recent</a>
				</li>
				<li>
					<a class="big-button" href="#/tags/order=usage_count,desc">Popular</a>
				</li>
			</ul>
		</div>

		<div class="pagination-target">
			<table class="tags">
				<thead>
					<th class="name">Tag name</th>
					<th class="implications">Implications</th>
					<th class="suggestions">Suggestions</th>
					<th class="usages">Usages</th>
					<th class="banned">Usable?</th>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>
	</div>
</div>
