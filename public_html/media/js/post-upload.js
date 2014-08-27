var localPostId = 0;

function Post()
{
	var post = this;
	this.id = ++localPostId;
	this.url = '';
	this.file = null;
	this.fileName = '';
	this.safety = 1;
	this.source = '';
	this.tags = [];
	this.anonymous = false;
	this.thumbnail = null;
}

function bindFileHandlerEvents()
{
	$('#file-handler').on('dragenter', function(e)
	{
		$(this).addClass('active');
	}).on('dragleave', function(e)
	{
		$(this).removeClass('active');
	}).on('dragover', function(e)
	{
		e.preventDefault();
	}).on('drop', function(e)
	{
		e.preventDefault();
		addFiles(e.originalEvent.dataTransfer.files);
		$(this).trigger('dragleave');
	}).on('click', function(e)
	{
		$(':file').show().focus().trigger('click').hide();
	});

	$(':file').change(function(e)
	{
		addFiles(this.files);
	});
}

function bindUrlHandlerEvents()
{
	$('#url-handler-wrapper input').keydown(function(e)
	{
		if (e.which == 13)
		{
			$('#url-handler-wrapper button').trigger('click');
			e.preventDefault();
		}
	});
	$('#url-handler-wrapper button').click(function(e)
	{
		var url = $('#url-handler-wrapper input').val();
		url = url.replace(/^\s+|\s+$/, '');
		if (url == '')
			return;
		protocol = /^(\w+):\/\//.exec(url)
		if (!protocol)
			url = 'http://' + url;
		else
		{
			protocol = protocol[1].toLowerCase();
			if (protocol != 'http' && protocol != 'https')
			{
				alert('Unsupported protocol: ' + protocol);
				return;
			}
		}
		$('#url-handler-wrapper input').val('');
		addURLs([url]);
	});
}

function bindPostTableOperations()
{
	Mousetrap.bind('a', function()
	{
		var prevPost = $('#posts tbody tr.selected:eq(0)').prev().data('post');
		if (prevPost)
			selectPostTableRow(prevPost);
	}, 'keyup');

	Mousetrap.bind('d', function()
	{
		var nextPost = $('#posts tbody tr.selected:eq(0)').next().data('post');
		if (nextPost)
			selectPostTableRow(nextPost);
	}, 'keyup');

	$('#upload-step2').find('.remove').click(function(e)
	{
		e.preventDefault();
		removePosts(getSelectedPosts());
	});
	$('#upload-step2').find('.move-up').click(function(e)
	{
		e.preventDefault();
		movePostsUp(getSelectedPosts());
	});
	$('#upload-step2').find('.move-down').click(function(e)
	{
		e.preventDefault();
		movePostsDown(getSelectedPosts());
	});
}

function bindPostTableRowLightboxEvents(postTableRow)
{
	var img = $(postTableRow).find('img');
	img.unbind('mouseenter').bind('mouseenter', function(e)
	{
		if (!img.attr('src'))
			return;

		$('#lightbox img').attr('src', $(this).attr('src'));
		$('#lightbox')
			.show()
			.position({
				of: $(this),
				my: 'left+10 center',
				at: 'right center',
			});
	});
	img.bind('mouseleave', function(e)
	{
		$('#lightbox').hide();
	});
}

function bindPostTableRowSelectEvent(tableRow)
{
	tableRow.find('td.checkbox').click(function(e)
	{
		if (e.target.nodeName == 'TD')
		{
			var checkbox = $(this).find('input[type=checkbox]');
			checkbox.prop('checked', !checkbox.prop('checked'));
		}
		postTableCheckboxesChangedEventHandler();
	});
}

function bindSelectAllEvent()
{
	$('#posts thead th.checkbox').click(function(e)
	{
		var checkbox = $(this).find('input[type=checkbox]');
		if (e.target.nodeName == 'TH')
			checkbox.prop('checked', !checkbox.prop('checked'));
		$('#posts tbody input[type=checkbox]').prop('checked', checkbox.prop('checked'));
		postTableCheckboxesChangedEventHandler();
	});
}

function bindPostTagChangeEvents(form, posts)
{
	form.find('[name=tags]').tagit(
	{
		beforeTagAdded: function(e, ui) { addTagToPosts(posts, ui.tagLabel); },
		beforeTagRemoved: function(e, ui) { removeTagFromPosts(posts, ui.tagLabel); }
	});
}

function bindPostAnonymityChangeEvent(form, posts)
{
	form.find('[name=anonymous]').unbind('change').bind('change', function(e)
	{
		setPostsAnonymity(posts, $(e.target).is(':checked'));
	});
}

