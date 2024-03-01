define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'shesu/index' + location.search,
                    table: 'record_shesu',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                showExport: false,
                showColumns: false,
                showToggle: false,
                search: false,
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'search_id', title: __('Search_id') },
                        { field: 'search_name', title: __('Search_name'), operate: 'LIKE' },
                        { field: 'search_type', title: __('Search_type'), searchList: { "1": __('Search_type 1'), "2": __('Search_type 2') }, formatter: Table.api.formatter.normal },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'updatetime', title: __('Updatetime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'result', title: __('Result'), formatter: Table.api.formatter.label, searchList: { "1": __('Result 1'), "2": __('Result 2') },},
                        {
                            field: 'operate', title: 'Excel', table: table, events: Table.api.events.operate, formatter: function (value, row, index) {
                                if(!row.link) return '';
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
