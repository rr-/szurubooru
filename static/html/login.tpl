<div class='center' id='login'>
    <h1>Log in</h1>
    <form>
        <fieldset class='input'>
            <ul>
                <li>
                    <label for='user-name'>User name</label>
                    <input id='user-name' name='name' type='text' required/>
                </li>
                <li>
                    <label for='user-password'>Password</label>
                    <input id='user-password' name='password' type='password' required/>
                </li>
                <li>
                    <input id='remember-user' name='remember-user' type='checkbox'/>
                    <label for='remember-user' class='checkbox'>Remember me</label>
                </li>
            </ul>
        </fieldset>
        <fieldset class='messages'></fieldset>
        <fieldset class='buttons'>
            <input type='submit' value='Log in'/>
            <a>Forgot the password?</a>
        </fieldset>
    </form>
</div>
