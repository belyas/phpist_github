{**
 * 2017 - 2018 PHPIST
 *
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future.
 *
 *  @author    PHPIST <yassine.belkaid87@gmail.com>
 *  @copyright 2017 - 2018 PHPIST
 *  @license   MIT
 *}

{if isset($commits) && $commits|@count}
<div class="commits_container">
	<h3 class="commit_title">{l s='Latest %d commits' sprintf=$commits_number|intval mod='phpist_github'}</h3>
	<ul class="commits_list">
		{foreach from=$commits item=commit}
			<li>
				<div class="commit_header">
					<span class="commit_date">{l s='Committed on' mod='phpist_github'}: <strong>{$commit.commit_date|escape:'html':'UTF-8'}</strong></span> | 
					<span class="commit_author">{l s='Committed by' mod='phpist_github'}: <a href="{$commit.author_url|escape:'html':'UTF-8'}" target="_blank"><strong>@{$commit.author|escape:'html':'UTF-8'}</strong></a></span>
				</div>
				<a class="commit_url" href="{$commit.commit_url|escape:'html':'UTF-8'}" target="_blank">{$commit.message|escape:'html':'UTF-8'}</a>
			</li>
		{/foreach}
	</ul>
</div>
{/if}
