/*
 * yui-ext 0.33
 * Copyright(c) 2006, Jack Slocum.
 */


YAHOO.ext.Element=function(element,forceNew){var dom=YAHOO.util.Dom.get(element);if(!dom){return null;}
if(!forceNew&&YAHOO.ext.Element.cache[dom.id]){return YAHOO.ext.Element.cache[dom.id];}
this.dom=dom;this.id=this.dom.id;this.visibilityMode=YAHOO.ext.Element.VISIBILITY;this.originalDisplay=YAHOO.util.Dom.getStyle(this.dom,'display')||'';if(this.autoDisplayMode){if(this.originalDisplay=='none'){this.setVisibilityMode(YAHOO.ext.Element.DISPLAY);}}
if(this.originalDisplay=='none'){this.originalDisplay='';}
this.defaultUnit='px';}
YAHOO.ext.Element.prototype={setVisibilityMode:function(visMode){this.visibilityMode=visMode;return this;},enableDisplayMode:function(display){this.setVisibilityMode(YAHOO.ext.Element.DISPLAY);if(typeof display!='undefined')this.originalDisplay=display;return this;},animate:function(args,duration,onComplete,easing,animType){this.anim(args,duration,onComplete,easing,animType);return this;},anim:function(args,duration,onComplete,easing,animType){animType=animType||YAHOO.util.Anim;var anim=new animType(this.dom,args,duration||.35,easing||YAHOO.util.Easing.easeBoth);if(onComplete){if(!(onComplete instanceof Array)){anim.onComplete.subscribe(onComplete,this,true);}else{for(var i=0;i<onComplete.length;i++){var fn=onComplete[i];if(fn)anim.onComplete.subscribe(fn,this,true);}}}
anim.animate();},scrollIntoView:function(container){var c=getEl(container||document.body,true);var cp=c.getStyle('position');var restorePos=false;if(cp!='relative'&&cp!='absolute'){c.setStyle('position','relative');restorePos=true;}
var el=this.dom;var childTop=parseInt(el.offsetTop,10);var childBottom=childTop+el.offsetHeight;var containerTop=parseInt(c.scrollTop,10);var containerBottom=containerTop+c.clientHeight;if(childTop<containerTop){c.scrollTop=childTop;}else if(childBottom>containerBottom){c.scrollTop=childBottom-c.clientHeight;}
if(restorePos){c.setStyle('position',cp);}
return this;},autoHeight:function(animate,duration,onComplete,easing){var oldHeight=this.getHeight();this.clip();this.setHeight(1);setTimeout(function(){var height=parseInt(this.dom.scrollHeight,10);if(!animate){this.setHeight(height);this.unclip();if(typeof onComplete=='function'){onComplete();}}else{this.setHeight(oldHeight);this.setHeight(height,animate,duration,function(){this.unclip();if(typeof onComplete=='function')onComplete();}.createDelegate(this),easing);}}.createDelegate(this),0);return this;},isVisible:function(deep){var vis=YAHOO.util.Dom.getStyle(this.dom,'visibility')!='hidden'&&YAHOO.util.Dom.getStyle(this.dom,'display')!='none';if(!deep||!vis){return vis;}
var p=this.dom.parentNode;while(p&&p.tagName.toLowerCase()!='body'){if(YAHOO.util.Dom.getStyle(p,'visibility')=='hidden'||YAHOO.util.Dom.getStyle(p,'display')=='none'){return false;}
p=p.parentNode;}
return true;},select:function(selector,unique){return YAHOO.ext.Element.select('#'+this.dom.id+' '+selector,unique);},initDD:function(group,config,overrides){var dd=new YAHOO.util.DD(YAHOO.util.Dom.generateId(this.dom),group,config);return YAHOO.ext.util.Config.apply(dd,overrides);},initDDProxy:function(group,config,overrides){var dd=new YAHOO.util.DDProxy(YAHOO.util.Dom.generateId(this.dom),group,config);return YAHOO.ext.util.Config.apply(dd,overrides);},initDDTarget:function(group,config,overrides){var dd=new YAHOO.util.DDTarget(YAHOO.util.Dom.generateId(this.dom),group,config);return YAHOO.ext.util.Config.apply(dd,overrides);},setVisible:function(visible,animate,duration,onComplete,easing){if(!animate||!YAHOO.util.Anim){if(this.visibilityMode==YAHOO.ext.Element.DISPLAY){this.setDisplayed(visible);}else{YAHOO.util.Dom.setStyle(this.dom,'visibility',visible?'visible':'hidden');}}else{this.setOpacity(visible?0:1);YAHOO.util.Dom.setStyle(this.dom,'visibility','visible');if(this.visibilityMode==YAHOO.ext.Element.DISPLAY){this.setDisplayed(true);}
var args={opacity:{from:(visible?0:1),to:(visible?1:0)}};var anim=new YAHOO.util.Anim(this.dom,args,duration||.35,easing||(visible?YAHOO.util.Easing.easeIn:YAHOO.util.Easing.easeOut));anim.onComplete.subscribe((function(){if(this.visibilityMode==YAHOO.ext.Element.DISPLAY){this.setDisplayed(visible);}else{YAHOO.util.Dom.setStyle(this.dom,'visibility',visible?'visible':'hidden');}}).createDelegate(this));if(onComplete){anim.onComplete.subscribe(onComplete);}
anim.animate();}
return this;},isDisplayed:function(){return YAHOO.util.Dom.getStyle(this.dom,'display')!='none';},toggle:function(animate,duration,onComplete,easing){this.setVisible(!this.isVisible(),animate,duration,onComplete,easing);return this;},setDisplayed:function(value){if(typeof value=='boolean'){value=value?this.originalDisplay:'none';}
YAHOO.util.Dom.setStyle(this.dom,'display',value);return this;},focus:function(){try{this.dom.focus();}catch(e){}
return this;},blur:function(){try{this.dom.blur();}catch(e){}
return this;},addClass:function(className){if(className instanceof Array){for(var i=0,len=className.length;i<len;i++){this.addClass(className[i]);}}else{if(!this.hasClass(className)){this.dom.className=this.dom.className+' '+className;}}
return this;},radioClass:function(className){var siblings=this.dom.parentNode.childNodes;for(var i=0;i<siblings.length;i++){var s=siblings[i];if(s.nodeType==1){YAHOO.util.Dom.removeClass(s,className);}}
this.addClass(className);return this;},removeClass:function(className){if(className instanceof Array){for(var i=0,len=className.length;i<len;i++){this.removeClass(className[i]);}}else{var re=new RegExp('(?:^|\\s+)'+className+'(?:\\s+|$)','g');var c=this.dom.className;if(re.test(c)){this.dom.className=c.replace(re,' ');}}
return this;},toggleClass:function(className){if(this.hasClass(className)){this.removeClass(className);}else{this.addClass(className);}
return this;},hasClass:function(className){var re=new RegExp('(?:^|\\s+)'+className+'(?:\\s+|$)');return re.test(this.dom.className);},replaceClass:function(oldClassName,newClassName){this.removeClass(oldClassName);this.addClass(newClassName);return this;},getStyle:function(name){return YAHOO.util.Dom.getStyle(this.dom,name);},setStyle:function(name,value){if(typeof name=='string'){YAHOO.util.Dom.setStyle(this.dom,name,value);}else{var D=YAHOO.util.Dom;for(var style in name){if(typeof name[style]!='function'){D.setStyle(this.dom,style,name[style]);}}}
return this;},applyStyles:function(style){YAHOO.ext.DomHelper.applyStyles(this.dom,style);},getX:function(){return YAHOO.util.Dom.getX(this.dom);},getY:function(){return YAHOO.util.Dom.getY(this.dom);},getXY:function(){return YAHOO.util.Dom.getXY(this.dom);},setX:function(x,animate,duration,onComplete,easing){if(!animate||!YAHOO.util.Anim){YAHOO.util.Dom.setX(this.dom,x);}else{this.setXY([x,this.getY()],animate,duration,onComplete,easing);}
return this;},setY:function(y,animate,duration,onComplete,easing){if(!animate||!YAHOO.util.Anim){YAHOO.util.Dom.setY(this.dom,y);}else{this.setXY([this.getX(),y],animate,duration,onComplete,easing);}
return this;},setLeft:function(left){YAHOO.util.Dom.setStyle(this.dom,'left',this.addUnits(left));return this;},setTop:function(top){YAHOO.util.Dom.setStyle(this.dom,'top',this.addUnits(top));return this;},setRight:function(right){YAHOO.util.Dom.setStyle(this.dom,'right',this.addUnits(right));return this;},setBottom:function(bottom){YAHOO.util.Dom.setStyle(this.dom,'bottom',this.addUnits(bottom));return this;},setXY:function(pos,animate,duration,onComplete,easing){if(!animate||!YAHOO.util.Anim){YAHOO.util.Dom.setXY(this.dom,pos);}else{this.anim({points:{to:pos}},duration,onComplete,easing,YAHOO.util.Motion);}
return this;},setLocation:function(x,y,animate,duration,onComplete,easing){this.setXY([x,y],animate,duration,onComplete,easing);return this;},moveTo:function(x,y,animate,duration,onComplete,easing){this.setXY([x,y],animate,duration,onComplete,easing);return this;},getRegion:function(){return YAHOO.util.Dom.getRegion(this.dom);},getHeight:function(contentHeight){var h=this.dom.offsetHeight;return contentHeight!==true?h:h-this.getBorderWidth('tb')-this.getPadding('tb');},getWidth:function(contentWidth){var w=this.dom.offsetWidth;return contentWidth!==true?w:w-this.getBorderWidth('lr')-this.getPadding('lr');},getSize:function(contentSize){return{width:this.getWidth(contentSize),height:this.getHeight(contentSize)};},adjustWidth:function(width){if(typeof width=='number'){if(this.autoBoxAdjust&&!this.isBorderBox()){width-=(this.getBorderWidth('lr')+this.getPadding('lr'));}
if(width<0){width=0;}}
return width;},adjustHeight:function(height){if(typeof height=='number'){if(this.autoBoxAdjust&&!this.isBorderBox()){height-=(this.getBorderWidth('tb')+this.getPadding('tb'));}
if(height<0){height=0;}}
return height;},setWidth:function(width,animate,duration,onComplete,easing){width=this.adjustWidth(width);if(!animate||!YAHOO.util.Anim){YAHOO.util.Dom.setStyle(this.dom,'width',this.addUnits(width));}else{this.anim({width:{to:width}},duration,onComplete,easing||(width>this.getWidth()?YAHOO.util.Easing.easeOut:YAHOO.util.Easing.easeIn));}
return this;},setHeight:function(height,animate,duration,onComplete,easing){height=this.adjustHeight(height);if(!animate||!YAHOO.util.Anim){YAHOO.util.Dom.setStyle(this.dom,'height',this.addUnits(height));}else{this.anim({height:{to:height}},duration,onComplete,easing||(height>this.getHeight()?YAHOO.util.Easing.easeOut:YAHOO.util.Easing.easeIn));}
return this;},setSize:function(width,height,animate,duration,onComplete,easing){if(!animate||!YAHOO.util.Anim){this.setWidth(width);this.setHeight(height);}else{width=this.adjustWidth(width);height=this.adjustHeight(height);this.anim({width:{to:width},height:{to:height}},duration,onComplete,easing);}
return this;},setBounds:function(x,y,width,height,animate,duration,onComplete,easing){if(!animate||!YAHOO.util.Anim){this.setWidth(width);this.setHeight(height);this.setLocation(x,y);}else{width=this.adjustWidth(width);height=this.adjustHeight(height);this.anim({points:{to:[x,y]},width:{to:width},height:{to:height}},duration,onComplete,easing,YAHOO.util.Motion);}
return this;},setRegion:function(region,animate,duration,onComplete,easing){this.setBounds(region.left,region.top,region.right-region.left,region.bottom-region.top,animate,duration,onComplete,easing);return this;},addListener:function(eventName,handler,scope,override){YAHOO.util.Event.addListener(this.dom,eventName,handler,scope||this,true);return this;},bufferedListener:function(eventName,fn,scope,millis){var task=new YAHOO.ext.util.DelayedTask();scope=scope||this;var newFn=function(){task.delay(millis||250,fn,scope,Array.prototype.slice.call(arguments,0));}
this.addListener(eventName,newFn);return newFn;},addHandler:function(eventName,stopPropagation,handler,scope,override){var fn=YAHOO.ext.Element.createStopHandler(stopPropagation,handler,scope||this,true);YAHOO.util.Event.addListener(this.dom,eventName,fn);return this;},on:function(eventName,handler,scope,override){YAHOO.util.Event.addListener(this.dom,eventName,handler,scope||this,true);return this;},addManagedListener:function(eventName,fn,scope,override){return YAHOO.ext.EventManager.on(this.dom,eventName,fn,scope||this,true);},mon:function(eventName,fn,scope,override){return YAHOO.ext.EventManager.on(this.dom,eventName,fn,scope||this,true);},removeListener:function(eventName,handler,scope){YAHOO.util.Event.removeListener(this.dom,eventName,handler);return this;},removeAllListeners:function(){YAHOO.util.Event.purgeElement(this.dom);return this;},setOpacity:function(opacity,animate,duration,onComplete,easing){if(!animate||!YAHOO.util.Anim){YAHOO.util.Dom.setStyle(this.dom,'opacity',opacity);}else{this.anim({opacity:{to:opacity}},duration,onComplete,easing);}
return this;},getLeft:function(local){if(!local){return this.getX();}else{return parseInt(this.getStyle('left'),10)||0;}},getRight:function(local){if(!local){return this.getX()+this.getWidth();}else{return(this.getLeft(true)+this.getWidth())||0;}},getTop:function(local){if(!local){return this.getY();}else{return parseInt(this.getStyle('top'),10)||0;}},getBottom:function(local){if(!local){return this.getY()+this.getHeight();}else{return(this.getTop(true)+this.getHeight())||0;}},setAbsolutePositioned:function(zIndex){this.setStyle('position','absolute');if(zIndex){this.setStyle('z-index',zIndex);}
return this;},setRelativePositioned:function(zIndex){this.setStyle('position','relative');if(zIndex){this.setStyle('z-index',zIndex);}
return this;},clearPositioning:function(){this.setStyle('position','');this.setStyle('left','');this.setStyle('right','');this.setStyle('top','');this.setStyle('bottom','');return this;},getPositioning:function(){return{'position':this.getStyle('position'),'left':this.getStyle('left'),'right':this.getStyle('right'),'top':this.getStyle('top'),'bottom':this.getStyle('bottom')};},getBorderWidth:function(side){return this.addStyles(side,YAHOO.ext.Element.borders);},getPadding:function(side){return this.addStyles(side,YAHOO.ext.Element.paddings);},setPositioning:function(positionCfg){if(positionCfg.position)this.setStyle('position',positionCfg.position);if(positionCfg.left)this.setLeft(positionCfg.left);if(positionCfg.right)this.setRight(positionCfg.right);if(positionCfg.top)this.setTop(positionCfg.top);if(positionCfg.bottom)this.setBottom(positionCfg.bottom);return this;},setLeftTop:function(left,top){this.dom.style.left=this.addUnits(left);this.dom.style.top=this.addUnits(top);return this;},move:function(direction,distance,animate,duration,onComplete,easing){var xy=this.getXY();direction=direction.toLowerCase();switch(direction){case'l':case'left':this.moveTo(xy[0]-distance,xy[1],animate,duration,onComplete,easing);break;case'r':case'right':this.moveTo(xy[0]+distance,xy[1],animate,duration,onComplete,easing);break;case't':case'top':case'up':this.moveTo(xy[0],xy[1]-distance,animate,duration,onComplete,easing);break;case'b':case'bottom':case'down':this.moveTo(xy[0],xy[1]+distance,animate,duration,onComplete,easing);break;}
return this;},clip:function(){if(!this.isClipped){this.isClipped=true;this.originalClip={'o':this.getStyle('overflow'),'x':this.getStyle('overflow-x'),'y':this.getStyle('overflow-y')};this.setStyle('overflow','hidden');this.setStyle('overflow-x','hidden');this.setStyle('overflow-y','hidden');}
return this;},unclip:function(){if(this.isClipped){this.isClipped=false;var o=this.originalClip;if(o.o){this.setStyle('overflow',o.o);}
if(o.x){this.setStyle('overflow-x',o.x);}
if(o.y){this.setStyle('overflow-y',o.y);}}
return this;},alignTo:function(element,position,offsets,animate,duration,onComplete,easing){var otherEl=getEl(element);if(!otherEl){return this;}
offsets=offsets||[0,0];var r=otherEl.getRegion();position=position.toLowerCase();switch(position){case'bl':this.moveTo(r.left+offsets[0],r.bottom+offsets[1],animate,duration,onComplete,easing);break;case'br':this.moveTo(r.right+offsets[0],r.bottom+offsets[1],animate,duration,onComplete,easing);break;case'tl':this.moveTo(r.left+offsets[0],r.top+offsets[1],animate,duration,onComplete,easing);break;case'tr':this.moveTo(r.right+offsets[0],r.top+offsets[1],animate,duration,onComplete,easing);break;}
return this;},clearOpacity:function(){if(window.ActiveXObject){this.dom.style.filter='';}else{this.dom.style.opacity='';this.dom.style['-moz-opacity']='';this.dom.style['-khtml-opacity']='';}
return this;},hide:function(animate,duration,onComplete,easing){this.setVisible(false,animate,duration,onComplete,easing);return this;},show:function(animate,duration,onComplete,easing){this.setVisible(true,animate,duration,onComplete,easing);return this;},addUnits:function(size){if(size===''||size=='auto'||typeof size=='undefined'){return size;}
if(typeof size=='number'||!YAHOO.ext.Element.unitPattern.test(size)){return size+this.defaultUnit;}
return size;},beginMeasure:function(){var el=this.dom;if(el.offsetWidth||el.offsetHeight){return this;}
var changed=[];var p=this.dom;while((!el.offsetWidth&&!el.offsetHeight)&&p&&p.tagName&&p.tagName.toLowerCase()!='body'){if(YAHOO.util.Dom.getStyle(p,'display')=='none'){changed.push({el:p,visibility:YAHOO.util.Dom.getStyle(p,'visibility')});p.style.visibility='hidden';p.style.display='block';}
p=p.parentNode;}
this._measureChanged=changed;return this;},endMeasure:function(){var changed=this._measureChanged;if(changed){for(var i=0,len=changed.length;i<len;i++){var r=changed[i];r.el.style.visibility=r.visibility;r.el.style.display='none';}
this._measureChanged=null;}
return this;},update:function(html,loadScripts,callback){if(typeof html=='undefined'){html='';}
if(loadScripts!==true){this.dom.innerHTML=html;if(typeof callback=='function'){callback();}
return this;}
var id=YAHOO.util.Dom.generateId();var dom=this.dom;html+='<span id="'+id+'"></span>';YAHOO.util.Event.onAvailable(id,function(){var hd=document.getElementsByTagName("head")[0];var re=/(?:<script.*?>)((\n|\r|.)*?)(?:<\/script>)/img;var srcRe=/\ssrc=([\'\"])(.*?)\1/i;var match;while(match=re.exec(html)){var srcMatch=match[0].match(srcRe);if(srcMatch&&srcMatch[2]){var s=document.createElement("script");s.src=srcMatch[2];hd.appendChild(s);}else if(match[1]&&match[1].length>0){eval(match[1]);}}
var el=document.getElementById(id);if(el){el.parentNode.removeChild(el);}
if(typeof callback=='function'){callback();}});dom.innerHTML=html.replace(/(?:<script.*?>)((\n|\r|.)*?)(?:<\/script>)/img,'');return this;},load:function(){var um=this.getUpdateManager();um.update.apply(um,arguments);return this;},getUpdateManager:function(){if(!this.updateManager){this.updateManager=new YAHOO.ext.UpdateManager(this);}
return this.updateManager;},unselectable:function(){this.dom.unselectable='on';this.swallowEvent('selectstart',true);this.applyStyles('-moz-user-select:none;-khtml-user-select:none;');return this;},getCenterXY:function(offsetScroll){var centerX=Math.round((YAHOO.util.Dom.getViewportWidth()-this.getWidth())/2);var centerY=Math.round((YAHOO.util.Dom.getViewportHeight()-this.getHeight())/2);if(!offsetScroll){return[centerX,centerY];}else{var scrollX=document.documentElement.scrollLeft||document.body.scrollLeft||0;var scrollY=document.documentElement.scrollTop||document.body.scrollTop||0;return[centerX+scrollX,centerY+scrollY];}},center:function(centerIn){if(!centerIn){this.setXY(this.getCenterXY(true));}else{var box=YAHOO.ext.Element.get(centerIn).getBox();this.setXY([box.x+(box.width/2)-(this.getWidth()/2),box.y+(box.height/2)-(this.getHeight()/2)]);}
return this;},getChildrenByTagName:function(tagName){var children=this.dom.getElementsByTagName(tagName);var len=children.length;var ce=new Array(len);for(var i=0;i<len;++i){ce[i]=YAHOO.ext.Element.get(children[i],true);}
return ce;},getChildrenByClassName:function(className,tagName){var children=YAHOO.util.Dom.getElementsByClassName(className,tagName,this.dom);var len=children.length;var ce=new Array(len);for(var i=0;i<len;++i){ce[i]=YAHOO.ext.Element.get(children[i],true);}
return ce;},isBorderBox:function(){if(typeof this.bbox=='undefined'){var el=this.dom;var b=YAHOO.ext.util.Browser;var strict=YAHOO.ext.Strict;this.bbox=((b.isIE&&!strict&&el.style.boxSizing!='content-box')||(b.isGecko&&YAHOO.util.Dom.getStyle(el,"-moz-box-sizing")=='border-box')||(!b.isSafari&&YAHOO.util.Dom.getStyle(el,"box-sizing")=='border-box'));}
return this.bbox;},getBox:function(contentBox,local){var xy;if(!local){xy=this.getXY();}else{var left=parseInt(YAHOO.util.Dom.getStyle('left'),10)||0;var top=parseInt(YAHOO.util.Dom.getStyle('top'),10)||0;xy=[left,top];}
var el=this.dom;var w=el.offsetWidth;var h=el.offsetHeight;if(!contentBox){return{x:xy[0],y:xy[1],width:w,height:h};}else{var l=this.getBorderWidth('l')+this.getPadding('l');var r=this.getBorderWidth('r')+this.getPadding('r');var t=this.getBorderWidth('t')+this.getPadding('t');var b=this.getBorderWidth('b')+this.getPadding('b');return{x:xy[0]+l,y:xy[1]+t,width:w-(l+r),height:h-(t+b)};}},setBox:function(box,adjust,animate,duration,onComplete,easing){var w=box.width,h=box.height;if((adjust&&!this.autoBoxAdjust)&&!this.isBorderBox()){w-=(this.getBorderWidth('lr')+this.getPadding('lr'));h-=(this.getBorderWidth('tb')+this.getPadding('tb'));}
this.setBounds(box.x,box.y,w,h,animate,duration,onComplete,easing);return this;},repaint:function(){var dom=this.dom;YAHOO.util.Dom.addClass(dom,'yui-ext-repaint');setTimeout(function(){YAHOO.util.Dom.removeClass(dom,'yui-ext-repaint');},1);return this;},getMargins:function(side){if(!side){return{top:parseInt(this.getStyle('margin-top'),10)||0,left:parseInt(this.getStyle('margin-left'),10)||0,bottom:parseInt(this.getStyle('margin-bottom'),10)||0,right:parseInt(this.getStyle('margin-right'),10)||0};}else{return this.addStyles(side,YAHOO.ext.Element.margins);}},addStyles:function(sides,styles){var val=0;for(var i=0,len=sides.length;i<len;i++){var w=parseInt(this.getStyle(styles[sides.charAt(i)]),10);if(!isNaN(w))val+=w;}
return val;},createProxy:function(config,renderTo,matchBox){if(renderTo){renderTo=YAHOO.util.Dom.get(renderTo);}else{renderTo=document.body;}
config=typeof config=='object'?config:{tag:'div',cls:config};var proxy=YAHOO.ext.DomHelper.append(renderTo,config,true);if(matchBox){proxy.setBox(this.getBox());}
return proxy;},createShim:function(){var config={tag:'iframe',frameBorder:'no',cls:'yiframe-shim',style:'position:absolute;visibility:hidden;left:0;top:0;overflow:hidden;',src:YAHOO.ext.SSL_SECURE_URL};var shim=YAHOO.ext.DomHelper.append(this.dom.parentNode,config,true);shim.setBox(this.getBox());return shim;},remove:function(){this.dom.parentNode.removeChild(this.dom);delete YAHOO.ext.Element.cache[this.dom.id];},addClassOnOver:function(className){this.on('mouseover',function(){this.addClass(className);},this,true);this.on('mouseout',function(){this.removeClass(className);},this,true);return this;},swallowEvent:function(eventName,preventDefault){var fn=function(e){e.stopPropagation();if(preventDefault){e.preventDefault();}};this.mon(eventName,fn);return this;},fitToParent:function(monitorResize){var p=getEl(this.dom.parentNode,true);p.beginMeasure();var box=p.getBox(true,true);p.endMeasure();this.setSize(box.width,box.height);if(monitorResize===true){YAHOO.ext.EventManager.onWindowResize(this.fitToParent,this,true);}
return this;},getNextSibling:function(){var n=this.dom.nextSibling;while(n&&n.nodeType!=1){n=n.nextSibling;}
return n;},getPrevSibling:function(){var n=this.dom.previousSibling;while(n&&n.nodeType!=1){n=n.previousSibling;}
return n;},appendChild:function(el){el=getEl(el);el.appendTo(this);return this;},createChild:function(config,insertBefore){var c;if(insertBefore){c=YAHOO.ext.DomHelper.insertBefore(insertBefore,config,true);}else{c=YAHOO.ext.DomHelper.append(this.dom,config,true);}
return c;},appendTo:function(el){var node=getEl(el).dom;node.appendChild(this.dom);return this;},insertBefore:function(el){var node=getEl(el).dom;node.parentNode.insertBefore(this.dom,node);return this;},insertAfter:function(el){var node=getEl(el).dom;node.parentNode.insertBefore(this.dom,node.nextSibling);return this;},wrap:function(config){if(!config){config={tag:'div'};}
var newEl=YAHOO.ext.DomHelper.insertBefore(this.dom,config,true);newEl.dom.appendChild(this.dom);return newEl;},replace:function(el){el=getEl(el);this.insertBefore(el);el.remove();return this;},insertHtml:function(where,html){YAHOO.ext.DomHelper.insertHtml(where,this.dom,html);return this;},set:function(o){var el=this.dom;var useSet=el.setAttribute?true:false;for(var attr in o){if(attr=='style'||typeof o[attr]=='function')continue;if(attr=='cls'){el.className=o['cls'];}else{if(useSet)el.setAttribute(attr,o[attr]);else el[attr]=o[attr];}}
YAHOO.ext.DomHelper.applyStyles(el,o.style);return this;},addKeyListener:function(key,fn,scope){var config;if(typeof key!='object'||key instanceof Array){config={key:key,fn:fn,scope:scope};}else{config={key:key.key,shift:key.shift,ctrl:key.ctrl,alt:key.alt,fn:fn,scope:scope};}
var map=new YAHOO.ext.KeyMap(this,config);return map;},addKeyMap:function(config){return new YAHOO.ext.KeyMap(this,config);}};YAHOO.ext.Element.prototype.autoBoxAdjust=true;YAHOO.ext.Element.prototype.autoDisplayMode=true;YAHOO.ext.Element.unitPattern=/\d+(px|em|%|en|ex|pt|in|cm|mm|pc)$/i;YAHOO.ext.Element.VISIBILITY=1;YAHOO.ext.Element.DISPLAY=2;YAHOO.ext.Element.blockElements=/^(?:address|blockquote|center|dir|div|dl|fieldset|form|h\d|hr|isindex|menu|ol|ul|p|pre|table|dd|dt|li|tbody|tr|td|thead|tfoot|iframe)$/i;YAHOO.ext.Element.borders={l:'border-left-width',r:'border-right-width',t:'border-top-width',b:'border-bottom-width'};YAHOO.ext.Element.paddings={l:'padding-left',r:'padding-right',t:'padding-top',b:'padding-bottom'};YAHOO.ext.Element.margins={l:'margin-left',r:'margin-right',t:'margin-top',b:'margin-bottom'};YAHOO.ext.Element.createStopHandler=function(stopPropagation,handler,scope,override){return function(e){if(e){if(stopPropagation){YAHOO.util.Event.stopEvent(e);}else{YAHOO.util.Event.preventDefault(e);}}
handler.call(override&&scope?scope:window,e,scope);};};YAHOO.ext.Element.cache={};YAHOO.ext.Element.get=function(el,autoGenerateId){if(!el){return null;}
autoGenerateId=true;if(el instanceof YAHOO.ext.Element){el.dom=YAHOO.util.Dom.get(el.id);YAHOO.ext.Element.cache[el.id]=el;return el;}else if(el.isComposite){return el;}else if(el instanceof Array){return YAHOO.ext.Element.select(el);}else if(el===document){if(!YAHOO.ext.Element.cache['__ydocument']){var docEl=function(){};docEl.prototype=YAHOO.ext.Element.prototype;var o=new docEl();o.dom=document;YAHOO.ext.Element.cache['__ydocument']=o;}
return YAHOO.ext.Element.cache['__ydocument'];}
var key=el;if(typeof el!='string'){if(!el.id&&!autoGenerateId){return null;}
YAHOO.util.Dom.generateId(el,'elgen-');key=el.id;}
var element=YAHOO.ext.Element.cache[key];if(!element){element=new YAHOO.ext.Element(key);if(!element.dom)return null;YAHOO.ext.Element.cache[key]=element;}else{element.dom=YAHOO.util.Dom.get(key);}
return element;};var getEl=YAHOO.ext.Element.get;YAHOO.util.Event.addListener(window,'unload',function(){YAHOO.ext.Element.cache=null;});