Object.prototype.nextObject = function() {
	var n = this;
	do {
		n = n.nextSibling;
	}while (n && n.nodeType != 1);
	return n;
};

Object.prototype.previousObject = function() {
	var p = this;
	do {
		p = p.previousSibling;
	}while (p && p.nodeType != 1);
	return p;
};


if (typeof Uversa == "undefined") {
	/**
	* The Uversa global namespace object
	* @class Uversa
	* @static
	*/
	var Uversa = {};
}

if (typeof Uversa.SureInvoice == "undefined") {
	/**
	* The Uversa.SureInvoice global namespace object
	* @class Uversa.SureInvoice
	* @static
	*/
	Uversa.SureInvoice = {};
}

Uversa.SureInvoice.AutoComplete = function(){
	var oACDS;
	var oAutoComplete;
	var oConfig;
	
	return {
		init: function(config){
			this.oConfig = config;
			var siQueryPath = "json.php/findTasks"; 
			var siSchema = ["tasks", "string", "id", "company_name", "project_name", "task_name"]; 
			oACDS = new YAHOO.widget.DS_XHR(siQueryPath, siSchema); 
			oACDS.maxCacheEntries = 0;
			oAutoComplete = new YAHOO.widget.AutoComplete(config.inputId, config.containerId, oACDS); 
			oAutoComplete.input2Id = config.input2Id;
			oAutoComplete.inputId = config.inputId;
			oAutoComplete.useShadow = true;
			oAutoComplete.itemCodeId = config.itemCodeId;
			if(typeof config.maxResults != 'undefined'){
				oAutoComplete.maxResultsDisplayed = config.maxResults;
			}else{
				oAutoComplete.maxResultsDisplayed = 20;
			}
			oAutoComplete.forceSelection = true;
			oAutoComplete.formatResult = function(oResultItem, sQuery) { 
				return oResultItem[2] + " - " + oResultItem[3] + " - " + oResultItem[4]; 
			};
			
			oAutoComplete.dataErrorEvent.subscribe(Uversa.SureInvoice.AutoComplete.handleError); 
			oAutoComplete.dataReturnEvent.subscribe(Uversa.SureInvoice.AutoComplete.myOnDataReturn); 
			oAutoComplete.itemSelectEvent.subscribe(Uversa.SureInvoice.AutoComplete.myOnItemSelect); 
			oAutoComplete.textboxBlurEvent.subscribe(Uversa.SureInvoice.AutoComplete.myOnTextboxBlur); 
		},
		
		myOnDataReturn: function(sType, aArgs){
			var oAutoComp = aArgs[0]; 
			var sQuery = aArgs[1]; 
			var aResults = aArgs[2]; 

			if(aResults.length == 0) { 
				oAutoComp.setBody("<div>No matching results</div>"); 
			} 
		},

		handleError: function(o){
			alert("JSON Error:\nStatus: " + o.status + "\nText: " + o.statusText);	
		},
		
		parseResponse: function(o){
			var response = o.responseText; 
			response = response.split("<!")[0]; 
			try{
				result = YAHOO.ext.util.JSON.decode(response);
				if(typeof result.error != 'undefined'){
					alert('Error getting timer data from server:\n' + result.error);
				}else{
					return result.result;
				}
			}catch(ex){
				alert("Could not decode response: \n" + response);
			}
			return false;
		},
		
		onGetDefaultItemCodeSuccess: function(o){
			result = Uversa.SureInvoice.AutoComplete.parseResponse(o);
			if(result == false){
				return;
			}

			var default_item_code_id = result;
			var oItemCode = document.getElementById(this.itemCodeId);
			for(i = 0; i < oItemCode.options.length; i++){
				if(oItemCode.options[i].value == default_item_code_id){
					oItemCode.selectedIndex = i;
					return;
				}
			}
		},
		
		myOnItemSelect: function(elItem, oResult){
			var elId = YAHOO.util.Dom.get(this.input2Id);
			if(typeof elId != 'undefined'){
				elId.value = oResult[2][1];
				YAHOO.util.Dom.removeClass(this.inputId, 'input_error');
			}

			YAHOO.util.Connect.asyncRequest('GET', 'json.php/getDefaultItemCode/'+oResult[2][1], { success: Uversa.SureInvoice.AutoComplete.onGetDefaultItemCodeSuccess, scope: this}); 
		},
		
		myOnTextboxBlur: function( oSelf ){
			var elInput = YAHOO.util.Dom.get(this.inputId);
			var elId = YAHOO.util.Dom.get(this.input2Id);
			if(typeof elId != 'undefined' && elInput.value == ''){
				YAHOO.util.Dom.addClass(elInput, 'input_error');
				alert("You must use the autocomplete list to select a task, type in a few letters of a project, company or task name and select the correct entry in the list that is provided!");
				elId.value = '';
			}
		}
	};
}();

