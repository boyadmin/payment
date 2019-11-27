<?php

/*
 * The file is part of the payment lib.
 *
 * (c) Leo <dayugog@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Payment\Gateways\CMBank;

use Payment\Contracts\IGatewayRequest;
use Payment\Exceptions\GatewayException;

/**
 * @package Payment\Gateways\Alipay
 * @author  : Leo
 * @email   : dayugog@gmail.com
 * @date    : 2019/3/28 10:21 PM
 * @version : 1.0.0
 * @desc    : 一网通支付: 客户首次一网通支付时，商户必须为客户生成一网通支付协议号，招行系统将引导客户先进行绑卡签约，再完成支付。
 *            非首次支付时，商户传送已签约的客户协议号，客户输入支付密码等信息后完成支付。
 **/
class Charge extends CMBaseObject implements IGatewayRequest
{
    const ONLINE_METHOD = 'https://netpay.cmbchina.com/netpayment/BaseHttp.dll?MB_EUserPay';

    const SANDBOX_METHOD = 'http://121.15.180.66:801/NetPayment/BaseHttp.dll?MB_EUserPay';

    /**
     * 获取第三方返回结果
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function request(array $requestParams)
    {
        // 初始 网关地址
        $this->setGatewayUrl(self::ONLINE_METHOD);
        if ($this->isSandbox) {
            $this->setGatewayUrl(self::SANDBOX_METHOD);
        }
    }

    /**
     * http://121.15.180.72/OpenAPI2/API/PWDPayAPI4.aspx
     * @param array $requestParams
     * @return mixed
     */
    protected function getRequestParams(array $requestParams)
    {
        $nowTime    = time();
        $timeExpire = $requestParams['time_expire'] ?? 0;
        $timeExpire = $timeExpire - $nowTime;
        if ($timeExpire < 3) {
            $timeExpire = 30; // 如果设置不合法，默认改为30
        }

        $params = [
            'dateTime'         => date('YmdHis', $nowTime),
            'branchNo'         => self::$config->get('branch_no', ''),
            'merchantNo'       => self::$config->get('mch_id', ''),
            'date'             => date('Ymd', $requestParams['date'] ?? $nowTime),
            'orderNo'          => $requestParams['order_no'] ?? '',
            'amount'           => $requestParams['amount'] ?? '', // 固定两位小数，最大11位整数
            'expireTimeSpan'   => $timeExpire,
            'payNoticeUrl'     => self::$config->get('notify_url', ''),
            'payNoticePara'    => $requestParams['return_param'] ?? '',
            'returnUrl'        => self::$config->get('return_url', ''),
            'clientIP'         => $requestParams['client_ip'] ?? '',
            'cardType'         => $requestParams['limit_pay'] ?? '', // A:储蓄卡支付，即禁止信用卡支付
            'agrNo'            => $requestParams['agr_no'] ?? '',
            'merchantSerialNo' => $requestParams['merchant_serial_no'] ?? '',
            'userID'           => $requestParams['user_id'] ?? '',
            'mobile'           => $requestParams['mobile'] ?? '',
            'lon'              => $requestParams['lon'] ?? '',
            'lat'              => $requestParams['lat'] ?? '',
            'riskLevel'        => $requestParams['risk_level'] ?? '',
            'signNoticeUrl'    => self::$config->get('sign_notify_url', ''),
            'signNoticePara'   => self::$config->get('return_param', ''),
            //'extendInfo' => '',
            //'extendInfoEncrypType' => '',
        ];

        return $params;
    }
}
