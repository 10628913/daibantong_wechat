define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/usercheck/index' + location.search,
                    table: 'user_check',
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
                columns: [
                    [
                        { field: 'id', title: __('Id') },
                        {
                            field: 'user_id', title: __('申请用户'), formatter: function (value, row, index) {
                                var user = row.user;
                                if (!user) return '';
                                var arr = new Array();
                                if (user.id) arr.push('ID:' + user.id);
                                if (user.nickname) arr.push('昵称:' + user.nickname);
                                if (user.mobile) arr.push('手机:' + user.mobile);
                                return arr.join('<br/>');
                            }, operate: false
                        },
                        { field: 'identity', title: __('Identity'), searchList: { "1": __('Identity 1'), "2": __('Identity 2'), "3": __('Identity 3'), "4": __('Identity 4'), "5": __('Identity 5') }, formatter: Table.api.formatter.label },
                        { field: 'name', title: __('Name'), operate: 'LIKE' },
                        { field: 'company_name', title: __('Company_name'), operate: 'LIKE' },
                        { field: 'phone', title: __('Phone'), operate: 'LIKE' },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'audit_result', title: __('Audit_result'), searchList: { "0": __('Audit_result 0'), "1": __('Audit_result 1'), "2": __('Audit_result 2') }, formatter: Table.api.formatter.label },
                        { field: 'audit_remark', title: __('Audit_remark'), operate: false },
                        { field: 'audit_time', title: __('Audit_time'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        {
                            field: 'admin_id', title: __('Admin_id'), formatter: function (value, row, index) {
                                var admin = row.admin;
                                if (!admin) return '';
                                var arr = new Array();
                                if (admin.id) arr.push('ID:' + admin.id);
                                if (admin.nickname) arr.push('昵称:' + admin.nickname);
                                if (admin.username) arr.push('账户:' + admin.username);
                                return arr.join('<br/>');
                            }, operate: false
                        },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, buttons: [
                                {
                                    name: 'audit',
                                    text: '认证审核',
                                    title: __('申请信息'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-check',
                                    url: 'user/usercheck/audit',
                                    hidden: function (row) {
                                        return row.audit_result != 0;
                                    }
                                }
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },
        audit: function () {
            $('.btn-success').on('click', function () {
                $("[name='row[audit_result]']").val('1');
                $("[type='submit']").trigger("click");
            });
            $('.btn-danger').on('click', function () {
                Layer.prompt({ title: '请输入审核失败说明', formType: 2 }, function (value, index, elem) {
                    if (value === '') return elem.focus();
                    $("[name='row[audit_result]']").val('2');
                    $("[name='row[audit_remark]']").val(value);
                    $("[type='submit']").trigger("click");
                    // 关闭 prompt
                    layer.close(index);
                });

            });
            Controller.api.bindevent();
        },
    };
    return Controller;
});
