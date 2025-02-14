{strip}
	<div>
		{if count($hooplaUsers) > 1} {* Linked Users contains the active user as well*}
			<div id='pickupLocationOptions' class="form-group">
				<label class="control-label" for="patronId">Please choose the account to check out from : </label>
				<div class="controls">
					<select name="patronId" id="patronId" class="form-control">
						{foreach from=$hooplaUsers item=tmpUser}
						{assign var="userId" value=$tmpUser->id}
							<option value="{$tmpUser->id}">
								{$tmpUser->getNameAndLibraryLabel()}
								{if !empty($hooplaUserStatuses[$userId])}
									{assign var="hooplaPatronStatus" value=$hooplaUserStatuses[$userId]}
									&nbsp; ({$hooplaPatronStatus->numCheckoutsRemaining} check outs remaining this month)
								{else}
									&nbsp; (no Hoopla account)
								{/if}
							</option>
						{/foreach}
					</select>
				</div>
			</div>
		{/if}
	</div>
{/strip}