<div class="messages"></div>

<form class="form-wrapper browsing-settings">
    <div class="form-row">
        <label class="form-label">Safety:</label>
        <div class="form-input">
            <input <% print(settings.listPosts.safe ? 'checked="checked"' : '') %> type="checkbox" id="browsing-settings-safety-safe" name="listSafePosts" value="safe"/>
            <label for="browsing-settings-safety-safe">
                Safe
            </label>

            <input <% print(settings.listPosts.sketchy ? 'checked="checked"' : '') %> type="checkbox" id="browsing-settings-safety-sketchy" name="listSketchyPosts" value="sketchy"/>
            <label for="browsing-settings-safety-sketchy">
                Sketchy
            </label>

            <input <% print(settings.listPosts.unsafe ? 'checked="checked"' : '') %> type="checkbox" id="browsing-settings-safety-unsafe" name="listUnsafePosts" value="unsafe"/>
            <label for="browsing-settings-safety-unsafe">
                Unsafe
            </label>
        </div>
    </div>

    <div class="form-row">
        <label class="form-label" for="browsing-settings-endless-scroll">Endless scroll:</label>
        <div class="form-input">
            <input <% print(settings.endlessScroll ? 'checked="checked"' : '') %> type="checkbox" id="browsing-settings-endless-scroll" name="endlessScroll"/>
            <label for="browsing-settings-endless-scroll">
                Enabled
            </label>
        </div>
    </div>

    <div class="form-row">
        <label class="form-label" for="browsing-settings-hide-downvoted">Hide down-voted:</label>
        <div class="form-input">
            <input <% print(settings.hideDownvoted ? 'checked="checked"' : '') %> type="checkbox" id="browsing-settings-hide-downvoted" name="hideDownvoted"/>
            <label for="browsing-settings-hide-downvoted">
                Enabled
            </label>
        </div>
    </div>

    <div class="form-row">
        <label class="form-label" for="browsing-settings-keyboard-shortcuts">Keyboard shortcuts:</label>
        <div class="form-input">
            <input <% print(settings.keyboardShortcuts ? 'checked="checked"' : '') %> type="checkbox" id="browsing-settings-keyboard-shortcuts" name="keyboardShortcuts"/>
            <label for="browsing-settings-keyboard-shortcuts">
                Enabled
            </label>
        </div>
    </div>

    <div class="form-row">
        <label class="form-label">Default fit mode:</label>
        <div class="form-input">
            <input <% print(settings.fitMode === 'fit-width' ? 'checked="checked"' : '') %> type="radio" id="browsing-settings-fit-width" name="fitMode" value="fit-width"/>
            <label for="browsing-settings-fit-width">
                Fit to width
            </label>
            <br/>

            <input <% print(settings.fitMode === 'fit-height' ? 'checked="checked"' : '') %> type="radio" id="browsing-settings-fit-height" name="fitMode" value="fit-height"/>
            <label for="browsing-settings-fit-height">
                Fit to height
            </label>
            <br/>

            <input <% print(settings.fitMode === 'fit-both' ? 'checked="checked"' : '') %> type="radio" id="browsing-settings-fit-both" name="fitMode" value="fit-both"/>
            <label for="browsing-settings-fit-both">
                Fit width and height
            </label>
            <br/>

            <input <% print(settings.fitMode === 'original' ? 'checked="checked"' : '') %> type="radio" id="browsing-settings-fit-original" name="fitMode" value="original"/>
            <label for="browsing-settings-fit-original">
                Original
            </label>
            <br/>

            <input <% print(settings.upscale ? 'checked="checked"' : '') %> type="checkbox" id="browsing-settings-upscale" name="upscale" value="upscale"/>
            <label for="browsing-settings-upscale">
                Upscale small posts
            </label>
        </div>
    </div>

    <div class="form-row">
        <label class="form-label"></label>
        <div class="form-input">
            <button type="submit">Update settings</button>
        </div>
    </div>
</form>
