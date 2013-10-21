$(function()
{
	var tags = [];
	$.getJSON('/tags?json', function(data)
	{
		tags = data['tags'];
	});

	var handler = $('#file-handler');
	handler.on('dragenter', function(e)
	{
		$(this).addClass('active');
	});

	handler.on('dragleave', function(e)
	{
		$(this).removeClass('active');
	});

	handler.on('dragover', function(e)
	{
		e.preventDefault();
	});

	handler.on('drop', function(e)
	{
		e.preventDefault();
		handleFiles(e.originalEvent.dataTransfer.files);
		$(this).trigger('dragleave');
	});

	handler.on('click', function(e)
	{
		$(':file').show().focus().trigger('click').hide();
	});

	$(':file').change(function(e)
	{
		handleFiles(this.files);
	});

	$('.post .move-down-trigger, .post .move-up-trigger').on('click', function()
	{
		var dir = $(this).hasClass('move-down-trigger') ? 'd' : 'u';
		var post = $(this).parents('.post');
			if (dir == 'u')
				post.insertBefore(post.prev('.post'));
			else
				post.insertAfter(post.next('.post'));
	});
	$('.post .remove-trigger').on('click', function()
	{
		$(this).parents('.post').slideUp(function()
		{
			$(this).remove();
		});
		if ($('#upload-step2 .post').length == 1)
		{
			$('#upload-step2').slideUp();
			$('#upload-no-posts').slideDown();
		}
	});


	function sendNextPost()
	{
		var posts = $('#upload-step2 .post');
		if (posts.length == 0)
		{
			uploadFinished();
			return;
		}

		var postDom = posts.first();
		var url = postDom.find('form').attr('action') + '?json';
		var file = postDom.data('file');
		var tags = postDom.find('[name=tags]').val();
		var safety = postDom.find('[name=safety]:checked').val();
		var source = postDom.find('[name=source]').val();
		var fd = new FormData();
		fd.append('file', file);
		fd.append('tags', tags);
		fd.append('safety', safety);
		fd.append('source', source);

		var ajaxData =
		{
			url: url,
			data: fd,
			processData: false,
			contentType: false,
			dataType: 'json',
			type: 'POST',
			success: function(data)
			{
				if (data['success'])
				{
					postDom.slideUp(function()
					{
						postDom.remove();
						sendNextPost();
					});
				}
				else
				{
					postDom.find('.alert').html(data['errorHtml']).slideDown();
					enableUpload();
				}
			}
		};

		$.ajax(ajaxData);
	}

	function uploadFinished()
	{
		window.location.href = $('#upload-step2').attr('data-redirect-url');
	}

	function disableUpload()
	{
		var theSubmit = $('#the-submit');
		theSubmit.addClass('inactive');
		var posts = $('#upload-step2 .post');
		posts.find(':input').attr('readonly', true);
		posts.addClass('inactive');
	}

	function enableUpload()
	{
		var theSubmit = $('#the-submit');
		theSubmit.removeClass('inactive');
		var posts = $('#upload-step2 .post');
		posts.removeClass('inactive');
		posts.find(':input').attr('readonly', false);
	}

	$('#the-submit').click(function(e)
	{
		e.preventDefault();
		var theSubmit = $(this);
		disableUpload();
		sendNextPost();
	});

	function handleFiles(files)
	{
		$('#upload-step1').fadeOut(function()
		{
			for (var i = 0; i < files.length; i ++)
			{
				var file = files[i];
				var postDom = $('#post-template').clone(true);
				postDom.find('form').submit(false);
				postDom.removeAttr('id');
				postDom.data('file', file);
				$('.file-name strong', postDom).text(file.name);
				$('.posts').append(postDom);

				postDom.show();
				var tagItOptions = getTagItOptions();
				tagItOptions.availableTags = tags;
				tagItOptions.placeholderText = $('.tags input').attr('placeholder');
				$('.tags input', postDom).tagit(tagItOptions);

				if (!file.type.match('image.*'))
				{
					continue;
				}

				var img = postDom.find('img')
				var reader = new FileReader();
				reader.onload = (function(theFile, img)
				{
					return function(e)
					{
						/*img.css('max-width', img.css('width'));
						img.css('max-height', img.css('height'));
						img.css('width', 'auto');
						img.css('height', 'auto');*/
						img.css('background-image', 'none');
						img.attr('src', e.target.result);
					};
				})(file, img);
				reader.readAsDataURL(file);
			}
			$('#upload-step2').fadeIn(function()
			{
			});
		});
	}
});
