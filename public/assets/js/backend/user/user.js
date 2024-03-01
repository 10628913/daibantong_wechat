define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/index',
                    add_url: 'user/user/add',
                    edit_url: 'user/user/edit',
                    del_url: 'user/user/del',
                    multi_url: 'user/user/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'user.id',
                showColumns: false,
                showToggle: false,
                search: false,
                showExport: true,
                exportDataType: "auto",
                exportTypes: ['excel'],
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id'), sortable: true },
                        { field: 'identity', title: __('已认证身份'), formatter: Table.api.formatter.label, searchList: Config.identityObj, operate: 'FIND_IN_SET', cellStyle: Controller.api.formatter.css, addClass: 'selectpicker', data: 'data-live-search="true"' },
                        { field: 'site', title: __('所属站点'), formatter: Table.api.formatter.label, searchList: Config.siteObj, operate: '=', cellStyle: Controller.api.formatter.css, addClass: 'selectpicker', data: 'data-live-search="true"' },
                        { field: 'mobile', title: __('Mobile'), operate: 'LIKE' },
                        { field: 'nickname', title: __('Nickname'), operate: 'LIKE' },
                        { field: 'avatar', title: __('Avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false },
                        { field: 'amount', title: __('Amount'), operate: false },
                        { field: 'recharge_amount', title: __('Recharge_amount'), operate: false },
                        {
                            field: 'pay', title: __('使用金额'), operate: false, formatter: function (value, row, index) {
                                return (row.recharge_amount - row.amount).toFixed(2);
                            }
                        },
                        { field: 'successions', title: __('Successions'), visible: false, operate: 'BETWEEN', sortable: true },
                        { field: 'maxsuccessions', title: __('Maxsuccessions'), visible: false, operate: 'BETWEEN', sortable: true },
                        { field: 'logintime', title: __('Logintime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true },
                        { field: 'loginip', title: __('Loginip'), formatter: Table.api.formatter.search },
                        { field: 'jointime', title: __('Jointime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true },
                        { field: 'joinip', title: __('Joinip'), formatter: Table.api.formatter.search },
                        { field: 'status', title: __('Status'), formatter: Table.api.formatter.status, searchList: { normal: __('Normal'), hidden: __('Hidden') } },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, buttons: [
                                {
                                    dropdown: '充值操作',
                                    name: 'recharge',
                                    title: '充值',
                                    text: '余额充值',
                                    classname: 'btn btn-xs btn-dialog',
                                    url: function (row) {
                                        return 'user/user/recharge?ids=' + row.id
                                    }
                                },
                                {
                                    dropdown: '充值操作',
                                    name: 'recharge_log',
                                    title: '充值记录',
                                    text: '充值记录',
                                    classname: 'btn btn-xs btn-dialog',
                                    url: function (row) {
                                        return 'user/recharge/index?ids=' + row.id
                                    }
                                },
                                {
                                    dropdown: '充值操作',
                                    name: 'pay_log',
                                    title: '查询记录',
                                    text: '查询记录',
                                    extend: 'data-area=\'["90%","80%"]\'',
                                    classname: 'btn btn-xs btn-dialog',
                                    url: function (row) {
                                        return 'user/search?ids=' + row.id
                                    }
                                },

                                {
                                    name: 'edit_site',
                                    title: '修改站点',
                                    text: '修改站点',
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-edit',
                                    url: 'user/user/edit_site',
                                    refresh:true
                                },
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        edit_site: function () {
            Controller.api.bindevent();
        },
        recharge: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: {
                css: function () {
                    return {
                        css: {
                            "max-width": "130px",
                            "white-space": "pre-wrap"
                        }
                    }
                }
            }
        }
    };
    return Controller;
});