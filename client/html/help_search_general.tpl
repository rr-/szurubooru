<p>Search queries are built of tokens that are separated by spaces. Each token
can be of following form:</p>

<table>
    <thead>
        <tr>
            <th>Syntax</th>
            <th>Token type</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>&lt;value&gt;</code></td>
            <td>anonymous tokens</td>
            <td>used for basic filters</td>
        </tr>
        <tr>
            <td><code>&lt;key&gt;:&lt;value&gt;</code></td>
            <td>named tokens</td>
            <td>used for advanced filters</td>
        </tr>
        <tr>
            <td><code>sort:&lt;style&gt;</code></td>
            <td>sort style tokens</td>
            <td>used to sort the results</td>
        </tr>
        <tr>
            <td><code>special:&lt;value&gt;</code></td>
            <td>special tokens</td>
            <td>filters usually tied to the logged in user</td>
        </tr>
    </tbody>
</table>

<p>Most of anonymous and named tokens support ranged and composite values that
take following form:</p>

<table>
    <tbody>
        <tr>
            <td><code>a,b,c</code></td>
            <td>will show things that satisfy either <code>a</code>,
            <code>b</code> or <code>c</code>.</td>
        </tr>
        <tr>
            <td><code>1..</code></td>
            <td>will show things that are equal to or greater than 1.</td>
        </tr>
        <tr>
            <td><code>..4</code></td>
            <td>will show things that are equal to at most 4.</td>
        </tr>
        <tr>
            <td><code>1..4</code></td>
            <td>will show things that are equal to 1, 2, 3 or 4.</td>
        </tr>
    </tbody>
</table>

<p>Ranged values can be also supplied by appending <code>-min</code> or
<code>-max</code> to the key, for example like this:
<code>score-min:1</code>.</p>

<p>Date/time values can be of following form:</p>

<ul>
    <li><code>today</code></li>
    <li><code>yesterday</code></li>
    <li><code>&lt;year&gt;</code></li>
    <li><code>&lt;year&gt;-&lt;month&gt;</code></li>
    <li><code>&lt;year&gt;-&lt;month&gt;-&lt;day&gt;</code></li>
</ul>

<p>Some fields, such as user names, can take wildcards (<code>*</code>).</p>

<p>All tokens can be negated by prepending them with <code>-</code>.</p>

<p>Sort style token values can be appended with <code>,asc</code> or
<code>,desc</code> to control the sort direction, which can be also controlled
by negating the whole token.</p>

<p>You can escape special characters such as <code>:</code> and <code>-</code>
by prepending them with a backslash: <code>\\</code>.</p>

<h1>Example</h1>

<p>Searching for posts with following query:</p>

<pre><code>sea -fav-count:8.. type:swf uploader:Pirate</code></pre>

<p>will show flash files tagged as sea, that were liked by seven people at
most, uploaded by user Pirate.</p>

<p>Searching for posts with <code>re:zero</code> will show an error message
about unknown named token.</p>

<p>Searching for posts with <code>re\:zero</code> will show posts tagged with
<code>re:zero</code>.</p>
