<div class='tabs'>
    <form>
        <div class='tabs-wrapper'><!--
            --><div class='preview tab'><!--
                --><div class='comment-content-wrapper'><!--
                    --><div class='comment-content'><!--
                        --><%= ctx.makeMarkdown(ctx.comment.text) %><!--
                    --></div><!--
                --></div><!--
            --></div><!--

            --><div class='edit tab'><!--
                --><textarea required minlength=1><%- ctx.comment.text %></textarea><!--
            --></div><!--
        --></div>

        <nav class='buttons'>
            <ul>
                <li class='preview'><a href>Preview</a></li>
                <li class='edit'><a href>Edit</a></li>
            </ul>
        </nav>

        <nav class='actions'>
            <input type='submit' class='save' value='Save'/>
            <input type='button' class='cancel discourage' value='Cancel'/>
        </nav>
    </form>

    <div class='messages'></div>
</div>
