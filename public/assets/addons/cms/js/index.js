
  $(document).ready(function(){
		  $("#orderid").hover(function(){
				this.select();
		  });
	  }
  )
  
  function searchOrders(){
	  var ordersId = $("#orderid").val();
	  if(ordersId && ordersId.length > 0){
		  var orders = ordersId.split(";");
		  for(var i=0; i< orders.length; i++){
			  window.open("/index/search_kuai_di/search/orderid/"+orders[i]);
		  }
	  }
	  
  }