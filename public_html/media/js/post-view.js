$(function()
{
	function onDomUpdate()
	{
		$('#sidebar a.edit-post').bindOnce('edit-post', 'click', function(e)
		{
			e.preventDefault();

			var aDom = $(this);
			if (aDom.hasClass('inactive'))
				return;
			aDom.addClass('inactive');

			var formDom = $('form.edit-post');
			if (formDom.find('.tagit').length == 0)
			{
				attachTagIt($('.tags input'));
				aDom.removeClass('inactive');

				formDom.find('input[type=text]:visible:eq(0)').focus();
				formDom.find('textarea, input').bind('change keyup', function()
				{
					if (formDom.serialize() != formDom.data('original-data'))
						enableExitConfirmation();
				});
			}
			else
				aDom.removeClass('inactive');

			var editUnit =  formDom.parents('.unit');
			var postUnit = $('.post-wrapper');
			if (!$(formDom).is(':visible'))
			{
				formDom.data('original-data', formDom.serialize());

				editUnit.show();
				var editUnitHeight = formDom.height();
				editUnit.css('height', editUnitHeight);
				editUnit.hide();

				if (postUnit.height() < editUnitHeight)
					postUnit.animate({height: editUnitHeight + 'px'}, 'fast');

				editUnit.slideDown('fast', function()
					{
						$(this).css('height', 'auto');
					});
			}
			else
			{
				editUnit.slideUp('fast');

				var postUnitOldHeight = postUnit.height();
				postUnit.height('auto');
				var postUnitHeight = postUnit.height();
				postUnit.height(postUnitOldHeight);
				if (postUnitHeight != postUnitOldHeight)
					postUnit.animate({height: postUnitHeight + 'px'});

				if ($('.post-wrapper').height() < editUnitHeight)
					$('.post-wrapper').animate({height: editUnitHeight + 'px'});
				return;
			}

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
