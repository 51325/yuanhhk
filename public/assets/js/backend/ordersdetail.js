define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'ordersdetail/index' + location.search,
                    add_url: 'ordersdetail/add',
                    edit_url: 'ordersdetail/edit',
                    del_url: 'ordersdetail/del',
                    multi_url: 'ordersdetail/multi',
                    table: 'ordersdetail',
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
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'orders.name', title: __('Orders_id'), operate: 'LIKE'},
                        {field: 'content', title: __('Content'), operate: 'LIKE'},
                        {field: 'delivertime', title: __('Delivertime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ],
				search: false,
				commonSearch: true,
				searchFormVisible: true,
                queryParams: function (params) {
					console.log(params);
                    //这里可以追加搜索条件
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以动态赋值，比如从URL中获取admin_id的值，filter.admin_id=Fast.api.query('admin_id');
					var xjid = Fast.api.query('orders_id');
					if(xjid){
						filter.orders_id = Fast.api.query('orders_id');
						op.orders_id = "=";
					}
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                },
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