$(function()
{
	$('.add-fav a, .rem-fav a').click(function(e)
	{
		e.preventDefault();
		var url = $(this).attr('href');
		url += '?json';
		$.get(url, function(data)
		{
			if (data['errorMessage'])
			{
				alert(data['errorMessage']);
			}
			else
			{
				window.location.reload();
			}
		});
	});
});
