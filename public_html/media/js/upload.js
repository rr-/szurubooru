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


	$('#the-submit').click(function(e)
	{
		e.preventDefault();
		var theSubmit = $(this);
		theSubmit.addClass('inactive');
		var posts = $('#upload-step2 .post');

		if (posts.length == 0)
		{
			//shouldn't happen
			alert('No posts to upload!');
			return;
		}

		var ajaxCalls = [];
		posts.each(function()
		{
			var postDom = $(this);
			var url = postDom.find('form').attr('action') + '?json';
			var file = postDom.data('file');
			var tags = postDom.find('[name=tags]').val();
			var safety = postDom.find('[name=safety]:checked').val();
			var fd = new FormData();
			fd.append('file', file);
			fd.append('tags', tags);
			fd.append('safety', safety);

			postDom.find(':input').attr('readonly', true);
			postDom.addClass('inactive');

			var ajaxData =
			{
				url: url,
				data: fd,
				processData: false,
				contentType: false,
				type: 'POST',
				context: postDom
			};

			var defer = $.ajax(ajaxData)
				.then(function(data)
				{
					data.postDom = $(this);
					return data;
				}).promise();

			ajaxCalls.push(defer);
		});


		$.when.all(ajaxCalls).then(function(allData)
		{
			var errors = false;
			for (var i in allData)
			{
				var data = allData[i];
				var postDom = data.postDom;
				if (data['success'])
				{
					postDom.slideUp(function()
					{
						$(this).remove();
					});
				}
				else
				{
					postDom.removeClass('inactive');
					postDom.find(':input').attr('readonly', false);
					postDom.find('.alert').html(data['errorHtml']).slideDown();
					errors = true;
				}
			}

			if (errors)
			{
				theSubmit.removeClass('inactive');
			}
			else
			{
				window.location.href = $('#upload-step2').attr('data-redirect-url');
			}
		});
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
				var tagItOptions =
				{
					caseSensitive: true,
					availableTags: tags,
					placeholderText: $('.tags input').attr('placeholder')
				};
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
