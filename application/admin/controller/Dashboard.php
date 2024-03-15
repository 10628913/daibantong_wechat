<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\admin\model\User;
use app\common\controller\Backend;
use app\common\model\Attachment;
use fast\Date;
use think\Db;

/**
 * 控制台
 *
 * @icon   fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    /**
     * 查看
     */
    public function index()
    {
        try {
            \think\Db::execute("SET @@sql_mode='';");
        } catch (\Exception $e) {

        }
        $siteWhere = [];
        if ($this->auth->site) {
            $siteWhere = ['site' => $this->auth->site];
        }

        $column = [];
        $starttime = Date::unixtime('day', -6);
        $endtime = Date::unixtime('day', 0, 'end');
        $joinlist = Db("user")->where('jointime', 'between time', [$starttime, $endtime])->where($siteWhere)
            ->field('jointime, status, COUNT(*) AS nums, DATE_FORMAT(FROM_UNIXTIME(jointime), "%Y-%m-%d") AS join_date')
            ->group('join_date')
            ->select();
        for ($time = $starttime; $time <= $endtime;) {
            $column[] = date("Y-m-d", $time);
            $time += 86400;
        }
        $userlist = array_fill_keys($column, 0);
        foreach ($joinlist as $k => $v) {
            $userlist[$v['join_date']] = $v['nums'];
        }

        $dbTableList = Db::query("SHOW TABLE STATUS");
        $addonList = get_addon_list();
        $totalworkingaddon = 0;
        $totaladdon = count($addonList);
        foreach ($addonList as $index => $item) {
            if ($item['state']) {
                $totalworkingaddon += 1;
            }
        }
        $this->view->assign([
            'totaluser'         => User::where($siteWhere)->count(),
            // 'totaladdon'        => $totaladdon,
            // 'totalcategory'     => \app\common\model\Category::count(),
            'todayusersignup'   => User::where($siteWhere)->whereTime('jointime', 'today')->count(),
            'todayuserlogin'    => User::where($siteWhere)->whereTime('logintime', 'today')->count(),
            'sevendau'          => User::where($siteWhere)->whereTime('jointime|logintime|prevtime', '-7 days')->count(),
            'thirtydau'         => User::where($siteWhere)->whereTime('jointime|logintime|prevtime', '-30 days')->count(),
            'threednu'          => User::where($siteWhere)->whereTime('jointime', '-3 days')->count(),
            'sevendnu'          => User::where($siteWhere)->whereTime('jointime', '-7 days')->count(),
            'dbtablenums'       => count($dbTableList),
            'dbsize'            => array_sum(array_map(function ($item) {
                return $item['Data_length'] + $item['Index_length'];
            }, $dbTableList)),
            // 'totalworkingaddon' => $totalworkingaddon,
            // 'attachmentnums'    => Attachment::count(),
            // 'attachmentsize'    => Attachment::sum('filesize'),
            // 'picturenums'       => Attachment::where('mimetype', 'like', 'image/%')->count(),
            // 'picturesize'       => Attachment::where('mimetype', 'like', 'image/%')->sum('filesize'),
            //总充值金额
            'total_recharge' => Db::name('record_recharge')->where($siteWhere)->sum('money'),
            //总查询金额
            'total_search_money' => Db::name('record_search')->where($siteWhere)->sum('money'),
            //总查询记录数
            'total_search_count' => Db::name('record_search')->where($siteWhere)->count(),
            //涉诉记录数
            'total_shesu_count' => Db::name('record_shesu')->count(),
            //涉诉记录Api请求数
            'total_shesu_api_count' => Db::name('record_shesu_api')->count(),
        ]);

        $this->assignconfig('column', array_keys($userlist));
        $this->assignconfig('userdata', array_values($userlist));

        return $this->view->fetch();
    }

}
