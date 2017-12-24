<?php
/**
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
 *  @license   MIT license
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Phpist_Github extends Module
{
	// define constants to be used throughout the module
	const PHPIST_GITHUB_ACCOUNT = 'PHPIST_GITHUB_ACCOUNT';
	const PHPIST_GITHUB_REPO = 'PHPIST_GITHUB_REPO';
	const PHPIST_GITHUB_COMMITS = 'PHPIST_GITHUB_COMMITS';
	
    private $_html = '';

	public function __construct()
    {
        $this->name = 'phpist_github';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Yassine Belkaid - PHPIST';
        $this->need_instance = 0;
        $this->secure_key = Tools::encrypt($this->name);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Github commits API listing');
        $this->description = $this->l('A module which allows to display a list of commits from a chosen Github repo.');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.1.17');
    }

    /**
     * @see Module::install()
     *
     */
    public function install()
    {
        if (parent::install()
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayHome')
        ) {
        	// add default configuration
            Configuration::updateValue(self::PHPIST_GITHUB_ACCOUNT, 'PrestaShop');
            Configuration::updateValue(self::PHPIST_GITHUB_REPO, 'PrestaShop');
            Configuration::updateValue(self::PHPIST_GITHUB_COMMITS, 10);

            return true;
        }

        return false;
    }

    /**
     * @see Module::uninstall()
     *
     */
    public function uninstall()
    {
        if (parent::uninstall()) {
            /* Unset configuration */
            Configuration::deleteByName(self::PHPIST_GITHUB_ACCOUNT);
            Configuration::deleteByName(self::PHPIST_GITHUB_REPO);
            Configuration::deleteByName(self::PHPIST_GITHUB_COMMITS);

            return true;
        }
           
        return false;
    }

    public function getContent()
    {
        // Validate & process
        if (Tools::isSubmit('submitConfigurations')) {
            if ($this->_postValidation()) {
                $this->_postProcess();
            }
        }

        return $this->_html . $this->renderForm();
    }

    /**
     * Create configration form
     *
     */
    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Github Settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Github account'),
                        'class' => 'fixed-width-lg',
                        'name' => self::PHPIST_GITHUB_ACCOUNT,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Github repo'),
                        'class' => 'fixed-width-lg',
                        'name' => self::PHPIST_GITHUB_REPO,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Number of commits'),
                        'class' => 'fixed-width-lg',
                        'name' => self::PHPIST_GITHUB_COMMITS
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitConfigurations';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            self::PHPIST_GITHUB_ACCOUNT => Configuration::get(self::PHPIST_GITHUB_ACCOUNT),
            self::PHPIST_GITHUB_REPO => Configuration::get(self::PHPIST_GITHUB_REPO),
            self::PHPIST_GITHUB_COMMITS => (int)Configuration::get(self::PHPIST_GITHUB_COMMITS),
        );
    }

    public function hookDisplayBackOfficeHeader($params)
    {
    	// run script only on this configuration page.
        if (!Tools::getValue('configure') || Tools::getValue('configure') != $this->name) {
            return;
        }

        $this->context->controller->addJS($this->_path .'views/js/pg_bk.js');
        Media::addJsDef(array(
        	'pg_mum_warning_msg' => $this->l('Only numbers are allowed!')
        ));
    }

    public function hookDisplayHeader($params)
    {
    	// only serve the css file at homepage
    	if ($this->context->controller->php_self != 'index') {
    		return;
    	}

        $this->context->controller->addCSS($this->_path .'views/css/pg_list.css');
    }

    public function hookDisplayHome($params)
    {
        $cacheId = $this->name .'::hookDisplayHome';

        // cache template for better performance
        if (!$this->isCached('commits_list.tpl', $this->getCacheId($cacheId))) {
            $commits    = array();
            $autoloader = __DIR__ .'/vendor/autoload.php';
            $configs = $this->getConfigFieldsValues();

            if (file_exists($autoloader)) {
                // call the composer autoload file to access to the Github client
            	require_once $autoloader;

            	try {
	            	$client = new \Github\Client();
	            	$commits = $client->api('repo')->commits()->all($configs[self::PHPIST_GITHUB_ACCOUNT], $configs[self::PHPIST_GITHUB_REPO], array('sha' => 'master', 'per_page' => (int)$configs[self::PHPIST_GITHUB_COMMITS]));
            	} catch (Github\Exception\RuntimeException $e) {
            		PrestaShopLogger::addLog('Github client: '. (string)$e->getMessage(), 3, $e->getCode());
            		return false;
            	}

            	$commits = $this->commitPresenter($commits);
            }

            $this->context->smarty->assign(array(
                'commits' => $commits,
                'commits_number' => (int)$configs[self::PHPIST_GITHUB_COMMITS],
            ));
        }

        return $this->display(__FILE__, 'commits_list.tpl', $this->getCacheId($cacheId));
    }

    /**
     * Validate configuration data
     *
     * @return boolean
     */
    private function _postValidation()
    {
        $errors = array();

        // Validatr configurations
        if (!Validate::isGenericName(Tools::getValue(self::PHPIST_GITHUB_ACCOUNT))) {
            $errors[] = $this->l('Invalid Github account name.');
        }

        if (!Validate::isGenericName(Tools::getValue(self::PHPIST_GITHUB_REPO))) {
            $errors[] = $this->l('Invalid Github repository.');
        }

        if (!Validate::isInt(Tools::getValue(self::PHPIST_GITHUB_COMMITS)) || (int)Tools::getValue(self::PHPIST_GITHUB_COMMITS) <= 0) {
            $errors[] = $this->l('Invalid number of commits, please provide a valid number.');
        }

        // Display errors if needed
        if (count($errors)) {
            foreach ($errors as $error) {
                $this->_html .= $this->displayError($error);
            }

            return false;
        }

        // validation is ok
        return true;
    }

    /**
     * Process configuration data to be updated
     *
     * @return void
     */
    private function _postProcess()
    {
        $res = true;
        $res &= Configuration::updateValue(self::PHPIST_GITHUB_ACCOUNT, Tools::getValue(self::PHPIST_GITHUB_ACCOUNT));
        $res &= Configuration::updateValue(self::PHPIST_GITHUB_REPO, Tools::getValue(self::PHPIST_GITHUB_REPO));
        $res &= Configuration::updateValue(self::PHPIST_GITHUB_COMMITS, (int)Tools::getValue(self::PHPIST_GITHUB_COMMITS));

        if (!$res) {
            $this->_html .= $this->displayError($this->l('The configuration could not be updated.'));
        } else {
	        // clear template cache
        	$this->_clearCache('commits_list.tpl');
            $this->_html .= $this->displayConfirmation($this->l('The configuration has been successfully updated.'));
        }
    }

    /**
     * Present commits data to be used in displaying content
     * 
     * @param array $commits  List of returned commits from the Github API
     *
     * @return array
     */
    public function commitPresenter($commits = array())
    {
    	$presentCommits = array();
    	if (!is_array($commits) || !count($commits)) {
    		return $presentCommits;
    	}

    	foreach ($commits as $commit) {
    		$commit_date = new DateTime($commit['commit']['author']['date']);
			$presentCommits[] = array(
				'message'     => $commit['commit']['message'],
				'commit_url'  => $commit['html_url'],
				'author'      => $commit['author']['login'],
				'author_url'  => $commit['author']['html_url'],
				'commit_date' => $commit_date->format('d/m/Y'),
			);
		}

		return $presentCommits;
    }
}