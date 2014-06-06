<?php

/*
* The MIT License (MIT)
*
* Copyright (c) 2014 Guillaume ROY
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
* 
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
* 
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*
* @author Guillaume ROY
* @copyright  Guillaume ROY
*
*/

if (!defined('_PS_VERSION_'))
	exit;

class HolidaysMode extends Module
{
	public function __construct()
	{
		$this->name = 'holidaysmode';
		$this->tab = 'front_office_features';
		$this->version = 1.0;
		$this->author = 'Guillaume ROY';
		$this->need_instance = 0;

		$this->bootstrap = true;

		parent::__construct();	

		$this->displayName = $this->l('Holidays Mode');
		$this->description = $this->l('Set the current store in holidays mode.');
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
	}

	public function install()
	{
		return 
		parent::install() &&
		$this->registerHook('displayPaymentTop') &&
		$this->registerHook('actionValidateOrder') &&
		$this->registerHook('displayHeader') && 
		$this->initVariables();
	}
	
	public function uninstall()
	{
		return 
		parent::uninstall() &&
		$this->unregisterHook('displayPaymentTop') &&
		$this->unregisterHook('actionValidateOrder') &&
		$this->unregisterHook('displayHeader') && 
		$this->cleanVariables();
	}

	protected function initVariables()
	{
		$languages = Language::getLanguages(false);
		foreach ($languages as $lang)
		{
			$values['HOLIDAYSMODE_MESSAGE'][(int)$lang['id_lang']] = '';
			Configuration::updateValue('HOLIDAYSMODE_MESSAGE', $values['HOLIDAYSMODE_MESSAGE']);

			$values['HOLIDAYSMODE_EMAIL_BODY'][(int)$lang['id_lang']] = '';
			Configuration::updateValue('HOLIDAYSMODE_EMAIL_BODY', $values['HOLIDAYSMODE_EMAIL_BODY']);

			$values['HOLIDAYSMODE_EMAIL_OBJECT'][(int)$lang['id_lang']] = '';
			Configuration::updateValue('HOLIDAYSMODE_EMAIL_OBJECT', $values['HOLIDAYSMODE_EMAIL_OBJECT']);
		}

		Configuration::updateValue('HOLIDAYSMODE_ACTIVATE', 0);
		Configuration::updateValue('HOLIDAYSMODE_EMAIL', 0);
		Configuration::updateValue('HOLIDAYSMODE_ACTIVATE_MESSAGE', 0);

		return true;
	}

	protected function cleanVariables()
	{
		Configuration::deleteByName('HOLIDAYSMODE_ACTIVATE');
		Configuration::deleteByName('HOLIDAYSMODE_ACTIVATE_MESSAGE');
		Configuration::deleteByName('HOLIDAYSMODE_MESSAGE');
		Configuration::deleteByName('HOLIDAYSMODE_EMAIL_OBJECT');
		Configuration::deleteByName('HOLIDAYSMODE_EMAIL_BODY');
		Configuration::deleteByName('HOLIDAYSMODE_EMAIL');

		return true;
	}

	public function hookDisplayHeader($params)
	{
		if($this->moduleIsActivated())
		{
			$this->context->controller->addCSS($this->_path.'css/holidaysmode.css', 'all');
		}
	}

	public function hookDisplayPaymentTop($params)
	{
		if(!$this->moduleIsActivated())
			return '';

		if (!$this->isCached('blockbanner.tpl', $this->getCacheId()))
		{
			$holidaysmode_activate_message = intval(Configuration::get('HOLIDAYSMODE_ACTIVATE_MESSAGE'));
			$holidaysmode_message = strval(Configuration::get('HOLIDAYSMODE_MESSAGE', $this->context->language->id));

			if($holidaysmode_activate_message == 0 || empty($holidaysmode_message))
				return;

			$this->smarty->assign(array(
				'holidaysmode_message' => $holidaysmode_message
				));
		}

		return $this->display(__FILE__, 'holidaysmode.tpl', $this->getCacheId());
	}

