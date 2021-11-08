<?php

namespace qingtui\QingtuiApi;

use Exception;
use \Predis\Client;

/**
 * 轻推开发接口
 */
class QingtuiApi
{
    // AppID是轻应用/订阅号唯一识别标志
    private $appId;

    // AppSecret是给轻应用/订阅号分配的密钥
    private $appSecret;

    // 固定填写client_credential
    private $grantType = 'client_credential';
    private $redis;

    // 获取access_token url
    const GET_ACCESS_TOKEN_URL = 'https://open.qingtui.cn/v1/token';
    // 刷新 access_token及缓存,其中 refresh_token： 刷新Token，从获取access_token接口获得
    const REFRESH_ACCESS_TOKEN_URL = 'https://open.qingtui.cn/auth/autoRefreshToken';

    // 消息推送 请求接口地址
    // 消息推送-获取使用者-获取使用者列表
    const FOLLOWERS_UTL = 'https://open.qingtui.cn/v1/app/followers';
    // 消息推送-获取使用者-通过userid获取openid url
    const OPENID_GET_URL = 'https://open.qingtui.cn/team/member/openid/get';
    // 消息推送-发送消息-文字消息-单发文字消息
    const SEND_SINGLE_TEXT_MESSAGE_URL = 'https://open.qingtui.cn/v1/message/text/send/single';
    // 消息推送-发送消息-文字消息-群发文字消息
    const MASS_TEXT_MESSAGE_URL = 'https://open.qingtui.cn/v1/message/text/send/service';
    // 消息推送-发送消息-文字消息-给部分人发文字消息
    const SEND_TEXT_MESSAGE_PART_URL = 'https://open.qingtui.cn/v1/message/text/send/mass';
    // 消息推送-发送消息-文字消息-发文字消息至群聊
    const SEND_TEXT_MESSAGE_GROUP_CHART_URL = 'https://open.qingtui.cn/v1/message/text/send/channel';
    // 消息推送-发送消息-图片消息-单发图片消息
    const SINGLE_PICTURE_MESSAGE_URL = 'https://open.qingtui.cn/v1/message/image/send/single';
    // 消息推送-发送消息-图片消息-群发图片消息
    const GROUP_PICTURE_MESSAGE_URL = 'https://open.qingtui.cn/v1/message/image/send/service';
    // 消息推送-发送消息-图片消息-给部分人发图片消息
    const SEND_PICTURE_MESSAGE_TO_SOME_PEOPLE_URL = 'https://open.qingtui.cn/v1/message/image/send/mass';
    // 消息推送-发送消息-图片消息-发图片消息至群聊
    const SEND_PICTURE_MESSAGE_TO_GROUP_CHART_URL = 'https://open.qingtui.cn/v1/message/image/send/channel';
    // 消息推送-发送消息-文本卡片消息-单发文本卡片消息
    const SEND_SINGLE_TEXT_CARD_MESSAGE_URL = 'https://open.qingtui.cn/v1/message/textCard/send/singl';
    // 消息推送-发送消息-文本卡片消息-给部分人发文本卡片消息
    const SEND_TEXT_CARD_MESSAGE_TO_SOME_PEOPLE_URL = 'https://open.qingtui.cn/v1/message/textCard/send/mass';
    // 消息推送-发送消息-文本卡片消息-发文本卡片消息至群聊
    const SEND_TEXT_CARD_MESSAGE_TO_GROUP_CHAT_URL = 'https://open.qingtui.cn/v1/message/textCard/send/channel';
    // 消息推送-发送消息-待办消息-单发待办消息
    const SEND_SINGLE_TO_DO_MESSAGE_URL = 'https://open.qingtui.cn/v1/message/process/send/single';
    // 消息推送-发送消息-待办消息-给部分人发待办消息
    const SEND_SINGLE_TO_DO_MESSAGE_TO_PART_URL = 'https://open.qingtui.cn/v1/message/process/send/mass';
    // 消息推送-发送消息-待办消息-待办消息置为已处理
    const SET_PENDING_MESSAGE_URL = 'https://open.qingtui.cn/v1/message/process/complete';
    // 通讯录管理
    // 通讯录管理-企业成员管理-获取企业内所有成员
    const GET_ALL_COMPANY_MEMBERS_URL = 'https://open.qingtui.cn/team/member/all/paged';
    // 通讯录管理-企业成员管理-创建成员
    const ADD_MEMBER_URL = 'https://open.qingtui.cn/team/member/create/single';
    // 通讯录管理-企业成员管理-删除成员
    const DELETE_MEMBER_URL = 'https://open.qingtui.cn/team/member/delete/single';
    // 通讯录管理-企业成员管理-更新成员
    const UPDATE_MEMBER_URL = 'https://open.qingtui.cn/team/member/update/single';
    // 通讯录管理-企业组织机构管理-获取企业Id
    const GET_COMPANY_ID_URL = 'https://open.qingtui.cn/team/domain/id/get';
    // 通讯录管理-企业组织机构管理-创建组织机构
    const ADD_ORG_URL = 'https://open.qingtui.cn/team/org/create';
    // 通讯录管理-企业组织机构管理-删除组织机构
    const DELETE_ORG_URL = 'https://open.qingtui.cn/team/org/delete';
    // 通讯录管理-企业组织机构管理-修改组织机构
    const MODIFY_ORG_URL = 'https://open.qingtui.cn/team/org/update';
    // 通讯录管理-企业组织机构管理-分页获取组织机构列表
    const GET_ORG_LIST_URL = 'https://open.qingtui.cn/team/org/paged';

