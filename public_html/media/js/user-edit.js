$(function()
{
	$('.avatar-content').parents('.form-row').hide();
	$('.avatar-style').click(function()
	{
		if ($(this).val() == '2'/*custom*/)
		{
			$('.avatar-content').parents('.form-row').show();
		}
	});
});
