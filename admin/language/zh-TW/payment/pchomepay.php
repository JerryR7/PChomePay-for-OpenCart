<?php
/**
 * Created by PhpStorm.
 * User: Jerry
 * Date: 2017/11/21
 * Time: 上午10:25
 */

// Heading
$_['heading_title'] = 'PChomePay 付款';

// Text
$_['text_payment'] = '付款(Payment)';
$_['text_success'] = '修改成功!';
$_['text_edit'] = '編輯 PChomePay 付款';
$_['text_pchomepay'] = '<a target="_BLANK" href="https://www.pchomepay.com.tw/"><img src="view/image/payment/pchomepay.png" alt="PChomePay Website Payment" title="PChomePay Website Payment iFrame" style="border: 1px solid #EEEEEE;" /></a>';
$_['text_all_zones'] = 'All Zones';
$_['text_card'] = '信用卡';
$_['text_atm'] = 'ATM';
$_['text_each'] = '銀行支付';
$_['text_acct'] = '支付連餘額付款';
$_['text_card_0'] = '一次付清';
$_['text_card_3'] = '3 期';
$_['text_card_6'] = '6 期';
$_['text_card_12'] = '12 期';

// Entry
$_['entry_status'] = '狀態';
$_['entry_appid'] = 'APP ID';
$_['entry_secret'] = 'SECRET';
$_['entry_sandbox_secret'] = 'SECRET for SandBox';
$_['entry_test'] = 'Sandbox 測試模式';
$_['entry_debug'] = '除錯模式';
$_['entry_geo_zone'] = 'Geo Zone';
$_['entry_sort_order'] = 'Sort Order';
$_['entry_payment_methods'] = '付款方式';
$_['entry_card_installment'] = '信用卡分期';
$_['entry_atm_expiredate'] = 'ATM 虛擬帳號繳費期限';
$_['entry_cover_transfee'] = 'Inter-bank Transfer Fee';
$_['entry_card_last_number'] = '記錄信用卡末四碼';

// Help
$_['help_test'] = 'Use the live or testing (sandbox) gateway server to process transactions?';
$_['help_debug'] = 'Logs additional information to the system log';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify payment PChomePay!';
$_['error_appid'] = 'APP ID 必填!';
$_['error_secret'] = 'SECRET 必填!';
$_['error_sandbox_secret'] = 'SandBox SECRET 必填!';
$_['error_atm_expiredate_required'] = 'ATM 虛擬帳號繳費期限必填!';
$_['error_atm_expiredate_number'] = 'ATM 虛擬帳號繳費期限應為 1~5 天';
$_['error_cover_transfee'] = 'Inter-bank Transfer Fee 必填!';
$_['error_payment_methods'] = '付款方式 必填!';