var App = App || {};
App.Controls = App.Controls || {};

App.Controls.FileDropper = function(
	$fileInput,
	onChange,
	jQuery) {

	var $dropDiv = jQuery('<div class="file-handler"></div>');
	var allowMultiple = $fileInput.attr('multiple');
	$dropDiv.html((allowMultiple ? 'Drop files here!' : 'Drop file here!') + '<br/>Or just click on this box.');
	$dropDiv.insertBefore($fileInput);
	$fileInput.attr('multiple', allowMultiple);
	$fileInput.hide();

	$fileInput.change(function(e) {
		addFiles(this.files);
	});

	$dropDiv.on('dragenter', function(e) {
		$dropDiv.addClass('active');
	}).on('dragleave', function(e) {
		$dropDiv.removeClass('active');
	}).on('dragover', function(e) {
		e.preventDefault();
	}).on('drop', function(e) {
		e.preventDefault();
		addFiles(e.originalEvent.dataTransfer.files);
	}).on('click', function(e) {
		$fileInput.show().focus().trigger('click').hide();
		$dropDiv.addClass('active');
	});

	function addFiles(files) {
		$dropDiv.removeClass('active');
		if (!allowMultiple && files.length > 1) {
			window.alert('Cannot select multiple files.');
			return;
		}
		onChange(files);
	}

};

App.DI.register('fileDropper', App.Controls.FileDropper);
