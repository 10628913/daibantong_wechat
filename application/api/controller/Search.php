<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use think\Db;
use think\Exception;
use think\Log;

/**
 * 查询
 */
class Search extends Api
{

    protected $noNeedLogin = ['getFengxian', 'getShesu', 'getShesuV2'];
    protected $noNeedRight = [];

    protected $accessKey = "";
    protected $secretKey = "";
    protected $hostUrl = "";
    protected $companyId = "";


    public function _initialize()
    {
        parent::_initialize();
        $this->hostUrl = 'https://www.dianhuitech.com';
        $this->accessKey = 'a2b00aa5a6a54ef73faac292c5bce7a6';
        $this->secretKey = '00202357aac84a1895c0bde2254e6203';
        $this->companyId = 'a2b00aa5a6a54ef73faac292c5bce7a6';
    }


    /**
     * 获取风险评分
     * @ApiInternal
     */
    public function getFengxian()
    {
        $reqUrl = $this->hostUrl . '/api/cloud/open/pgyw/v1/getScoreInfoPro';
        $reqBody =
            [
                'accessKey' => $this->accessKey,
                'requestNo' => md5($this->getMillisecond()),
                'nonce' => 123,
                'timestamp' => $this->getMillisecond(),
                'companyId' => '4b48ef67e6adfda6630ab8d20b882a83',
                'name' => '',
                'idcard' => ''
            ];
        ksort($reqBody);
        $reqBody['signature'] = $this->getReqSign($reqBody);
        $result = $this->json_post($reqUrl, $reqBody);

        return $result;
    }

    /**
     * 关联企业涉诉查询
     * @ApiInternal
     */
    public function getShesu()
    {
        $reqUrl = $this->hostUrl . '/api/cloud/open/pgyw/v1/queryRelevanceOrgShesuData';
        $reqBody =
            [
                'accessKey' => $this->accessKey,
                'requestNo' => md5($this->getMillisecond()),
                'nonce' => 123,
                'timestamp' => $this->getMillisecond(),
                'id' => '123456789987654321',
                'companyId' => '4b48ef67e6adfda6630ab8d20b882a83',
                'orgId' => '20220809155033107000',
                'orgName' => "测试公司A新版本"
            ];
        ksort($reqBody);
        $reqBody['signature'] = $this->getReqSign($reqBody);
        $result = $this->json_post($reqUrl, $reqBody);

        return $result;
    }

    /**
     * 判断查询是否收费
     * @ApiMethod(POST)
     * @ApiParams  (name="search_id", type="string", required=true, description="身份证/企业组织结构代码") 
     * @ApiParams  (name="search_name", type="string", required=true, description="姓名/企业名称") 
     * @ApiParams  (name="search_type", type="int", required=true, description="目标类型:1=个人,2=企业") 
     */
    public function checkNeedMoney()
    {
        if (!$this->request->isPost()) return;

        $searchId = input('search_id');
        $searchName = input('search_name');
        $sarchType = input('search_type');
        if (!$searchId || !$searchName || !$sarchType || !in_array($sarchType, ['1', '2'])) $this->error('参数错误!');
        $user = $this->auth->getUserinfo();

        $searchDb = Db::name('record_search');

        $where = [
            'user_id' => $user['id'],
            'type' => 1,
            'search_id' => $searchId,
            'search_name' => $searchName,
            'search_type' => $sarchType,
        ];

        $siteConfig = Config::get("site");
        $needMoney = $siteConfig['shesu_money'];

        $hasSearch = $searchDb->where($where)->find();
        if ($hasSearch) {
            $this->success('获取成功', ['is_need_money' => 2, 'msg' => '已有记录,无需付费!']);
        } else {
            $this->success('获取成功', ['is_need_money' => 1, 'need_money' => $needMoney, 'msg' => '无查询记录,需付费!']);
        }
    }


