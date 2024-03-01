define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/recharge/index' + location.search,
                    table: 'record_recharge',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                commonSearch:false,
                search:false,
                showExport: false,
                showColumns: false,
                showToggle: false,
                columns: [
                    [
                        { field: 'id', title: __('Id') },
                        { field: 'admin_username', title: '操作人', operate: false },
                        { field: 'type', title: __('Type'), formatter: Table.api.formatter.label,searchList: { '1': __('Type 1'), '2': __('Type 2'), '3': __('Type 3') }  },
                        { field: 'money', title: __('Money'), operate: 'BETWEEN' },
                        { field: 'before', title: __('Before'), operate: 'BETWEEN' },
                        { field: 'after', title: __('After'), operate: 'BETWEEN' },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'remark', title: __('Remark'), operate: 'LIKE' },
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
