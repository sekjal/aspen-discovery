{strip}
	{if $showEmailThis || $showShareOnExternalSites}
		<div class="result-tools-horizontal btn-toolbar" role="toolbar">
			{* More Info Link, only if we are showing other data *}
			{if $showMoreInfo}
				{if $showMoreInfo !== false}
					<div class="btn-group btn-group-sm">
						<a href="{if $summUrl}{$summUrl}{else}{$recordDriver->getLinkUrl()}{/if}" class="btn btn-sm btn-tools" onclick="AspenDiscovery.EBSCO.trackEdsUsage('{$recordDriver->getPermanentId()}')" target="_blank"><i class="fas fa-external-link-alt"></i> {translate text="More Info"}</a>
					</div>
				{/if}
				{if $showFavorites == 1}
					<div class="btn-group btn-group-sm">
						<button onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'EbscoEds', '{$recordDriver->getPermanentId()|escape}');" class="btn btn-sm btn-tools">{translate text='Add to list'}</button>
					</div>
				{/if}
			{/if}

			<div class="btn-group btn-group-sm">
				{include file="EBSCO/share-tools.tpl"}
			</div>
		</div>
	{/if}
{/strip}