    /**
     * 配置app_id、app_secret参数
     * QingtuiApi constructor.
     * @param $redis
     * @param $config
     */
    public function __construct($config, $redis)
    {
        if (isset($config['app_id'])) {
            $this->appId = $config['app_id'];
        }

        if (isset($config['secret'])) {
            $this->appSecret = $config['secret'];
        }
        $this->redis = $redis;
    }

    /**
     * 获取access token
     * @return mixed
     * @throws Exception
     */
    public function getAccessToken()
    {
        // 从缓存中读取access token
        $accessToken = $this->redis->get('qingtui_api_access_token');
        //如果文件为空，说明还未写入参数是第一次请求
        if (empty($accessToken)) {
            $input           = [];
            $input['url']    = self::GET_ACCESS_TOKEN_URL;
            $input['params'] = [
                'grant_type' => $this->grantType,
                'appid'      => $this->appId,
                'secret'     => $this->appSecret,
            ];
            $return          = $this->call($input);
            if (isset($return['errcode'])) {
                return $return;
            }
            $accessToken = $return['access_token'];
            $this->redis->set('qingtui_api_access_token', $accessToken);
            $this->redis->expire('qingtui_api_access_token', $return['expires_in']);
        }
        return $accessToken;
    }

    /**
     * 获取使用者列表
     * @param int $requestPage
     * @param int $pageSize
     * @return array|mixed
     * @throws Exception
     */
    public function getFollowers($requestPage = 1, $pageSize = 20)
    {
        $input           = [];
        $input['url']    = self::FOLLOWERS_UTL;
        $input['params'] = [
            'access_token' => $this->getAccessToken(),
            'page_size'    => $pageSize,
            'request_page' => $requestPage,
        ];
        $this->response($input);
    }

    /**
     * 通过 userid 获取openid
     * @param $userId
     * @param bool $isJson
     * @return false|mixed|string
     * @throws Exception
     */
    public function getOpenIdByUserId($userId, $isJson = true)
    {
        $input           = [];
        $input['url']    = self::OPENID_GET_URL;
        $input['params'] = [
            'access_token' => $this->getAccessToken(),
        ];
        if (isset($userId)) {
            $input['params']['user_id'] = $userId;
        }

        if ($isJson) {
            $this->response($input);
        } else {
            return $this->call($input);
        }
    }

