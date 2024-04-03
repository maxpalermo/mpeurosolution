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
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/src/helpers/HtmlHelper.php';
require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/models/autoload.php';

class MpEurosolution extends MpSoft\MpEurosolution\Module\ModuleTemplate
{
    public function __construct()
    {
        $this->name = 'mpeurosolution';
        $this->tab = 'administration';
        $this->version = '1.6.0';
        $this->author = 'Massimiliano Palermo';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->module_key = '';

        parent::__construct();

        $this->displayName = $this->l('Eurosolution connector');
        $this->description = $this->l('Tools for Eurosolution');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        $this->context = ContextCore::getContext();
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        $hooks = [
            'actionAdminControllerSetMedia',
            'actionAdminOrdersListingResultsModifier',
            'actionAdminOrdersListingFieldsModifier',
            'actionAdminCustomersListingResultsModifier',
            'actionAdminCustomersListingFieldsModifier',
            'actionAdminCustomersControllerRenderForm',
            'actionObjectCustomerAddAfter',
            'actionObjectCustomerUpdateAfter',
            'actionObjectCustomerDeleteAfter',
            'displayAdminCustomersPersonalInfo',
            'displayAdminOrderBeforeContent',
        ];

        return parent::install()
            && $this->registerHooks($this, $hooks)
            && ModelEurosolution::createTable();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookDisplayAdminOrderBeforeContent($params)
    {
        $id_order = (int) $params['id_order'];
        $order = new Order($id_order);
        $customer = new Customer($order->id_customer);
        $eurosolution = new ModelEurosolution($order->id_customer);
        $template = $this->local_path . 'views/templates/admin/eurosolution.tpl';
        $tpl_vars = array(
            'id_order' => (int) $id_order,
            'customer' => $customer,
            'eurosolution' => $eurosolution,
        );
        $this->context->smarty->assign($tpl_vars);
        return $this->context->smarty->fetch($template);
    }

    public function hookActionAdminCustomersControllerRenderForm(&$params)
    {
        $form = $params['form'];
        $values = $params['values'];
        $id_customer = $params['id_customer'];
        $eurosolution = new ModelEurosolution($id_customer);

        $inputs = [
            [
                'label' => $this->l('Id EUR'),
                'type' => 'text',
                'name' => 'id_eurosolution',
                'col' => 4,
            ],
            [
                'label' => $this->l('Ragione Sociale'),
                'type' => 'text',
                'name' => 'company',
                'col' => 4,
            ],
            [
                'label' => $this->l('P. IVA'),
                'type' => 'text',
                'name' => 'vat_number',
                'col' => 4,
            ],
            [
                'label' => $this->l('Codice Fiscale'),
                'type' => 'text',
                'name' => 'uid',
                'col' => 4,
            ],
            [
                'label' => $this->l('UID'),
                'type' => 'text',
                'name' => 'uid',
                'col' => 4,
            ],
            [
                'label' => $this->l('PEC'),
                'type' => 'text',
                'name' => 'pec',
                'col' => 4,
            ],
            [
                'label' => $this->l('CIG'),
                'type' => 'text',
                'name' => 'cig',
                'col' => 4,
            ],
            [
                'label' => $this->l('CUP'),
                'type' => 'text',
                'name' => 'cup',
                'col' => 4,
            ],
        ];

        if (Validate::isLoadedObject($eurosolution)) {
            $values['id_eurosolution'] = $eurosolution->id_eurosolution;
            $values['company'] = $eurosolution->company;
            $values['vat_number'] = $eurosolution->vat_number;
            $values['uid'] = $eurosolution->uid;
            $values['pec'] = $eurosolution->pec;
            $values['cig'] = $eurosolution->cig;
            $values['cup'] = $eurosolution->cup;
            $values['id_subject'] = $eurosolution->id_subject;
        }

        $inputs = array_merge($form['input'], $inputs);
        $form['input'] = $inputs;

        $params['form'] = $form;
        $params['values'] = $values;
    }

    public function hookActionObjectCustomerUpdateAfter($params)
    {
        $id_customer = (int) $params['object']->id;
        $eurosolution = new ModelEurosolution($id_customer);
        $eurosolution->company = Tools::getValue('company');
        $eurosolution->vat_number = Tools::getValue('vat_number');
        $eurosolution->uid = Tools::getValue('uid');
        $eurosolution->pec = Tools::getValue('pec');
        $eurosolution->cig = Tools::getValue('cig');
        $eurosolution->cup = Tools::getValue('cup');
        $eurosolution->id_employee = (int) Context::getContext()->employee->id;
        $eurosolution->id_eurosolution = (int) Tools::getValue('id_eurosolution');
        $eurosolution->save();
    }

    public function hookDisplayAdminCustomersPersonalInfo($params)
    {
        if ($params['type'] == 'customer') {
            $id_customer = (int) $params['id_customer'];
            $customer = new Customer($id_customer);
            $eurosolution = new ModelEurosolution($customer->id);
            $template = $this->local_path . 'views/templates/admin/adminCustomersPersonalInfo.tpl';
            $tpl_vars = [
                'id_customer' => (int) $id_customer,
                'customer' => $customer,
                'eurosolution' => $eurosolution,
                'elements' => [
                    [
                        'title' => $this->l('Id Eurosolution'),
                        'style' => 'primary',
                        'icon' => '',
                        'label' => $eurosolution->id_eurosolution,
                    ],
                    [
                        'title' => $this->l('Ragione Sociale'),
                        'style' => 'primary',
                        'icon' => '',
                        'label' => $eurosolution->company,
                    ],
                    [
                        'title' => $this->l('P. IVA'),
                        'style' => 'primary',
                        'icon' => '',
                        'label' => $eurosolution->vat_number,
                    ],
                    [
                        'title' => $this->l('Codice Fiscale'),
                        'style' => 'primary',
                        'icon' => '',
                        'label' => $eurosolution->uid,
                    ],
                    [
                        'title' => $this->l('PEC'),
                        'style' => 'primary',
                        'icon' => '',
                        'label' => $eurosolution->pec,
                    ],
                    [
                        'title' => $this->l('CIG'),
                        'style' => 'primary',
                        'icon' => '',
                        'label' => $eurosolution->cig,
                    ],
                    [
                        'title' => $this->l('CUP'),
                        'style' => 'primary',
                        'icon' => '',
                        'label' => $eurosolution->cup,
                    ],
                ],
            ];
            $this->context->smarty->assign($tpl_vars);
            return $this->context->smarty->fetch($template);
        }

        if ($params['type'] == 'address') {

        }
    }

    public function hookActionAdminOrdersListingFieldsModifier($params)
    {
        if (isset($params['select'])) {
            //Table alias `a` is `orders` table
            $params['select'] = rtrim($params['select'], ',')
                . ",xyz.`id_eurosolution` as `id_eurosolution`\n"
                . ",xyz.`vat_number` as `vat_number`\n";
            $params['join'] .=
                " LEFT JOIN `" . _DB_PREFIX_ . 'mp_eurosolution` xyz ON '
                . "(a.id_customer=xyz.id_customer)";
        }

        if (isset($params['fields'])) {
            //ID EUROSOLUTION
            $idx = $this->getIndexOfField($params['fields'], 'customer');
            $field = [];
            $field['id_eurosolution'] = [
                'title' => $this->l('Id EUR'),
                'type' => 'text',
                'float' => true,
                'search' => true,
                'filter_key' => 'xyz!id_eurosolution',
                'orderby' => false,
            ];
            //Insert field after customer column
            $params['fields'] = $this->insertValueAtPosition(
                $params['fields'],
                $field,
                $idx
            );

            //VAT NUMBER
            $field = [];
            $field['vat_number'] = [
                'title' => $this->l('P. IVA'),
                'type' => 'text',
                'float' => true,
                'search' => true,
                'filter_key' => 'xyz!vat_number',
                'orderby' => false,
            ];

            //Insert field after customer column
            $params['fields'] = $this->insertValueAtPosition(
                $params['fields'],
                $field,
                $idx + 1
            );
        }
    }

    public function hookActionAdminCustomersListingFieldsModifier($params)
    {
        if (isset($params['select'])) {
            //Table alias `a` is `orders` table
            $params['select'] = rtrim($params['select'], ',')
                . ",xyz.`id_eurosolution` as `id_eurosolution`\n"
                . ",xyz.`id_subject` as `id_subject`\n"
                . ",xyz.`company` as `company`\n"
                . ",xyz.`uid` as `uid`\n"
                . ",xyz.`pec` as `pec`\n"
                . ",xyz.`cig` as `cig`\n"
                . ",xyz.`cup` as `cup`\n"
                . ",xyz.`vat_number` as `vat_number`\n";
            $params['join'] .=
                " LEFT JOIN `" . _DB_PREFIX_ . 'mp_eurosolution` xyz ON '
                . "(a.id_customer=xyz.id_customer)";
        }

        if (isset($params['fields'])) {
            //ID EUROSOLUTION
            $idx = $this->getIndexOfField($params['fields'], 'email');

            $field = [];
            $field['company'] = [
                'title' => $this->l('Rag. Soc.'),
                'type' => 'text',
                'float' => true,
                'search' => true,
                'filter_key' => 'xyz!company',
                'orderby' => false,
                'callback' => 'displayCompany',
                'callback_object' => $this,
            ];
            //Insert field after customer column
            $params['fields'] = $this->insertValueAtPosition(
                $params['fields'],
                $field,
                $idx
            );

            $field = [];
            $field['id_eurosolution'] = [
                'title' => $this->l('Id EUR'),
                'type' => 'text',
                'float' => true,
                'search' => true,
                'filter_key' => 'xyz!id_eurosolution',
                'orderby' => false,
                'callback' => 'displayIdEur',
                'callback_object' => $this,
            ];
            //Insert field after customer column
            $params['fields'] = $this->insertValueAtPosition(
                $params['fields'],
                $field,
                $idx + 1
            );

            //VAT NUMBER
            $field = [];
            $field['vat_number'] = [
                'title' => $this->l('P. IVA'),
                'type' => 'text',
                'float' => true,
                'search' => true,
                'filter_key' => 'xyz!vat_number',
                'orderby' => false,
                'callback' => 'displayPiva',
                'callback_object' => $this,
            ];

            //Insert field after customer column
            $params['fields'] = $this->insertValueAtPosition(
                $params['fields'],
                $field,
                $idx + 2
            );
        }
    }

    public function displayIdEur($value)
    {
        if (!$value) {
            return '--';
        }

        return $value;
    }

    public function displayPiva($value)
    {
        if (!$value) {
            return '--';
        }

        return $value;
    }

    public function displayCompany($value)
    {
        if (!$value) {
            return '--';
        }

        return $value;
    }
}