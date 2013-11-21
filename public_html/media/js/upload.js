$(function()
{
	$('.tabs nav a').click(function(e)
	{
		e.preventDefault();
		var className = $(this).parents('li').attr('class').replace('selected', '').replace(/^\s+|\s+$/, '');
		$('.tabs nav li').removeClass('selected');
		$(this).parents('li').addClass('selected');
		$('.tab').hide();
		$('.tab.' + className).show();
	});

	var tags = [];
	$.getJSON('/tags?json', {filter: 'order:popularity,desc'}, function(data)
	{
		tags = data['tags'];
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



	$('#url-handler-wrapper button').click(function(e)
	{
		var urls = [];
		$.each($('#url-handler-wrapper textarea').val().split(/\s+/), function(i, url)
		{
			url = url.replace(/^\s+|\s+$/, '');
			if (url == '')
				return;
			urls.push(url);
		});
		$('#url-handler-wrapper textarea').val('');
		handleURLs(urls);
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
		console.log(postDom.find('form').get(0));
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
					postDom.find('.alert').html(data['messageHtml']).slideDown();
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
		handleInputs(files, function(postDom, file)
		{
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
						img.css('background-image', 'none');
						img.attr('src', e.target.result);
					};
				})(file, img);
				reader.readAsDataURL(file);
			}
		});
	}

	function handleURLs(urls)
	{
		handleInputs(urls, function(postDom, url)
		{
			postDom.data('url', url);
			postDom.find('[name=source]').val(url);
			if (matches = url.match(/watch.*?=([a-zA-Z0-9_-]+)/))
			{
				postDom.find('.file-name strong').text(url);
				$.getJSON('http://gdata.youtube.com/feeds/api/videos/' + matches[1] + '?v=2&alt=jsonc', function(data)
				{
					postDom.find('.file-name strong')
						.text(data.data.title);
					postDom.find('img')
						.css('background-image', 'none')
						.attr('src', data.data.thumbnail.hqDefault);
				});
			}
			else
			{
				postDom.find('.file-name strong')
					.text(url);
				postDom.find('img')
					.css('background-image', 'none')
					.attr('src', url);
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
			var tagItOptions = getTagItOptions();
			tagItOptions.availableTags = tags;
			tagItOptions.placeholderText = $('.tags input').attr('placeholder');
			$('.tags input', postDom).tagit(tagItOptions);

			callback(postDom, input);
		}
		if ($('.posts .post').length == 0)
			$('#upload-step2').fadeOut();
		else
			$('#upload-step2').fadeIn();
	}
});
