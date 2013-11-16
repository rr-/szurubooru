function onDomUpdate()
{
	$('li.edit a').click(function(e)
	{
		e.preventDefault();

		var aDom = $(this);
		if (aDom.hasClass('inactive'))
			return;
		aDom.addClass('inactive');

		var tags = [];
		$.getJSON('/tags?json', function(data)
		{
			aDom.removeClass('inactive');
			var formDom = $('form.edit-post');
			tags = data['tags'];

			if (!$(formDom).is(':visible'))
			{
				var tagItOptions = getTagItOptions();
				tagItOptions.availableTags = tags;
				tagItOptions.placeholderText = $('.tags input').attr('placeholder');
				$('.tags input').tagit(tagItOptions);
				formDom.show().css('height', formDom.height()).hide().slideDown();
			}

			formDom.find('input[type=text]:visible:eq(0)').focus();
			$('html, body').animate({ scrollTop: $(formDom).offset().top + 'px' }, 'fast');
		});
	});

	$('.comments.unit a.simple-action').data('callback', function()
	{
		$.get(window.location.href, function(data)
		{
			$('.comments.unit').replaceWith($(data).find('.comments.unit'));
			$('body').trigger('dom-update');
		});
	});

	$('#sidebar a.simple-action').data('callback', function()
	{
		$.get(window.location.href, function(data)
		{
			$('#sidebar').replaceWith($(data).find('#sidebar'));
			$('body').trigger('dom-update');
		});
	});
}

$(function()
{
	$('body').bind('dom-update', onDomUpdate);
	onDomUpdate();

	$('form.edit-post').submit(function(e)
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
					alert(data['message']);
					formDom.find(':input').attr('readonly', false);
					formDom.removeClass('inactive');
				}
			}
		};

		$.ajax(ajaxData);
	});

	$('form.add-comment').submit(function(e)
	{
		e.preventDefault();

		var formDom = $(this);
		if (formDom.hasClass('inactive'))
			return;
		formDom.addClass('inactive');
		formDom.find(':input').attr('readonly', true);

		var url = formDom.attr('action') + '?json';
		var fd = new FormData(formDom[0]);

		var preview = false;
		$.each(formDom.serializeArray(), function(i, x)
		{
			if (x.name == 'sender' && x.value == 'preview')
				preview = true;
		});

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
					if (preview)
					{
						formDom.find('.preview').html(data['textPreview']).show();
					}
					else
					{
						$.get(window.location.href, function(data)
						{
							$('.comments.unit').replaceWith($(data).find('.comments.unit'));
							$('body').trigger('dom-update');
						});
						formDom.find('textarea').val('');
					}
					formDom.find(':input').attr('readonly', false);
					formDom.removeClass('inactive');
				}
				else
				{
					alert(data['message']);
					formDom.find(':input').attr('readonly', false);
					formDom.removeClass('inactive');
				}
			}
		};

		$.ajax(ajaxData);
	});

	Mousetrap.bind('a', function() { var url = $('#sidebar .left a').attr('href'); if (typeof url !== 'undefined') window.location.href = url; }, 'keyup');
	Mousetrap.bind('d', function() { var url = $('#sidebar .right a').attr('href'); if (typeof url !== 'undefined') window.location.href = url; }, 'keyup');
	Mousetrap.bind('e', function() { $('li.edit a').trigger('click'); return false; }, 'keyup');
});
