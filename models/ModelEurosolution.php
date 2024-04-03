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

class ModelEurosolution extends ModelEurosolutionObjectModelTemplate
{
    public $id_eurosolution;
    public $id_subject;
    public $company;
    public $uid;
    public $pec;
    public $cig;
    public $cup;
    public $vat_number;
    public $id_employee;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'mp_eurosolution',
        'primary' => 'id_customer',
        'multilang' => false,
        'fields' => [
            'id_eurosolution' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true,
            ],
            'id_subject' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => false,
            ],
            'company' => [
                'type' => self::TYPE_STRING,
                'size' => 128,
                'validate' => 'isGenericName',
                'required' => false,
            ],
            'uid' => [
                'type' => self::TYPE_STRING,
                'size' => 7,
                'validate' => 'isAnything',
                'required' => false,
            ],
            'pec' => [
                'type' => self::TYPE_STRING,
                'size' => 255,
                'validate' => 'isEmail',
                'required' => false,
            ],
            'cig' => [
                'type' => self::TYPE_STRING,
                'size' => 10,
                'validate' => 'isAnything',
                'required' => false,
            ],
            'cup' => [
                'type' => self::TYPE_STRING,
                'size' => 15,
                'validate' => 'isAnything',
                'required' => false,
            ],
            'vat_number' => [
                'type' => self::TYPE_STRING,
                'size' => 32,
                'validate' => 'isString',
                'required' => false,
            ],
            'id_employee' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true,
            ],
            'date_add' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'required' => true,
            ],
            'date_upd' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'required' => true,
            ],
        ],
    ];

    public static function getCustomerInfo($id_customer)
    {
        $model = new self($id_customer);
        if (Validate::isLoadedObject($model)) {
            return $model;
        }

        return null;
    }
}