    /**
     * 单发文字消息
     * @param $openId
     * @param $content
     * @return mixed
     * @throws Exception
     */
    public function sendSingleTextMessage($openId, $content)
    {
        if (empty($openId) || empty($content)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }

        $input           = [];
        $input['url']    = self::SEND_SINGLE_TEXT_MESSAGE_URL . '?access_token=' . $this->getAccessToken();
        $input['params'] = [
            'to_user' => $openId,
            'message' => [
                'content' => $content,
            ],
        ];

        $this->response($input, 'POST_JSON');
    }

    /**
     * 群发文字消息
     * @param $content
     * @return mixed
     * @throws Exception
     */
    public function massTextMessaging($content)
    {
        if (empty($content)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }

        $input           = [];
        $input['url']    = self::MASS_TEXT_MESSAGE_URL . '?access_token=' . $this->getAccessToken();
        $input['params'] = [
            'message' => [
                'content' => $content,
            ]
        ];
        $this->response($input, 'POST_JSON');
    }

    /**
     * 给部分人发文字消息
     * @param $openIds
     * @param $content
     * @return mixed
     * @throws Exception
     */
    public function sendTextMessagesPart($openIds, $content)
    {
        if (empty($openIds) || empty($content) || !is_array($openIds)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }

        $input           = [];
        $input['url']    = self::SEND_TEXT_MESSAGE_PART_URL . '?access_token=' . $this->getAccessToken();
        $input['params'] = [
            'to_users' => $openIds,
            'message'  => [
                'content' => $content,
            ]
        ];
        $this->response($input, 'POST_JSON');
    }

    /**
     * 发文字消息至群聊
     * @param $channelId
     * @param $content
     * @return mixed
     * @throws Exception
     */
    public function sendTextMessageToGroupChat($channelId, $content)
    {
        if (empty($channelId) || empty($content)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }

        $input           = [];
        $input['url']    = self::SEND_TEXT_MESSAGE_GROUP_CHART_URL . '?access_token=' . $this->getAccessToken();
        $input['params'] = [
            'channel_id' => $channelId,
            'message'    => [
                'content' => $content,
            ]
        ];
        $this->response($input, 'POST_JSON');
    }

    /**
     * 单发图片消息
     * @param $openId
     * @param $mediaId
     * @return mixed
     * @throws Exception
     */
    public function singlePictureMessage($openId, $mediaId)
    {
        if (empty($openId) || empty($mediaId)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }

        $input           = [];
        $input['url']    = self::SINGLE_PICTURE_MESSAGE_URL . '?access_token=' . $this->getAccessToken();
        $input['params'] = [
            'to_user' => $openId,
            'message' => [
                'media_id' => $mediaId,
            ]
        ];
        $this->response($input, 'POST_JSON');
    }

    /**
     * 群发图片消息
     * @param $mediaId
     * @return mixed
     * @throws Exception
     */
    public function groupPictureMessage($mediaId)
    {
        if (empty($mediaId)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }

        $input           = [];
        $input['url']    = self::GROUP_PICTURE_MESSAGE_URL . '?access_token=' . $this->getAccessToken();
        $input['params'] = [
            'message' => [
                'media_id' => $mediaId,
            ]
        ];
        $this->response($input, 'POST_JSON');
    }

    /**
     * 给部分人发图片消息
     * @param $openIds
     * @param $mediaId
     * @return array|mixed
     * @throws Exception
     */
    public function sendPictureMessageToPart($openIds, $mediaId)
    {
        if (empty($openIds) || empty($mediaId) || !is_array($openIds)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }

        $input           = [];
        $input['url']    = self::SEND_PICTURE_MESSAGE_TO_SOME_PEOPLE_URL . '?access_token=' . $this->getAccessToken();
        $input['params'] = [
            'to_users' => $openIds,
            'message'  => [
                'media_id' => $mediaId,
            ]
        ];
        $this->response($input, 'POST_JSON');
    }

