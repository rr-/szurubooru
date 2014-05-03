$(function()
{
	$('.tabs a').click(function(e)
	{
		e.preventDefault();
		var className = $(this).parents('li').attr('class').replace('selected', '').replace(/^\s+|\s+$/, '');
		$('.tabs li').removeClass('selected');
		$(this).parents('li').addClass('selected');
		$('.tab-content').hide();
		$('.tab-content.' + className).show();
	});

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
		handleFiles(e.originalEvent.dataTransfer.files);
		$(this).trigger('dragleave');
	}).on('click', function(e)
	{
		$(':file').show().focus().trigger('click').hide();
	});

	$(':file').change(function(e)
	{
		handleFiles(this.files);
	});



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
		$('#url-handler-wrapper input').val('');
		handleURLs([url]);
	});



	$('.post .move-down-trigger, .post .move-up-trigger').on('click', function()
	{
		if ($('#the-submit').hasClass('inactive'))
			return;
		var dir = $(this).hasClass('move-down-trigger') ? 'd' : 'u';
		var post = $(this).parents('.post');
			if (dir == 'u')
				post.insertBefore(post.prev('.post'));
			else
				post.insertAfter(post.next('.post'));
	});
	$('.post .remove-trigger').on('click', function()
	{
		if ($('#the-submit').hasClass('inactive'))
			return;
		$(this).parents('.post').slideUp(function()
		{
			$(this).remove();
			handleInputs([]);
		});
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
		var fd = new FormData(postDom.find('form').get(0));

		fd.append('file', postDom.data('file'));
		fd.append('url', postDom.data('url'));


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
				postDom.slideUp(function()
				{
					postDom.remove();
					sendNextPost();
				});
			},
			error: function(xhr)
			{
				postDom
					.find('.alert')
					.html(xhr.responseJSON
						? xhr.responseJSON.messageHtml
						: 'Fatal error')
					.slideDown();
				enableUpload();
			}
		};

		$.ajax(ajaxData);
	}

	function uploadFinished()
	{
		disableExitConfirmation();
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
		if (theSubmit.hasClass('inactive'))
			return;
		disableUpload();
		sendNextPost();
	});

	function handleFiles(files)
	{
		handleInputs(files, function(postDom, file)
		{
			postDom.data('url', '');
			postDom.data('file', file);
			$('.file-name strong', postDom).text(file.name);

			if (file.type.match('image.*'))
			{
				var img = postDom.find('img')
				var reader = new FileReader();
				reader.onload = (function(theFile, img)
				{
					return function(e)
					{
						changeThumb(img, e.target.result);
					};
				})(file, img);
				reader.readAsDataURL(file);
			}
		});
	}

	function changeThumb(img, url)
	{
		$(img)
			.css('background-image', 'none')
			.attr('src', url)
			.data('custom-thumb', true);
	}

	function handleURLs(urls)
	{
		handleInputs(urls, function(postDom, url)
		{
			postDom.data('url', url);
			postDom.data('file', '');
			postDom.find('[name=source]').val(url);
			if (matches = url.match(/watch.*?=([a-zA-Z0-9_-]+)/))
			{
				postDom.find('.file-name strong').text(url);
				$.getJSON('http://gdata.youtube.com/feeds/api/videos/' + matches[1] + '?v=2&alt=jsonc', function(data)
				{
					postDom.find('.file-name strong')
						.text(data.data.title);
					changeThumb(postDom.find('img'), data.data.thumbnail.hqDefault);
				});
			}
			else
			{
				postDom.find('.file-name strong').text(url);
				changeThumb(postDom.find('img'), url);
			}
		});
	}

	function handleInputs(inputs, callback)
	{
		for (var i = 0; i < inputs.length; i ++)
		{
			var input = inputs[i];
			var postDom = $('#post-template').clone(true);
			postDom.find('form').submit(false);
			postDom.removeAttr('id');

			$('.posts').append(postDom);

			postDom.show();
			attachTagIt($('.tags input', postDom));

			callback(postDom, input);
		}
		if ($('.posts .post').length == 0)
		{
			disableExitConfirmation();
			$('#upload-step2').fadeOut();
		}
		else
		{
			enableExitConfirmation();
			$('#upload-step2').fadeIn();
		}
	}

	$('.post img').mouseenter(function(e)
	{
		if ($(this).data('custom-thumb') != true)
			return;

		$('#lightbox')
			.attr('src', $(this).attr('src'))
			.show()
			.position({
				of: $(this),
				my: 'center center',
				at: 'center center',
			})
			.show();
	});
	$('.post img').mouseleave(function(e)
	{
		$('#lightbox').hide();
	});
});
