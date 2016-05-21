<div class='file-dropper-holder'>
    <input type='file' id='<%= ctx.id %>'/>
    <label class='file-dropper' for='<%= ctx.id %>'>
        <% if (ctx.allowMultiple) { %>
            Drop files here!
        <% } else { %>
            Drop file here!
        <% } %>
        <br/>
        Or just click on this box.
    </label>
</div>
