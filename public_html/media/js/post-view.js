$(function()
{
	function onDomUpdate()
	{
		$('#sidebar a.edit-post').click(function(e)
		{
			e.preventDefault();

			var aDom = $(this);
			if (aDom.hasClass('inactive'))
				return;
			aDom.addClass('inactive');

			var formDom = $('form.edit-post');
			formDom.data('original-data', formDom.serialize());
			if (formDom.find('.tagit').length == 0)
			{
				$.getJSON('/tags?json', {filter: 'order:popularity,desc'}, function(data)
				{
					aDom.removeClass('inactive');
					var tags = data['tags'];

					var tagItOptions = getTagItOptions();
					tagItOptions.availableTags = tags;
					tagItOptions.placeholderText = $('.tags input').attr('placeholder');
					$('.tags input').tagit(tagItOptions);

					formDom.find('input[type=text]:visible:eq(0)').focus();
					formDom.find('textarea, input').bind('change keyup', function()
					{
						if (formDom.serialize() != formDom.data('original-data'))
							enableExitConfirmation();
					});
				});
			}
			else
				aDom.removeClass('inactive');

			if (!$(formDom).is(':visible'))
			{
				formDom.parents('.unit')
					.show().css('height', formDom.height()).hide()
					.slideDown(function()
					{
						$(this).css('height', 'auto');
					});
			}
			$('html, body').animate({ scrollTop: $(formDom).offset().top + 'px' }, 'fast');
			formDom.find('input[type=text]:visible:eq(0)').focus();
		});

		$('.comments.unit a.simple-action').data('callback', function()
		{
			$.get(window.location.href, function(data)
			{
				$('.comments-wrapper').replaceWith($(data).find('.comments-wrapper'));
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

	$('body').bind('dom-update', onDomUpdate);

	$('form.edit-post').submit(function(e)
	{
		e.preventDefault();
		rememberLastSearchQuery();

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
					disableExitConfirmation();

					$.get(window.location.href, function(data)
					{
						$('#sidebar').replaceWith($(data).find('#sidebar'));
						$('#edit-token').replaceWith($(data).find('#edit-token'));
						$('body').trigger('dom-update');
					});
					formDom.parents('.unit').hide();
				}
				else
				{
					alert(data['message']);
				}
				formDom.find(':input').attr('readonly', false);
				formDom.removeClass('inactive');
			},
			error: function()
			{
				alert('Fatal error');
				formDom.find(':input').attr('readonly', false);
				formDom.removeClass('inactive');
			}
		};

		$.ajax(ajaxData);
	});

	Mousetrap.bind('a', function() { var a = $('#sidebar .left a'); var url = a.attr('href'); if (typeof url !== 'undefined') { a.click(); window.location.href = url; } }, 'keyup');
	Mousetrap.bind('d', function() { var a = $('#sidebar .right a'); var url = a.attr('href'); if (typeof url !== 'undefined') { a.click(); window.location.href = url; } }, 'keyup');
	Mousetrap.bind('e', function() { $('a.edit-post').trigger('click'); return false; }, 'keyup');
});