Uversa.SureInvoice.TADialog = function(){
	var dialog;
	
	return {
		init: function(){
			dialog = new YAHOO.widget.Dialog("SIAddTAPopup", 
				{ 
					width : "800px",
					height: "250px",
					fixedcenter : true,
					visible : false, 
					constraintoviewport : true,
					buttons : [ 
						{ text:"Submit", handler:Uversa.SureInvoice.TADialog.handleSubmit, isDefault:true },
						{ text:"Cancel", handler:Uversa.SureInvoice.TADialog.handleCancel } 
					]
				}
			);

			var handleSuccess = function(o) { 
				var response = o.responseText; 
				response = response.split("<!")[0]; 
				try{
					result = YAHOO.ext.util.JSON.decode(response);
					if(typeof result.error != 'undefined'){
						alert('Error adding activity:\n' + result.error);
					}
					Uversa.SureInvoice.RecentTime.update();
				}catch(ex){
					alert("Error processing response; \n" + ex.message);
				}
			}; 
			
			var handleFailure = function(o) { 
				alert("Submission failed: " + o.status); 
			}; 

			dialog.callback = { success: handleSuccess, 
								failure: handleFailure 
			}; 		
			
			dialog.render();
		},
		
		handleSubmit: function(){
			dialog.submit();
		},
		
		handleCancel: function(){
			dialog.cancel();
		},
		
		show: function(){
			// Clear form
			var input = document.getElementById('ta_popup_task_id');
			input.value = '';
			input = document.getElementById('ta_popup_task_name');
			input.value = '';
			input = document.getElementById('ta_popup_text');
			input.value = '';
			
			dialog.show();
		}
	};
}();



YAHOO.util.Event.onContentReady('SIAddTAPopup', Uversa.SureInvoice.TADialog.init);
YAHOO.util.Event.addListener("SIOpenTADialog", "click", Uversa.SureInvoice.TADialog.show);
YAHOO.util.Event.onContentReady('SIAddTAPopup', Uversa.SureInvoice.AutoComplete.init, { inputId: "ta_popup_task_name", containerId: "ta_popup_ac_container", input2Id: "ta_popup_task_id", maxResults: 9, itemCodeId: 'ta_popup_item_code_id'});

Uversa.SureInvoice.SideBar = function(){
	return {
		init: function(){
			// Global var gSlideBarOpen is set by PHP in the header.php file
			Uversa.SureInvoice.SideBar.toggle(gSideBarOpen, false);
		},
		
		toggle: function(show, saveSetting){
			var sb = document.getElementById("sidebar");	
			var ss = document.getElementById("sidebarShow");		
			var ma = document.getElementById("main");			
			
			if(show){
				sb.style.display = "block";
				ss.style.display = "none";
				ma.style.marginLeft = "165px";
				if(saveSetting != false){
					YAHOO.util.Connect.asyncRequest('GET', 'json.php/saveUserSetting/show_menu/1'); 
				}
			}else{
				sb.style.display = "none";
				ss.style.display = "block";		
				ma.style.marginLeft = "26px";
				if(saveSetting != false){
					YAHOO.util.Connect.asyncRequest('GET', 'json.php/saveUserSetting/show_menu/0'); 
				}
			}
				
		}
	};
}();

