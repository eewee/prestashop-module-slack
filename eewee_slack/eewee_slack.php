<?php
/**
 * 2016-2017 EEWEE
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
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
 *  @author    PrestaShop SA <prestashop@eewee.fr>
 *  @copyright 2016-2017 EEWEE
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

//require_once('libs/slack/xxx.php');
require_once _PS_MODULE_DIR_.'eewee_slack/classes/EeweeslackLogModel.php';
require_once _PS_MODULE_DIR_.'eewee_slack/classes/EeweeslackSlackModel.php';

/**
 * Class Eewee_Slack
 */
class Eewee_Slack extends Module implements WidgetInterface
{
    /**
     * @var string
     */
    protected $html = '';

    public function __construct()
    {
        $this->name = 'eewee_slack';
        $this->author = 'eewee';
        $this->tab = 'front_office_features';
        $this->need_instance = 0;
        $this->version = '1.0';
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->_directory = dirname(__FILE__);

        parent::__construct();

        $this->displayName = $this->getTranslator()->trans('Module slack for PrestaShop v1.7', array(), 'Modules.EeweeSlack.Admin');
        $this->description = $this->getTranslator()->trans('Add Slack notifications for PrestaShop actions.', array(), 'Modules.EeweeSlack.Admin');

        $this->error = false;
        $this->valid = false;
    }

    /**
     * Install
     * @return bool
     * @throws PrestaShopException
     */
    public function install()
    {
        require_once(dirname(__FILE__) . '/sql/install.php');

        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);