function bindPostSafetyChangeEvent(form, posts)
{
	form.find('[name=safety]').unbind('change').bind('change', function(e)
	{
		changePostsSafety(posts, $(this).val());
	});
}

function bindPostSourceChangeEvent(form, posts)
{
	form.find('[name=source]').unbind('change').bind('change', function(e)
	{
		changePostsSource(posts, $(this).val());
	});
}

function addFiles(files)
{
	var posts = [];
	$.each(files, function(i, file)
	{
		var post = new Post();
		post.file = file;
		post.fileName = file.name;

		if (file.type.match('image.*'))
		{
			var reader = new FileReader();
			reader.onload = function(e)
			{
				post.thumbnail = e.target.result;
				updateThumbInForm(post);
				updatePostTableRow(post);
			};
			reader.readAsDataURL(file);
		}
		posts.push(post);
	});

	createTableRowsForPosts(posts);
}

function updateThumbInForm(post)
{
	var selectedPosts = getSelectedPosts();
	if (selectedPosts.length == 1 && selectedPosts[0] == post && post.thumbnail != null)
		$('#post-edit-form img')[0].setAttribute('src', post.thumbnail);
}

function addURLs(urls)
{
	var posts = [];
	$.each(urls, function(i, url)
	{
		post = new Post();
		post.url = url;
		post.fileName = url;
		post.source = url;

		if (matches = url.match(/watch.*?=([a-zA-Z0-9_-]+)/))
		{
			var realUrl = 'http://img.youtube.com/vi/' + matches[1] + '/mqdefault.jpg';
			post.thumbnail = realUrl;
		}
		else
		{
			post.thumbnail = '/posts/upload/thumb/' + btoa(url);
		}

		posts.push(post);
	});

	createTableRowsForPosts(posts);
}

function createTableRowsForPosts(posts)
{
	$.each(posts, function(i, post)
	{
		var tableRow = $('#posts .template').clone(true);
		tableRow.removeClass('template');
		tableRow.find('td:not(.checkbox)').click(postTableRowClickEventHandler);
		bindPostTableRowSelectEvent(tableRow);
		bindPostTableRowLightboxEvents(tableRow);
		tableRow.data('post', post);
		tableRow.data('post-id', post.id);
		$('#posts tbody').append(tableRow);
		updatePostTableRow(post);
	});

	selectPostTableRow(posts[0]);
	updateSelectAllState();
	showOrHidePostsTable();
}

function showOrHidePostsTable()
{
	var numberOfPosts = $('#posts tbody tr').length;
	if (numberOfPosts == 0)
	{
		disableExitConfirmation();
		$('#upload-step2').fadeOut();
	}
	else
	{
		enableExitConfirmation();
		$('#upload-step2').fadeIn();
		$('#posts-wrapper').show();
		/*if (numberOfPosts == 1)
		{
			$('#hybrid-view').append($('#the-submit-wrapper'));
			$('#posts-wrapper').hide('slide', {direction: 'left'});
			selectPostTableRow($('#posts tbody tr').eq(0).data('post'));
		}
		else
		{
			$('#posts-wrapper').append($('#the-submit-wrapper'));
			$('#posts-wrapper').show('slide', {direction: 'right'});
		}*/
	}
}

function removePosts(posts)
{
	var postTableRows = getPostTableRows(posts);
	$.each(postTableRows, function(i, postTableRow)
	{
		postTableRow.remove();
	});
	showOrHidePostsTable();
	postTableCheckboxesChangedEventHandler();
}

function movePostsUp(posts)
{
	var postTableRows = getPostTableRows(posts);
	$.each(postTableRows, function(i, postTableRow)
	{
		var postTableRow = $(postTableRow);
		postTableRow.insertBefore(postTableRow.prev('tr:not(.selected)'));
	});
}

function movePostsDown(posts)
{
	var postTableRows = getPostTableRows(posts).reverse();
	$.each(postTableRows, function(i, postTableRow)
	{
		var postTableRow = $(postTableRow);
		postTableRow.insertAfter(postTableRow.next('tr:not(.selected)'));
	});
}

function selectPostTableRow(post)
{
	$('#posts tbody input[type=checkbox]').prop('checked', false);
	$('#posts tbody tr').each(function(i, postTableRow)
	{
		if (post == $(postTableRow).data('post'))
		{
			$(this).find('input[type=checkbox]').prop('checked', true);
			return false;
		}
	});
	postTableCheckboxesChangedEventHandler();
}