    /**
     * 发图片消息至聊天群
     * @param $channelId
     * @param $mediaId
     * @return array|mixed
     * @throws Exception
     */
    public function sendPictureMessageToGroupChat($channelId, $mediaId)
    {
        if (empty($channelId) || empty($mediaId)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }

        $input           = [];
        $input['url']    = self::SEND_PICTURE_MESSAGE_TO_GROUP_CHART_URL . '?access_token=' . $this->getAccessToken();
        $input['params'] = [
            'channel_id' => $channelId,
            'message'    => [
                'media_id' => $mediaId,
            ]
        ];
        $this->response($input, 'POST_JSON');
    }

    /**
     * 单发文本卡片消息
     * @param $params
     * @return false|string
     * @throws Exception
     */
    public function singleTextCardMessage($params)
    {
        if (empty($params)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }
        $input        = [];
        $input['url'] = self::SEND_SINGLE_TEXT_CARD_MESSAGE_URL . '?access_token=' . $this->getAccessToken();
        // TODO 入参 content_list 是否需要重新定义？
        $input['params'] = [
            'to_user' => $params['open_id'],
            'message' => [
                'title'        => $params['title'],
                'url'          => $params['url'],
                'content_list' => $params['content_list'],
            ]
        ];
        //button_text 是非必须的
        if (isset($params['button_text'])) {
            $input['params']['message']['button_text'] = $params['button_text'];
        }
        $this->response($input, 'POST_JSON');
    }

    /**
     * 给部分人发文本卡片消息
     * @param $params
     * @return false|string
     * @throws Exception
     */
    public function sendTextCardMessageToSomePeople($params)
    {
        if (empty($params)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }
        $input        = [];
        $input['url'] = self::SEND_TEXT_CARD_MESSAGE_TO_SOME_PEOPLE_URL . '?access_token=' . $this->getAccessToken();
        // TODO 入参 content_list 是否需要重新定义？
        $input['params'] = [
            'to_users' => $params['open_ids'],
            'message'  => [
                'title'        => $params['title'],
                'url'          => $params['url'],
                'content_list' => $params['content_list'],
            ]
        ];
        //button_text 是非必须的
        if (isset($params['button_text'])) {
            $input['params']['message']['button_text'] = $params['button_text'];
        }
        $this->response($input, 'POST_JSON');
    }

    /**
     * 发文本卡片消息至群聊
     * @param $params
     * @return false|string
     * @throws Exception
     */
    public function sendTextCardMessageToGroupChat($params)
    {
        if (empty($params)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }
        $input        = [];
        $input['url'] = self::SEND_TEXT_CARD_MESSAGE_TO_GROUP_CHAT_URL . '?access_token=' . $this->getAccessToken();
        // TODO 入参 content_list 是否需要重新定义？
        $input['params'] = [
            'channel_id' => $params['channel_id'],
            'message'    => [
                'title'        => $params['title'],
                'url'          => $params['url'],
                'content_list' => $params['content_list'],
            ]
        ];
        //button_text 是非必须的
        if (isset($params['button_text'])) {
            $input['params']['message']['button_text'] = $params['button_text'];
        }
        $this->response($input, 'POST_JSON');
    }

    /**
     * 单发待办消息
     * @param $params
     * @return false|string
     * @throws Exception
     */
    public function sendSingleTODoMessage($params)
    {
        if (empty($params)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }
        $input           = [];
        $input['url']    = self::SEND_SINGLE_TO_DO_MESSAGE_URL . '?access_token=' . $this->getAccessToken();
        $input['params'] = [
            'to_user' => $params['open_id'],
            'message' => [
                'title' => $params['title'],
                'body'  => $params['body'],
                'url'   => $params['url'],
            ]
        ];
        $this->response($input, 'POST_JSON');
    }

    /**
     * 给部分人发待办消息
     * @param $params
     * @return false|string
     * @throws Exception
     */
    public function sendSingleTODoMessageToPart($params)
    {
        if (empty($params)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }
        $input           = [];
        $input['url']    = self::SEND_SINGLE_TO_DO_MESSAGE_TO_PART_URL . '?access_token=' . $this->getAccessToken();
        $input['params'] = [
            'to_users' => $params['open_ids'],
            'message'  => [
                'title' => $params['title'],
                'body'  => $params['body'],
                'url'   => $params['url'],
            ]
        ];
        $this->response($input, 'POST_JSON');
    }

