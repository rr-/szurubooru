<div id='post-upload'>
    <form>
        <div class='dropper-container'></div>

        <div class='control-strip'>
            <input type='submit' value='Upload all' class='submit'/>

            <div class='control-options'>
                <span class='skip-duplicates'>
                    <%= ctx.makeCheckbox({
                        text: 'Skip duplicate',
                        name: 'skip-duplicates',
                        checked: false,
                    }) %>
                </span>

                <span class='always-upload-similar'>
                    <%= ctx.makeCheckbox({
                        text: 'Force upload similar',
                        name: 'always-upload-similar',
                        checked: false,
                    }) %>
                </span>

                <span class='pause-remain-on-error'>
                    <%= ctx.makeCheckbox({
                        text: 'Pause on error',
                        name: 'pause-remain-on-error',
                        checked: true,
                    }) %>
                </span>

                <span class='upload-all-anonymous'>
                    <%= ctx.makeCheckbox({
                        text: 'Upload anonymously',
                        name: 'upload-all-anonymous',
                        checked: false,
                    }) %>
                </span>
            </div>

            <%= ctx.makeTextInput({placeholder: 'Common tags', id: 'common-tags', name: 'common-tags'}) %>

            <input type='button' value='Cancel' class='cancel'/>
        </div>

        <div class='messages'></div>

        <ul class='uploadables-container'></ul>
    </form>
</div>
