#轻推开发文档
轻推支持以轻应用/订阅号为载体，为企业提供身份验证、消息推送、通讯录管理、移动端SDK等接口。企业可以基于这些接口，将企业的系统、应用和服务接入轻推，为企业提供更多个性化的智能办公应用。

开发文档地址
https://www.qingtui.com/devdoc/ 

轻推支持轻应用和订阅号两种类型，区别如下：
轻应用：偏向于功能，可用于企业中含业务逻辑处理、功能操作等场景的工作，支持连接各种企业业务系统。 订阅号：偏向于内容，可用于企业文化建设、内部新闻、党建、员工培训等工作，支持连接各种为企业成员提供资讯的信息平台。 关于轻应用和订阅号的详细介绍，可以参见轻推帮助中心。https://qingtui.kf5.com/hc/

#安装
```
composer require qingtui/qingtui_api
```

#使用

```php
use Qingtui\QingtuiApi\QingtuiApi;
use Predis\Client;

$config = ['app_id' => 'xxx', 'secret' => 'xxx'];
$redis = new Client(
    [
        'host' => '127.0.0.1',
        'port' => 6379,
    ]
);
$obj = new QingtuiApi($config, $redis);

$obj->getAccessToken();
```