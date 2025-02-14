{strip}
<div id="horizontal-search-box" class="row">
	<form method="get" action="/Union/Search" id="searchForm" class="form-inline">

		{* Hidden Inputs *}
		<input type="hidden" name="view" id="view" value="{$displayMode}">

		{if isset($showCovers)}
			<input type="hidden" name="showCovers" value="{if $showCovers}on{else}off{/if}">
		{/if}

		{assign var="hiddenSearchSource" value=false}
		{* Switch sizing when no search source is to be displayed *}
		{if empty($searchSources) || count($searchSources) == 1}
			{assign var="hiddenSearchSource" value=true}
			<input type="hidden" name="searchSource" value="{$searchSource}">
		{/if}

		<div class="col-xs-12 col-sm-10 col-md-11">
			<div class="row">
				<div class="{if $hiddenSearchSource}col-lg-10 col-md-10{else}col-lg-7 col-md-7{/if} col-sm-12 col-xs-12">
					<label for="lookfor" class="label" id="lookfor-label"><i class="fas fa-search fa-2x"></i></label>
					{* Main Search Term Box *}
					<input type="text" class="form-control"{/strip}
						id="lookfor"
						name="lookfor"
						title="Enter one or more terms to search for.	Surrounding a term with quotes will limit result to only those that exactly match the term."
						onfocus="$(this).select()"
						autocomplete="off"
						aria-labelledby="horizontal-search-label"

						{if !empty($lookfor)}value="{$lookfor|escape:"html"}"{/if}
					{strip}>
				</div>

				{* Search Type *}
				<div class="col-lg-2 col-lg-offset-0 col-md-2 col-md-offset-0 {if $hiddenSearchSource} col-sm-12 col-sm-offset-0 col-xs-12 col-xs-offset-0 {else} col-sm-6 col-sm-offset-0 col-xs-5 col-xs-offset-0{/if}">
					<select name="searchIndex" class="searchTypeHorizontal form-control catalogType" id="searchIndex" title="The method of searching." aria-label="Search Index">
						{foreach from=$searchIndexes item=searchDesc key=searchVal}
							<option value="{$searchVal}"{if !empty($searchIndex) && $searchIndex == $searchVal} selected="selected"{/if}>{translate text="by"  inAttribute=true} {translate text=$searchDesc  inAttribute=true}</option>
						{/foreach}

						{* Add Advanced Search *}
						{if !empty($searchIndex) && $searchIndex == 'advanced'}*}
							<option id="advancedSearchLink" value="editAdvanced" selected="selected">
								{translate text='Edit Advanced Search' inAttribute=true}
							</option>
						{elseif $showAdvancedSearchbox}
							<option id="advancedSearchLink" value="advanced">
								{translate text='Advanced Search' inAttribute=true}
							</option>
						{/if}
					</select>
				</div>

				{if !$hiddenSearchSource}
					<div class="col-lg-3 col-md-3 col-sm-6 col-xs-7">
						<select name="searchSource" id="searchSource" title="Select what to search.	Items marked with a * will redirect you to one of our partner sites." onchange="AspenDiscovery.Searches.loadSearchTypes();" class="searchSourceHorizontal form-control" aria-label="Collection to Search">
							{foreach from=$searchSources item=searchOption key=searchKey}
								<option data-catalog_type="{$searchOption.catalogType}" value="{$searchKey}" title="{$searchOption.description}" data-advanced_search="{$searchOption.hasAdvancedSearch}" data-advanced_search_label="{translate text="Advanced Search" inAttribute=true}"
										{if $searchKey == $searchSource} selected="selected"{/if}
										{if $searchKey == $defaultSearchIndex} id="default_search_type"{/if}
										>
									{translate text="in"  inAttribute=true} {translate text=$searchOption.name inAttribute=true}{if !empty($searchOption.external)} *{/if}
								</option>
							{/foreach}
						</select>
					</div>
				{/if}
			</div>
		</div>

		{* GO Button & Search Links*}
		<div id="horizontal-search-button-container" class="col-xs-12 col-sm-2 col-md-1">
			<div class="row">
				<div class="col-tn-6 col-xs-6 col-sm-12 col-md-12">
					<button class="btn btn-default" type="submit">
						<i class="fas fa-search fas-lg"></i>
						<span id="horizontal-search-box-submit-text">&nbsp;{translate text='GO'}</span>
					</button>
				</div>

{*				<div id="horizontal-search-additional" class="col-tn-5 col-xs-5 col-sm-12 col-md-8">*}
{*					*}{* Return to Advanced Search Link *}
{*					{if !empty($searchType) && $searchType == 'advanced'}*}
{*						<a id="advancedSearchLink" href="/Search/Advanced" class="btn btn-default" {if !$searchSources.$searchSource.hasAdvancedSearch}style="display: none"{/if}>*}
{*							{translate text='Edit Advanced Search'}*}
{*						</a>*}

{*					*}{* Show Advanced Search Link *}
{*					{elseif $showAdvancedSearchbox}*}
{*						<a id="advancedSearchLink" href="/Search/Advanced"  class="btn btn-default" {if !$searchSources.$searchSource.hasAdvancedSearch}style="display: none"{/if}>*}
{*							{translate text='Advanced Search'}*}
{*						</a>*}
{*					{/if}*}
{*				</div>*}

				{* Show/Hide Search Facets & Sort Options *}
				{if !empty($recordCount) || !empty($sideRecommendations)}
					<div class="col-tn-6 col-xs-6 visible-xs text-right">
						<a class="btn btn-default" id="refineSearchButton" role="button" onclick="$('#side-bar').slideToggle('slow');return false;"><i class="fas fa-filter"></i> {translate text='Filters'}</a>
					</div>
				{/if}
			</div>
		</div>

	</form>
</div>
{/strip}