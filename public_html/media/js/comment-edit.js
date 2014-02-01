$(function()
{
	function onDomUpdate()
	{
		$('form.edit-comment textarea, form.add-comment textarea')
			.bind('change keyup', function(e)
			{
				enableExitConfirmation();
			});

		$('form.edit-comment, form.add-comment').submit(function(e)
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
							disableExitConfirmation();

							formDom.find('.preview').hide();
							var cb = function()
							{
								$.get(window.location.href, function(data)
								{
									$('.comments-wrapper').replaceWith($(data).find('.comments-wrapper'));
									$('body').trigger('dom-update');
								});
							}
							if (formDom.hasClass('add-comment'))
							{
								cb();
								formDom.find('textarea').val('');
							}
							else
							{
								formDom.slideUp(function()
								{
									cb();
									$(this).remove();
								});
							}
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

		$('.comment .edit a').click(function(e)
		{
			e.preventDefault();
			var commentDom = $(this).parents('.comment');
			$.get($(this).attr('href'), function(data)
			{
				commentDom.find('form.edit-comment').remove();
				var otherForm = $(data).find('form.edit-comment');
				otherForm.hide();
				commentDom.find('.body').append(otherForm);
				otherForm.slideDown();
				$('body').trigger('dom-update');
			});
		});
	}

	$('body').bind('dom-update', onDomUpdate);
});
