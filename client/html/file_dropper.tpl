<div class='file-dropper-holder'>
    <input type='file' id='<%= id %>'/>
    <label class='file-dropper' for='<%= id %>'>
        <% if (allowMultiple) { %>
            Drop files here!
        <% } else { %>
            Drop file here!
        <% } %>
        <br/>
        Or just click on this box.
    </label>
</div>
