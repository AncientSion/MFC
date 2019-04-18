

// Initialize namespaces.
if (typeof Deckbox == "undefined") Deckbox = {};
Deckbox.ui = Deckbox.ui || {};

/**
 * Main tooltip type. Will be initialized for both the image tooltips and for the wow tooltips, or simple text tooltips.
 */
Deckbox.ui.Tooltip = function(className, type){
    this.el = document.getElementById("card")
    this.el.className = className + ' ' + type;
    this.type = type;
    this.el.style.display = 'none';
    //document.body.appendChild(this.el);
    this.tooltips = {};
};

Deckbox.ui.Tooltip.prototype = {
    _padContent: function(content) {
        return "<table><tr><td>" + content + "</td><th style='background-position: right top;'></th></tr><tr>" +
            "<th style='background-position: left bottom;'/><th style='background-position: right bottom;'/></tr></table>";
    },

    showWow: function(posX, posY, content, url, el) {
        /* IE does NOT escape quotes apparently. */
        url = url.replace(/"/g, "%22");
        /* Problematic with routes on server. */
        url = url.replace(/\?/g, "");

        if (this.tooltips[url] && this.tooltips[url].content) {
            content = this._padContent(this.tooltips[url].content);
        } else {
            content = this._padContent('Loading...');
            this.tooltips[url] = this.tooltips[url] || {el: el};
            Deckbox._.loadJS(url);
            /* Remeber these for when (if) the register call wants to show the tooltip. */
            this.posX = posX; this.posY = posY;
        }

        this.el.style.width = '';
        this.el.innerHTML = content;
        this.el.style.display = '';
        this.el.style.width = (20 + Math.min(330, this.el.childNodes[0].offsetWidth)) + 'px';
        this.move(posX, posY);
    },

    showText: function(posX, posY, text) {
        this.el.innerHTML = text;
        this.el.style.display = '';
        this.move(posX, posY);
    },

    showImage: function(posX, posY, image) {
        if (image.complete) {
            this.el.innerHTML = '';
            this.el.appendChild(image);
        } else {
            this.el.innerHTML = 'Loading...';
            image.onload = function() {
                var self = Deckbox._.tooltip('image');
                self.el.innerHTML = '';
                image.onload = null;
                self.el.appendChild(image);
                self.move(posX, posY);
            }
        }
        this.el.style.display = '';
        this.move(posX, posY);
    },

    hide: function() {
        this.el.style.display = 'none';
    },

    move: function(posX, posY) {
        return;
        // The tooltip should be offset to the right so that it's not exactly next to the mouse.
        posX += 15;
        posY -= this.el.offsetHeight / 3;

        // Remeber these for when (if) the register call wants to show the tooltip.
        this.posX = posX; 
        this.posY = posY;
        if (this.el.style.display == 'none') return;

        var pos = Deckbox._.fitToScreen(posX, posY, this.el);

        this.el.style.top = pos[1] + "px";
        this.el.style.left = pos[0] + "px";
    },

    register: function(url, content) {
        this.tooltips[url].content = content;
        if (this.tooltips[url].el._shown) {
            this.el.style.width = '';
            this.el.innerHTML = this._padContent(content);
            this.el.style.width = (20 + Math.min(330, this.el.childNodes[0].offsetWidth)) + 'px';
            this.move(this.posX, this.posY);
        }
    }
};
Deckbox.ui.Tooltip.hide = function() {
    Deckbox._.tooltip('image').hide();
    Deckbox._.tooltip('wow').hide();
    Deckbox._.tooltip('text').hide();
};


Deckbox._ = {
    onDocumentLoad: function(callback) {
        if (window.addEventListener) {
            window.addEventListener("load", callback, false);
        } else {
            window.attachEvent && window.attachEvent("onload", callback);
        }
    },

    preloadImg: function(link) {
        let img = document.createElement('img');
            img.style.display = "none"
            img.style.width = "1px"
            img.style.height = "1px"
            img.src = link.href + '/tooltip';
        return img;
    },

    pointerX: function(event) {
        var docElement = document.documentElement,
            body = document.body || { scrollLeft: 0 };

        return event.pageX ||
            (event.clientX +
             (docElement.scrollLeft || body.scrollLeft) -
             (docElement.clientLeft || 0));
    },

    pointerY: function(event) {
        var docElement = document.documentElement,
            body = document.body || { scrollTop: 0 };

        return  event.pageY ||
            (event.clientY +
             (docElement.scrollTop || body.scrollTop) -
             (docElement.clientTop || 0));
    },

    scrollOffsets: function() {
        return [
            window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft,
            window.pageYOffset || document.documentElement.scrollTop  || document.body.scrollTop
        ];
    },

    viewportSize: function() {
        var ua = navigator.userAgent, rootElement;
        if (ua.indexOf('AppleWebKit/') > -1 && !document.evaluate) {
            rootElement = document;
        } else if (Object.prototype.toString.call(window.opera) == '[object Opera]' && window.parseFloat(window.opera.version()) < 9.5) {
            rootElement = document.body;
        } else {
            rootElement = document.documentElement;
        }

        /* IE8 in quirks mode returns 0 for these sizes. */
        var size = [rootElement['clientWidth'], rootElement['clientHeight']];
        if (size[1] == 0) {
            return [document.body['clientWidth'], document.body['clientHeight']];
        } else {
            return size;
        }
    },

    fitToScreen: function(posX, posY, el) {
        var scroll = Deckbox._.scrollOffsets(), viewport = Deckbox._.viewportSize();

        /* decide if wee need to switch sides for the tooltip */
        /* too big for X */
        if ((el.offsetWidth + posX) >= (viewport[0] - 15) ) {
            posX = posX - el.offsetWidth - 20;
        }

        /* If it's too high, we move it down. */
        if (posY - scroll[1] < 0) {
            posY += scroll[1] - posY + 5;
        }
        /* If it's too low, we move it up. */
        if (posY + el.offsetHeight - scroll[1] > viewport[1]) {
            posY -= posY + el.offsetHeight + 5 - scroll[1] - viewport[1];
        }

        return [posX, posY];
    },

    addEvent: function(obj, type, fn) {
        if (obj.addEventListener) {
            if (type == 'mousewheel') obj.addEventListener('DOMMouseScroll', fn, false);
            obj.addEventListener( type, fn, false );
        } else if (obj.attachEvent) {
            obj["e"+type+fn] = fn;
            obj[type+fn] = function() { obj["e"+type+fn]( window.event ); };
            obj.attachEvent( "on"+type, obj[type+fn] );
        }
    },

    removeEvent: function(obj, type, fn) {
        if (obj.removeEventListener) {
            if(type == 'mousewheel') obj.removeEventListener('DOMMouseScroll', fn, false);
            obj.removeEventListener( type, fn, false );
        } else if (obj.detachEvent) {
            obj.detachEvent( "on"+type, obj[type+fn] );
            obj[type+fn] = null;
            obj["e"+type+fn] = null;
        }
    },

    loadJS: function(url) {
        var s = document.createElement('s' + 'cript');
        s.setAttribute("type", "text/javascript");
        s.setAttribute("src", url);
        document.getElementsByTagName("head")[0].appendChild(s);
    },

    loadCSS: function(url) {
        var s = document.createElement("link");
        s.type = "text/css";
        s.rel = "stylesheet";
        s.href = url;
        document.getElementsByTagName("head")[0].appendChild(s);
    },

    needsTooltip: function(el) {
        if (el.getAttribute('data-tt')) return true;

        var href;
        if (!el || !(el.tagName == 'A') || !(href = el.getAttribute('href'))) return false;
        if (el.className.match('no_tooltip')) return false;
        return href.match(/^https?:\/\/[^\/]*\/(mtg)\/.+/);
    },

    tooltip: function(which)  {
        if (which == 'image') return this._iT = this._iT || new Deckbox.ui.Tooltip('deckbox_i_tooltip', 'image');
        if (which == 'wow') return this._wT = this._wT || new Deckbox.ui.Tooltip('deckbox_tooltip', 'wow');
        if (which == 'text') return this._tT = this._tT || new Deckbox.ui.Tooltip('deckbox_t_tooltip', 'text');
    },

    target: function(event) {
        var target = event.target || event.srcElement || document;
        /* check if target is a textnode (safari) */
        if (target.nodeType == 3) target = target.parentNode;
        return target;
    }
};

/**
 * Bind the listeners.
 */
(function() {
    function onmouseover(event) {
        var el = Deckbox._.target(event);
        if (Deckbox._.needsTooltip(el)) {
            var no = el.getAttribute('data-nott'), url, img,
                posX = Deckbox._.pointerX(event), posY = Deckbox._.pointerY(event);
            if (!no) {
                el._shown = true;
                if (url = el.getAttribute('data-tt')) {
                    showImage(el, url, posX, posY);
                } else if (el.href.match('/(mtg|whi)/')) {
                    showImage(el, el.href + '/tooltip', posX, posY);
                }
            }
        }
    }

    function showImage(el, url, posX, posY) {
        if (navigator.onLine){
            url = url.replace(/\?/g, ""); /* Problematic with routes on server. */
            img = document.createElement('img');
            img.onload = function(){
                $("#card").empty().append($("<div>").append($(img)));
            img.src = url;
          }
        }
        else {
            showRules();
        }
    }

    function showRules(){
        $.ajax({
            type: "GET",
            url: "shakers.php",
            datatype: "json",
            data: {
                    type: "cardrules",
                    cardname: window.cardName,
                    setname: window.setName
                },
            success: function(data){
                $("#card").html(data);
            },
            error: function(){console.log("error")},
        }); 

    }
    

    function appendImage(img){
        if (img){
            $("#card").empty().append(img);
        }
        else {
           console.log("ding");
        }
    }

    function onmousemove(event) {
		return;
        var el = Deckbox._.target(event), posX = Deckbox._.pointerX(event), posY = Deckbox._.pointerY(event);
        if (Deckbox._.needsTooltip(el)) {
            Deckbox._.tooltip('image').move(posX, posY);
            Deckbox._.tooltip('wow').move(posX, posY, el.href);
        }
    }

    function onmouseout(event) {
        return;
        var el = Deckbox._.target(event);
        if (Deckbox._.needsTooltip(el)) {
            el._shown = false;
            Deckbox._.tooltip('image').hide();
            Deckbox._.tooltip('wow').hide();
        }
    }

    function click(event) {
        Deckbox._.tooltip('image').hide();
        Deckbox._.tooltip('wow').hide();
    }

    Deckbox._.addEvent(document, 'mouseover', onmouseover);
     Deckbox._.addEvent(document, 'mousemove', onmousemove);
    //Deckbox._.addEvent(document, 'mouseout', onmouseout);
   // Deckbox._.addEvent(document, 'click', click);

    var protocol = (document.location.protocol == 'https:') ? 'https:' : 'http:';
    Deckbox._.loadCSS(protocol + '//deckbox.org/assets/external/deckbox_tooltip.css');
    /* IE needs more shit */

    if (!!window.attachEvent && !(Object.prototype.toString.call(window.opera) == '[object Opera]')) {
        Deckbox._.loadCSS(protocol + '//deckbox.org/assets/external/deckbox_tooltip_ie.css');
    }

    /* Preload the tooltip images. */
    Deckbox._.onDocumentLoad(function() {
        return;
        var allLinks = document.getElementsByTagName('a');
        for (var i = 0; i < allLinks.length; i ++) {
            var link = allLinks[i];
            if (Deckbox._.needsTooltip(link)) {
                document.body.appendChild(Deckbox._.preloadImg(link));
            }
        }
    });
})();