    /**
     * 待办消息置为已处理
     * @param $messageId
     * @param $openId
     * @return false|string
     * @throws Exception
     */
    public function setPendingMessagesAsProcessed($messageId, $openId)
    {
        if (empty($messageId) || empty($openId)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }
        $input           = [];
        $input['url']    = self::SET_PENDING_MESSAGE_URL . '?access_token=' . $this->getAccessToken();
        $input['params'] = [
            'msg_id'  => $messageId,
            'open_id' => $openId,
        ];
        $this->response($input, 'POST_JSON');
    }

    /**
     * 获取企业内所有成员
     * @param int $requestPage
     * @param int $pageSize
     * @throws Exception
     */
    public function getAllCompanyMembers($requestPage = 1, $pageSize = 20)
    {
        $input           = [];
        $input['url']    = self::GET_ALL_COMPANY_MEMBERS_URL;
        $input['params'] = [
            'access_token' => $this->getAccessToken(),
            'page_size'    => $pageSize,
            'request_page' => $requestPage,
        ];
        $this->response($input);
    }

    /**
     * 企业成员管理-创建成员
     * @param $params
     * @return false|string
     * @throws Exception
     */
    public function addMember($params)
    {
        if (empty($params)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }
        $input           = [];
        $input['url']    = self::ADD_MEMBER_URL . '?access_token=' . $this->getAccessToken();
        $input['params'] = [
            'name'        => $params['name'],
            'comment'     => $params['comment'],
            'mail'        => $params['mail'],
            'mobile'      => $params['mobile'],
            'org_list'    => $params['org_list'],
            'password'    => $params['password'],
            'employee_id' => $params['employee_id'],
        ];

        $this->response($input, 'POST_URL');
    }

    /**
     * 企业成员管理-删除成员
     * @param $userId
     * @return false|string
     * @throws Exception
     */
    public function deleteMember($userId)
    {
        if (empty($userId)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }
        $input           = [];
        $input['url']    = self::DELETE_MEMBER_URL . '?access_token=' . $this->getAccessToken();
        $input['params'] = [
            'user_id' => $userId,
        ];
        $this->response($input, 'POST_URL');
    }

    /**
     * 企业成员管理-更新成员
     * @param $params
     * @return false|string
     * @throws Exception
     */
    public function updateMember($params)
    {
        if (empty($params)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }
        $input           = [];
        $input['url']    = self::UPDATE_MEMBER_URL . '?access_token=' . $this->getAccessToken();
        $input['params'] = [
            'user_id'     => $params['user_id'],
            'comment'     => $params['comment'],
            'name'        => $params['name'],
            'org_list'    => $params['org_list'],
            'employee_id' => $params['employee_id'],
        ];
        $this->response($input, 'POST_URL');
    }

    /**
     * 企业组织机构管理-获取企业id
     * @param $companyId
     * @return false|string
     * @throws Exception
     */
    public function getCompanyId($companyId)
    {
        if (empty($companyId)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }
        $input           = [];
        $input['url']    = self::GET_COMPANY_ID_URL;
        $input['params'] = [
            'access_token' => $this->getAccessToken(),
            'number'       => $companyId,
        ];
        $this->response($input);
    }

    /**
     * 企业组织机构管理-创建组织机构
     * @param $parentId
     * @param $name
     * @return false|string
     * @throws Exception
     */
    public function addOrganization($parentId, $name)
    {
        if (empty($parentId) || empty($name)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }
        $input           = [];
        $input['url']    = self::ADD_ORG_URL . '?access_token=' . $this->getAccessToken();
        $input['params'] = [
            'parent_id' => $parentId,
            'name'      => $name,
        ];
        $this->response($input, 'POST_URL');
    }

    /**
     * 企业组织机构管理-删除组织机构
     * @param $params
     * @return false|string
     * @throws Exception
     */
    public function deleteOrganization($params)
    {
        if (empty($params)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }
        $input           = [];
        $input['url']    = self::DELETE_ORG_URL . '?access_token=' . $this->getAccessToken();
        $input['params'] = [
            'org_id' => $params['org_id'],
        ];

        $this->response($input, 'POST_URL');
    }

