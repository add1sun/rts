if(!Mint.RHC3) Mint.RHC3 = new Object();

Mint.RHC3.SessionTracker = {
	acceptscookies : 0,
	onsave : function() {
		var st_key = 'no';
		this.setcookie('eatscookies','yes');
		cookiecheck = this.getcookie('eatscookies');
		return '&eatscookies='+cookiecheck;
	},
	getcookie : function(cookiename){
		var thecookie = document.cookie;
		var crumbs = thecookie.split(';');
		for(var i=0;i<crumbs.length;i++){
			chips = crumbs[i].split('=');
			chips[0] = chips[0].replace(/^\s*|\s*$/g,'');
			if(chips[0] == cookiename){
				return chips[1];
			}
		}
		return 'no';
	},
	setcookie : function(cookiename, cookievalue){
		var sitedom = ".<?php echo $this->Mint->cfg['siteDomains'];?>";
		document.cookie = cookiename+'='+cookievalue+';domain='+sitedom+';';
	}
};
