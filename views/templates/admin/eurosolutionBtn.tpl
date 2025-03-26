{*
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 *}

{assign var=fontSize value='1.2rem'}
{assign var=textAlign value='text-center'}
{assign var=badgeColor value='success'}

<div class="{$textAlign}">
    {if $eurosolutionId != 0}
        <span
              class="eurosolutionId badge badge-{$badgeColor} pointer"
              style="font-size: {$fontSize}; border-radius: 0;"
              data-customer_id="{$customerId|intval}"
              data-eurosolution_id="{$eurosolutionId|intval}"
              data-employee_id="{$employeeId|intval}"
              data-tippy-content="Codice Eurosolution">
            {$eurosolutionId}
        </span>
    {else}
        <span
              class="eurosolutionId pointer"
              data-customer_id="{$customerId|intval}"
              data-eurosolution_id="0"
              data-employee_id="{$employeeId|intval}"
              data-tippy-content="Aggiungi un codice Eurosolution">
            <span class="material-icons text-info">add_circle</span>
        </span>
    {/if}
</div>