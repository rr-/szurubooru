<div class='content-wrapper banned-posts'>
    <form>
        <h1>Banned posts</h1>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class='checksum'>Checksum</th>
                        <th class='time'>Time of ban</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>

        <div class='messages'></div>

        <% if (ctx.canDelete) { %>
            <div class='buttons'>
                <input type='submit' class='save' value='Save changes'>
            </div>
        <% } %>
    </form>
</div>