YAHOO.util.Event.onAvailable('sidebar', Uversa.SureInvoice.SideBar.init);

Uversa.SureInvoice.Timers = function(){
	var timerupdater;
	var tplTimerRunning = YAHOO.ext.DomHelper.createTemplate(
		{tag: 'div', cls: 'timer_content', children: [
			{tag: 'span', cls: 'timer_name', html: '{1}'},
			{tag: 'span', cls: 'timer_count', html: '{2}'},
			{tag: 'a', href: 'javascript:;', onclick: 'Uversa.SureInvoice.Timers.pauseTimer({0})', children: [
				{tag: 'img', src: 'templates/blueish/timer_pause.png', align: 'top'}
			]},
			{tag: 'a', href: 'javascript:;', onclick: 'Uversa.SureInvoice.Timers.stopTimer({0})', children: [
				{tag: 'img', src: 'templates/blueish/timer_stop.png', align: 'top'}
			]},
			{tag: 'a', href: 'javascript:;', onclick: 'Uversa.SureInvoice.Timers.deleteTimer({0})', children: [
				{tag: 'img', src: 'images/delete.png', align: 'top'}
			]}
		]
	});
	
	var tplTimerPaused = YAHOO.ext.DomHelper.createTemplate(
		{tag: 'div', cls: 'timer_content', children: [
			{tag: 'span', cls: 'timer_name', html: '{1}'},
			{tag: 'span', cls: 'timer_count', html: '{2}'},
			{tag: 'a', href: 'javascript:;', onclick: 'Uversa.SureInvoice.Timers.startTimer({0})', children: [
				{tag: 'img', src: 'templates/blueish/timer_play.png', align: 'top'}
			]},
			{tag: 'a', href: 'javascript:;', onclick: 'Uversa.SureInvoice.Timers.stopTimer({0})', children: [
				{tag: 'img', src: 'templates/blueish/timer_stop.png', align: 'top'}
			]},
			{tag: 'a', href: 'javascript:;', onclick: 'Uversa.SureInvoice.Timers.deleteTimer({0})', children: [
				{tag: 'img', src: 'images/delete.png', align: 'top'}
			]}
		]
	});
	
	return {
		init: function(){
			// Global var gTimersOpen is set by PHP in the header.php file
			Uversa.SureInvoice.Timers.onResize();
			Uversa.SureInvoice.Timers.toggle(gTimersOpen, false);
		},
		
		toggle: function(show, saveSetting){
			var timerShown = document.getElementById("timers_shown");
			var timerHidden = document.getElementById("timers_hidden");
		
			if(show){
				timerHidden.style.display = 'none';
				timerShown.style.display = 'block';
				Uversa.SureInvoice.Timers.updateTimers();
				if(saveSetting != false){
					YAHOO.util.Connect.asyncRequest('GET', 'json.php/saveUserSetting/show_timers/1'); 
				}
			}else{
				timerShown.style.display = 'none';
				timerHidden.style.display = 'block';
				if(saveSetting != false){
					YAHOO.util.Connect.asyncRequest('GET', 'json.php/saveUserSetting/show_timers/0'); 
				}
				clearTimeout(timerupdater);
			}
		},
		
		onResize: function(){
			var headerRegion = YAHOO.util.Dom.getRegion('header');
			var timerX = headerRegion.right;
			var timerY = headerRegion.bottom;
		
			var timer = document.getElementById("timers");
			timer.style.display = 'block';
			var timerRegion = YAHOO.util.Dom.getRegion('timers');
			YAHOO.util.Dom.setX('timers', timerX - (timerRegion.right - timerRegion.left));
			YAHOO.util.Dom.setY('timers', timerY);			
		},

		handleError: function(o){
			alert("JSON Error:\nStatus: " + o.status + "\nText: " + o.statusText);	
		},
		
		parseResponse: function(o){
			var response = o.responseText; 
			response = response.split("<!")[0]; 
			try{
				result = YAHOO.ext.util.JSON.decode(response);
				if(typeof result.error != 'undefined'){
					alert('Error getting timer data from server:\n' + result.error);
				}else{
					return result.result;
				}
			}catch(ex){
				alert("Could not decode response: \n" + response);
			}
			return false;
		},
		
		getTimerDataSuccess: function(o) {
			result = Uversa.SureInvoice.Timers.parseResponse(o);
			if(result == false){
				return;
			}
			var timers = document.getElementById('running_timers');
			timers.innerHTML = '';
			for(i=0; i<result.length; i++){
				Uversa.SureInvoice.Timers.drawTimer(result[i].id, result[i].name, result[i].status, result[i].total);
			}
			clearTimeout(timerupdater);
		   	timerupdater = setTimeout(Uversa.SureInvoice.Timers.updateTimers, 15000);			

		   	/*
			var div = YAHOO.util.Dom.get('timers_shown');
			//div.style.visibility = 'hidden';
			var region = YAHOO.util.Dom.getRegion('timers_shown');
			//div.style.display = 'none';
			var anim = new YAHOO.util.Anim('timers_shown', { height: {from: 0, to: (region.bottom - region.top)} }, 1, YAHOO.util.Easing.backOut);
 			anim.animate();
			*/
		},
		
		updateTimers: function(){
			YAHOO.util.Connect.asyncRequest('GET', 'json.php/getTimerData', { success: Uversa.SureInvoice.Timers.getTimerDataSuccess}); 
		},
		
		pauseTimerSuccess: function(o) {
			result = Uversa.SureInvoice.Timers.parseResponse(o);
			if(result == false){
				return;
			}
			Uversa.SureInvoice.Timers.updateTimer(result.id, result.name, result.status, result.total);
		},

		pauseTimer: function(id){
			YAHOO.util.Connect.asyncRequest('GET', 'json.php/pauseTimer/'+id, { success: Uversa.SureInvoice.Timers.pauseTimerSuccess}); 
		},
		
		stopTimer: function(id){
			Uversa.SureInvoice.Timers.pauseTimer(id);
			Uversa.SureInvoice.TADialog.show();			
		},
		
		startTimerSuccess: function(o) {
			result = Uversa.SureInvoice.Timers.parseResponse(o);
			if(result == false){
				return;
			}
			Uversa.SureInvoice.Timers.updateTimer(result.id, result.name, result.status, result.total);
		},
		
		startTimer: function(id){
			YAHOO.util.Connect.asyncRequest('GET', 'json.php/startTimer/'+id, { success: Uversa.SureInvoice.Timers.startTimerSuccess}); 
		},
		
		deleteTimerSuccess: function(o) {
			Uversa.SureInvoice.Timers.parseResponse(o);
		},

		deleteTimer: function(id){
			YAHOO.util.Connect.asyncRequest('GET', 'json.php/deleteTimer/'+id, { success: Uversa.SureInvoice.Timers.deleteTimerSuccess}); 
			var div = document.getElementById('timer_'+id);
			div.innerHTML = '';
		},
		
		addTimerSuccess: function(o) {
			result = Uversa.SureInvoice.Timers.parseResponse(o);
			if(result == false){
				return;
			}
			Uversa.SureInvoice.Timers.drawTimer(result.id, result.name, result.status, result.total);
			var timer_name = document.getElementById('timer_new_name');
			timer_name.value = 'Enter timer name...';
		},

		addTimer: function(){
			var timer_name = document.getElementById('timer_new_name');
			if(timer_name.value == '' || timer_name.value == 'Enter timer name...'){
				alert("You must provide a name for the new timer");
				return;
			}
			var name = timer_name.value;
			YAHOO.util.Connect.asyncRequest('GET', 'json.php/addTimer/'+name, { success: Uversa.SureInvoice.Timers.addTimerSuccess}); 			
		},
		
		drawTimer: function(id, name, status, total){
			// Create the container
			YAHOO.ext.DomHelper.append('running_timers', {tag: 'div', id: 'timer_'+id, cls: 'timer'});
			
			// Add the content
			if(status == 'RUNNING'){
				tplTimerRunning.append('timer_'+id, [id, name, total]);
			}else{
				tplTimerPaused.append('timer_'+id, [id, name, total]);
			}
		},
		
		updateTimer: function(id, name, status, total){
			var timer_id = document.getElementById('timer_'+id);
			timer_id.innerHTML = '';
			
			// Add the content
			if(status == 'RUNNING'){
				tplTimerRunning.append('timer_'+id, [id, name, total]);
			}else{
				tplTimerPaused.append('timer_'+id, [id, name, total]);
			}	
			
		}
	};
}();

