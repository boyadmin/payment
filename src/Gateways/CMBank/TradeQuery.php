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
use Payment\Payment;

/**
 * @package Payment\Gateways\CMBank
 * @author  : Leo
 * @email   : dayugog@gmail.com
 * @date    : 2019/11/27 7:16 PM
 * @version : 1.0.0
 * @desc    : 查询单笔订单: 查询支付订单的状态等信息。商户未收到支付结果通知的情况下，如需确认订单状态，可以通过单笔订单查询接口查询订单状态。
 **/
class TradeQuery extends CMBaseObject implements IGatewayRequest
{
    const ONLINE_METHOD = 'https://payment.ebank.cmbchina.com/NetPayment/BaseHttp.dll?QuerySingleOrder';

    const SANDBOX_METHOD = 'http://121.15.180.66:801/NetPayment_dl/BaseHttp.dll?QuerySingleOrder';

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
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    protected function getRequestParams(array $requestParams)
    {
        $nowTime  = time();
        $orderDate = $requestParams['order_date'] ?? 0;
        if (empty($orderDate)) {
            throw new GatewayException('must have order date, format:[yyyyMMdd]', Payment::PARAMS_ERR);
        }
        $orderDate = date('Ymd', $orderDate);

        $params = [
            'dateTime'     => date('YmdHis', $nowTime),
            'branchNo'     => self::$config->get('branch_no', ''),
            'merchantNo'   => self::$config->get('mch_id', ''),
            'type'         => $requestParams['type'] ?? 'A',
            'bankSerialNo' => $requestParams['bank_serial_no'] ?? '', // 银行订单流水号,type=A时必填
            'date'         => $orderDate, // 商户订单日期,格式：yyyyMMdd
            'orderNo'      => $requestParams['order_no'] ?? '', // type=B时必填商户订单号
            'operatorNo'   => $requestParams['operator_id'] ?? '',
        ];

        return $params;
    }
}
