<p><strong>Anonymous tokens</strong></p>

<p>Same as <code>tag</code> token.</p>

<p><strong>Named tokens</strong></p>

<table>
    <tbody>
        <tr>
            <td><code>id</code></td>
            <td>having given post number</td>
        </tr>
        <tr>
            <td><code>tag</code></td>
            <td>having given tag (accepts wildcards)</td>
        </tr>
        <tr>
            <td><code>score</code></td>
            <td>having given score</td>
        </tr>
        <tr>
            <td><code>uploader</code></td>
            <td>uploaded by given user (accepts wildcards)</td>
        </tr>
        <tr>
            <td><code>upload</code></td>
            <td>alias of <code>uploader</code></td>
        </tr>
        <tr>
            <td><code>submit</code></td>
            <td>alias of <code>uploader</code></td>
        </tr>
        <tr>
            <td><code>comment</code></td>
            <td>commented by given user (accepts wildcards)</td>
        </tr>
        <tr>
            <td><code>fav</code></td>
            <td>favorited by given user (accepts wildcards)</td>
        </tr>
        <tr>
            <td><code>source</code></td>
            <td>having given source URL (accepts wildcards)</td>
        </tr>
        <tr>
            <td><code>pool</code></td>
            <td>belonging to the pool with the given ID</td>
        </tr>
        <tr>
            <td><code>tag-count</code></td>
            <td>having given number of tags</td>
        </tr>
        <tr>
            <td><code>comment-count</code></td>
            <td>having given number of comments</td>
        </tr>
        <tr>
            <td><code>fav-count</code></td>
            <td>favorited by given number of users</td>
        </tr>
        <tr>
            <td><code>note-count</code></td>
            <td>having given number of annotations</td>
        </tr>
        <tr>
            <td><code>note-text</code></td>
            <td>having given note text (accepts wildcards)</td>
        </tr>
        <tr>
            <td><code>relation-count</code></td>
            <td>having given number of relations</td>
        </tr>
        <tr>
            <td><code>feature-count</code></td>
            <td>having been featured given number of times</td>
        </tr>
        <tr>
            <td><code>type</code></td>
            <td>given type of posts. <code>&lt;value&gt;</code> can be either <code>image</code>, <code>animation</code> (or <code>animated</code> or <code>anim</code>), <code>flash</code> (or <code>swf</code>) or <code>video</code> (or <code>webm</code>).</td>
        </tr>
        <tr>
            <td><code>flag</code></td>
            <td>having given flag. <code>&lt;value&gt;</code> can be either <code>loop</code> or <code>sound</code>.</td>
        </tr>
        <tr>
            <td><code>sha1</code></td>
            <td>having given SHA1 checksum</td>
        </tr>
        <tr>
            <td><code>md5</code></td>
            <td>having given MD5 checksum</td>
        </tr>
        <tr>
            <td><code>content-checksum</code></td>
            <td>alias of <code>sha1</code></td>
        </tr>
        <tr>
            <td><code>file-size</code></td>
            <td>having given file size (in bytes)</td>
        </tr>
        <tr>
            <td><code>image-width</code></td>
            <td>having given image width (where applicable)</td>
        </tr>
        <tr>
            <td><code>image-height</code></td>
            <td>having given image height (where applicable)</td>
        </tr>
        <tr>
            <td><code>image-area</code></td>
            <td>having given number of pixels (image width * image height)</td>
        </tr>
        <tr>
            <td><code>image-aspect-ratio</code></td>
            <td>having given aspect ratio (image width / image height)</td>
        </tr>
        <tr>
            <td><code>image-ar</code></td>
            <td>alias of <code>image-aspect-ratio</code></td>
        </tr>
        <tr>
            <td><code>width</code></td>
            <td>alias of <code>image-width</code></td>
        </tr>
        <tr>
            <td><code>height</code></td>
            <td>alias of <code>image-height</code></td>
        </tr>
        <tr>
            <td><code>area</code></td>
            <td>alias of <code>image-area</code></td>
        </tr>
        <tr>
            <td><code>aspect-ratio</code></td>
            <td>alias of <code>image-aspect-ratio</code></td>
        </tr>
        <tr>
            <td><code>ar</code></td>
            <td>alias of <code>image-aspect-ratio</code></td>
        </tr>
        <tr>
            <td><code>creation-date</code></td>
            <td>posted at given date</td>
        </tr>
        <tr>
            <td><code>creation-time</code></td>
            <td>alias of <code>creation-date</code></td>
        </tr>
        <tr>
            <td><code>date</code></td>
            <td>alias of <code>creation-date</code></td>
        </tr>
        <tr>
            <td><code>time</code></td>
            <td>alias of <code>creation-date</code></td>
        </tr>
        <tr>
            <td><code>last-edit-date</code></td>
            <td>edited at given date</td>
        </tr>
        <tr>
            <td><code>last-edit-time</code></td>
            <td>alias of <code>last-edit-date</code></td>
        </tr>
        <tr>
            <td><code>edit-date</code></td>
            <td>alias of <code>last-edit-date</code></td>
        </tr>
        <tr>
            <td><code>edit-time</code></td>
            <td>alias of <code>last-edit-date</code></td>
        </tr>
        <tr>
            <td><code>comment-date</code></td>
            <td>commented at given date</td>
        </tr>
        <tr>
            <td><code>comment-time</code></td>
            <td>alias of <code>comment-date</code></td>
        </tr>
        <tr>
            <td><code>fav-date</code></td>
            <td>last favorited at given date</td>
        </tr>
        <tr>
            <td><code>fav-time</code></td>
            <td>alias of <code>fav-date</code></td>
        </tr>
        <tr>
            <td><code>feature-date</code></td>
            <td>featured at given date</td>
        </tr>
        <tr>
            <td><code>feature-time</code></td>
            <td>alias of <code>feature-time</code></td>
        </tr>
        <tr>
            <td><code>safety</code></td>
            <td>having given safety</td>
        </tr>
        <tr>
            <td><code>rating</code></td>
            <td>alias of <code>safety</code></td>
        </tr>
    </tbody>