	protected function moduleIsActivated()
	{
		if(intval(Configuration::get('HOLIDAYSMODE_ACTIVATE')) == 0)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	public function hookActionValidateOrder($params)
	{
		if(!$this->moduleIsActivated())
			return;

		$holidaysmode_activate_email = intval(Configuration::get('HOLIDAYSMODE_EMAIL'));
		$holidaysmode_email_body = strval(Configuration::get('HOLIDAYSMODE_EMAIL_BODY', $this->context->language->id));
		$holidaysmode_email_object = strval(Configuration::get('HOLIDAYSMODE_EMAIL_OBJECT', $this->context->language->id));

		if($holidaysmode_activate_email == 0 || empty($holidaysmode_email_body) || empty($holidaysmode_email_object))
			return;

		$customer = $params['customer'];

		$template_vars = array(
			'{lastname}' => $customer->lastname,
			'{firstname}' => $customer->firstname,
			'{HOLIDAYSMODE_EMAIL_BODY}' => $this->l($holidaysmode_email_body)
		);

		$email_to = $customer->email;

		return Mail::Send(
			$this->context->language->id, 
			'holidaysmode_order', 
			Mail::l($holidaysmode_email_object, $this->context->language->id), 
			$template_vars, 
			$email_to, 
			null, 
			strval(Configuration::get('PS_SHOP_EMAIL')), 
			strval(Configuration::get('PS_SHOP_NAME')), 
			null, 
			null, 
			dirname(__FILE__).'/mails/', 
			false, 
			$this->context->shop->id);
	}

	public function postProcess()
	{
		if (Tools::isSubmit('submit'.$this->name))
		{
			$languages = Language::getLanguages(false);
			$values = array();
			foreach ($languages as $lang)
			{
				$values['HOLIDAYSMODE_MESSAGE'][$lang['id_lang']] = strval(Tools::getValue('HOLIDAYSMODE_MESSAGE_'.$lang['id_lang']));
				$values['HOLIDAYSMODE_EMAIL_BODY'][$lang['id_lang']] = strval(Tools::getValue('HOLIDAYSMODE_EMAIL_BODY_'.$lang['id_lang']));
				$values['HOLIDAYSMODE_EMAIL_OBJECT'][$lang['id_lang']] = strval(Tools::getValue('HOLIDAYSMODE_EMAIL_OBJECT_'.$lang['id_lang']));
			}

			Configuration::updateValue('PS_CATALOG_MODE', intval(Tools::getValue('PS_CATALOG_MODE')));
			Configuration::updateValue('HOLIDAYSMODE_ACTIVATE', intval(Tools::getValue('HOLIDAYSMODE_ACTIVATE')));
			Configuration::updateValue('HOLIDAYSMODE_EMAIL', intval(Tools::getValue('HOLIDAYSMODE_EMAIL')));
			Configuration::updateValue('HOLIDAYSMODE_EMAIL_BODY', $values['HOLIDAYSMODE_EMAIL_BODY']);
			Configuration::updateValue('HOLIDAYSMODE_EMAIL_OBJECT', $values['HOLIDAYSMODE_EMAIL_OBJECT']);
			Configuration::updateValue('HOLIDAYSMODE_ACTIVATE_MESSAGE', intval(Tools::getValue('HOLIDAYSMODE_ACTIVATE_MESSAGE')));
			Configuration::updateValue('HOLIDAYSMODE_MESSAGE', $values['HOLIDAYSMODE_MESSAGE']);

			$this->_clearCache('holidaysmode.tpl');
			return $this->displayConfirmation($this->l('The settings have been updated.'));
		}

		return '';
	}

	public function getContent()
	{
		return $this->postProcess().$this->renderForm();
	}
	
	protected function renderForm()
	{
		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();

		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submit'.$this->name;
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'uri' => $this->getPathUri(),
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
			);

		return $helper->generateForm($this->buildForm());
	}

