$(function()
{
	$('.add-fav a, .rem-fav a, .hide a, .unhide a').click(function(e)
	{
		e.preventDefault();

		var aDom = $(this);
		if (aDom.hasClass('inactive'))
			return;
		aDom.addClass('inactive');

		var url = $(this).attr('href') + '?json';
		$.get(url, function(data)
		{
			if (data['success'])
			{
				window.location.reload();
			}
			else
			{
				alert(data['errorMessage']);
				aDom.removeClass('inactive');
			}
		});
	});

	$('.delete a').click(function(e)
	{
		e.preventDefault();

		var aDom = $(this);
		if (aDom.hasClass('inactive'))
			return;
		aDom.addClass('inactive');

		//todo: move this string literal to html
		if (confirm(aDom.attr('data-confirm-text')))
		{
			var url = $(this).attr('href') + '?json';
			$.get(url, function(data)
			{
				if (data['success'])
				{
					window.location.href = aDom.attr('data-redirect-url');
				}
				else
				{
					alert(data['errorMessage']);
					aDom.removeClass('inactive');
				}
			});
		}
		else
		{
			aDom.removeClass('inactive');
		}
	});

	$('li.edit a').click(function(e)
	{
		var aDom = $(this);
		if (aDom.hasClass('inactive'))
			return;
		aDom.addClass('inactive');

		var tags = [];
		$.getJSON('/tags?json', function(data)
		{
			tags = data['tags'];

			var tagItOptions =
			{
				caseSensitive: true,
				availableTags: tags,
				placeholderText: $('.tags input').attr('placeholder')
			};
			$('.tags input').tagit(tagItOptions);

			e.preventDefault();
			$('form.edit').slideDown();
		});
	});

	$('form.edit').submit(function(e)
	{
		e.preventDefault();

		var formDom = $(this);
		if (formDom.hasClass('inactive'))
			return;
		formDom.addClass('inactive');
		formDom.find(':input').attr('readonly', true);

		var url = formDom.attr('action') + '?json';
		var fd = new FormData(formDom[0]);

		var ajaxData =
		{
			url: url,
			data: fd,
			processData: false,
			contentType: false,
			type: 'POST',

			success: function(data)
			{
				if (data['success'])
				{
					window.location.reload();
				}
				else
				{
					alert(data['errorMessage']);
					formDom.find(':input').attr('readonly', false);
					formDom.removeClass('inactive');
				}
			}
		};

		$.ajax(ajaxData);
	});
});
