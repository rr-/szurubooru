<div class='content-wrapper' id='metric-sorter'>
    <h2>Sorting metric "<%- ctx.primaryMetric %>"</h2>
    <form>
        <div class='posts-container'>
            <div class='left-post-container'></div>
            <% if (window.innerWidth <= 1000) { %>
                <div class='messages'></div>
            <% } %>
            <div class='sorting-buttons'>
                <div class='compare-block'>
                    <% if (window.innerWidth <= 1000) { %>
                        <input class='mousetrap save-btn' type='submit' value='Save'>
                    <% } %>
                    <button class='compare left-lt-right'>
                        <i class='fa fa-less-than'></i>
                    </button>
                    <button class='compare left-gt-right'>
                        <i class='fa fa-greater-than'></i>
                    </button>
                    <% if (window.innerWidth <= 1000) { %>
                        <a href class='mousetrap append skip-btn'>Skip</a>
                    <% } %>
                </div>
            </div>
            <div class='right-post-container'></div>
        </div>

        <% if (window.innerWidth > 1000) { %>
            <div class='messages'></div>
        <% } %>

        <div class='buttons'>
            <% if (window.innerWidth > 1000) { %>
                <input class='mousetrap save-btn' type='submit' value='Save'>
                <a href class='mousetrap append skip-btn'>Skip</a>
            <% } %>
        </div>
    </form>
</div>
