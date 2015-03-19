<div id="post-upload-step1">
	<input name="post-content" multiple type="file"/>

	<div class="url-handler">
		<div class="input-wrapper">
			<input type="text" placeholder="Alternatively, paste an URL here." name="url"/>
		</div>
		<button type="submit">Add URL</button>
	</div>

	<div class="clear"></div>
</div>

<div id="post-upload-step2">
	<hr>

	<div class="hybrid-view">
		<div class="hybrid-window">
			<table>
				<thead>
					<tr>
						<th class="checkbox">
							<input id="post-upload-select-all" type="checkbox" name="select-all"/>
							<label for="post-upload-select-all"></label>
						</th>
						<th class="thumbnail"></th>
						<th class="tags">Tags</th>
						<th class="safety">Safety</th>
					</tr>
				</thead>

				<tbody>
				</tbody>

				<tfoot>
					<tr class="template">
						<td class="checkbox">
							<input type="checkbox"/>
							<label></label>
						</td>
						<td class="thumbnail">
							<img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Thumbnail"/>
						</td>
						<td class="tags"></td>
						<td class="safety"><div class="safety-template"></div></td>
					</tr>
				</tfoot>
			</table>

			<ul class="operations"><!--
				--><li>
					<button class="post-table-op remove"><i class="fa fa-remove"></i> Remove</button>
				</li><!--
				--><li>
					<button class="post-table-op move-up"><i class="fa fa-chevron-up"></i> Move up</button>
				</li><!--
				--><li>
					<button class="post-table-op move-down"><i class="fa fa-chevron-down"></i> Move down</button>
				</li><!--
				--><li>
					<button class="upload highlight" type="submit"><i class="fa fa-upload"></i> Submit</button>
				</li><!--
				--><li>
					<button class="stop highlight-red" type="submit"><i class="fa fa-times-circle"></i> Stop</button>
				</li><!--
			--></ul>

		</div>

		<div class="hybrid-window">
			<div class="messages"></div>

			<div class="form-slider">
				<div class="thumbnail">
					<img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Thumbnail"/>
				</div>

				<form class="form-wrapper">
					<div class="form-row file-name">
						<label class="form-label">File:</label>
						<div class="form-input">
							<strong>filename.jpg</strong>
						</div>
					</div>

					<div class="form-row">
						<label class="form-label">Safety:</label>
						<div class="form-input">
							<input type="radio" id="post-safety-safe" name="safety" value="safe"/>
							<label for="post-safety-safe">
								Safe
							</label>

							<input type="radio" id="post-safety-sketchy" name="safety" value="sketchy"/>
							<label for="post-safety-sketchy">
								Sketchy
							</label>

							<input type="radio" id="post-safety-unsafe" name="safety" value="unsafe"/>
							<label for="post-safety-unsafe">
								Unsafe
							</label>
						</div>
					</div>

					<div class="form-row">
						<label class="form-label" for="post-tags">Tags:</label>
						<div class="form-input">
							<input type="text" name="tags" id="post-tags" placeholder="Enter some tags&hellip;" value=""/>
						</div>
					</div>

					<div class="form-row">
						<label class="form-label" for="post-source">Source:</label>
						<div class="form-input">
							<input maxlength="200" type="text" name="source" id="post-source" placeholder="Where did you get this? (optional)" value=""/>
						</div>
					</div>

					<% if (canUploadPostsAnonymously) { %>
						<div class="form-row">
							<label class="form-label" for="post-anonymous">Anonymity:</label>
							<div class="form-input">
								<input type="checkbox" id="post-anonymous" name="anonymous"/>
								<label for="post-anonymous">
									Don't show my name in this post
								</label>
							</div>
						</div>
					<% } %>

				</form>
			</div>
		</div>
	</div>

</div>
