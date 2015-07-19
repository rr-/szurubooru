<%
var permaLink = '';
permaLink += window.location.origin + '/';
permaLink += window.location.pathname + '/';
permaLink += 'data/posts/' + post.name;
permaLink = permaLink.replace(/([^:])\/+/g, '$1/');
if (forceHttpInPermalinks > 0) {
    permaLink = permaLink.replace('https', 'http');
}
%>

<div id="post-current-search-wrapper">
    <div id="post-current-search">
        <div class="left">
            <a class="enabled">
                <i class="fa fa-chevron-left"></i>
                Next
            </a>
        </div>

        <div class="search">
            <a class="enabled" href="<%= util.appendComplexRouteParam('#/posts', util.simplifySearchQuery({query: query.query, order: query.order})) %>">
                Current search: <%= query.query || '-' %>
            </a>
        </div>

        <div class="right">
            <a class="enabled">
                Previous
                <i class="fa fa-chevron-right"></i>
            </a>
        </div>
    </div>
</div>

<div id="post-view-wrapper">
    <div id="sidebar">
        <ul class="essential">
            <% if (post.contentType !== 'youtube') { %>
                <li>
                    <a class="download" href="<%= permaLink %>">
                        <i class="fa fa-download"></i>
                        <br/>
                        <%= post.contentExtension + ', ' + util.formatFileSize(post.originalFileSize) %>
                    </a>
                </li>
            <% } %>

            <% if (isLoggedIn) { %>
                <li>
                    <% if (hasFav) { %>
                        <a class="delete-favorite" href="#">
                            <i class="fa fa-heart"></i>
                        </a>
                    <% } else { %>
                        <a class="add-favorite" href="#">
                            <i class="fa fa-heart-o"></i>
                        </a>
                    <% } %>
                </li>

                <li>
                    <a class="score-up <% print(ownScore === 1 ? 'active' : '') %>" href="#">
                        <% if (ownScore === 1) { %>
                            <i class="fa fa-thumbs-up"></i>
                        <% } else { %>
                            <i class="fa fa-thumbs-o-up"></i>
                        <% } %>
                    </a>
                </li>

                <li>
                    <a class="score-down <% print(ownScore === -1 ? 'active' : '') %>" href="#">
                        <% if (ownScore === -1) { %>
                            <i class="fa fa-thumbs-down"></i>
                        <% } else { %>
                            <i class="fa fa-thumbs-o-down"></i>
                        <% } %>
                    </a>
                </li>
            <% } %>
        </ul>

        <h1>Tags (<%= _.size(post.tags) %>)</h1>
        <ul class="tags">
            <% _.each(post.tags, function(tag) { %>
                <li class="tag-category-<%= tag.category %>"><!--
                    --><a class="tag-edit" href="#/tag/<%= tag.name %>"><!--
                        --><i class="fa fa-tag"></i><!--
                    --></a><!--

                    --><a class="post-search" href="#/posts/query=<%= tag.name %>"><!--
                        --><span class="tag-name"><%= tag.name %></span><!--
                        --><span class="usages"><%= (tag.usages) %></span>
                    </a>
                </li>
            <% }) %>
        </ul>

        <h1>Details</h1>

        <div class="author-box">
            <% if (post.user.name) { %>
                <a href="#/user/<%= post.user.name %>">
            <% } %>

            <img width="40" height="40" class="author-avatar"
                src="/data/thumbnails/40x40/avatars/<%= post.user.name || '!' %>"
                alt="<%= post.user.name || 'Anonymous user' %>"/>

            <span class="author-name">
                <%= post.user.name || 'Anonymous user' %>
            </span>

            <% if (post.user.name) { %>
                </a>
            <% } %>

            <br/>

            <span class="date" title="<%= util.formatAbsoluteTime(post.uploadTime) %>">
                <%= util.formatRelativeTime(post.uploadTime) %>
            </span>
        </div>

        <ul class="other-info">

            <li>
                Rating:
                <span class="safety-<%= post.safety %>">
                    <%= post.safety %>
                </span>
            </li>

            <% if (post.originalFileSize) { %>
                <li>
                    File size:
                    <%= util.formatFileSize(post.originalFileSize) %>
                </li>
            <% } %>

            <% if (post.contentType == 'image') { %>
                <li>
                    Image size:
                    <%= post.imageWidth + 'x' + post.imageHeight %>
                </li>
            <% } %>

            <% if (post.lastEditTime !== post.uploadTime) { %>
                <li>
                    Edited:
                    <span title="<%= util.formatAbsoluteTime(post.lastEditTime) %>">
                        <%= util.formatRelativeTime(post.lastEditTime) %>
                    </span>
                </li>
            <% } %>

            <% if (post.featureCount > 0) { %>
                <li>
                    Featured: <%= post.featureCount %> <%= post.featureCount < 2 ? 'time' : 'times' %>
                    <small>(<%= util.formatRelativeTime(post.lastFeatureTime) %>)</small>
                </li>
            <% } %>

            <% if (post.source) { %>
                <li><!--
                    --><% var link = post.source.match(/^(\/\/|https?:\/\/)/); %><!--
                    -->Source:&nbsp;<!--
                    --><% if (link) { %><!--
                        --><a href="<%= post.source %>"><!--
                    --><% } %><!--
                        --><%= post.source.trim() %><!--
                    --><% if (link) { %><!--
                        --></a><!--
                    --><% } %><!--
                --></li>
            <% } %>

            <li>
                Score: <%= post.score %>
            </li>
        </ul>

        <% if (_.any(postFavorites)) { %>
            <p>Favorites:</p>

            <ul class="favorites">
                <% _.each(postFavorites, function(user) { %>
                    <li>
                        <a href="#/user/<%= user.name %>">
                            <img class="fav-avatar"
                                src="/data/thumbnails/25x25/avatars/<%= user.name || '!' %>"
                                alt="<%= user.name || 'Anonymous user' %>"/>
                        </a>
                    </li>
                <% }) %>
            </ul>
        <% } %>

        <% if (_.any(post.relations)) { %>
            <h1>Related posts</h1>
            <ul class="related">
                <% _.each(post.relations, function(relatedPost) { %>
                    <li>
                        <a href="#/post/<%= relatedPost.id %>">
                            <%= relatedPost.idMarkdown %>
                        </a>
                    </li>
                <% }) %>
            </ul>
        <% } %>

        <% if (_.any(privileges) || _.any(editPrivileges) || post.contentType === 'image') { %>
            <h1>Options</h1>

            <ul class="operations">
                <% if (_.any(editPrivileges)) { %>
                    <li>
                        <a class="edit" href="#">
                            Edit
                        </a>
                    </li>
                <% } %>

                <% if (privileges.canAddPostNotes) { %>
                    <li>
                        <a class="add-note" href="#">
                            Add new note
                        </a>
                    </li>
                <% } %>

                <% if (privileges.canDeletePosts) { %>
                    <li>
                        <a class="delete" href="#">
                            Delete
                        </a>
                    </li>
                <% } %>

                <% if (privileges.canFeaturePosts) { %>
                    <li>
                        <a class="feature" href="#">
                            Feature
                        </a>
                    </li>
                <% } %>

                <% if (privileges.canViewHistory) { %>
                    <li>
                        <a class="history" href="#">
                            History
                        </a>
                    </li>
                <% } %>

                <% if (post.contentType === 'image') { %>
                    <li>
                        <a href="http://iqdb.org/?url=<%= permaLink %>">
                            Search on IQDB
                        </a>
                    </li>

                    <li>
                        <a href="https://www.google.com/searchbyimage?&image_url=<%= permaLink %>">
                            Search on Google Images
                        </a>
                    </li>
                <% } %>

                <li class="fit-mode">
                    Fit:
                    <a data-fit-mode="fit-width" href="#">width</a>,
                    <a data-fit-mode="fit-height" href="#">height</a>,
                    <a data-fit-mode="original" href="#">original</a>
                </li>
            </ul>
        <% } %>

    </div>

    <div id="post-view">
        <div class="messages"></div>

        <div id="post-edit-target">
        </div>

        <div id="post-content-target">
        </div>

        <% if (privileges.canViewHistory) { %>
            <div class="post-history-wrapper">
                <h1>History</h1>
                <%= historyTemplate({
                    history: postHistory,
                    util: util,
                }) %>
            </div>
        <% } %>

        <div id="post-comments-target">
        </div>
    </div>
</div>