        if (!parent::install() ||
            !$this->registerHook('actionValidateOrder') ||
            !$this->registerHook('actionCustomerAccountAdd') ||

            !Configuration::updateValue('EEWEE_SLACK_WEBHOOK_URL', '') ||
            !Configuration::updateValue('EEWEE_SLACK_CHANNEL_DEFAULT', '') ||
            !Configuration::updateValue('EEWEE_SLACK_NOTIF_ORDER_NEW', 1) ||
            !Configuration::updateValue('EEWEE_SLACK_NOTIF_CUSTOMER_NEW', 1)
        ) {
            return false;
        }
        return true;
    }

    /**
     * Uninstall
     * @return bool
     */
    public function uninstall()
    {
        require_once(dirname(__FILE__) . '/sql/uninstall.php');

        if (!parent::uninstall() ||
            !Configuration::deleteByName('EEWEE_SLACK_WEBHOOK_URL') ||
            !Configuration::deleteByName('EEWEE_SLACK_CHANNEL_DEFAULT') ||
            !Configuration::deleteByName('EEWEE_SLACK_NOTIF_ORDER_NEW') ||
            !Configuration::deleteByName('EEWEE_SLACK_NOTIF_CUSTOMER_NEW')
        ) {
            return false;
        }
        return true;
    }

    /**
     * Get Slack webhook url
     * @return bool|url $res
     */
    static public function getSlackWebhookUrl()
    {
        $res = Configuration::get('EEWEE_SLACK_WEBHOOK_URL');
        if (isset($res) && !empty($res)) {
            return $res;
        }
        return false;
    }

    /**
     * Get Slack channel default
     * @return bool|string $res
     */
    static public function getSlackChannelDefault()
    {
        $res = Configuration::get('EEWEE_SLACK_CHANNEL_DEFAULT');
        if (isset($res) && !empty($res)) {
            return $res;
        }
        return false;
    }

    /**
     * Content
     * @return string
     */
    public function getContent()
    {
        // INIT
        $output = null;

        // Form submitted
        if (Tools::isSubmit('submit' . $this->name)) {
            $webhookUrl = strval(Tools::getValue('EEWEE_SLACK_WEBHOOK_URL'));
            $channelDefault = strval(Tools::getValue('EEWEE_SLACK_CHANNEL_DEFAULT'));
            $notifOrderNew = strval(Tools::getValue('EEWEE_SLACK_NOTIF_ORDER_NEW'));
            $notifCustomerNew = strval(Tools::getValue('EEWEE_SLACK_NOTIF_CUSTOMER_NEW'));


            if (!$webhookUrl || empty($webhookUrl) || !Validate::isUrl($webhookUrl)) {
                $output .= $this->displayError($this->l('Invalid webhook url'));
            } elseif (!$channelDefault || empty($channelDefault)) {
                $output .= $this->displayError($this->l('Invalid channel default'));
            } else {
                Configuration::updateValue('EEWEE_SLACK_WEBHOOK_URL', $webhookUrl);
                Configuration::updateValue('EEWEE_SLACK_CHANNEL_DEFAULT', $channelDefault);
                Configuration::updateValue('EEWEE_SLACK_NOTIF_ORDER_NEW', $notifOrderNew);
                Configuration::updateValue('EEWEE_SLACK_NOTIF_CUSTOMER_NEW', $notifCustomerNew);

                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        // Informations Slack
        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->displayForm();
    }

    /**
     * Create form with helperForm : Add API informations
     * More : http://doc.prestashop.com/display/PS16/Using+the+HelperForm+class#UsingtheHelperFormclass-Selector
     * @return string
     */
    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Slack informations'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Webhook URL'),
                    'name' => 'EEWEE_SLACK_WEBHOOK_URL',
                    'required' => true,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Channel default'),
                    'hint' => $this->l('Without #'),
                    'name' => 'EEWEE_SLACK_CHANNEL_DEFAULT',
                    'required' => true,
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Notification new order'),
                    'name' => 'EEWEE_SLACK_NOTIF_ORDER_NEW',
                    'is_bool' => true,
                    'hint' => $this->l('For active Slack notification when new order PrestaShop'),
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Enabled'),
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('Disabled'),
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Notification new customer'),
                    'name' => 'EEWEE_SLACK_NOTIF_CUSTOMER_NEW',
                    'is_bool' => true,
                    'hint' => $this->l('For active Slack notification when new customer PrestaShop'),
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Enabled'),
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('Disabled'),
                        )
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                    '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );
        // fields_value 01
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),           // Load current value
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm($fields_form);
    }

    /**
     * Load values
     * @return array
     */
    public function getConfigFieldsValues()
    {
        return array(
            'EEWEE_SLACK_WEBHOOK_URL' => Tools::getValue('EEWEE_SLACK_WEBHOOK_URL', Configuration::get('EEWEE_SLACK_WEBHOOK_URL')),
            'EEWEE_SLACK_CHANNEL_DEFAULT' => Tools::getValue('EEWEE_SLACK_CHANNEL_DEFAULT', Configuration::get('EEWEE_SLACK_CHANNEL_DEFAULT')),
            'EEWEE_SLACK_NOTIF_ORDER_NEW' => Tools::getValue('EEWEE_SLACK_NOTIF_ORDER_NEW', Configuration::get('EEWEE_SLACK_NOTIF_ORDER_NEW')),
            'EEWEE_SLACK_NOTIF_CUSTOMER_NEW' => Tools::getValue('EEWEE_SLACK_NOTIF_CUSTOMER_NEW', Configuration::get('EEWEE_SLACK_NOTIF_CUSTOMER_NEW')),
        );
    }

    /**
     * hookActionValidateOrder
     * @param $params
     */
    public function hookActionValidateOrder($params)
    {
        if (Configuration::get('EEWEE_SLACK_NOTIF_ORDER_NEW')) {
            // INIT
            $customer           = $params['customer']->firstname . ' ' . $params['customer']->lastname;
            $order_reference    = $params['order']->reference;
            $message            = $customer . ', Ref : ' . $order_reference;
	        $channel = Tools::getValue('EEWEE_SLACK_CHANNEL_DEFAULT', Configuration::get('EEWEE_SLACK_CHANNEL_DEFAULT'));
	        if (!isset($channel) || empty($channel)) {
		        $channel = "prestashop";
	        }

            // SLACK SEND
            $m_slack = new EeweeslackSlackModel();
            $m_slack->send($message, "Order", $channel);
        }
    }

    /**
     * hookActionCustomerAccountAdd
     * @param $params
     */
    public function hookActionCustomerAccountAdd($params)
    {
        if (Configuration::get('EEWEE_SLACK_NOTIF_CUSTOMER_NEW')) {
            // INIT
            $customer = $params['newCustomer']->firstname.' '.$params['newCustomer']->lastname.', '.$params['newCustomer']->email;
            if (isset($params['newCustomer']->company) && !empty($params['newCustomer']->company)) {
                $customer .= ' ('.$params['newCustomer']->company.')';
            }
            $message = $customer;
            $channel = Tools::getValue('EEWEE_SLACK_CHANNEL_DEFAULT', Configuration::get('EEWEE_SLACK_CHANNEL_DEFAULT'));
            if (!isset($channel) || empty($channel)) {
	            $channel = "prestashop";
            }

            // SLACK SEND
            $m_slack = new EeweeslackSlackModel();
            $m_slack->send($message, "Customer", $channel);
        }
    }

	/**
	 * renderWidget
	 * @param $hookName
	 * @param array $params
	 * @return mixed
	 */
    public function renderWidget($hookName, array $params)
    {

    }

	/**
	 * getWidgetVariables
	 * @param $hookName
	 * @param array $params
	 * @return array
	 */
    public function getWidgetVariables($hookName, array $params)
    {

    }

}
