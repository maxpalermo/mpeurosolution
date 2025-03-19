<?php
/**
 * 2017 mpSOFT
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Massimiliano Palermo <info@mpsoft.it>
 *  @copyright 2019 Digital Solutions®
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of mpSOFT
 */
class AdminMpEurosolutionController extends ModuleAdminController
{
    public function __construct()
    {
        $this->context = Context::getContext();
        $this->module = Module::getInstanceByName('mpeurosolution');
        $this->translator = Context::getContext()->getTranslator();

        $this->bootstrap = true;
        $this->table = 'customer';
        $this->identifier = 'id_customer';
        $this->className = 'MpSoft\MpEurosolution\Models\ModelCustomerEurosolution';
        $this->lang = false;

        $this->initFields();

        parent::__construct();
    }

    public function initFields()
    {
        $id_shop = (int) Context::getContext()->shop->id;
        $this->fields_list = [
            'id_customer' => [
                'title' => $this->trans('Id Cliente'),
                'align' => 'right',
                'width' => 25,
            ],
            'lastname' => [
                'title' => $this->trans('Cognome'),
                'align' => 'left',
                'width' => 'auto',
                'filter_key' => 'a!lastname',
            ],
            'firstname' => [
                'title' => $this->trans('Nome'),
                'align' => 'left',
                'width' => 'auto',
                'filter_key' => 'a!firstname',
            ],
            'email' => [
                'title' => $this->trans('email'),
                'align' => 'left',
                'width' => 'auto',
                'type' => 'text',
                'filter_key' => 'a!email',
            ],
            'note' => [
                'title' => $this->trans('note'),
                'align' => 'left',
                'width' => 'auto',
                'type' => 'text',
                'filter_key' => 'a!note',
            ],
            'id_eurosolution' => [
                'title' => $this->trans('Id Eurosolution'),
                'align' => 'left',
                'width' => 'auto',
                'type' => 'text',
                'float' => true,
                'filter_key' => 'b!id_eurosolution',
                'callback' => 'displayEurosolution',
                'class' => 'fixed-width-md text-center',
            ]
        ];

        $this->bulk_actions = [
            'doChange' => [
                'text' => $this->trans('Do changes'),
                'icon' => 'icon-refresh',
            ],
        ];

        $this->actions = ['edit', 'delete'];
        $this->_select = 'b.id_eurosolution';
        $this->_join .= ' LEFT JOIN `' . _DB_PREFIX_ . 'customer_eurosolution` b ON (a.id_customer = b.id_customer)';
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        unset($this->page_header_toolbar_btn['new']);
        $this->page_header_toolbar_btn = [
            'configure' => [
                'href' => $this->context->link->getAdminLink('AdminModules') . '&configure=mplocation',
                'desc' => $this->trans('Configurazione'),
            ],
        ];
    }

    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
        $this->toolbar_btn = [
            'configure' => [
                'href' => $this->context->link->getAdminLink('AdminModules') . '&configure=mplocation',
                'desc' => $this->trans('Configurazione'),
            ],
        ];
    }

    public function initContent()
    {
        if (Tools::getValue('action') == 'configure') {
            $this->fields_list = [];
            $this->content = $this->displayConfiguration();
        }

        parent::initContent();
    }

    protected function displayConfiguration()
    {
        return '';
    }

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->trans('Product'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->trans('Location'),
                    'name' => 'location',
                    'required' => true,
                ],
            ],
            'submit' => [
                'title' => $this->trans('Save'),
            ],
        ];

        return parent::renderForm();
    }

    protected function response($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function displayEurosolution($value)
    {
        if ((int)$value) {
            return "<span class=\"badge bg-info\" style=\"font-size: 1.2rem;\">{$value}</span>";
        }

        return "--";
    }
}