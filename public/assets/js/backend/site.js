define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'site/index' + location.search,
                    add_url: 'site/add',
                    edit_url: 'site/edit',
                    del_url: 'site/del',
                    multi_url: 'site/multi',
                    import_url: 'site/import',
                    table: 'city_site',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {
                            field: 'admin_id', title: __('站长'), formatter: function (v, row, i) {
                                var admin = row.admin;
                                if (!admin) return '';
                                var arr = new Array();
                                if (admin.id) arr.push('ID:' + admin.id);
                                if (admin.nickname) arr.push('昵称:' + admin.nickname);
                                if (admin.username) arr.push('账号:' + admin.username);
                                return arr.join('<br/>');
                            }
                        },
                        {field: 'weigh', title: __('Weigh'), operate: false},
                        {field: 'show_switch', title: __('Show_switch'), searchList: {"1":__('Yes'),"0":__('No')}, table: table, formatter: Table.api.formatter.toggle},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, buttons: [
                            {
                                name: 'commission',
                                title: function(row){
                                    return '站点 ['+row.name+'] 的佣金统计';
                                },
                                text: '佣金统计',
                                classname: 'btn btn-xs btn-info btn-dialog',
                                icon: 'fa fa-area-chart',
                                url: function (row) {
                                    return 'user/search/tj?ids=' + row.id
                                }
                            },
                        ]}
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
