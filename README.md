<h1 align="center">Payment使用文档</h1>


# JetBrains OS licenses

`payment` had been being developed with PhpStorm under the free JetBrains Open Source license(s) granted by JetBrains s.r.o., hence I would like to express by thanks here.

[![Stargazers over time](./jetbrains-variant-4.svg)](https://www.jetbrains.com/?from=ABC)


[![Software license][ico-license]](LICENSE)
[![Latest development][ico-version-dev]][link-packagist]
[![Monthly installs][ico-downloads-monthly]][link-downloads]

老版本文档：http://helei112g.github.io/payment

新版本文档如下


## Stargazers over time

[![Stargazers over time][starchart-cc]](https://starchart.cc/helei112g/payment)

-----

# 联系&打赏

[打赏名单](SUPPORT.md)

请大家使用时根据示例代码来，有bug直接提交 `issue`；**提供付费技术支持**。

<div style="margin:0 auto;">
    <p align="center" style="margin:0px;"><img width="60%" src="https://dayutalk.cn/img/pub-qr.jpeg?v=123"></p>
    <p align="center" style="margin:0px;"><img width="60%" src="https://dayutalk.cn/img/pay-qr.jpeg"></p>
</div>


# 目录

- [公告](#公告)
    - [重要通知](#重要通知)
    - [计划](#计划)
- [Payment解决什么问题](#Payment解决什么问题)
- [如何使用](#如何使用)
    - [安装](#安装)
    - [项目集成](#项目集成)
    - [设计支付系统](#设计支付系统)
    - [支持的接口](#支持的接口)
- [贡献指南](#贡献指南)
    - [代码设计](#代码设计)
    - [接入支付指南](#接入支付指南)
- [第三方文档](#第三方文档)
- [License](#License)

# 公告

第三方支付的一些重要更新提示，以及项目相关的计划信息。

## 重要通知

1. 2019-04: **提醒：微信CA证书进行了更新，请更新项目到最新版本。否则5月29日后，将无法支付**
> 官方公告： https://pay.weixin.qq.com/index.php/public/cms/content_detail?lang=zh&id=56602

## 计划

重构整个项目，doing... ...

**重构后的项目与 `4.x` 以前的版本不兼容，请使用者注意！**

# Payment解决什么问题

`Payment` 的目的是简化大家在对接主流第三方时需要频繁去阅读第三方文档，还经常遇到各种问题。`Payment` 将所有第三方的接口进行了合理的建模分类，对大家提供统一的接入入口，大家只需要关注自身业务并且支付系统设计上。

目前已经集成：支付宝、微信、招商绝大部分功能。也欢迎各位贡献代码。 [贡献指南](#贡献指南)


# 如何使用

## 安装

当前 `Payment` 项目仅支持 `PHP version > 7.0` 的版本，并且仅支持通过 `composer` 进行安装。

**需要 `PHP` 安装以下扩展：**

```txt
- ext-curl
- ext-mbstring
- ext-bcmath
- package-Guzzle
```

**composer安装方式：**

直接在命令行下安装：

```bash
composer require "riverslei/payment:*"
```

通过项目配置文件方式安装：

```yaml
"require": {
    "riverslei/payment": "*"
}
```


## 项目集成

按照上面的步骤完成安装后，即可在项目中使用。

对于整个过程，提供了唯一的入口类 `\Payment\Client`，每一个渠道，均只介绍 `APP支付` 与 `异步/同步通知` 该如何接入。会重点说明每个请求支持的参数。

**APP支付demo**

```php
$config = [
    // 配置信息，各个渠道的配置模板见对应子目录
];

// 请求参数，完整参数见具体表格
$payData = [
    'body'         => 'test body',
    'subject'      => 'test subject',
    'trade_no'     => 'trade no',// 自己实现生成
    'time_expire'  => time() + 600, // 表示必须 600s 内付款
    'amount'       => '5.52', // 微信沙箱模式，需要金额固定为3.01
    'return_param' => '123',
    'client_ip'    => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1', // 客户地址
];``

// 使用
try {
    $client = new \Payment\Client(\Payment\Client::WECHAT, $wxConfig);
    $res    = $client->pay(\Payment\Client::WX_CHANNEL_APP, $payData);
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();
    exit;
} catch (\Payment\Exceptions\GatewayException $e) {
    echo $e->getMessage();
    var_dump($e->getRaw());
    exit;
} catch (\Payment\Exceptions\ClassNotFoundException $e) {
    echo $e->getMessage();
    exit;
} catch (Exception $e) {
    echo $e->getMessage();
    exit;
}

```


**异步/同步通知**

```php
// 自己实现一个类，继承该接口
class TestNotify implements \Payment\Contracts\IPayNotify
{
    /**
     * 处理自己的业务逻辑，如更新交易状态、保存通知数据等等
     * @param string $channel 通知的渠道，如：支付宝、微信、招商
     * @param string $notifyType 通知的类型，如：支付、退款
     * @param string $notifyWay 通知的方式，如：异步 async，同步 sync
     * @param array $notifyData 通知的数据
     * @return bool
     */
    public function handle(
        string $channel,
        string $notifyType,
        string $notifyWay,
        array $notifyData
    ) {
        //var_dump($channel, $notifyType, $notifyWay, $notifyData);exit;
        return true;
    }
}

$config = [
    // 配置信息，各个渠道的配置模板见对应子目录
];

// 实例化继承了接口的类
$callback = new TestNotify();

try {
    $client = new \Payment\Client(\Payment\Client::ALIPAY, $config);
    $xml = $client->notify($callback);
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();
    exit;
} catch (\Payment\Exceptions\GatewayException $e) {
    echo $e->getMessage();
    exit;
} catch (\Payment\Exceptions\ClassNotFoundException $e) {
    echo $e->getMessage();
    exit;
} catch (Exception $e) {
     echo $e->getMessage();
     exit;
 }

```

从上面的例子简单总结下，所有的支持的能力，通过 `\Payment\Client` 对外暴露方法；所有需要的常量也在这个类中进行了定义。其次需要一个 `$config`，关于config的模板，在每个渠道下面去看。最后一个传入请求的参数，完整的参数会在每个渠道中列出来，需要说明的是这些参数名字根据第三方文档部分进行了改写。在使用的时候请注意。

### 支付宝

**配置文件模板**

```php
$config = [
    'use_sandbox' => true, // 是否使用沙盒模式

    'app_id'    => '2016073100130857',
    'sign_type' => 'RSA2', // RSA  RSA2


    // 支付宝公钥字符串
    'ali_public_key' => '',

    // 自己生成的密钥字符串
    'rsa_private_key' => '',

    'limit_pay' => [
        //'balance',// 余额
        //'moneyFund',// 余额宝
        //'debitCardExpress',// 	借记卡快捷
        //'creditCard',//信用卡
        //'creditCardExpress',// 信用卡快捷
        //'creditCardCartoon',//信用卡卡通
        //'credit_group',// 信用支付类型（包含信用卡卡通、信用卡快捷、花呗、花呗分期）
    ], // 用户不可用指定渠道支付当有多个渠道时用“,”分隔

    // 与业务相关参数
    'notify_url' => 'https://dayutalk.cn/notify/ali',
    'return_url' => 'https://dayutalk.cn',

    'return_raw' => false, // 在处理回调时，是否直接返回原始数据，默认为 true
];


```

### 微信

对于每一个微信支持的能力，并不是所有参数都支持了，有些参数绝大多数场景并不需要用到。如果确实需要请自行对源码进行修改。

**配置文件模板**

```php

$config = [
    'use_sandbox' => false, // 是否使用 微信支付仿真测试系统

    'app_id'       => 'wxxxxxxxx',  // 公众账号ID
    'sub_appid'    => 'wxxxxxxxx',  // 公众子商户账号ID
    'mch_id'       => '123123123', // 商户id
    'sub_mch_id'   => '123123123', // 子商户id
    'md5_key'      => '23423423dsaddasdas', // md5 秘钥
    'app_cert_pem' => 'apiclient_cert.pem',
    'app_key_pem'  => 'apiclient_key.pem',
    'sign_type'    => 'MD5', // MD5  HMAC-SHA256
    'limit_pay'    => [
        //'no_credit',
    ], // 指定不能使用信用卡支付   不传入，则均可使用
    'fee_type' => 'CNY', // 货币类型  当前仅支持该字段

    'notify_url' => 'https://dayutalk.cn/v1/notify/wx',

    'redirect_url' => 'https://dayutalk.cn/', // 如果是h5支付，可以设置该值，返回到指定页面

    'return_raw' => false, // 在处理回调时，是否直接返回原始数据，默认为true
];
```

#### 支付请求参数

字段 | 解释 | 必须
---|---|---
subject | 商品简单描述，该字段须严格按照规范传递，具体请见[参数规定](https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=4_2) | Y
body | 单品优惠字段(暂未上线) | Y
trade_no | 商户系统内部的订单号,32个字符内、可包含字母, 其他说明见[商户订单号](https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=4_2) | Y
amount | 订单总金额，单位为元 | Y
client_ip | 必须传正确的用户端IP,支持ipv4、ipv6格式，获取方式详见[获取用户ip指引](https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=15_5) | Y
device_info | 终端设备号(门店号或收银设备ID)，注意：PC网页或公众号内支付请传"WEB" | N
return_param | 附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据 | N
time_expire | 订单失效时间，时间戳 | N
goods_tag | 商品标记，代金券或立减优惠功能的参数，说明详见[代金券或立减优惠](https://pay.weixin.qq.com/wiki/doc/api/tools/sp_coupon.php?chapter=12_1) | N
scene_info | 该字段用于上报支付的场景信息，具体见微信文档 | N

使用时，自行使用上面的字段构建好一个数组，并传入到 `\Payment\Client` 实例对应的方法中。后面均是相同，不在重复。

#### 账单请求参数

字段 | 解释 | 必须
---|---|---
bill_date | 对账单日期 | Y
bill_type | ALL（默认值），返回当日所有订单信息（不含充值退款订单）SUCCESS，返回当日成功支付的订单（不含充值退款订单）REFUND，返回当日退款订单（不含充值退款订单） RECHARGE_REFUND，返回当日充值退款订单  | N

#### 关闭交易请求参数

字段 | 解释 | 必须
---|---|---
trade_no | 商户系统内部订单号，要求32个字符内，只能是数字、大小写字母_-|*@ ，且在同一个商户号下唯一。 | Y

#### 撤销交易请求参数

字段 | 解释 | 必须
---|---|---
trade_no | 商户系统内部的订单号,transaction_id、trade_no二选一，如果同时存在优先级：transaction_id> trade_no | Y
transaction_id | 微信的订单号，优先使用 | Y

#### 退款请求参数

字段 | 解释 | 必须
---|---|---
transaction_id | 微信生成的订单号，在支付通知中有返回 | Y
trade_no | 商户系统内部订单号，要求32个字符内，只能是数字、大小写字母_-|*@ ，且在同一个商户号下唯一。transaction_id、trade_no二选一，如果同时存在优先级：transaction_id> trade_no | Y
refund_no | 商户系统内部的退款单号，商户系统内部唯一，只能是数字、大小写字母_-|*@ ，同一退款单号多次请求只退一笔。 | Y
total_fee | 订单总金额，单位为元 | Y
refund_fee | 退款总金额，订单总金额，单位为元 | Y 
refund_desc | 若商户传入，会在下发给用户的退款消息中体现退款原因 | N
refund_account | 仅针对老资金流商户使用 | N

#### 退款查询请求参数

字段 | 解释 | 必须
---|---|---
transaction_id | 微信订单号查询的优先级是： refund_id > refund_no > transaction_id > trade_no | Y
trade_no | 商户系统内部订单号，要求32个字符内，只能是数字、大小写字母_-|*@ ，且在同一个商户号下唯一。 | Y
refund_no | 商户系统内部的退款单号，商户系统内部唯一，只能是数字、大小写字母_-|*@ ，同一退款单号多次请求只退一笔。 | Y
refund_id | 微信生成的退款单号，在申请退款接口有返回 | Y
offset | 偏移量，当部分退款次数超过10次时可使用，表示返回的查询结果从这个偏移量开始取记录 | N

#### 资金账单请求参数

字段 | 解释 | 必须
---|---|---
bill_date | 下载对账单的日期，格式：20140603 | Y
bill_type | 账单的资金来源账户：Basic  基本账户 Operation 运营账户 Fees 手续费账户 | Y


#### 交易查询请求参数

字段 | 解释 | 必须
---|---|---
transaction_id | 微信的订单号，建议优先使用 | Y
trade_no | 商户系统内部订单号，要求32个字符内，只能是数字、大小写字母_-|*@ ，且在同一个商户号下唯一 | Y


#### 付款到零钱请求参数

字段 | 解释 | 必须
---|---|---
channel | 付款的渠道 bank:付款到银行；account:付款到账号 | Y
device_info | 微信支付分配的终端设备号 | N
trans_no | 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有其它字符) | Y
openid | 商户appid下，某用户的openid | Y
check_name | NO_CHECK：不校验真实姓名;FORCE_CHECK：强校验真实姓名 | Y
re_user_name | 收款用户真实姓名。如果check_name设置为FORCE_CHECK，则必填用户真实姓名 | O
amount | 企业付款金额，单位为元 | Y
desc | 企业付款备注，必填。注意：备注中的敏感词会被转成字符* | Y
client_ip | 该IP同在商户平台设置的IP白名单中的IP没有关联，该IP可传用户端或者服务端的IP。 | Y

#### 付款到银行请求参数

字段 | 解释 | 必须
---|---|---
channel | 付款的渠道 bank:付款到银行；account:付款到账号 | Y
trans_no | 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有其它字符) | Y
enc_bank_no | 收款方银行卡号（采用标准RSA算法，公钥由微信侧提供）,详见[获取RSA加密公钥API](https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=24_7) | Y
enc_true_name | 收款方用户名（采用标准RSA算法，公钥由微信侧提供） | Y
bank_code | 银行卡所在开户行编号,详见[银行编号列表](https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=24_4) | Y
amount | 企业付款金额，单位为元 | Y
desc | 企业付款到银行卡付款说明,即订单备注 | N

#### 付款到零钱/银行查询请求参数

字段 | 解释 | 必须
---|---|---
trans_no | 商户订单号，需保持唯一（只允许数字[0~9]或字母[A~Z]和[a~z]最短8位，最长32位） | Y


### 招商银行

**配置文件模板**

```php

$config = [
    'use_sandbox' => true, // 是否使用 招商测试系统

    'branch_no' => 'xxx',  // 商户分行号，4位数字
    'mch_id'    => 'xxxx', // 商户号，6位数字
    'mer_key'   => 'xxxxxx', // 秘钥16位，包含大小写字母 数字

    // 招商的公钥，建议每天凌晨2:15发起查询招行公钥请求更新公钥。
    'cmb_pub_key' => 'xxxxx',

    'op_pwd'    => 'xxxxx', // 操作员登录密码。
    'sign_type' => 'SHA-256', // 签名算法,固定为“SHA-256”
    'limit_pay' => [
        //'A',
    ], // 允许支付的卡类型,默认对支付卡种不做限制，储蓄卡和信用卡均可支付   A:储蓄卡支付，即禁止信用卡支付

    'notify_url' => 'https://dayutalk.cn/notify/cmb', // 支付成功的回调

    'sign_notify_url' => 'https://dayutalk.cn/notify/cmb', // 成功签约结果通知地址
    'sign_return_url' => 'https://dayutalk.cn', // 成功签约结果通知地址

    'return_url' => 'https://dayutalk.cn', // 如果是h5支付，可以设置该值，返回到指定页面

    'return_raw' => false, // 在处理回调时，是否直接返回原始数据，默认为true
];
```

## 设计支付系统

`Payment` 解决了对接第三方渠道的各种问题，但是一个合理的支付完整系统该如何设计？估计大家还有很多疑问。关于支付系统的设计大家可以参考该项目：https://github.com/skr-shop/manuals

这是我与小伙伴开源的另外一个关于电商的项目，里边对电商的各个模块设计进行了详细的描述。

## 支持的接口

对应到第三方的具体接口

### 支付宝

### 微信

支持 `普通商户与服务商两个版本`

- [付款码支付](https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_20&index=1)
- [JSAPI支付](https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_20&index=1)
- [Native支付](https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_20&index=1)
- [APP支付](https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_20&index=1)
- [H5支付](https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_20&index=1)
- [小程序支付](https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_20&index=1)
- [退款](https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_4&index=4)
- [关闭交易](https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_3&index=3)
- [撤销交易](https://pay.weixin.qq.com/wiki/doc/api/micropay.php?chapter=9_11&index=3)
- [交易查询](https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_2&index=2)
- [退款查询](https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_5&index=5)
- [企业转账查询](https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=24_3)
- [零钱转账查询](https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=14_3)
- [下载对账单](https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_6&index=6)
- [下载资金账单](https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_18&index=7)
- [企业转账](https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=24_2) 该接口还有些问题待处理
- [零钱转账](https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=14_2)


### 招商

# 贡献指南

## 代码设计

整个代码结构的设计

## 开发指南

接入一个新的能力该如何操作

#第三方文档

- [支付宝](https://docs.open.alipay.com/api_1)
- [微信](https://pay.weixin.qq.com/wiki/doc/api/index.html)
- [招商银行](http://openhome.cmbchina.com/paynew/pay/Home)

# License

The code for Payment is distributed under the terms of the MIT license (see [LICENSE](LICENSE)).


[ico-license]: https://img.shields.io/github/license/helei112g/payment.svg
[ico-version-dev]: https://img.shields.io/packagist/vpre/riverslei/payment.svg
[ico-downloads-monthly]: https://img.shields.io/packagist/dm/riverslei/payment.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/riverslei/payment
[link-downloads]: https://packagist.org/packages/riverslei/payment/stats
[starchart-cc]: https://starchart.cc/helei112g/payment.svg
