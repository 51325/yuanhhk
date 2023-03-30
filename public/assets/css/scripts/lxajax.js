/*!
* 一个简单的Ajax类
* author: ichenshy@gmail.com
* date:   2010/06/04 Friday
*
* @param function fnBefore     用户自定义函数 Ajax开始前执行，若无则为null
* @param function fnAfter      用户自定义函数 Ajax完成后执行，若无则为null
* @param function fnTimeout    用户自定义函数 Ajax请求超时后执行，若无则为null
* @param integer  iTime        设置超时时间 单位毫秒
* @param boolean  bSync        是否为同步请求，默认为false
*/

function Ajax(fnBefore,fnAfter,fnTimeout,iTime,bSync){
	this.before		= fnBefore;
	this.after		= fnAfter;
	this.timeout	= fnTimeout;
	this.time		= iTime ? iTime : 10000;
	this.async		= bSync ? false : true;
	this._request	= null;
	this._response	= null;
}

Ajax.prototype = {
	/**
	*  将需要发送的数据进行编码
	*
	*  @param object data  JSON格式的数据，如: {username:"fyland",password:"ichenshy"}
	*/
	formatParam : function( data ){
		if ( ! data || typeof data != "object" ) return data;
		var k,r = [];
		for ( k in data ) {
			r.push([k,'=',encodeURIComponent(data[k])].join(''));
		}
		return r.join('&');
	},

	/**
	* 创建 XMLHttpRequest对象
	*/
	create : function(){
		if( window.XMLHttpRequest ) {
			this._request = new XMLHttpRequest();
		} else {
			try {
				this._request = new window.ActiveXObject("Microsoft.XMLHTTP");
			} catch(e) {}
		}
	},

	/**
	* 发送请求
	*
	* @param string				url     请求地址
	* @param object or string   data    可以是字符串或JSON格式的数据，如: {username:"fyland",password:"ichenshy"}
	* @param string             method  请求方式 ： GET or POST
	* @param boolean            ifCache	返回数据是否在浏览器端缓存，默认为false;
	*/
	send : function(url,data,method,ifCache){
		if ( typeof this.before == "function" ) this.before();

		method = method.toUpperCase();
		this.create();

		var self = this;
		var timer = setTimeout(function(){
				if ( typeof self.timeout == "function" ) self.timeout();
				if ( self._request ) {
					self._request.abort();
					self._request = null;
				}
				return true;
			},this.time);

		var sendBody  = this.formatParam(data);

		if ( 'GET' == method ) {
			url = [url, ( url.indexOf('?') == -1 ? '?' : '&') ,sendBody].join('');
			sendBody = null;
		}

		if ( ! ifCache ) {
			url = [url, ( url.indexOf('?') == -1 ? '?' : '&') , "ajaxtimestamp=" , (new Date()).getTime()].join('');
		}

		this._request.open(method,url,this.async);
		if ( "POST" == method ) this._request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

		this._request.onreadystatechange = function(){
			if( self._request.readyState==4 ){
				if ( self._request.status==200 ){
					if ( timer ) clearTimeout(timer);
					self._response = self._request.responseText;
					if ( typeof self.after == "function") self.after(self._response);
				}
			}
		}
		this._request.send( sendBody );
	},

	/**
	*   简单的GET请求
	*
	*   @param string url  请求地址
	*   @param null or string or object	data
	*   @param object html element or string id	  e
	*   @param string loading                     loading时在e中的显示
	*   @param boolean  ifCache    浏览器是否缓存
	*/
	get : function(url,data,e,loading,ifCache){
			if ( typeof e == "string" ) e = document.getElementById(e);
			if ( loading ) {
				var rg = /\.(gif|jpg|jpeg|png|bmp)$/i;
				if ( rg.test(loading) ){
					loading = ['<img src="', loading , '"  align="absmiddle" />'].join('');
				}
				this.before = function(){e.innerHTML = loading;}
			}
			this.after		= function(s){e.innerHTML = s;}
			this.timeout	= function(){e.innerHTML = ' 请求超时! ';}
			this.send(url,data,"GET",ifCache ? true : false);
	}
};