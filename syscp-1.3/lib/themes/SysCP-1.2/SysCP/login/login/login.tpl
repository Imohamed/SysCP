    <form method="post" action="{url module=login}">
     <table cellpadding="3" cellspacing="1" border="0" align="center" class="maintable">
      <tr>
       <td colspan="2" class="title">{l10n get=SysCP.login.syscplogin}</td>
      </tr>
      <tr>
       <td class="maintable"><font size="-1">{l10n get=SysCP.globallang.username}:</font></td>
       <td class="maintable"><input type="text" name="loginname" value="" maxlength="50"></td>
      </tr>
      <tr>
       <td class="maintable"><font size="-1">{l10n get=SysCP.globallang.password}:</font></td>
       <td class="maintable"><input type="password" name="password" maxlength="50"></td>
      </tr>
      <tr>
       <td class="maintable"><font size="-1">{l10n get=SysCP.login.language}:</font></td>
       <td class="maintable"><select name="language">
       {html_options options=$lang_list selected=$lang_sel}</select></td>
      </tr>
      <tr>
       <td class="maintable"><font size="-1">{l10n get=SysCP.login.theme}:</font></td>
       <td class="maintable">
		<select name="theme">
			{html_options options=$theme_list selected=$theme_sel}
		</select>
       </td>
      </tr>
      <tr>
       <td class="maintable" colspan="2" align="right">
       <input type="hidden" name="send" value="send">
       <input type="submit" value="{l10n get=SysCP.login.login}"></td>
      </tr>
     </table>
    </form>
