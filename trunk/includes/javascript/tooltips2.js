var tooltipCallback = null;

function initTooltips(callback){
	if(typeof callback != 'function'){
		return false;
	}
	var tips = getElementsByAttribute("class", "hastooltip");
	tooltipCallback = callback;
	for (var i = 0; i < tips.length; i++){
		attachEventListener(tips[i], "mouseover", showTip, false);
		attachEventListener(tips[i], "mouseout", hideTip, false);
	}

	return true;
}

function showTip(event)
{
  if (typeof event == "undefined")
  {
    event = window.event;
  }

  var target = getEventTarget(event);

  while (target.className == null || !/(^| )hastooltip( |$)/.test(target.className))
  {
    target = target.parentNode;
  }

  var tip = document.createElement("div");
  var content = tooltipCallback(target.getAttribute("id"), tip);

  target.tooltip = tip;
  //target.setAttribute("title", "");

  if (target.getAttribute("id") != "")
  {
    tip.setAttribute("id", target.getAttribute("id") + "tooltip");
  }

  tip.className = "tooltip";
  tip.innerHTML = content;

  var scrollingPosition = getScrollingPosition();
  var cursorPosition = [0, 0];

  if (typeof event.pageX != "undefined" && typeof event.x != "undefined")
  {
    cursorPosition[0] = event.pageX;
    cursorPosition[1] = event.pageY;
  }
  else
  {
    cursorPosition[0] = event.clientX + scrollingPosition[0];
    cursorPosition[1] = event.clientY + scrollingPosition[1];
  }

  tip.style.position = "absolute";
  tip.style.left = cursorPosition[0] + 10 + "px";
  tip.style.top = cursorPosition[1] + 10 + "px";
  tip.style.visibility = "hidden";

  document.getElementsByTagName("body")[0].appendChild(tip);

  var viewportSize = getViewportSize();

  if (cursorPosition[0] - scrollingPosition[0] + 10 + tip.offsetWidth > viewportSize[0] - 25)
  {
    tip.style.left = scrollingPosition[0] + viewportSize[0] - 25 - tip.offsetWidth + "px";
  }
  else
  {
    tip.style.left = cursorPosition[0] + 10 + "px";
  }

  if (cursorPosition[1] - scrollingPosition[1] + 10 + tip.offsetHeight > viewportSize[1] - 25)
  {
    if (event.clientX > (viewportSize[0] - 25 - tip.offsetWidth))
    {
      tip.style.top = cursorPosition[1] - tip.offsetHeight - 10 + "px";
    }
    else
    {
      tip.style.top = scrollingPosition[1] + viewportSize[1] - 25 - tip.offsetHeight + "px";
    }
  }
  else
  {
    tip.style.top = cursorPosition[1] + 10 + "px";
  }

  tip.style.visibility = "visible";

  return true;
}

function hideTip(event)
{
  if (typeof event == "undefined")
  {
    event = window.event;
  }

  var target = getEventTarget(event);

  while (target.className == null || !target.className.match(/(^| )hastooltip( |$)/))
  {
    target = target.parentNode;
  }

  if (target.tooltip != null)
  {
    //target.setAttribute("title", target.tooltip.childNodes[0].nodeValue);
    target.tooltip.parentNode.removeChild(target.tooltip);
  }

  return false;
}

function addLoadListener(fn)
{
  if (typeof window.addEventListener != 'undefined')
  {
    window.addEventListener('load', fn, false);
  }
  else if (typeof document.addEventListener != 'undefined')
  {
    document.addEventListener('load', fn, false);
  }
  else if (typeof window.attachEvent != 'undefined')
  {
    window.attachEvent('onload', fn);
  }
  else
  {
    var oldfn = window.onload;
    if (typeof window.onload != 'function')
    {
      window.onload = fn;
    }
    else
    {
      window.onload = function()
      {
        oldfn();
        fn();
      };
    }
  }
}

function attachEventListener(target, eventType, functionRef, capture)
{
  if (typeof target.addEventListener != "undefined")
  {
    target.addEventListener(eventType, functionRef, capture);
  }
  else if (typeof target.attachEvent != "undefined")
  {
    target.attachEvent("on" + eventType, functionRef);
  }
  else
  {
    eventType = "on" + eventType;

    if (typeof target[eventType] == "function")
    {
      var oldListener = target[eventType];

      target[eventType] = function()
      {
        oldListener();

        return functionRef();
      }
    }
    else
    {
      target[eventType] = functionRef;
    }
  }

  return true;
}

function getEventTarget(event)
{
  var targetElement = null;

  if (typeof event.target != "undefined")
  {
    targetElement = event.target;
  }
  else
  {
    targetElement = event.srcElement;
  }

  while (targetElement.nodeType == 3 && targetElement.parentNode != null)
  {
    targetElement = targetElement.parentNode;
  }

  return targetElement;
}

function getViewportSize()
{
  var size = [0,0];

  if (typeof window.innerWidth != 'undefined')
  {
    size = [
        window.innerWidth,
        window.innerHeight
    ];
  }
  else if (typeof document.documentElement != 'undefined'
      && typeof document.documentElement.clientWidth != 'undefined'
      && document.documentElement.clientWidth != 0)
  {
    size = [
        document.documentElement.clientWidth,
        document.documentElement.clientHeight
    ];
  }
  else
  {
    size = [
        document.getElementsByTagName('body')[0].clientWidth,
        document.getElementsByTagName('body')[0].clientHeight
    ];
  }

  return size;
}

function getScrollingPosition()
{
  //array for X and Y scroll position
  var position = [0, 0];

  //if the window.pageYOffset property is supported
  if(typeof window.pageYOffset != 'undefined')
  {
    //store position values
    position = [
        window.pageXOffset,
        window.pageYOffset
    ];
  }

  //if the documentElement.scrollTop property is supported
  //and the value is greater than zero
  if(typeof document.documentElement.scrollTop != 'undefined'
    && document.documentElement.scrollTop > 0)
  {
    //store position values
    position = [
        document.documentElement.scrollLeft,
        document.documentElement.scrollTop
    ];
  }

  //if the body.scrollTop property is supported
  else if(typeof document.body.scrollTop != 'undefined')
  {
    //store position values
    position = [
        document.body.scrollLeft,
        document.body.scrollTop
    ];
  }

  //return the array
  return position;
}

function getElementsByAttribute(attribute, attributeValue)
{
  var elementArray = new Array();
  var matchedArray = new Array();

  if (document.all)
  {
    elementArray = document.all;
  }
  else
  {
    elementArray = document.getElementsByTagName("*");
  }

  for (var i = 0; i < elementArray.length; i++)
  {
    if (attribute == "class")
    {
      var pattern = new RegExp("(^| )" + attributeValue + "( |$)");

      if (elementArray[i].className.match(pattern))
      {
        matchedArray[matchedArray.length] = elementArray[i];
      }
    }
    else if (attribute == "for")
    {
      if (elementArray[i].getAttribute("htmlFor") || elementArray[i].getAttribute("for"))
      {
        if (elementArray[i].htmlFor == attributeValue)
        {
          matchedArray[matchedArray.length] = elementArray[i];
        }
      }
    }
    else if (elementArray[i].getAttribute(attribute) == attributeValue)
    {
      matchedArray[matchedArray.length] = elementArray[i];
    }
  }

  return matchedArray;
}