if(typeof window.addEventListener != 'undefined')
	window.addEventListener('load', outclicks_init, false);
else if(typeof document.addEventListener != 'undefined')
	document.addEventListener('load', outclicks_init, false);

else if(typeof window.attachEvent != 'undefined')
	window.attachEvent('onload', outclicks_init);
else
{
	if(typeof window.onload == 'function')
	{
		var existing = onload;

		window.onload = function()
		{
			existing();
			outclicks_init();
		};
	}
	else
	{
		window.onload = outclicks_init;
	}
}

function oc_addEvent(elm, evType, fn, useCapture) {
	if (elm.addEventListener) {
		elm.addEventListener(evType, fn, useCapture);
		return true;
	}
	else if (elm.attachEvent) {
		var r = elm.attachEvent('on' + evType, fn);
		return r;
	}
	else {
		elm['on' + evType] = fn;
	}
}

function outclicks_init () {
	 olinks = document.getElementsByTagName('a');
	 this_domain = '<?php echo str_replace(array("http://", "https://", "www."), "", $this->Mint->cfg['installDomain']); ?>';

	 for (i=0; i < olinks.length; i++) {
		link = olinks[i].href;
		// if it is off-domain and not a link, add listener
		if(oc_get_domain(link) != this_domain && link.indexOf('javascript:') == -1) {
            // hopefully that will do
            oc_addEvent(olinks[i], "click", trackOutclicks);
                        
        }
	 }

}

function oc_get_domain(str) {
	if (str.substr(0,7) == 'http://') str = str.substr(7);
	if (str.substr(0,8) == 'https://') str = str.substr(8);
	str = str.substr(0,str.indexOf('/'));
	str = str.replace('www.','');
	return str;
}

function esc (str) {
	if (typeof encodeURIComponent == 'undefined')
		return escape(str);
	else
		return encodeURIComponent(str);
}

function trackOutclicks (e) {
    e = (e) ? e : ((window.event) ? window.event : "");
    if(e){
        var elem = (e.target) ? e.target : e.srcElement;
        var path = '<?php echo $this->Mint->cfg['installDir']; ?>/pepper/andrewsutherland/outclicks/data.php';
        // Fix for "undefined" results on images
        // http://www.haveamint.com/retired/forum/viewtopic.php?id=1053
        //if(elem.nodeName == 'IMG') {
        // LOCAL FIX for other stuff than images
        if(elem.parentNode && elem.parentNode.href) {
            path += "?outclick="+esc(elem.parentNode.href);
        } else {
            path += "?outclick="+esc(elem.href);
        }
        path += "&from_title="+esc(document.title);
        path += "&from="+esc(self.location);

        // old browsers
        if (typeof encodeURIComponent == 'undefined') {
                // when user clicks a site, then back, then clicks another, don't retrack their hits
                // don't worry, it confuses me too
                c = document.getElementById('outClickTracker');
                if (c) c.parentNode.removeChild(c);
                document.body.innerHTML += '<script src="'+path+'" language="javascript" id="outClickTracker"></script>';
        }
        else {

            var data = false;
            /*@cc_on @*/
            /*@if (@_jscript_version >= 5)
            try { data = new ActiveXObject("Msxml2.XMLHTTP"); } 
            catch (e) { try { data = new ActiveXObject("Microsoft.XMLHTTP"); } catch (E) { data = false; } }
            @end @*/
            if (!data && typeof XMLHttpRequest!='undefined') data = new XMLHttpRequest();
            if (data) data.open("GET", path, false); data.send(null);
        
        }    
    }

}