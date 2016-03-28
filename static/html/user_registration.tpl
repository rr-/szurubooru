<div class='center' id='user-registration'>
    <h1>Registration</h1>
    <form autocomplete='off'>
        <fieldset class='input'>
            <ul>
                <li>
                    <label for='user-name'>User name</label>
                    <input id='user-name' name='user-name' type='text' autocomplete='off' placeholder='letters, digits, _, -' required/>
                </li>
                <li>
                    <label for='user-password'>Password</label>
                    <input id='user-password' name='user-password' type='password' autocomplete='off' placeholder='5+ characters' required/>
                </li>
                <li>
                    <label for='user-email'>Email</label>
                    <input id='user-email' name='user-email' type='email' autocomplete='off' placeholder='optional'/>
                    <p class="hint">Used for password reminder and to show a <a href='http://gravatar.com/'>Gravatar</a>. Leave blank for random Gravatar.</p>
                </li>
            </ul>
        </fieldset>
        <fieldset class='buttons'>
            <input type='submit' value='Create an account'/>
        </fieldset>
    </form>
    <div class='info'>
        <p>Registered users can:</p>
        <ul>
            <li><i class="fa fa-upload"></i> upload new posts</li>
            <li><i class="fa fa-heart"></i> mark them as favorite</li>
            <li><i class="fa fa-commenting-o"></i> add comments</li>
            <li><i class="fa fa-star-half-o"></i> vote up/down on posts and comments</li>
        </ul>
        <hr/>
        <p>By creating an account, you are agreeing to the <a href='/help/tos'>Terms of Service</a>.</p>
    </div>
</div>