</table>

<p><strong>Sort style tokens</strong></p>

<table>
    <tbody>
        <tr>
            <td><code>random</code></td>
            <td>as random as it can get</td>
        </tr>
        <tr>
            <td><code>id</code></td>
            <td>highest to lowest post number</td>
        </tr>
        <tr>
            <td><code>score</code></td>
            <td>highest scored</td>
        </tr>
        <tr>
            <td><code>tag-count</code></td>
            <td>with most tags</td>
        </tr>
        <tr>
            <td><code>comment-count</code></td>
            <td>most commented first</td>
        </tr>
        <tr>
            <td><code>fav-count</code></td>
            <td>loved by most</td>
        </tr>
        <tr>
            <td><code>note-count</code></td>
            <td>with most annotations</td>
        </tr>
        <tr>
            <td><code>relation-count</code></td>
            <td>with most relations</td>
        </tr>
        <tr>
            <td><code>feature-count</code></td>
            <td>most often featured</td>
        </tr>
        <tr>
            <td><code>file-size</code></td>
            <td>largest files first</td>
        </tr>
        <tr>
            <td><code>image-width</code></td>
            <td>widest images first</td>
        </tr>
        <tr>
            <td><code>image-height</code></td>
            <td>tallest images first</td>
        </tr>
        <tr>
            <td><code>image-area</code></td>
            <td>largest images first</td>
        </tr>
        <tr>
            <td><code>width</code></td>
            <td>alias of <code>image-width</code></td>
        </tr>
        <tr>
            <td><code>height</code></td>
            <td>alias of <code>image-height</code></td>
        </tr>
        <tr>
            <td><code>area</code></td>
            <td>alias of <code>image-area</code></td>
        </tr>
        <tr>
            <td><code>creation-date</code></td>
            <td>newest to oldest (pretty much same as <code>id</code>)</td>
        </tr>
        <tr>
            <td><code>creation-time</code></td>
            <td>alias of <code>creation-date</code></td>
        </tr>
        <tr>
            <td><code>date</code></td>
            <td>alias of <code>creation-date</code></td>
        </tr>
        <tr>
            <td><code>time</code></td>
            <td>alias of <code>creation-date</code></td>
        </tr>
        <tr>
            <td><code>last-edit-date</code></td>
            <td>like <code>creation-date</code>, only looks at last edit time</td>
        </tr>
        <tr>
            <td><code>last-edit-time</code></td>
            <td>alias of <code>last-edit-date</code></td>
        </tr>
        <tr>
            <td><code>edit-date</code></td>
            <td>alias of <code>last-edit-date</code></td>
        </tr>
        <tr>
            <td><code>edit-time</code></td>
            <td>alias of <code>last-edit-date</code></td>
        </tr>
        <tr>
            <td><code>comment-date</code></td>
            <td>recently commented by anyone</td>
        </tr>
        <tr>
            <td><code>comment-time</code></td>
            <td>alias of <code>comment-date</code></td>
        </tr>
        <tr>
            <td><code>fav-date</code></td>
            <td>recently added to favorites by anyone</td>
        </tr>
        <tr>
            <td><code>fav-time</code></td>
            <td>alias of <code>fav-date</code></td>
        </tr>
        <tr>
            <td><code>feature-date</code></td>
            <td>recently featured</td>
        </tr>
        <tr>
            <td><code>feature-time</code></td>
            <td>alias of <code>feature-time</code></td>
        </tr>
        <tr>
            <td><code>pool</code></td>
            <td>pool order, requires pool named token</code></td>
        </tr>
    </tbody>
</table>

<p><strong>Special tokens</strong></p>

<table>
    <tbody>
        <tr>
            <td><code>liked</code></td>
            <td>posts liked by currently logged in user</td>
        </tr>
        <tr>
            <td><code>disliked</code></td>
            <td>posts disliked by currently logged in user</td>
        </tr>
        <tr>
            <td><code>fav</code></td>
            <td>posts added to favorites by currently logged in user</td>
        </tr>
        <tr>
            <td><code>tumbleweed</code></td>
            <td>posts with score of 0, without comments and without favorites</td>
        </tr>
    </tbody>
</table>