    /**
     * 涉诉查询
     * @ApiMethod(POST)
     * @ApiParams  (name="search_id", type="string", required=true, description="身份证/企业组织结构代码") 
     * @ApiParams  (name="search_name", type="string", required=true, description="姓名/企业名称") 
     * @ApiParams  (name="search_type", type="int", required=true, description="目标类型:1=个人,2=企业") 
     */
    public function queryShesuData()
    {
        if (!$this->request->isPost()) return;

        $searchId = input('search_id');
        $searchName = input('search_name');
        $sarchType = input('search_type');
        if (!$searchId || !$searchName || !$sarchType || !in_array($sarchType, ['1', '2'])) $this->error('参数错误!');
        $user = $this->auth->getUserinfo();

        $searchDb = Db::name('record_search');
        $shesuDb = Db::name('record_shesu');

        if ($sarchType == 1 && strlen($searchId) != 18) $this->error('请输入正确的身份证号码!');
        // $result = $this->queryShesuDataImpl($searchId, $searchName, $sarchType);
        // var_dump($result);
        // return;

        $where = [
            'user_id' => $user['id'],
            'type' => 1,
            'search_id' => $searchId,
            'search_name' => $searchName,
            'search_type' => $sarchType,
        ];

        $siteConfig = Config::get("site");
        $needMoney = $siteConfig['shesu_money'];



        $hasSearch = $searchDb->where($where)->find();
        $shesuRecord = null;
        if ($hasSearch) {
            //有历史查询记录并且有查询结果
            $shesuRecord = $shesuDb->where('id', $hasSearch['relation_id'])->find();
            //如果有记录且不超30天,直接返回
            if (time() - $shesuRecord['updatetime'] <= 2592000) {
                $this->success('查询成功', $shesuRecord['link']);
            }
        } else {
            //无历史记录
            //处理有相同目标的查询记录
            $commonShesuWhere = $where;
            unset($commonShesuWhere['type'], $commonShesuWhere['user_id']);
            $shesuRecord = $shesuDb->where($commonShesuWhere)->find();
            if ($shesuRecord) {
                //不超过30天,扣款并返回记录
                if (time() - $shesuRecord['updatetime'] <= 2592000) {
                    if ($user['amount'] < $needMoney) {
                        $this->error('余额不足,请先进行充值!');
                    }
                    Db::startTrans();
                    try {
                        Db::name('user')->where('id', $user['id'])->setDec('amount', $needMoney);

                        $searchDb->insert(array_merge($where, [
                            'relation_id' => $shesuRecord['id'],
                            'createtime' => time(),
                            'money' => $needMoney,
                        ]));
                        if ($shesuRecord['result'] == 2) {
                            $this->error('未查到目标相关涉诉记录!');
                        }
                        if (!$shesuRecord['result'] == 1 && !$shesuRecord['link']) {
                            $excelResult = $this->getShesuExcel($shesuRecord['request_no']);
                            if ($excelResult['success']) {
                                $shesuRecord['link'] = $excelResult['result'];
                                $searchResult = $excelResult['result'];
                                $shesuDb->where('id', $shesuRecord['id'])->update(['link' => $excelResult['result']]);
                            }
                        }
                        Db::commit();
                        if (!$shesuRecord['link']) $this->error('未查到目标相关涉诉记录!');
                        $this->success('查询成功', $shesuRecord['link']);
                    } catch (Exception $e) {
                        Db::rollback();
                        $this->error('查询失败');
                    }
                }
            }
        }
        /***************** 处理无查询记录或有记录但超过30天 *********************/
        //判断是否需要扣费
        $isNeedMoney = 0;
        if (!$hasSearch) {
            $isNeedMoney = 1;
            if ($user['amount'] < $needMoney) {
                $this->error('余额不足,请先进行充值!');
            }
        }

        //请求编号
        $requestNo = $user['id'] . '_' . $this->getMillisecond();

        $result = $this->queryShesuDataImpl($searchId, $searchName, $sarchType, $requestNo);
        Log::write('涉诉查询结果:' . $result);

        $noShesuRecord = false;
        $resultData = json_decode((string)$result, true);
        if (!$resultData) $this->error('请求出错!');
        if ($resultData['success'] === false) {
            $this->error($resultData['message']);
        }

        // if (isset($resultData['shesuData'])) {
        //     $shesuData = $resultData['shesuData'];
        //     if ($shesuData['status'] != 0 || $shesuData['error_code'] < 0) {
        //         $noShesuRecord = true;
        //     }
        // }


        if (isset($resultData['verifyIdcardC']) && $resultData['verifyIdcardC'] == 2) {
            $noShesuRecord = true;
        }
        // if (isset($resultData['verifyIdcardC']) && $resultData['verifyIdcardC'] == 2) {
        //     $shesuRecordData = [
        //         'request_no' => $requestNo,
        //         'search_id' => $where['search_id'],
        //         'search_name' => $searchName,
        //         'search_type' => $sarchType,
        //         'updatetime' => time(),
        //         'createtime' => time(),
        //         'result' => 2
        //     ];
        //     $shesuRecordApiData = $shesuRecordData;
        //     unset($shesuRecordApiData['updatetime']);
        //     Db::name('record_shesu_api')->insert($shesuRecordApiData);
        //     $shesuRecordId = $shesuDb->insertGetId($shesuRecordData);
        //     $this->error('未查到目标相关涉诉记录!');
        // }

        Db::startTrans();
        $searchResult = null;

        try {

            $searchRecordId = 0;
            //如果不存在用户查询记录,插入记录
            if (!$hasSearch) {
                $searchRecordId = $searchDb->insertGetId(array_merge($where, ['money' => $needMoney, 'createtime' => time(), 'search_result' => 1]));
            }


            //处理涉诉记录
            $shesuRecordId = $shesuRecord ? $shesuRecord['id'] : 0;
            if ($shesuRecordId) {
                $upData = ['request_no' => $requestNo, 'updatetime' => time(), 'result' => $noShesuRecord ? 2 : 1];

                $shesuDb->where('id', $shesuRecordId)->update($upData);
            } else {
                $shesuRecordData = [
                    'request_no' => $requestNo,
                    'search_id' => $where['search_id'],
                    'search_name' => $searchName,
                    'search_type' => $sarchType,
                    'updatetime' => time(),
                    'createtime' => time(),
                    'result' => $noShesuRecord ? 2 : 1
                ];

                $shesuRecordApiData = $shesuRecordData;
                unset($shesuRecordApiData['updatetime']);
                Db::name('record_shesu_api')->insert($shesuRecordApiData);
                $shesuRecordId = $shesuDb->insertGetId($shesuRecordData);
            }
            //记录关联
            $searchDb->where('id', $searchRecordId)->update(['relation_id' => $shesuRecordId]);
            //扣款
            if ($isNeedMoney) {
                Db::name('user')->where('id', $user['id'])->setDec('amount', $needMoney);
            }
            if (!$noShesuRecord) {
                //获取excel
                $excelResult = $this->getShesuExcel($requestNo);
                if ($excelResult['success']) {
                    $searchResult = $excelResult['result'];
                    $shesuDb->where('id', $shesuRecordId)->update(['link' => $excelResult['result']]);
                }
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error('查询失败!');
        }
        if ($noShesuRecord) {
            $this->error('未查到目标相关涉诉记录!');
        }
        if (!$searchResult) {
            $this->error('获取涉诉记录表格失败!请稍后再试!');
        }
        $this->success('获取成功!', $searchResult);
    }

    /**
     * 获取用户查询记录
     * @ApiMethod(POST)
     * @ApiParams  (name="type", type="int", required=true, description="类型:1=涉诉记录,2=征信记录") 
     * @ApiParams   (name="page", type="int", required=false, description="页码(默认1)")
     * @ApiParams   (name="page_size", type="int", required=false, description="每页数据(默认10)")
     */
    public function getSearchRecord()
    {
        if (!$this->request->isPost()) return;


        $params = input();
        if (!isset($params['type']) || !in_array($params['type'], [1, 2])) $this->error('参数错误');

        $user = $this->auth->getUserinfo();
        $page = isset($params['page']) && is_numeric($params['page']) ? $params['page'] : 1;
        $page_size = isset($params['page_size']) && is_numeric($params['page_size']) ? $params['page_size'] : 10;

        switch ($params['type']) {
            case 1:
                //涉诉记录
                $rows = Db::name('record_search')
                    ->alias('s')
                    ->join('record_shesu a', 's.relation_id = a.id')
                    ->where(['s.user_id' => $user['id']])
                    ->order('s.createtime desc')
                    ->limit((intval($page) - 1) * $page_size, $page_size)
                    ->field('s.*,a.result,a.link')
                    ->select();
                if (!$rows) $this->error($page == 1 ? '暂无记录' : '已加载全部记录');
                $this->success('获取成功', $rows);
                break;
            case 2:
                //征信
                break;
        }
    }

    /**
     * 请求涉诉查询
     */
    private function queryShesuDataImpl($id = '', $name = '', $type = '', $requestNo = '')
    {
        $reqUrl = $this->hostUrl . '/api/cloud/open/pgyw/v1/queryShesuDataV2';
        $reqBody =
            [
                'accessKey' => $this->accessKey,
                'requestNo' => $requestNo,
                'nonce' => 123,
                'timestamp' => $this->getMillisecond(),
                'id' => $id,
                'name' => $name,
                'type' => $type == 1 ? 'PEOPLE' : 'ORG',
                'companyId' => $this->companyId
            ];
        ksort($reqBody);
        $reqBody['signature'] = $this->getReqSign($reqBody);
        Log::write('请求api参数:' . json_encode($reqBody));
        $result = $this->json_post($reqUrl, $reqBody);

        return $result;
    }

    /**
     * 获取涉诉查询excel
     */
    private function getShesuExcel($requestNo = '')
    {
        $reqUrl = $this->hostUrl . '/api/cloud/open/pgyw/v1/shesuExcel';
        $reqBody =
            [
                'accessKey' => $this->accessKey,
                'requestNo' => $requestNo . '_excel',
                'nonce' => 123,
                'timestamp' => $this->getMillisecond(),
                'companyId' => $this->companyId,
                'id' => $requestNo,
            ];
        ksort($reqBody);
        $reqBody['signature'] = $this->getReqSign($reqBody);
        $result = $this->json_post($reqUrl, $reqBody);
        Log::write($result);
        return json_decode((string)$result, true);
    }

    private function getReqSign($params = [])
    {
        return hash('sha256', json_encode($params, JSON_UNESCAPED_UNICODE) . $this->secretKey);
    }

    private function getMillisecond()
    {
        list($s1, $s2) = explode(' ', microtime());
        return sprintf('%d', (floatval($s1) + floatval($s2)) * 1000);
    }


    /**
     * PHP发送Json对象数据
     * @param $url 请求url
     * @param $data 发送的json字符串/数组
     * @return array
     */
    private function json_post($url, $data = NULL)
    {

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if (!$data) {
            return 'data is null';
        }
        if (is_array($data)) {
            $data = json_encode($data);
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . strlen($data),
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        $errorno = curl_errno($curl);
        if ($errorno) {
            return $errorno;
        }
        curl_close($curl);
        return $res;
    }
}
