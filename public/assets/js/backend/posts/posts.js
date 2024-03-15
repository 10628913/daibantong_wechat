define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'posts/posts/index' + location.search,
                    add_url: 'posts/posts/add',
                    edit_url: 'posts/posts/edit',
                    del_url: 'posts/posts/del',
                    multi_url: 'posts/posts/multi',
                    import_url: 'posts/posts/import',
                    table: 'posts',
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
                            field: 'user_id', title: __('User_id'), formatter: function (v, row, i) {
                                var user = row.user;
                                if (!user) return '';
                                var arr = new Array();
                                if (user.id) arr.push('ID:' + user.id);
                                if (user.nickname) arr.push('昵称:' + user.nickname);
                                if (user.mobile) arr.push('手机:' + user.mobile);
                                return arr.join('<br/>');
                            }
                        },
                        { field: 'cid', title: __('Cid'), searchList: { "0": __('Cid 0'), "1": __('Cid 1'), "2": __('Cid 2'), "3": __('Cid 3'), "4": __('Cid 4') }, formatter: Table.api.formatter.label },
                        { field: 'post_type', title: __('Post_type'), formatter: Table.api.formatter.label, searchList: Config.typelistObj, operate: 'FIND_IN_SET', cellStyle: Controller.api.formatter.css, addClass: 'selectpicker', data: 'data-live-search="true"' },
                        { field: 'site', title: __('Site'), formatter: Table.api.formatter.label, searchList: Config.siteObj, operate: '=', cellStyle: Controller.api.formatter.css, addClass: 'selectpicker', data: 'data-live-search="true"' },
                        // {
                        //     field: 'title', title: __('Title'), operate: 'LIKE', formatter: function (v, row, i) {
                        //         return v.length > 20 ? v.substring(0, 19) + '...' : v;
                        //     }
                        // },
                        {
                            field: 'content', title: __('Content'), operate: 'LIKE', formatter: function (v, row, i) {
                                if(!v){
                                    return '';
                                }
                                return v.length > 20 ? v.substring(0, 19) + '...' : v;
                            }
                        },
                        { field: 'is_recommend', title: __('Is_recommend'), searchList: { "1": __('Is_recommend 1'), "0": __('Is_recommend 0') }, formatter: Table.api.formatter.label },
                        { field: 'recommend_start_time', title: __('Recommend_start_time'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'recommend_end_time', title: __('Recommend_end_time'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'is_top', title: __('Is_top'), searchList: { "1": __('Is_top 1'), "0": __('Is_top 0') }, formatter: Table.api.formatter.label },
                        { field: 'top_start_time', title: __('Top_start_time'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'top_end_time', title: __('Top_end_time'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'visibility_data', title: __('Visibility_data'), searchList: { "0": __('Visibility_data 0'), "1": __('Visibility_data 1'), "2": __('Visibility_data 2'), "3": __('Visibility_data 3'), "4": __('Visibility_data 4'), "5": __('Visibility_data 5') }, operate: 'FIND_IN_SET', formatter: Table.api.formatter.label },
                        { field: 'show_switch', title: __('Show_switch'), searchList: { "1": __('Show_switch 1'), "0": __('Show_switch 0') }, table: table, formatter: Table.api.formatter.toggle },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'updatetime', title: __('Updatetime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'like_num', title: __('Like_num') },
                        { field: 'comment_num', title: __('Comment_num') },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        recyclebin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'posts/posts/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'title', title: __('Title'), align: 'left' },
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            width: '140px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'posts/posts/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'posts/posts/destroy',
                                    refresh: true
                                }
                            ],
                            formatter: Table.api.formatter.operate
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
