<div class='post-merge'>
    <form>
        <ul class='input'>
            <li class='post-mirror'>
                <div class='left-post-container'></div>
                <div class='right-post-container'></div>
            </li>

            <li>
                <p>Tags, relations, scores, favorites and comments will be
                merged. All other properties need to be handled manually.</p>

                <%= ctx.makeCheckbox({required: true, text: 'I confirm that I want to merge these posts.'}) %>
            </li>
        </ul>

        <div class='messages'></div>

        <div class='buttons'>
            <input type='submit' value='Merge posts'/>
        </div>
    </form>
</div>
