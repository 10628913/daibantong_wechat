define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'adv/index' + location.search,
                    add_url: 'adv/add',
                    edit_url: 'adv/edit',
                    del_url: 'adv/del',
                    table: 'adv',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {field: 'id', title: __('Id')},
                        {field: 'pos', title: __('Pos'), searchList: {"1":__('Pos 1'),"2":__('Pos 2')}, formatter: Table.api.formatter.label},
                        {field: 'adv_image', title: __('Adv_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'adv_url', title: __('Adv_url'), formatter: Table.api.formatter.url},
                        {field: 'show_switch', title: __('Show_switch'), searchList: {"1":__('Yes'),"0":__('No')}, table: table, formatter: Table.api.formatter.toggle},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
