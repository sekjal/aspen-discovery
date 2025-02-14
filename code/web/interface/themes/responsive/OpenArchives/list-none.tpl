{strip}
{* Recommendations *}
{if $topRecommendations}
	{foreach from=$topRecommendations item="recommendations"}
		{include file=$recommendations}
	{/foreach}
{/if}

<h1>{translate text='nohit_heading'}</h1>

<p class="alert alert-info">{translate text='nohit_prefix'} - <b>{if $lookfor}{$lookfor|escape:"html"}{else}&lt;empty&gt;{/if}</b> - {translate text='nohit_suffix'}</p>

{if !empty($solrSearchDebug)}
	<div id="solrSearchOptionsToggle" onclick="$('#solrSearchOptions').toggle()">Show Search Options</div>
	<div id="solrSearchOptions" style="display:none">
		<pre>Search options: {$solrSearchDebug}</pre>
	</div>
{/if}

{if !empty($solrLinkDebug)}
	<div id='solrLinkToggle' onclick='$("#solrLink").toggle()'>Show Solr Link</div>
	<div id='solrLink' style='display:none'>
		<pre>{$solrLinkDebug}</pre>
	</div>
{/if}

<div>
	{if !empty($parseError)}
		<div class="alert alert-danger">
			{$parseError}
		</div>
	{/if}

	{include file="Search/spellingSuggestions.tpl"}

	{include file="Search/searchSuggestions.tpl"}

	{if $showExploreMoreBar}
		<div id="explore-more-bar-placeholder"></div>
		<script type="text/javascript">
			$(document).ready(
					function () {ldelim}
						AspenDiscovery.Searches.loadExploreMoreBar('open_archives', '{$exploreMoreSearchTerm|escape:"html"}');
						{rdelim}
			);
		</script>
	{/if}

	{if $showDplaLink}
		{* DPLA Results *}
		<div id='dplaSearchResultsPlaceholder'></div>
	{/if}

	{if $showSearchTools || ($loggedIn && count($userPermissions) > 0)}
		<div class="search_tools well small">
			<strong>{translate text='Search Tools'}:</strong>
			{if $showSearchTools}
				<a href="{$rssLink|escape}">{translate text='Get RSS Feed'}</a>
				<a href="#" onclick="return AspenDiscovery.Account.ajaxLightbox('/Search/AJAX?method=getEmailForm', true);">{translate text='Email this Search'}</a>
				{if $savedSearch}
					<a href="#" onclick="return AspenDiscovery.Account.saveSearch('{$searchId}')">{translate text='save_search_remove'}</a>
				{else}
					<a href="#" onclick="return AspenDiscovery.Account.saveSearch('{$searchId}')">{translate text='save_search'}</a>
				{/if}
				<a href="{$excelLink|escape}">{translate text='Export To Excel'}</a>
			{/if}
		</div>
	{/if}

</div>

<script type="text/javascript">
	$(function(){ldelim}
		{if $showDplaLink}
		AspenDiscovery.DPLA.getDPLAResults('{$lookfor}');
		{/if}
		{rdelim});
</script>
{/strip}