YAHOO.util.Event.onContentReady('timers', Uversa.SureInvoice.Timers.init);
//YAHOO.util.Event.addListener(window, 'load', Uversa.SureInvoice.Timers.init);
YAHOO.util.Event.addListener(window, 'resize', Uversa.SureInvoice.Timers.onResize);
YAHOO.util.Event.addListener('timer_new_name', 'focus', function(){
	var el = document.getElementById('timer_new_name');
	if(el.value == 'Enter timer name...'){
		el.value = '';
	}
});

YAHOO.widget.Calendar.IMG_ROOT = "images/";

Uversa.SureInvoice.Calendar = function(){
	var oCal;
	
	return {
		init: function(){
			oCal = new YAHOO.widget.Calendar('si_cal', 'SICalContainer', { 
				close: true,
				NAV_ARROW_LEFT: 'images/callt.gif',
				NAV_ARROW_RIGHT: 'images/calrt.gif'
			});
			
			oCal.hide();
			oCal.render();
		},
		
		currentTime: function(date_id, time_id, date2_id, time2_id){
			var tbDate = document.getElementById(date_id);
			var tbTime = document.getElementById(time_id);
			var right_now = new Date();
			if(typeof tbDate != 'undefined'){
				tbDate.value = (right_now.getMonth()+1)+'/'+right_now.getDate()+'/'+(right_now.getYear() > 2000 ? right_now.getYear() : right_now.getYear() + 1900);
			}
			if(typeof tbTime != 'undefined'){
				tbTime.value = right_now.getHours()+':'+right_now.getMinutes();
			}
			
			if(typeof date2_id != 'undefined'){
				tbDate2 = document.getElementById(date2_id);
				tbDate2.value = (right_now.getMonth()+1)+'/'+right_now.getDate()+'/'+(right_now.getYear() > 2000 ? right_now.getYear() : right_now.getYear() + 1900);
			}
			if(typeof time2_id != 'undefined'){
				tbTime2 = document.getElementById(time2_id);
				tbTime2.value = right_now.getHours()+':'+right_now.getMinutes();
			}
		},
		
		show: function(target, container, target2){
			if(typeof container != 'undefined'){
				containerRegion = YAHOO.util.Dom.getRegion(container);
			}
			if(typeof target == 'string'){
				target = YAHOO.util.Dom.get(target);
			}
			
			if(typeof target2 == 'string'){
				target2 = YAHOO.util.Dom.get(target2);
			}

			//oCal.reset();
			oCal.selectEvent.unsubscribeAll();
			/*
			if(target.value != ''){
				oCal.select(target.value);
				oCal.render();
			}
			*/
			var selectHandler = function(type, args, obj){
				var dates = args[0];
				var date = dates[0]; 
				var year = date[0], month = date[1], day = date[2]; 
				
				target.value = month + "/" + day + "/" + year; 
				if(typeof(target2) != 'undefined'){
					target2.value = month + "/" + day + "/" + year; 
				}
				oCal.hide();
			};
			
			oCal.selectEvent.subscribe(selectHandler);
			var headerRegion = YAHOO.util.Dom.getRegion(target);
			var calX = headerRegion.right;
			var calY = headerRegion.bottom;
		
			var cal = YAHOO.util.Dom.get('SICalContainer');
			cal.style.display = 'block';
			if(typeof containerRegion != 'undefined'){
				YAHOO.util.Dom.setXY(cal, [calX, calY]);
				calRegion = YAHOO.util.Dom.getRegion(cal);
				if(!containerRegion.contains(calRegion)){
					if(calRegion.top < containerRegion.top){
						YAHOO.util.Dom.setY(cal, containerRegion.top - calRegion.top + 5);
					}
					if(calRegion.bottom > containerRegion.bottom){
						newY = calRegion.top - (calRegion.bottom - containerRegion.bottom) - 5;
						YAHOO.util.Dom.setY(cal, newY);
					}
				}
			}else{
				YAHOO.util.Dom.setXY(cal, [(calX + 3), (calY + 3)]);
			}
			
		},
		
		hide: function(){
			oCal.hide();
		}
	};
}();