    /**
     * 业组织机构管理-修改组织机构
     * @param $params
     * @return false|string
     * @throws Exception
     */
    public function modifyOrganization($params)
    {
        if (empty($params)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }
        $input           = [];
        $input['url']    = self::MODIFY_ORG_URL . '?access_token=' . $this->getAccessToken();
        $input['params'] = [
            'org_id' => $params['org_id'],
        ];
        if (isset($params['name'])) {
            $input['params']['name'] = $params;
        }
        if (isset($params['sequence'])) {
            $input['params']['sequence'] = $params['sequence'];
        }
        if (isset($params['auto_sequence'])) {
            $input['params']['autoSequence'] = $params['auto_sequence'];
        }
        $this->response($input, 'POST_URL');
    }

    /**
     * 企业组织机构管理-分页获取组织机构列表
     * @param $params
     * @return false|string
     * @throws Exception
     */
    public function getOrganizationList($params)
    {
        if (empty($params)) {
            return json_encode(['errcode' => 40100, 'errmsg' => 'invalid param']);
        }
        $input           = [];
        $input['url']    = self::GET_ORG_LIST_URL;
        $input['params'] = [
            'access_token' => $this->getAccessToken(),
            'page_size'    => $params['page_size'],
            'request_page' => $params['request_page'],
        ];
        if (isset($params['org_id'])) {
            $input['params']['org_id'] = $params['org_id'];
        }
        $this->response($input);
    }

    /**
     * 前端相应接口
     * @param $input
     * @param string $httpMethod
     * @param string $desc
     * @return false|string
     */
    private function response($input, $httpMethod = 'GET', $desc = '')
    {
        header('Content-Type:application/json');

        try {
            echo json_encode($this->call($input, $httpMethod, $desc));
        } catch (Exception $e) {
            echo json_encode(['errcode' => '-1', 'errmsg' => $e->getMessage()]);
        }
    }

    /**
     * 轻推API
     * @param array $input
     * @param string $httpMethod
     * @param string $desc
     * @return mixed
     * @throws Exception
     */
    public function call($input = [], $httpMethod = 'GET', $desc = '')
    {
        if ('GET' == $httpMethod) {
            $result = json_decode($this->requestGet($input['url'], $input['params']), true);
        } elseif ('POST_JSON' == $httpMethod) {
            $data   = json_encode($input['params'], 320);
            $result = json_decode($this->requestPost($input['url'], $data), true);
        } elseif ('POST' == $httpMethod) {
            $result = json_decode($this->requestPostData($input['url'], $input['params']), true);
        } elseif ('POST_URL' == $httpMethod) {
            $result = json_decode($this->requestPostUrl($input['url'], $input['params']), true);
        } else {
            throw new Exception($desc . '请求未定义，方式为：' . $httpMethod);
        }
        return $result;
    }

    /**
     * post urlencoded请求
     * @param $url
     * @param $data
     * @return bool|string
     */
    private function requestPostUrl($url, $data)
    {
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        //设置post数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//绕过ssl验证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        //执行命令
        $result = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        return $result;
    }

    /**
     *
     * @param $url
     * @param $data
     * @return bool|string
     */
    private function requestGet($url, $data)
    {
        //初始化
        $curl = curl_init();
        $url  .= '?' . http_build_query($data);
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//绕过ssl验证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        //执行命令
        $result = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        return $result;
    }

    /**
     * post请求
     * @param $url
     * @param $data
     * @return bool|string
     */
    private function requestPostData($url, $data)
    {
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        //设置post数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//绕过ssl验证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        //执行命令
        $result = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        return $result;
    }

    /**
     * post 请求 参数为json
     * @param $url
     * @param $data
     * @return bool|string
     */
    private function requestPost($url, $data)
    {
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        //设置post数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//绕过ssl验证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data)));
        //执行命令
        $result = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        return $result;
    }
}
