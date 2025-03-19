<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace MpSoft\MpEurosolution\Core\Grid\Column\Type;

use PrestaShop\PrestaShop\Core\Grid\Column\AbstractColumn;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CustomBadge extends AbstractColumn
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'custom_badge';
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver
            ->setRequired([
                'field',
                'callback_method',
                'callback_class',
            ])
            ->setDefaults([
                'badge_type' => 'success',
                'empty_value' => '',
                'clickable' => true,
                'color_field' => '',
                'callback_method' => 'displayBadgeIdEurosolution',
                'employeeId' => \Context::getContext()->employee->id,
            ])
            ->setAllowedTypes('field', 'string')
            ->setAllowedTypes('empty_value', 'string')
            ->setAllowedTypes('clickable', 'bool')
            ->setAllowedValues('badge_type', ['success', 'info', 'danger', 'warning', '']);
    }
}
