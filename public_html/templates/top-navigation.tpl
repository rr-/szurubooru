<ul>
	<%
		var links = [];
		links.push({
			target: '#/home',
			title: 'Home',
			icon: 'fa-home'});

		if (canListPosts) {
			links.push({
				target: '#/posts',
				title: 'Posts',
				icon: 'fa-th'});

			if (canUploadPosts) {
				links.push({
					target: '#/upload',
					title: 'Upload',
					icon: 'fa-upload'});
			}
		}

		if (canListComments) {
			links.push({
				target: '#/comments',
				title: 'Comments',
				icon: 'fa-comments'});
		}

		if (canListTags) {
			links.push({
				target: '#/tags',
				title: 'Tags',
				icon: 'fa-tags'});
		}

		if (canListUsers) {
			links.push({
				target: '#/users',
				title: 'Users',
				icon: 'fa-users'});
		}

		if (!loggedIn) {
			links.push({
				target: '#/login',
				title: 'Login',
				icon: 'fa-sign-in'});

			links.push({
				target: '#/register',
				title: 'Register',
				icon: 'fa-file-text-o'});

		} else {
			links.push({
				className: 'my-account',
				target: '#/user/' + user.name,
				title: 'Account',
				icon: 'fa-user'});

			links.push({
				target: '#/logout',
				title: 'Logout',
				icon: 'fa-sign-out'});
		}

		links.push({
			target: '#/help',
			title: 'Help',
			icon: 'fa-question-circle'});

		var takenAccessKeys = [];
		links = _.map(links, function(link) {
			if (typeof(link.className) === 'undefined') {
				link.className = link.title.toLowerCase();
			}
			if (typeof(link.accessKey) === 'undefined') {
				for (var i = 0; i < link.title.length; i ++) {
					var accessKey = link.title.charAt(i);
					if (!_.contains(takenAccessKeys, accessKey)) {
						link.accessKey = accessKey;
						takenAccessKeys.push(accessKey);
						break;
					}
				}
			}
			return link;
		});
	%>

	<% _.each(links, function(link) { %><!--
		--><li class="<%= link.className %>">
			<a class="big-button" href="<%= link.target %>" <%= link.accessKey ? 'accessKey="' + link.accessKey + '"' : '' %>>
				<i class="fa <%= link.icon %>"></i><br/>
				<% var pos = link.title.indexOf(link.accessKey) %>
				<%= pos === -1 ? link.title : link.title.substring(0, pos) + '<span class="accesskey">' + link.accessKey + '</span>' + link.title.substring(pos + 1) %>
			</a>
		</li><!--
	--><% }) %>
</ul>
