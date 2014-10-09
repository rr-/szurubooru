<form class="form-wrapper post-edit">
	<% if (privileges.canChangeSafety) { %>
		<div class="form-row">
			<label class="form-label">Safety:</label>
			<div class="form-input">
				<input type="radio" id="post-safety-safe" name="safety" value="safe" <%= post.safety === 'safe' ? 'checked="checked"' : '' %>/>
				<label for="post-safety-safe">
					Safe
				</label>

				<input type="radio" id="post-safety-sketchy" name="safety" value="sketchy" <%= post.safety === 'sketchy' ? 'checked="checked"' : '' %>/>
				<label for="post-safety-sketchy">
					Sketchy
				</label>

				<input type="radio" id="post-safety-unsafe" name="safety" value="unsafe" <%= post.safety === 'unsafe' ? 'checked="checked"' : '' %>/>
				<label for="post-safety-unsafe">
					Unsafe
				</label>
			</div>
		</div>
	<% } %>

	<% if (privileges.canChangeTags) { %>
		<div class="form-row">
			<label class="form-label" for="post-tags">Tags:</label>
			<div class="form-input">
				<input type="text" name="tags" id="post-tags" placeholder="Enter some tags&hellip;" value="<%= _.pluck(post.tags, 'name').join(' ') %>"/>
			</div>
		</div>
	<% } %>

	<% if (privileges.canChangeSource) { %>
		<div class="form-row">
			<label class="form-label" for="post-source">Source:</label>
			<div class="form-input">
				<input maxlength="200" type="text" name="source" id="post-source" placeholder="Where did you get this? (optional)" value="<%= post.source %>"/>
			</div>
		</div>
	<% } %>

	<% if (privileges.canChangeRelations) { %>
		<div class="form-row">
			<label class="form-label" for="post-relations">Relations:</label>
			<div class="form-input">
				<input maxlength="200" type="text" name="relations" id="post-relations" placeholder="Post ids, separated with space" value="<%= _.pluck(post.relations, 'id').join(' ') %>"/>
			</div>
		</div>
	<% } %>

	<% if (privileges.canChangeFlags && post.contentType === 'video') { %>
		<div class="form-row">
			<label class="form-label">Loop:</label>
			<div class="form-input">
				<input type="checkbox" id="post-loop" name="loop" value="loop" <%= post.flags.loop ? 'checked="checked"' : '' %>/>
				<label for="post-loop">
					Automatically repeat video after playback
				</label>
			</div>
		</div>
	<% } %>

	<% if (privileges.canChangeContent) { %>
		<div class="form-row">
			<label class="form-label" for="post-content">Content:</label>
			<div class="form-input">
				<input type="file" id="post-content" name="content"/>
			</div>
		</div>
	<% } %>

	<% if (privileges.canChangeThumbnail) { %>
		<div class="form-row">
			<label class="form-label" for="post-thumbnail">Thumbnail:</label>
			<div class="form-input">
				<input type="file" id="post-thumbnail" name="thumbnail"/>
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
