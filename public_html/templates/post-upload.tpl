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
							<input type="checkbox" name="select-all"/>
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
					<button class="remove"><i class="fa fa-remove"></i> Remove</button>
				</li><!--
				--><li>
					<button class="move-up"><i class="fa fa-chevron-up"></i> Move up</button>
				</li><!--
				--><li>
					<button class="move-down"><i class="fa fa-chevron-down"></i> Move down</button>
				</li><!--
				--><li class="right">
					<button class="submit highlight" type="submit"><i class="fa fa-upload"></i> Submit</button>
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
							<label for="post-safety-safe">
								<input type="radio" id="post-safety-safe" name="safety" value="safe"/>
								Safe
							</label>

							<label for="post-safety-sketchy">
								<input type="radio" id="post-safety-sketchy" name="safety" value="sketchy"/>
								Sketchy
							</label>

							<label for="post-safety-unsafe">
								<input type="radio" id="post-safety-unsafe" name="safety" value="unsafe"/>
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

					<div class="form-row">
						<label class="form-label" for="post-anonymous">Anonymity:</label>
						<div class="form-input">
							<label for="post-anonymous">
								<input type="checkbox" id="post-anonymous" name="anonymous"/>
								Don't show my name in this post
							</label>
						</div>
					</div>

				</form>
			</div>
		</div>
	</div>

	<div id="lightbox">
		<img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Preview">
	</div>

</div>