function postTableRowClickEventHandler(e)
{
	e.preventDefault();

	var allCheckboxes = $(this).parents('table').find('tbody input[type=checkbox]');
	var myCheckbox = $(this).parents('tr').find('input[type=checkbox]');
	allCheckboxes.prop('checked', false);
	myCheckbox.prop('checked', true);
	postTableCheckboxesChangedEventHandler();
}

function updateSelectAllState()
{
	var numberOfAllPosts = $('#posts tbody tr').length;
	var numberOfSelectedPosts = $('#posts tbody tr.selected').length;
	$('#posts [name=select-all]').prop('checked', numberOfSelectedPosts == numberOfAllPosts);
}

function postTableCheckboxesChangedEventHandler(e)
{
	if ($('#posts').hasClass('disabled'))
	{
		e.preventDefault();
		return;
	}

	$('#posts tbody tr').each(function(i, postRow)
	{
		var checked = $(this).find('input[type=checkbox]').prop('checked');
		$(postRow).toggleClass('selected', checked);
	});

	var allPosts = getAllPendingPosts();
	var selectedPosts = getSelectedPosts();
	updateSelectAllState();

	if (selectedPosts.length == 0)
		hideForm();
	else
		showFormForPosts(selectedPosts);
}

function getPostIds(posts)
{
	var postIds = [];
	for (var i = 0; i < posts.length; i ++)
		postIds.push(posts[i].id);
	return postIds;
}

function getPostTableRows(posts)
{
	var postTableRows = [];
	var postIds = getPostIds(posts);
	$('#posts tbody tr').each(function(i, postTableRow)
	{
		var postId = $(postTableRow).data('post-id');
		if (postIds.indexOf(postId) != -1)
			postTableRows.push(postTableRow);
	});
	return postTableRows;
}

function getAllPendingPosts()
{
	var posts = [];
	$('#posts tbody tr').each(function(i, postTableRow)
	{
		posts.push($(postTableRow).data('post'));
	});
	return posts;
}

function getSelectedPosts()
{
	var posts = [];
	$('#posts tbody tr.selected').each(function(i, postTableRow)
	{
		posts.push($(postTableRow).data('post'));
	});
	return posts;
}

function updatePostTableRow(post)
{
	var safetyDescriptions =
	{
		1: 'safe',
		2: 'sketchy',
		3: 'unsafe'
	};
	var postTableRow = $(getPostTableRows([post])[0]);
	postTableRow.find('.tags').text(post.tags.join(', ') || '-');
	postTableRow.find('.safety div').attr('class', 'safety-' + safetyDescriptions[post.safety]);
	postTableRow.find('img').css('background-image', 'none')
	if (postTableRow.find('img').attr('src') != post.thumbnail && post.thumbnail != null) //huge speedup
		postTableRow.find('img')[0].setAttribute('src', post.thumbnail);
}

function hideForm()
{
	$('#post-edit-form').slideUp(function()
	{
		$('#post-edit-form .thumbnail').hide();
		$('#post-edit-form .source').hide();
	});
}

function showFormForPosts(posts)
{
	var form = $('#post-edit-form');

	form.slideDown();
	if (posts.length != 1)
	{
		form.find('.source').slideUp();
		form.find('.file-name strong').text('Multiple posts selected');
		form.find('.thumbnail').slideUp();
	}
	else
	{
		var post = posts[0];
		form.find('.source').slideDown();
		form.find('[name=source]').val(post.source);
		form.find('.file-name strong').text(post.fileName);
		form.find('.thumbnail').slideDown();
		if (post.thumbnail != null)
		{
			form.find('img').css('background-mage', 'none');
			form.find('img')[0].setAttribute('src', post.thumbnail);
		}
	}

	var commonAnonymity = getCommonPostAnonymity(posts);
	form.find('[name=anonymous]').prop('checked', commonAnonymity);

	var commonSafety = getCommonPostSafety(posts);
	form.find('[name=safety]').prop('checked', false);
	if (commonSafety != 0)
		form.find('[name=safety][value=' + commonSafety + ']').prop('checked', true);

	form.find('.related-tags').slideUp();
	form.find('[name=tags]').tagit(
	{
		beforeTagAdded: function(e, ui) { },
		beforeTagRemoved: function(e, ui) { }
	});
	var commonTags = getCommonPostTags(posts);
	form.find('[name=tags]').tagit('removeAll');
	$.each(commonTags, function(i, tag)
	{
		form.find('[name=tags]').tagit('createTag', tag);
	});

	bindPostSafetyChangeEvent(form, posts);
	bindPostSourceChangeEvent(form, posts);
	bindPostAnonymityChangeEvent(form, posts);
	bindPostTagChangeEvents(form, posts);
}

