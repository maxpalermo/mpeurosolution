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

require_once dirname(__FILE__) . '/vendor/autoload.php';

use Doctrine\ORM\QueryBuilder;
use MpSoft\MpEurosolution\Core\Grid\Column\Type\CustomBadge;
use MpSoft\MpEurosolution\Install\InstallMenu;
use MpSoft\MpEurosolution\Install\InstallTable;
use MpSoft\MpEurosolution\Models\ModelCustomerEurosolution;
use PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinitionInterface;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use PrestaShop\PrestaShop\Core\Search\Filters\CustomerFilters;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class MpEurosolution extends Module implements WidgetInterface
{
    public $active_panel;

    public function __construct()
    {
        $this->name = 'mpeurosolution';
        $this->tab = 'administration';
        $this->version = '2.0.3';
        $this->author = 'Massimiliano Palermo';
        $this->need_instance = 0;
        $this->module_key = '';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('MP Id Eurosolution');
        $this->description = $this->l('Questo modulo Gestisce il codice Eurosolution dei clienti.');
        $this->confirmUninstall = $this->l('Are you sure you want uninstall this module?');
        $this->ps_versions_compliancy = ['min' => '8.0', 'max' => _PS_VERSION_];
    }

    public function renderWidget($hookName, array $configuration)
    {
        switch ($hookName) {
            case 'displayAdminOrderMain':
            case 'displayAdminOrderSide':
            case 'displayAdminOrderTop':
                break;
            case 'displayBackOfficeFooter':
                $vars = $this->getWidgetVariables($hookName, $configuration);
                $tpl = $this->context->smarty->createTemplate('module:mpeurosolution/views/templates/admin/displayBackOfficeFooter.tpl', $this->context->smarty);
                $tpl->assign($vars);

                return $tpl->render();
            default:
                return '';
        }
    }

    public function getWidgetVariables($hookName, array $configuration)
    {
        switch ($hookName) {
            case 'displayAdminOrderMain':
            case 'displayAdminOrderSide':
            case 'displayAdminOrderTop':
                $vars = [];

                return $vars;
            case 'displayBackOfficeFooter':
                $vars = [
                    'adminAjaxUrl' => $this->context->link->getModuleLink('mpeurosolution', 'FetchAsync'),
                    'employeeId' => (int) $this->context->employee->id,
                    'orderId' => (int) Tools::getValue('id_order'),
                    'customerId' => (int) Tools::getValue('id_customer'),
                ];

                return $vars;
            default:
                return [];
        }
    }

    public function displayBadgeIdEurosolution($id_eurosolution)
    {
        if (!$id_eurosolution) {
            return false;
        }

        return $id_eurosolution;
    }

    public function install()
    {
        $installMenu = new InstallMenu($this);
        $installTable = new InstallTable($this);

        $hooks = [
            'actionObjectCustomerAddAfter',
            'actionObjectCustomerUpdateAfter',
            'actionCustomerGridDefinitionModifier',
            'actionCustomerGridQueryBuilderModifier',
            'hookActionCustomerFormBuilderModifier',
            'actionOrderGridDefinitionModifier',
            'actionOrderGridQueryBuilderModifier',
            'actionAdminControllerSetMedia',
            'actionGetAdminToolbarButtons',
            'actionGetAdminOrderButtons',
            'displayAdminOrderMain',
            'displayAdminOrderSide',
            'displayAdminOrderTop',
            'actionCustomerFormDataProviderData',
            'actionAfterCreateCustomerFormHandler',
            'actionAfterUpdateCustomerFormHandler',
            'actionCustomerFormBuilderModifier',
            'displayBackOfficeFooter',
        ];

        return parent::install()
            && $this->registerHook($hooks)
            && $installMenu->installMenu(
                'AdminMpEurosolution',
                $this->l('MP Eurosolution'),
                'AdminParentCustomer',
                'fa-user'
            )
            && $installTable->installFromSqlFile('customer_eurosolution');
    }

    public function hookActionObjectCustomerAddAfter($params)
    {
        // nothing
    }

    public function hookActionObjectCustomerUpdateAfter($params)
    {
        $customer = $params['object'];
        $id_customer = (int) $customer->id;
        $id_eurosolution = Tools::getValue('id_eurosolution');
        $model = new ModelCustomerEurosolution($id_customer);
        $model->id_eurosolution = $id_eurosolution;
        $model->id_employee = (int) $this->context->employee->id;
        if (Validate::isLoadedObject($model)) {
            $model->date_upd = date('Y-m-d H:i:s');
            $model->update();
        } else {
            $model->force_id = true;
            $model->id_customer = $id_customer;
            $model->date_add = date('Y-m-d H:i:s');
            $model->add();
        }
    }

    /**
     * Modify product form builder
     *
     * @param array $params
     */
    public function hookActionCustomerFormBuilderModifier(array $params): void
    {
        $formBuilder = $params['form_builder'];

        $formBuilder->add('id_eurosolution', TextType::class, [
            'label' => $this->l('Eurosolution'),
            'required' => false,
        ]);

        $params['form_builder'] = $formBuilder;
    }

    public function hookActionAdminControllerSetMedia($params)
    {
        $controller = Tools::strtolower(Tools::getValue('controller'));
        $controllers = [
            'admincustomers',
            'adminorders',
        ];
        $jsPath = $this->getLocalPath() . 'views/js/';
        $cssPath = $this->getLocalPath() . 'views/css/';
        if (in_array($controller, $controllers)) {
            $this->context->controller->addJqueryPlugin('growl');

            $this->context->controller->addCSS(
                [
                    $cssPath . 'datatables/datatables.min.css',
                    $cssPath . 'toastify/toastify.css',
                    $cssPath . 'style.css',
                    $cssPath . 'swal2/sweetalert2.min.css',
                ]
            );
            $this->context->controller->addJS(
                [
                    $jsPath . 'datatables/dataTables.min.js',
                    $jsPath . 'toastify/toastify.js',
                    $jsPath . 'toastify/showToastify.js',
                    $jsPath . 'swal2/sweetalert2.all.min.js',
                    $jsPath . 'tippy/popper-core2.js',
                    $jsPath . 'tippy/tippy.js',
                    $jsPath . 'AdminController/script.js',
                ]
            );
        }
    }

    protected function customBadge($params, $insertAfter = '')
    {
        $definition = $params['definition'];
        $definition
            ->getColumns()
            ->addAfter(
                $insertAfter,
                (new CustomBadge('id_eurosolution'))
                    ->setName($this->l('Eurosolution'))
                    ->setOptions([
                        'field' => 'id_eurosolution',
                        'sortable' => true,
                        'badge_type' => 'success',
                        'clickable' => false,
                        'callback_method' => 'displayBadgeIdEurosolution',
                        'callback_class' => $this,
                        'attr' => [
                            'font_size' => '1.5rem',
                        ],
                    ])
            );

        // Add a new text filter
        $definition->getFilters()->add(
            (new Filter('id_eurosolution', TextType::class))
            ->setTypeOptions([
                'required' => false,
            ])
            ->setAssociatedColumn('id_eurosolution')
        );

        $params['definition'] = $definition;

        return $params;
    }

    public function hookActionCustomerGridDefinitionModifier(array $params)
    {
        /** @var GridDefinitionInterface $definition */
        $definition = $params['definition'];

        // Aggiungi la colonna id_eurosolution
        $definition
            ->getColumns()
            ->addAfter(
                'id_customer',
                (new CustomBadge('id_eurosolution'))
                    ->setName($this->trans('Eur ID', [], 'Modules.Mpeurosolution.Admin'))
                    ->setOptions([
                        'field' => 'id_eurosolution',
                        'badge_type' => 'success',
                        'sortable' => true,
                        'clickable' => false,
                        'alignment' => 'center',
                        'callback_class' => $this,
                        'callback_method' => 'displayBadgeIdEurosolution',
                        'attr' => [
                            'font_size' => '1.5rem',
                        ],
                    ])
            );

        // Aggiungi il filtro per id_eurosolution
        $definition->getFilters()->add(
            (new Filter('id_eurosolution', TextType::class))
                ->setTypeOptions([
                    'required' => false,
                    'attr' => [
                        'placeholder' => $this->trans('EuroSolution ID', [], 'Modules.Customereurosolution.Admin'),
                    ],
                ])
                ->setAssociatedColumn('id_eurosolution')
        );
    }

    public function hookActionCustomerGridQueryBuilderModifier(array $params)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $params['search_query_builder'];
        /** @var CustomerFilters $searchCriteria */
        $searchCriteria = $params['search_criteria'];

        // Aggiungi id_eurosolution alla query
        $queryBuilder->addSelect('eur.id_eurosolution');
        $queryBuilder->leftJoin('c', _DB_PREFIX_ . 'customer_eurosolution', 'eur', 'c.id_customer = eur.id_customer');

        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
            if ($filterName == 'id_eurosolution') {
                $queryBuilder->andWhere('eur.id_eurosolution = :id_eurosolution');
                $queryBuilder->setParameter('id_eurosolution', $filterValue);
            }
        }

        // Filtro per id_eurosolution
        if (isset($params['filter']['id_eurosolution'])) {
            $queryBuilder->andWhere('eur.id_eurosolution = :id_eurosolution')
                ->setParameter('id_eurosolution', $params['filter']['id_eurosolution']);
        }
    }

    public function hookActionOrderGridDefinitionModifier(&$params)
    {
        $params = $this->customBadge($params, 'osname');
    }

    public function hookActionOrderGridQueryBuilderModifier(&$params)
    {
        /** @var QueryBuilder $searchQueryBuilder */
        $searchQueryBuilder = $params['search_query_builder'];
        /** @var CustomerFilters $searchCriteria */
        $searchCriteria = $params['search_criteria'];
        $searchQueryBuilder->addSelect('eur.id_eurosolution');
        $searchQueryBuilder->leftJoin('o', _DB_PREFIX_ . 'customer_eurosolution', 'eur', 'o.id_customer = eur.id_customer');

        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
            if ($filterName == 'id_eurosolution') {
                $searchQueryBuilder->andWhere('eur.id_eurosolution = :id_eurosolution');
                $searchQueryBuilder->setParameter('id_eurosolution', $filterValue);
            }
        }

        $params['search_query_builder'] = $searchQueryBuilder;
        $params['search_criteria'] = $searchCriteria;
    }

    public function hookActionGetAdminToolbarButtons($params)
    {
        // Add a new button
    }

    public function hookActionGetAdminOrderButtons($params)
    {
        // Add a new button
    }

    public function hookActionCustomerFormDataProviderData(array $params)
    {
        $customerId = $params['id'];
        if ($customerId) {
            $customerEurosolution = new ModelCustomerEurosolution($customerId);
            $params['data']['id_eurosolution'] = $customerEurosolution->id_eurosolution;
        }
    }

    public function hookActionAfterCreateCustomerFormHandler(array $params)
    {
        $customerId = $params['id'];
        $idEurosolution = $params['form_data']['id_eurosolution'];
        $model = new ModelCustomerEurosolution();
        $model->id_customer = $customerId;
        $model->id_eurosolution = $idEurosolution;
        $model->id_employee = (int) $this->context->employee->id;
        $model->date_add = date('Y-m-d H:i:s');
        $model->add();
    }

    public function hookActionAfterUpdateCustomerFormHandler(array $params)
    {
        $customerId = $params['id'];
        $idEurosolution = $params['form_data']['id_eurosolution'];
        $model = new ModelCustomerEurosolution($customerId);
        $model->id_eurosolution = $idEurosolution;
        $model->id_employee = (int) $this->context->employee->id;
        $model->date_upd = date('Y-m-d H:i:s');
        if (Validate::isLoadedObject($model)) {
            $model->update();
        } else {
            $model->add();
        }
    }

    public function uninstall()
    {
        $installMenu = new InstallMenu($this);

        return parent::uninstall()
            && $installMenu->uninstallMenu('AdminMpEurosolution');
    }
}
