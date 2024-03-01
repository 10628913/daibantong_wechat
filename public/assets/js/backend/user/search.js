define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/search/index' + location.search,
                    table: 'record_search',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                showExport: false,
                showColumns: false,
                showToggle: false,
                search: false,
                columns: [
                    [
                        { field: 'id', title: __('Id') },
                        { field: 'user_id', title: __('User_id') },
                        {
                            field: 'mobile', title: '用户手机号', ormatter: function (value, row, index) {
                                return row.user.mobile;
                            }, operate: false
                        },
                        { field: 'type', title: __('Type'), searchList: { "1": __('Type 1'), "2": __('Type 2') }, formatter: Table.api.formatter.normal },
                        { field: 'search_id', title: __('Search_id') },
                        { field: 'search_name', title: __('Search_name'), operate: 'LIKE' },
                        { field: 'search_type', title: __('Search_type'), searchList: { "1": __('Search_type 1'), "2": __('Search_type 2') }, formatter: Table.api.formatter.normal },
                        // { field: 'search_company_id', title: __('Search_company_id') },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'money', title: __('Money'), operate: 'BETWEEN' },
                        // { field: 'look_count', title: __('Look_count') },
                        {
                            field: 'operate', title: '查询结果', table: table, events: Table.api.events.operate, formatter: function (value, row, index) {
                                    return '<a href="' + row.link + '" class="btn btn-xs btn-success" title="下载"><i class="fa fa-download"></i> 下载</a>';
                            }
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