	protected function buildForm()
	{
		$fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('General'),
				'icon' => 'icon-cogs'
				),
			'input' => array(
				array(
					'name' => 'HOLIDAYSMODE_ACTIVATE',
					'type' => 'switch',
					'label' => $this->l('Activate'),
					'desc' => $this->l('Activate the holidays mode.'),
					'is_bool' => true,
					'values' => array(
						array(
							'id' => 'active_on',
							'value' => 1,
							'label' => $this->l('Enabled')
							),
						array(
							'id' => 'active_off',
							'value' => 0,
							'label' => $this->l('Disabled')
							)
						)
					)	
				),
			'submit' => array(
				'title' => $this->l('Save')
				)
			);
		
		$fields_form[1]['form'] = array(
			'legend' => array(
				'title' => $this->l('Options'),
				'icon' => 'icon-cogs'
				),
			'input' => array(
				array(
					'name' => 'PS_CATALOG_MODE',
					'type' => 'switch',
					'label' => $this->l('Catalog mode'),
					'desc' => $this->l('Disable orders functionalities.'),
					'is_bool' => true,
					'values' => array(
						array(
							'id' => 'active_on',
							'value' => 1,
							'label' => $this->l('Enabled')
							),
						array(
							'id' => 'active_off',
							'value' => 0,
							'label' => $this->l('Disabled')
							)
						)
					),
				array(
					'name' => 'HOLIDAYSMODE_ACTIVATE_MESSAGE',
					'type' => 'switch',
					'label' => $this->l('Display Message'),
					'desc' => $this->l('Display the message in the payment selection page.'),
					'is_bool' => true,
					'values' => array(
						array(
							'id' => 'active_on',
							'value' => 1,
							'label' => $this->l('Enabled')
						),
						array(
							'id' => 'active_off',
							'value' => 0,
							'label' => $this->l('Disabled')
						)
					)
				),
				array(
					'name' => 'HOLIDAYSMODE_MESSAGE',
					'type' => 'text',
					'label' => $this->l('Message'),
					'desc' => $this->l('The message to be displayed.'),
					'lang' => true
					),
				array(
					'name' => 'HOLIDAYSMODE_EMAIL',
					'type' => 'switch',
					'label' => $this->l('Send Email'),
					'desc' => $this->l('Notification email sent after the order validation.'),
					'is_bool' => true,
					'values' => array(
						array(
							'id' => 'active_on',
							'value' => 1,
							'label' => $this->l('Enabled')
						),
						array(
							'id' => 'active_off',
							'value' => 0,
							'label' => $this->l('Disabled')
						)
					)
				),
				array(
					'name' => 'HOLIDAYSMODE_EMAIL_OBJECT',
					'type' => 'text',
					'label' => $this->l('Email Object'),
					'lang' => true,
					'desc' => $this->l('Email object. Used in html and text mails.')
					),
				array(
					'name' => 'HOLIDAYSMODE_EMAIL_BODY',
					'type' => 'text',
					'label' => $this->l('Email Body'),
					'lang' => true,
					'desc' => $this->l('Email body without the header and footer. Used in html and text mails.')
					)
			),
			'submit' => array(
				'title' => $this->l('Save')
			)
		);

		return $fields_form;
	}

	protected function getConfigFieldsValues()
	{
		$languages = Language::getLanguages(false);
		$fields = array();

		foreach ($languages as $lang)
		{
			$fields['HOLIDAYSMODE_MESSAGE'][$lang['id_lang']] = strval(Tools::getValue('HOLIDAYSMODE_MESSAGE_'.$lang['id_lang'], Configuration::get('HOLIDAYSMODE_MESSAGE', $lang['id_lang'])));
			$fields['HOLIDAYSMODE_EMAIL_BODY'][$lang['id_lang']] = strval(Tools::getValue('HOLIDAYSMODE_EMAIL_BODY_'.$lang['id_lang'], Configuration::get('HOLIDAYSMODE_EMAIL_BODY', $lang['id_lang'])));
			$fields['HOLIDAYSMODE_EMAIL_OBJECT'][$lang['id_lang']] = strval(Tools::getValue('HOLIDAYSMODE_EMAIL_OBJECT_'.$lang['id_lang'], Configuration::get('HOLIDAYSMODE_EMAIL_OBJECT', $lang['id_lang'])));
		}

		return array(
			'HOLIDAYSMODE_ACTIVATE_MESSAGE' => intval(Tools::getValue('HOLIDAYSMODE_ACTIVATE_MESSAGE', Configuration::get('HOLIDAYSMODE_ACTIVATE_MESSAGE'))),
			'HOLIDAYSMODE_ACTIVATE' => intval(Tools::getValue('HOLIDAYSMODE_ACTIVATE', Configuration::get('HOLIDAYSMODE_ACTIVATE'))),
			'HOLIDAYSMODE_MESSAGE' => $fields['HOLIDAYSMODE_MESSAGE'],
			'HOLIDAYSMODE_EMAIL_BODY' => $fields['HOLIDAYSMODE_EMAIL_BODY'],
			'HOLIDAYSMODE_EMAIL_OBJECT' => $fields['HOLIDAYSMODE_EMAIL_OBJECT'],
			'HOLIDAYSMODE_EMAIL' => intval(Tools::getValue('HOLIDAYSMODE_EMAIL', Configuration::get('HOLIDAYSMODE_EMAIL'))),
			'PS_CATALOG_MODE' => intval(Tools::getValue('PS_CATALOG_MODE', Configuration::get('PS_CATALOG_MODE')))
			);
	}
}