function getCommonPostAnonymity(posts)
{
	for (var i = 1; i < posts.length; i ++)
		if (posts[i].anonymous != posts[0].anonymous)
			return false;
	return posts[0].anonymous;
}

function getCommonPostSafety(posts)
{
	for (var i = 1; i < posts.length; i ++)
		if (posts[i].safety != posts[0].safety)
			return 0;
	return posts[0].safety;
}

function getCommonPostTags(posts)
{
	var commonTags = posts[0].tags;
	for (var i = 1; i < posts.length; i ++)
	{
		commonTags = commonTags.filter(function(tag)
		{
			return posts[i].tags.indexOf(tag) != -1;
		});
	}
	return commonTags;
}

function changePostsSource(posts, newSource)
{
	var maxLength = $('#post-edit-form input[name=source]').attr('maxlength');
	$.each(posts, function(i, post)
	{
		post.source = maxLength
			? newSource.substring(0, maxLength)
			: newSource;
	});
}

function changePostsSafety(posts, newSafety)
{
	$.each(posts, function(i, post)
	{
		post.safety = newSafety;
		updatePostTableRow(post);
	});
}

function setPostsAnonymity(posts, newAnonymity)
{
	$.each(posts, function(i, post)
	{
		post.anonymous = newAnonymity;
		updatePostTableRow(post);
	});
}

function addTagToPosts(posts, tag)
{
	$.each(posts, function(i, post)
	{
		var index = post.tags.indexOf(tag);
		if (index == -1)
			post.tags.push(tag);
	});
	$.each(posts, function(i, post)
	{
		updatePostTableRow(post);
	});
}

function removeTagFromPosts(posts, tag)
{
	$.each(posts, function(i, post)
	{
		var index = post.tags.indexOf(tag);
		if (index != -1)
			post.tags.splice(index, 1);
	});
	$.each(posts, function(i, post)
	{
		updatePostTableRow(post);
	});
}



function enableOrDisableEditing(enabled)
{
	var theSubmit = $('#the-submit');
	theSubmit.toggleClass('inactive', !enabled);
	var posts = $('#upload-step2 #posts');
	posts.toggleClass('inactive', !enabled);
	$('#post-edit-form input').prop('readonly', !enabled);
}

function enableEditing()
{
	enableOrDisableEditing(true);
}

function disableEditing()
{
	enableOrDisableEditing(false);
}

function uploadFinished()
{
	disableExitConfirmation();
	window.location.href = $('#upload-step2').attr('data-redirect-url');
}

function stopUploadAndShowError(message)
{
	$('#uploading-alert').slideUp();
	$('#upload-error-alert')
		.html(message)
		.slideDown();
	enableEditing();
}

function sendNextPost()
{
	$('#upload-error-alert').slideUp();

	var posts = getAllPendingPosts();
	if (posts.length == 0)
	{
		uploadFinished();
		return;
	}

	var post = posts[0];
	var postTableRow = $('#posts tbody tr:first-child');
	var url = $('#the-submit-wrapper').find('form').attr('action');
	var fd = new FormData();

	fd.append('file', post.file);
	fd.append('url', post.url);
	fd.append('source', post.source);
	fd.append('safety', post.safety);
	fd.append('anonymous', post.anonymous);
	fd.append('tags', post.tags.join(', '));

	if (post.tags.length == 0)
	{
		stopUploadAndShowError('No tags set.');
		return;
	}

	var ajaxData =
	{
		url: url,
		data: fd,
		processData: false,
		contentType: false,
		dataType: 'json',
		success: function(data)
		{
			postTableRow.slideUp(function()
			{
				postTableRow.remove();
				sendNextPost();
			});
		},
		error: function(xhr)
		{
			stopUploadAndShowError(
				xhr.responseJSON
					? xhr.responseJSON.messageHtml
					: 'Fatal error');
		}
	};

	postJSON(ajaxData);
}

$(function()
{
	bindFileHandlerEvents();
	bindUrlHandlerEvents();
	bindSelectAllEvent();
	bindPostTableOperations();
	attachTagIt($('input[name=tags]'));

	$('#the-submit').click(function(e)
	{
		e.preventDefault();
		var theSubmit = $(this);
		if (theSubmit.hasClass('inactive'))
			return;
		disableEditing();

		$('#posts input[type=checkbox]').prop('checked', false);
		postTableCheckboxesChangedEventHandler();
		$('#uploading-alert').slideDown();

		sendNextPost();
	});
});
