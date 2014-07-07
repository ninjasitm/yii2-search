/*!
 * Nitm v1 (http://www.ninjasitm.com)
 * Copyright 2012-2014 NITM, Inc.
 */

if (typeof jQuery === 'undefined') { throw new Error('Nitm\'s JavaScript requires jQuery') }

//var r = {'url':'/r/', 'type':'POST', 'dataType':'json', 'token':'c1e48dd56b43196a06a66b67ec3bede6'};
function Nitm ()
{
	var self = this;
	this.events = {};
	this.current ='';
	this.modules = {};
	this.r = {'url':'/r/', 'type':'POST', 'dataType':'json'};
	this.responseSection = 'alert';
	this.classes = {
		warning: 'alert alert-warning',
		success: 'alert alert-success',
		information: 'alert alert-info',
		error: 'alert alert-info',
	};
	
	/* gap is in millisecs */
	this.delay = function(gap) { 
		var then,now; 
		then=new Date().getTime();
		now=then;
		while((now-then) < gap)
		{
			now=new Date().getTime();
			//notify(now, 'notify', true);
		}
	}
	
	this.popUp = function (url, id, h, w, scr) 
	{
		day = new Date();
		id = day.getTime();
		h = (eval(h) != undefined) ? h : '800';
		w = (eval(w) != undefined) ? w : '720';
		scr = ((eval(scr)) == '0') ? 'no' : 'yes';
		window.open(url, id, 'toolbar=0,scrollbars='+scr+',location=no,statusbar=no,menubar=no,resizable=no,width='+w+',height='+h);
		return false;
	}	
	
	this.animateScroll = function (elem, parent, highlight, highlight_class)
	{
		var element = $(this.getObj(elem).get(0));
		var container = this.getObj(((!parent) ? element.parent().attr('id') : parent));
		switch(true)
		{
			case (element.position().top > container.height()) && (element.position().top < 0):
				var scrollToPos = container.scrollTop + element.position().top;
				break;
				
			default:
				var scrollToPos = element.position().top;
				break;
		}
		container.animate({scrollTop: scrollToPos}, 150, function () {
			try
			{
				switch(highlight)
				{
					case true:
						element.effect("pulsate", {times: 3}, 150, 'ease');
						break;
				}
			} catch(error) {};
		});
	}
	
	this.animateSubmit = function (form, before)
	{
		var _form = getObj(form);
		switch(1)
		{
			case _form.find("input[type='submit']").get(0) !== undefined:
				var button = _form.find("input[type='submit']");
				break;
				
			case _form.find("button[type='submit']").get(0) !== undefined:
				var button = _form.find("button[type='submit']");
				break;
				
			default:
				var button = _form.find("input[type='image']");
				break;
		}
		switch(before)
		{
			case true:
				try
				{
					button.attr('oldtype', button.attr('type'));
					button.attr('type', 'image');
					button.attr('oldsrc', button.attr('src'));
					button.attr('src', globals.loading_src);
					button.prop('disabled', true);
					button.attr('oldonclick', button.attr('onclick'));
					button.click(void(0));
				} catch(error) {};
				break;
				
			default:
				try
				{
					button.attr('src', button.attr('oldsrc'));
					button.attr('onclick', button.attr('oldonclick'));
					button.attr('type', button.attr('oldtype'));
					button.removeAttr('oldonclick');
					button.removeAttr('oldsrc');
					button.removeAttr('oldtype');
					button.removeProp('disabled');
				} catch(error) {};
				break;
		}
	}
	
	this.dump = function (arr,level) 
	{
		var dumped_text = "";
		if(!level) level = 0;
		
		//The padding given at the beginning of the line.
		var level_padding = "";
		for(var j=0;j<level+1;j++) level_padding += "    ";
		
		if(typeof(arr) == 'object') 
		{ //Array/Hashes/Objects
			for(var item in arr) 
			{
				var value = arr[item];
				
				if(typeof(value) == 'object') 
				{ //If it is an array,
					dumped_text += level_padding + "'" + item + "' ...\n";
					dumped_text += dump(value,level+1);
				} 
				else 
				{
					dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
				}
			}
		} 
		else 
		{ //Stings/Chars/Numbers etc.
			dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
		}
		return dumped_text;
	} 
	
	this.objectLength = function (obj)
	{
		switch(obj.length != undefined)
		{
			case true:
				count = obj.length;
				break;
				
			default:
				count = 0;
				break;
		}
		switch(count)
		{
			case 0:
				for (k in obj) if (obj.hasOwnProperty(k)) count++;
				break;
		}
		return count;
	}
	
	this.notify = function (nMsg, nClass, nObj)
	{
		var nMessage = new String(nMsg);
		var obj = $(null);
		if(nMessage.length <= 5)
		{
			return false;
		}
		else
		{
			var obj = this.getObj((nObj == undefined ? this.responseSection : nObj), null, false, false);
			if(obj instanceof jQuery)
			{
				obj.fadeIn();
				obj.removeClass().addClass(nClass);
				obj.html(nMessage);
			}
		}
		return obj;
	}
	
	this.updateSingle = function (uMsg, uClass, uApp, uID)
	{
		var uMessage = new String(uMsg);
		switch(uApp)
		{
			case true:
				this.getObj(uID).append("<span class='"+uClass+"'>"+uMessage+"</span>");
				break;
				
			default:
				this.getObj(uID).html("<span class='"+uClass+"'>"+uMessage+"</span>");
				break;
		}
	}
	
	this.clearNotify = function ()
	{
		this.getObj(this.responseSection).html("");
	}
	
	//function fo focus items with special box
	this.setFocus = function (item)
	{
		var obj = this.getObj(item);
		var orig_border = obj.css('border');
		obj.effect('pulsate', {times:2}, 'fast');
		obj.focus();
	}
	
	this.toggleElem = function (selector, by, by_val)
	{
		switch(typeof selector)
		{
			case 'string':
			case 'number':
				break;
				
			case 'object':
				selector = (selector.id == undefined) ? selector.name : selector.id;
				break;
				
			default:
				return false;
				break;
		}
		selector = this.this.jqEscape(selector);
		switch(by)
		{
			case 'name':
				selector = selector+' [name="'+by_val+'"]';
				break;
				
			case 'class':
				var obj = (selector[0] != '.') ? '.'+selector : selector;
				obj = '\\'+obj;
				break;
				
			default:
				selector = (selector[0] != '#') ? '#'+selector : selector;
				break
		}
		try {this.getObj(selector, '', false, false).each(function() {this.disabled = !this.disabled;})} catch(error) {};
	}
	
	this.addOnLoadEvent = function (func)
	{
		switch(document.readyState)
		{
			case 'complete':
			func();
			break;
				
			default:
			$(document).ready(function () {func()});
			break;
		}
	}
	
	
	//function to add elements to a parent element
	/*
	 p ar = the parent *element
	 cData = the type of data to append. Can be an array or single element
	 'events' = the events to be added to this element. Can be an array or single event
	 'function' = the function to be handled by the event'
	 'type' = the type of the event
	 'listeners' = the listeners to be added to this element. Can be an array or single listener
	 'function' = the function to be handled by this listener
	 'type' = the type of the listener
	 'type' = the type of the element
	 '...' = any attribute which can be handled by this element
	 '
	 */
	this.addChildrenTo = function (_parent, cData)
	{
		var par = this.getObj(_parent);
		if(typeof(par) == 'object')
		{
			if(typeof(cData) == 'object')
			{
				var childElem = (typeof(cData['type']) != 'undefined') ? document.createElement(cData['type']) : false;
				for(var attr in cData)
				{
					switch(attr)
					{
						case 'events':
							for(event in cData[attr])
							{
								cData[attr][event] = (typeof(cData[attr][event]) == 'object') ? cData[attr][event] : new Object(cData[attr][event]);
								for(subEvent in cData[attr][event])
								{
									$(childElem).on(cData[attr][event][subEvent]['type'], function(){
										cData[attr][event][subEvent]['function']
									});
								}
							}
							break;
							
						case 'type':
							break;
							
						default:
							switch(typeof(cData[attr]))
							{
								
								case 'object':
									this.addChildrenTo(par, cData[attr]);
									break;
									
								default:
									childElem.setAttribute(attr, cData[attr]);
									break;
							}
							break;
					}
				}
				par.appendChild(childElem);
			}
		}
	}
	
	this.handleVis = function (e, onlyShow)
	{
		switch(onlyShow)
		{
			case true:
			this.getObj(e).each(function () {
				$(this).show('slow');
				if($(this).hasClass('hidden')) 
					$(this).removeClass('hidden');
			});
			break;
			
			default:
			this.getObj(e).each(function () {
				$(this).slideToggle('slow');
				if($(this).hasClass('hidden')) 
					$(this).toggleClass('hidden');
			});
			break;
		}
	}
	//function to hcndle element visibility and hide others
	this.handleVisHideOther = function (iSub, iRowObj)
	{
		if((this.getObj(iSub).data('hideThis').length > 0))
		{
			handleVis(this.getObj(iSub).data('hideThis'));
		}
		this.getObj(iSub).data('hideThis', {0:iSub, 1:iRowObj});
		handleVis(iSub);
	}
	
	//get the object information
	this.getObj = function (selector, by, alert_obj, esc)
	{
		esc = (esc == undefined) ? true : esc;
		if(selector instanceof jQuery)
		{
			try
			{
				switch(!selector.attr('id'))
				{
					case true:
						uniqueId = new Date().getTime();
						$(selector).attr('id', 'object'+uniqueId);
						break;
				}
			} catch (error) {};
			selector = selector.attr('id');
		} else if((typeof HTMLElement === "object" && selector instanceof HTMLElement) || //DOM2
			(selector && typeof selector === "object" && 
			selector !== null && selector.nodeType === 1 && 
			typeof selector.nodeName==="string")) {
			try
			{
				switch(!selector.id)
				{
					case true:
						uniqueId = new Date().getTime();
						selector.setAttribute('id', 'object'+uniqueId);
						break;
				}
			} catch (error) {};
			selector = selector.id;
		} else {
			switch(typeof selector)
			{
				case 'string':
				case 'number':
					break;
					
				default:
					return false;
					break;
			}
		}
		switch(selector)
		{
			case 'body':
			case 'document':
			case 'window':
			case document:
			case window:
				var obj = selector;
				break;
				
			default:
				selector = (esc === true) ? this.jqEscape(selector) : selector;
				if(selector[0] == '.') {
					by = 'class';
				}
				switch(by)
				{
					case 'name':
						var obj = '[name="'+selector+'"]';
						break;
					
					case 'class':
						var obj = (selector[0] != '.') ? '.'+selector : selector;
						obj = '\\'+obj;
						break;
						
					default:
						switch((selector[0] == '[')
						|| (selector.indexOf(',') != -1)
						)
						{
							case true:
								var obj = selector;
								break;
								
							default:
								var obj = (selector[0] != '#') ? '#'+selector : selector;
								break;
						}
						break
				}
				switch(alert_obj)
				{
					case true:
						alert(selector+" -> "+obj);
						break;
				}
				break;
		}
		return $(obj);
			
	}
	
	this.jqEscape = function (val) 
	{
		// return new String(val).replace(/[-[\]{}()*+?.,\\^$|]/g, '\\$&');
		return new String(val).replace(/[-{}()*+?.,\\^$|]/g, '\\$&');
	}
	
	function charsLeft(field, cntfield, maxlimit) 
	{
		field = this.getObj(field).get(0);
		cntfield = this.getObj(cntfield).get(0);
		switch(field.value.length >= maxlimit+1)
		{
			case true:
				field.value = field.value.substring(0, maxlimit);
				cntfield.innerHTML = maxlimit - field.value.length;
				alert("You've maxed out the "+maxlimit+" character limit\n\nPlease shorten your message. :-).");
				break;
				
			default:
				cntfield.innerHTML = maxlimit - field.value.length;
				break;
		}
	}
	
	this.doRequest = function (rUrl, rData, success, error, timeout, headers)
	{
		switch(this.r.hasOwnProperty('token'))
		{
			case true:
			this.r.beforeSend = function (xhr) {xhr.setRequestHeader("Authorization", "Basic "+this.r.token);};
			break;
		}
		if (rUrl != undefined) {
			//code
			this.r.url = rUrl;
		}
		this.r.data = rData;
		this.r.success = success;
		this.r.error = (error == undefined) ? function (e) { console.log(e) } : error;
		this.r.timeout = (timeout !== undefined) ? timeout : 30000;
		this.r.type = 'POST';
		if(headers != undefined)
		{
			for(var key in headers)
			{
				this.r.beforeSend = function (xhr) {xhr.setRequestHeader(key, headers[key])};
			}
		}
		var ret_val = $.ajax(this.r);
		return ret_val;
	}
	
	this.doRequestFileData = function (_form, data)
	{
		//make sure the form is setup to send files
		this.$form.attr('enctype', "multipart/form-data");
		this.$form.attr('encoding', "multipart/form-data");
		
		// match anything not a [ or ]
		regexp = /^[^[\]]+/;
		
		//Deliver files with ajax submission
		var data = (data == undefined) ? new FormData() : data;
		this.$form.find(":file").each(function (i, file) {
			var fileInputName = regexp.exec(file.name);
			data.append(fileInputName+'['+i+']', file);
		});
		return data;
	}
	
	this.toHex = function (dec)
	{ 
		var result = (parseInt(dec).toString(16)); 
		if(result.length ==1)
		{ 
			result= ("0" +result); 
		}
		return result.toUpperCase(); 
	}
	
	this.rgbToHex = function (rgbInput)
	{
		var r, g, b; 
		var commaFirst, commaSec; 
		var output; 
		if(rgbInput.indexOf("rgb(") == -1 || rgbInput.indexOf(")") == -1) 
		{
			return rgbInput;
		}
		var tempStr = rgbInput.substring(rgbInput.indexOf("rgb(")); 
		var tempStrIndex = rgbInput.indexOf(tempStr); 
		var rgb = rgbInput.substring(tempStrIndex+4, tempStrIndex+ tempStr.indexOf(")"));
		commaFirst = rgb.indexOf(","); commaSec = rgb.lastIndexOf(",");
		r = rgb.substring(0, commaFirst); 
		g = rgb.substring(commaFirst+1, commaSec); 
		b = rgb.substring(commaSec+1);
		output = rgbInput.substring(0, rgbInput.indexOf("rgb(")); 
		output += "#"+toHex(r)+toHex(g)+toHex(b); 
		output += rgbInput.substring(tempStrIndex+tempStr.indexOf(")")+1);
		if(output.indexOf("rgb(")>0) 
		{
			output= rgbToHex(output);
		} 
		return output;
	}
	
	this.createElementHierarchy = function (_hierarchy, chld_ins_pt, _parent)
	{
		if(!_hierarchy)
		{
			return false;
		}
		lastElem = false;
		topElem = false;
		parentElem = (this.getObj(_parent) === false) ? false : this.getObj(_parent).get(0);
		switch(parentElem.tagName)
		{
			case 'TABLE':
				//we need to start at tbody for tables....retarded yes
				parentElem = $(parentElem).find('tbody').get(0);
				break;
		}
		hierarchy = new Array();
		ins_pt = new Array();
		hierarchy = _hierarchy.split(',');
		ins_pt = (typeof(chld_ins_pt) == 'string') ? chld_ins_pt.split(',') : false;
		switch(typeof hierarchy)
		{
			case 'string':
				hierarchy = new Array(hierarchy);
				break;
				
			case 'object':
				break;
				
			default:
				alert("I need a string to create an element createElementHierarchy default:");
				return false;
				break;
		}
		for(i = 0; i < hierarchy.length; i++)
		{
			freq = new Array();
			freq = hierarchy[i].split('-');
			freq = (freq.length == 1) ? Array(freq[0], 1) : freq;
			for(j = 0; j < freq[1]; j++)
			{
				switch(parentElem != undefined)
				{
					case false:
						parentElem = document.createElement(freq[0]);
						break;
						
					default:
						lastElem = document.createElement(freq[0]);
						parentElem.appendChild(lastElem);
						parentElem = lastElem;
						if(!topElem)
						{
							topElem = lastElem;
						}
						break;
				}
			}
		}
		var cur_time = new Date();
		topElem.id = _parent+'_parent_'+cur_time.getTime();
		ret = new Object();
		ret.topElem = topElem;
		ret.lastElem = lastElem;
		return ret;
	}
	
	this.visibility = function (id, pour, caller)
	{
		data = {};
		data.get_html = false;
		data.for = pour;
		data.unique = id;
		var request_data = {0:"visibility", 1:data};
		var request = this.doRequest({'module':'api', 'proc':'procedure', 'data':request_data});
		request.done(function(result)
		{
			if(result)
			{
				var new_action = (result.data.hidden == 0) ? 'hide' : 'show';
				$(caller).text(new_action);
				switch(Number(result.data.hidden))
				{
					case 0:
						$(caller).parents("div[id='note_content"+id+"']").removeClass('hidden_displayed');
						break;
						
					default:
						$(caller).parents("div[id='note_content"+id+"']").addClass('hidden_displayed');
						break;
				}
			}
		});
	}
	
	this.place = function (newElem, data, addToElem, format, clear)
	{
		switch(typeof(newElem))
		{
			case 'object':
				var addTo = self.getObj(addToElem);
				var scrollToPos = 0;
				switch(format)
				{
					case 'text':
						var newElement = $('<div style="width:100%; padding:10px;" id="text_result"><br>'+data+'</div>');
						scrollToPos = newElement.get(0).id;
						break;
						
					default:
						var newElement = $(data);
						scrollToPos = newElement.get(0).id;
						break;
				}
				switch(typeof clear)
				{
					case 'string':
						addTo.find(clear).html('');
						break;
						
					case 'boolean':
						if(clear === true) {addTo.html('')};
						break;
				}
				if(newElem.prepend === true) {
					try 
					{
						switch(1)
						{
							case 1:
								switch(addTo.find(':first-child').attr('id'))
								{
									case 'noreplies':
										addTo.find(':first-child').remove();
										break;
								}
								newElement.appendTo(addTo);
								addTo.hide().slideDown('fast').effect('pulsate', {times:1}, 150);
								break;
						}
						self.animateScroll(scrollToPos, addTo);
					}catch(error){}
				} else if(newElem.replace === true) {
					try 
					{
						addTo.replaceWith(data).effect('pulsate', {times:1}, 150);
						//self.animateScroll(scrollToPos, addTo);
					}catch(error){}
				} else {
					try 
					{
						switch(addTo.children().length)
						{
							case 0:
								addTo.append(newElement).next().hide().slideDown('fast').effect('pulsate', {times:1}, 150);
								break;
								
							default:
								switch(addTo.find(':first-child').attr('id'))
								{
									case 'noreplies':
										addTo.find(':first-child').hide();
										newElement.prependTo('#'+addTo).hide().slideDown('fast').effect('pulsate', {times:1}, 150);
										break;
										
									default:
										switch(newElem.index)
										{
											case -1:
												newElement.prependTo(addTo).hide().slideDown('fast').effect('pulsate', {times:1}, 150);
												break;
												
											default:
												addTo.children().eq(newElem.index).after(newElement).next().hide().slideDown('fast').effect('pulsate', {times:2}, 150);
												break;
										}
										break;
								}
								break;
						}
						self.animateScroll(scrollToPos, addTo);
					} catch(error){}
				}
				break;
		}
	}
	
	this.safeFunctionName = function (input) {
		var array = new String(input).split('-');
		var string = $.map(array, function (value, index) {
			return value.ucfirst();
		});
		return string.join('');
	}
	
	/**
	 * Module related functions
	 */
	this.onModuleLoad = function(module, callback, namespace) {
		var ns = namespace == undefined ? '' : '.'+namespace;
		var event = 'nitm:'+module+ns;
		$('body').one(event, callback);
		switch(self.hasModule(module, false))
		{
			case true:
			self.moduleLoaded(module, namespace);
			break;
		}
	}
	
	this.moduleLoaded = function(module, namespace) {
		var ns = namespace == undefined ? '' : '.'+namespace;
		var event = 'nitm:'+module+ns;
		$('body').trigger(event);
	}
	
	this.module = function (name, defaultValue) {
		var found = false;
		var hierarchy = name.split(':');
		var index = this;
		for(var i in hierarchy)
		{
			if (index.hasOwnProperty('modules')) {
				index = index.modules;
			} 
			if(index.hasOwnProperty(hierarchy[i])) {
				index = index[hierarchy[i]];
				if(i == (hierarchy.length - 1)) {
					found = true;
					break;
				}
			}
		}
		ret_val = (found === true) ? index : defaultValue;
		return ret_val;
	}
	
	this.hasModule = function (name) {
		var ret_val = self.module(name, false) === false ? false : true;
		return ret_val;
	}
	
	this.setModule = function (name, module) {
		var hierarchy = name.split(':');
		var moduleName = hierarchy.pop();
		var parent = (hierarchy.length == 0) ? self : self.module(hierarchy.join(':'));
		if(!parent.hasOwnProperty('modules')) {
			parent['modules'] = {};
			Object.defineProperty(parent, 'modules', {
				'value': new Object,
				'enumerable': true
			});
		}
		Object.defineProperty(parent.modules, moduleName, {
			'value': module,
			'enumerable': true
		});
	}
	
	this.setCurrent = function (index) {
		if(index != undefined) {
			self.current = index;
		}
	}
	
	this.initModule = function (name, object) {
		switch(typeof object == 'object') {
			case true:
			switch(self.hasModule(name))
			{
				case false:
				self.current = name;
				self.setModule(name, object);
				if(typeof object.init == 'function') {
					switch(document.readyState)
					{
						case 'complete':
						object.init();
						self.moduleLoaded(name);
						break;
						
						default:
						$(document).ready(function () {
							object.init();
							self.moduleLoaded(name);
						});							
						break;
					}
				}
				break;
			}
			break;
		}
		switch((typeof self.defaultInit == 'object') && (self.selfInit == false))
		{
			case true:
			self.defaultInit.map(function (method, key) {
				if(typeof self[method] == 'function'){
					var container = (typeof object == 'object') ? object.views.container : '';
					self[method](name, container);
				}
			});
			self.selfInit = true;
			break;
		}
	}
}

String.prototype.ucfirst = function() {
	return this.charAt(0).toUpperCase() + this.slice(1);
}

$nitm = (window.$nitm == undefined) ? new Nitm() : $nitm;