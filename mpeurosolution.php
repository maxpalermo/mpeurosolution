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
use MpSoft\MpEurosolution\Install\TableGenerator;
use MpSoft\MpEurosolution\Models\ModelCustomerEurosolution;
use PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinitionInterface;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use PrestaShop\PrestaShop\Core\Search\Filters\CustomerFilters;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;

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
                break;
            case 'displayAdminCustomers':
                return $this->hookDisplayAdminCustomers($configuration);
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
                    'MPEUROSOLUTION_adminAjaxURL' => $this->context->link->getModuleLink('mpeurosolution', 'FetchAsync'),
                    'MPEUROSOLUTION_employeeId' => (int) $this->context->employee->id,
                    'MPEUROSOLUTION_orderId' => (int) Tools::getValue('id_order'),
                    'MPEUROSOLUTION_customerId' => (int) Tools::getValue('id_customer'),
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
        $installTable = new TableGenerator($this);

        $hooks = [
            'actionObjectCustomerUpdateBefore',
            'actionObjectCustomerAddAfter',
            'actionObjectCustomerUpdateAfter',
            'actionCustomerGridDefinitionModifier',
            'actionCustomerGridQueryBuilderModifier',
            'actionCustomerFormDataProviderData',
            'actionAfterCreateCustomerFormHandler',
            'actionAfterUpdateCustomerFormHandler',
            'actionCustomerFormBuilderModifier',
            'actionOrderGridDefinitionModifier',
            'actionOrderGridQueryBuilderModifier',
            'actionAdminControllerSetMedia',
            'actionGetAdminToolbarButtons',
            'actionGetAdminOrderButtons',
            'displayAdminOrderMain',
            'displayAdminOrderSide',
            'displayAdminOrderTop',
            'displayAdminCustomers',
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
            && $installTable->createTablesFromModel(ModelCustomerEurosolution::$definition);
    }

    public function uninstall()
    {
        $installMenu = new InstallMenu($this);

        return parent::uninstall()
            && $installMenu->uninstallMenu('AdminMpEurosolution');
    }

    public function hookActionObjectCustomerAddAfter($params)
    {
        // nothing
    }

    public function hookActionObjectCustomerUpdateAfter($params)
    {
        // Nothing
    }

    /**
     * Hook eseguito PRIMA che un oggetto Customer venga aggiornato.
     * Usato qui per salvare dati custom da campi non mappati nel form.
     *
     * @param array $params
     *
     * @return bool Sempre true, altrimenti potrebbe bloccare l'update.
     *              Gestire errori internamente.
     */
    public function hookActionObjectCustomerUpdateBefore(array $params)
    {
        $controller = Tools::getValue('controller');

        if (!preg_match('/^AdminCustomers/i', $controller)) {
            return true;
        }

        // 1. Recupera l'oggetto Customer (prima del salvataggio)
        /** @var Customer $customer */
        $customer = $params['object'];

        if (!Validate::isLoadedObject($customer)) {
            // Dovrebbe essere sempre un oggetto valido qui, ma meglio controllare
            return true; // Non fare nulla se l'oggetto non è valido
        }

        $id_customer = (int) $customer->id;
        if ($id_customer <= 0) {
            // Non dovrebbe succedere in un UpdateBefore, ma per sicurezza...
            return true;
        }

        // 2. Recupera i dati inviati dal form usando Tools::getValue()
        //    USA I NOMI ESATTI DEI CAMPI come definiti nel FormBuilder!
        $customerValues = Tools::getValue('customer');
        $id_eurosolution = $customerValues['id_eurosolution']; // Es: '12345678901'

        // 3. Prepara i dati per il salvataggio (validazione/pulizia minima se necessaria)
        //    La validazione principale dovrebbe essere avvenuta con Symfony Form Constraints.
        //    Assicurati che i valori siano nei formati attesi per il DB.
        $dataToSave = [
            'id_eurosolution' => pSQL($id_eurosolution), // Usa pSQL per sicurezza base, anche se già validato
            'id_customer' => $id_customer, // Assicurati di avere id_customer nella tabella
        ];

        // 4. Salva i dati nella tua tabella custom (ps_customer_invoice)
        //    Questa logica fa un UPDATE se esiste già una riga per id_customer,
        //    altrimenti fa un INSERT.
        return $this->saveOrUpdateCustomerInvoiceData($id_customer, $dataToSave);
    }

    /**
     * Salva o aggiorna i dati nella tabella customer_invoice.
     *
     * @param int $id_customer
     * @param array $data Dati da salvare (colonna => valore), già "puliti".
     *
     * @return bool True se successo o se non c'erano dati da salvare, false in caso di errore DB grave.
     */
    private function saveOrUpdateCustomerInvoiceData(int $id_customer, array $data): bool
    {
        // Rimuovi l'id_customer dai dati per l'update, ma tienilo per l'insert
        $table = ModelCustomerEurosolution::$definition['table'];
        $updateData = $data;
        unset($updateData['id_customer']);

        // Controlla se esiste già una riga per questo cliente
        $existsQuery = new DbQuery();
        $existsQuery->select('id_customer'); // Seleziona la chiave primaria o un campo qualsiasi
        $existsQuery->from($table);
        $existsQuery->where('id_customer = ' . $id_customer);

        try {
            $exists = Db::getInstance()->getValue($existsQuery);

            if ($exists) {
                 // Esiste: Aggiorna la riga esistente
                 // La condizione WHERE è fondamentale!
                $result = Db::getInstance()->update($table, $updateData, 'id_customer = ' . $id_customer, 1); // '1' limita a 1 riga
            } else {
                 // Non esiste: Inserisci una nuova riga
                 // Assicurati che $data contenga 'id_customer'
                $result = Db::getInstance()->insert($table, $data, false, true, DbCore::INSERT_IGNORE); // INSERT_IGNORE può essere utile se c'è una gara (race condition)
            }
            // $result contiene true/false per l'operazione DB o numero righe per update? Controlla documentazione Db::update/insert
            // Qui assumiamo che se non ci sono eccezioni, l'operazione logica è andata a buon fine
            // Potresti voler controllare $result più in dettaglio se necessario.

            return $result; // Operazione tentata
        } catch (PrestaShopDatabaseException $e) {
            PrestaShopLogger::addLog('[mpeurosolution] Errore salvataggio dati fattura cliente ID ' . $id_customer . ': ' . $e->getMessage(), 3, null, 'mpeurosolution');

            return false; // Errore DB
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
            'mapped' => true,
            'constraints' => [
                new NotBlank([
                    'message' => $this->l('Il campo Eurosolution non può essere vuoto'),
                ]),
            ],
            'attr' => [
                'class' => 'form-control',
                'placeholder' => $this->l('Inserisci il codice Eurosolution'),
            ],
            'help' => $this->l('Inserisci il codice Eurosolution'),
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
        $MPEUROSOLUTION_customerId = $params['id'];
        if ($MPEUROSOLUTION_customerId) {
            $customerEurosolution = new ModelCustomerEurosolution($MPEUROSOLUTION_customerId);
            $params['data']['id_eurosolution'] = $customerEurosolution->id_eurosolution;
        }
    }

    public function hookActionAfterCreateCustomerFormHandler(array $params)
    {
        $MPEUROSOLUTION_customerId = $params['id'];
        $idEurosolution = $params['form_data']['id_eurosolution'];
        $model = new ModelCustomerEurosolution();
        $model->id_customer = $MPEUROSOLUTION_customerId;
        $model->id_eurosolution = $idEurosolution;
        $model->id_employee = (int) $this->context->employee->id;
        $model->date_add = date('Y-m-d H:i:s');
        $model->add();
    }

    public function hookActionAfterUpdateCustomerFormHandler(array $params)
    {
        $MPEUROSOLUTION_customerId = $params['id'];
        $idEurosolution = $params['form_data']['id_eurosolution'];
        $model = new ModelCustomerEurosolution($MPEUROSOLUTION_customerId);
        $model->id_eurosolution = $idEurosolution;
        $model->id_employee = (int) $this->context->employee->id;
        $model->date_upd = date('Y-m-d H:i:s');
        if (Validate::isLoadedObject($model)) {
            $model->update();
        } else {
            $model->add();
        }
    }

    public function hookDisplayAdminCustomers($params)
    {
        $controller_name = Tools::strtolower($this->context->controller->controller_name);
        if ($controller_name != 'admincustomers') {
            return;
        }

        $this->context->controller->confirmations[] = 'HOOK displayAdminCustomers';
        $fontSize = '1.2rem';
        $controller = $this->context->link->getModuleLink($this->name, 'FetchAsync');
        $id_customer = (int) Tools::getValue('id_customer');
        $eurosolutionModel = new ModelCustomerEurosolution($id_customer);
        if (Validate::isLoadedObject($eurosolutionModel)) {
            $eurosolutionId = $eurosolutionModel->id_eurosolution;
            $badgeColor = 'info';
        } else {
            $eurosolutionId = '--';
            $badgeColor = 'warning';
        }

        $script = <<<JS
            <template id="mpeurosolution-personal-info">
                <div class="row mb-1 eurosolution-container">
                    <div class="col-4 text-right">
                        Eurosolution
                    </div>
                    <div class="col-8">
                        <span class="eurosolutionId badge badge-{$badgeColor} rounded" style="font-size: {$fontSize}; border-radius: 0;">
                            <i class="material-icons">key</i>
                            {$eurosolutionId}
                        </span>
                    </div>
                </div>
            </template>

            <script type="text/javascript">
                const MPEUROSOLUTION_adminAjaxURL = "{$controller}";
                const MPEUROSOLUTION_employeeId = {$this->context->employee->id};
                const MPEUROSOLUTION_customerId = {$id_customer};
                const MPEUROSOLUTION_orderId = 0;

                //creo un nuovo custom event
                const MpEurosolutionReady = new CustomEvent('MpEurosolutionReady', {
                    detail: {
                        MPEUROSOLUTION_employeeId: MPEUROSOLUTION_employeeId??0,
                        MPEUROSOLUTION_customerId: MPEUROSOLUTION_customerId??0,
                        MPEUROSOLUTION_orderId: MPEUROSOLUTION_orderId??0,
                    },
                });
                document.dispatchEvent(MpEurosolutionReady);
            </script>
        JS;

        return $script;
    }
}
