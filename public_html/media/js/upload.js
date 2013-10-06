$(function()
{
	var handler = $('#file-handler');
	var tags = []; //todo: retrieve tags

	$('#upload-step2').hide();

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
	});


	$('#upload-step2 form').submit(function(e)
	{
		var url = $(this).attr('action') + '?json';
		e.preventDefault();
		var posts = $('.post', $(this));

		if (posts.length == 0)
		{
			alert('No posts to upload!');
			return;
		}

		posts.each(function()
		{
			var postDom = $(this);
			var file = postDom.data('file');
			var tags = postDom.find('[name=tags]').val();
			var safety = postDom.find('[name=safety]').val();
			var fd = new FormData();
			fd.append('file', file);
			fd.append('tags', tags);
			fd.append('safety', safety);

			var ajax =
			{
				url: url,
				data: fd,
				processData: false,
				contentType: false,
				type: 'POST',
				success: function(data)
				{
					//todo: do this nice
					if (data['success'])
					{
						postDom.slideUp();
						//alert(file.name + ': success!');
					}
					else
					{
						alert(data['errorMessage']);
					}
				}
			};

			$.ajax(ajax);
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
				postDom.removeAttr('id');
				postDom.data('file', file);
				$('.file-name strong', postDom).text(file.name);
				$('.tags input', postDom).tagit({caseSensitive: true, availableTags: tags, placeholderText: $('.tags input').attr('placeholder')});
				$('.posts').append(postDom);

				if (!file.type.match('image.*'))
				{
					continue;
				}

				var reader = new FileReader();
				reader.onload = (function(theFile)
				{
					return function(e)
					{
						var img = postDom.find('img')
						/*img.css('max-width', img.css('width'));
						img.css('max-height', img.css('height'));
						img.css('width', 'auto');
						img.css('height', 'auto');*/
						img.css('background-image', 'none');
						img.attr('src', e.target.result);
					};
				})(file);
				reader.readAsDataURL(file);
			}
			$('#upload-step2').fadeIn(function()
			{
			});
		});
	}
});
