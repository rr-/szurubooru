function bindToggleTag()
{
	$('.post a.toggle-tag').bindOnce('toggle-tag', 'click', toggleTagEventHandler);
}

function toggleTagEventHandler(e)
{
	e.preventDefault();

	var aDom = $(this);
	if (aDom.hasClass('inactive'))
		return;
	aDom.addClass('inactive');

	var enable = !aDom.parents('.post').hasClass('tagged');
	var url = $(this).attr('href');
	url = url.replace(/\/[01]\/?$/, '/' + (enable ? '1' : '0'));
	postJSON({ url: url }).success(function(data)
	{
		aDom.removeClass('inactive');
		aDom.parents('.post').removeClass('tagged');
		if (enable)
			aDom.parents('.post').addClass('tagged');
		aDom.text(enable
			? aDom.attr('data-text-tagged')
			: aDom.attr('data-text-untagged'));
	}).error(function(xhr)
	{
		alert(xhr.responseJSON
			? xhr.responseJSON.message
			: 'Fatal error');
		aDom.removeClass('inactive');
	});
}

function alignPosts()
{
	var samplePost = $('.posts .post:last-child');
	var container = $('.posts');
	samplePost.find('.thumb').css('width', thumbnailWidth + 'px');
	var containerWidth = container.width();
	var thumbnailOuterWidth = samplePost.outerWidth(true);
	var thumbnailInnerWidth = samplePost.find('.thumb').outerWidth();
	var margin = thumbnailOuterWidth - thumbnailInnerWidth;
	var numberOfThumbnailsToFitInRow = Math.ceil(containerWidth / thumbnailOuterWidth);
	var newThumbnailWidth = Math.floor(containerWidth / numberOfThumbnailsToFitInRow) - margin;
	container.find('.thumb').css('width', newThumbnailWidth + 'px');
}

$(function()
{
	$('body').bind('dom-update', function()
	{
		bindToggleTag();
		alignPosts();
	});
});
