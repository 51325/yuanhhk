define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'orders/index' + location.search,
                    add_url: 'orders/add',
                    edit_url: 'orders/edit',
                    del_url: 'orders/del',
                    qr_url: 'orders/buildqrcode',
                    multi_url: 'orders/multi',
                    import_url: 'orders/import',
                    table: 'orders',
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
                        {field: 'name', title: __('Orderid'), operate: 'LIKE'},
                        {field: 'username', title: __('Username'), operate: 'LIKE'},
                        {field: 'phone', title: __('Phone'), operate: 'LIKE'},
                        {field: 'inlandcomp', title: __('Inlandcomp'), operate: 'LIKE'},
                        {field: 'inlandorderid', title: __('Inlandorderid'), operate: 'LIKE'},
						{field: 'id', title: __('查看物流信息'), operate: false, formatter: Controller.api.formatter.details},
						{field: 'operate', title: __('Operate'), table: table, 
									buttons:[{
										name: 'qrcode',
										text: __('二维码'),
										title: __('生成专属二维码'),
										classname: 'btn btn-xs btn-warning btn-dialog',
										icon: '',
										url: 'orders/buildqrcode',
									}],
									events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ],
				search: false,
				commonSearch: true,
				searchFormVisible: true
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
                details: function (value, row, index) {
                    //这里手动构造URL
                    url = "ordersdetail?orders_id=" + value;

                    //方式一,直接返回class带有addtabsit的链接,这可以方便自定义显示内容
                    return '<a href="' + url + '" class="label label-success addtabsit" title="' + __("查看物流信息") + '">' + __('查看物流信息') + '</a>';

                }
            }
        }
    };
    return Controller;
});