Uversa.SureInvoice.RecentTime = function(){
	var tempHTML = '<li><strong>{0}</strong> - <a href="reports.php?resource_id={1}&start_ts={3}&end_ts={4}&save=Report">{2}</a></li>';
	var tplRecentTimeEntery = new YAHOO.ext.DomHelper.Template(tempHTML);

	return {
		update: function(){
			var handleSuccess = function(o){
				var response = o.responseText; 
				var times;
				response = response.split("<!")[0]; 
				try{
					result = YAHOO.ext.util.JSON.decode(response);
					if(typeof result.error != 'undefined'){
						alert('Error getting timer data from server:\n' + result.error);
					}else{
						times = result.result;
					}
				}catch(ex){
					alert("Could not decode response: \n" + response);
				}
	
				
				var timeList = document.getElementById('recentTimeList');
				timeList.innerHTML = '';
				for(i=0; i<times.length; i++){
					tplRecentTimeEntery.append('recentTimeList', [times[i].date, times[i].user_id, times[i].hours, times[i].start_ts, times[i].end_ts]);
				}
			};
			
			YAHOO.util.Connect.asyncRequest('GET', 'json.php/getRecentTime', {success: handleSuccess});
		}
	};
}();


YAHOO.util.Event.onAvailable('SICalContainer', Uversa.SureInvoice.Calendar.init);

// Couple of remaining global functions for compatibility
function toggleGrid(el){
	el.blur();
	el.onfocus = function(){
		this.blur();
	};
	nextObj = el.nextObject();
	if(nextObj.style.display == "none"){
		nextObj.style.display = "";
		el.firstChild.src = "images/arrow_down.jpg";
	}else{
		el.firstChild.src = "images/arrow_right.jpg";
		nextObj.style.display = "none";
	}
}
	
function fixPNG(){
	var msie = ((navigator.appVersion.indexOf("MSIE")!= -1)&&!window.opera)? true : false; 
	if(msie){
		allimg = document.getElementsByTagName("img");
		for(var i=0;i<allimg.length;i++){
			allimg[i].runtimeStyle.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='"+allimg[i].src+ "',sizingMethod='scale')";				
		}
	}
}

// Invert checks on all checkboxes on a page
// 
// All check boxes must be on a form that
// is named chk

function SelectAll(prefix) {
	for (var i = 0; i < document.chk.elements.length; i++) {
		if ((document.chk.elements[i].name.substr(0, prefix.length) == prefix) && (document.chk.elements[i].style.visibility != 'hidden')) {
			document.chk.elements[i].checked = !(document.chk.elements[i].checked);
		}
	}
}
