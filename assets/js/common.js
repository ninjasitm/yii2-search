//add variable to show that window was loaded
window.onload = function() {
    window.loaded = true;
}

function setVars()
{
	window.loaded = true;
}

function delay(gap)/* gap is in millisecs */
{ 
	var then,now; 
	then=new Date().getTime();
	now=then;
	while((now-then) < gap)
	{
		now=new Date().getTime();
		//notify(now, 'notify', true);
	}
}

function popUp(url, id, h, w, scr) 
{
	day = new Date();
	id = day.getTime();
	h = (eval(h) != undefined) ? h : '800';
	w = (eval(w) != undefined) ? w : '720';
	scr = ((eval(scr)) == '0') ? 'no' : 'yes';
	window.open(url, id, 'toolbar=0,scrollbars='+scr+',location=no,statusbar=no,menubar=no,resizable=no,width='+w+',height='+h);
	return false;
}	

function redirectTo(url, timeout)
{
	if(url)
	{
		timeout = (!timeout) ? 0 : timeout;
		setTimeout("window.location.href='"+url+"'", timeout);
	}
}

function animateScroll(elem, parent, highlight, highlight_class)
{
	var element = $(getObj('#'+elem).get(0));
	var container = getObj('#'+((!parent) ? element.parent().attr('id') : parent));
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

function animateSubmit(_form, before)
{
	switch(1)
	{
		case getObj("#"+_form+" input[type='submit']", null, false, false).get(0) !== undefined:
		var submit_method = "#"+_form+" input[type='submit']";
		break;
		
		case getObj('#'+_form+" button[type=submit]", null, false, false).get(0) !== undefined:
		var submit_method = "#"+_form+" button[type='submit']";
		break;
		
		default:
		var submit_method = "#"+_form+" input[type='image']";
		break;
	}
	switch(before)
	{
		case true:
		try
		{
			var button = getObj(submit_method, null, false, false);
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
			var button = getObj(submit_method, null, false, false);
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

function dump(arr,level) 
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

function notify(nMsg, nClass, nApp)
{
	var nMessage = new String(nMsg);
	if(nMessage.length <= 5)
	{
	    return false;
	}
	else
	{
	    getObj(responseSection).removeClass().addClass(nClass);
	    getObj(responseSection).html(nMessage);
	}
}
	
function objectLength(obj)
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

function updateSingle(uMsg, uClass, uApp, uID)
{
	var uMessage = new String(uMsg);
	switch(uApp)
	{
		case true:
		getObj(uID).append("<span class='"+uClass+"'>"+uMessage+"</span>");
		break;
		
		default:
		getObj(uID).html("<span class='"+uClass+"'>"+uMessage+"</span>");
		break;
	}
}

function clearNotify()
{
	getObj(responseSection).html("");
}

//function fo focus items with special box
function setFocus(item)
{
	var obj = getObj(item);
	var orig_border = obj.css('border');
	obj.effect('pulsate', {times:2}, 'fast');
	obj.focus();
}

function setCKEFocus(e)
{
	obj = getObj(e).get(0);
	switch(typeof obj)
	{
		case 'object':
		try {
			editor = CKEDITOR.instances[obj.id];
			editor.focus();
		}
		catch (error)
		{
			setFocus(obj.id);
		}
		/*try {
			animateScroll(obj.id, getObj(obj.parentNode).attr('id'), true, 'item_notice');
		} catch (error) {};*/
		break;
	}
}

function toggleElem(selector, by, by_val)
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
	selector = jqEscape(selector);
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
	try {getObj(selector, '', false, false).each(function() {this.disabled = !this.disabled;})} catch(error) {};
}

function addOnLoadEvent(func)
{
	switch(document.readyState)
	{
		case 'complete':
		func();
		break;
		
		default:
		$(window).load(func);
		break;
	}
}

function nullHandler(e) 
{
 	return true;
}

//function to add elements to a parent element
/*
	par = the parent element
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
function addChildrenTo(_parent, cData)
{
	var par = getObj(_parent);
	if(typeof(par) == 'object')
	{
		if(typeof(cData) == 'object')
		{
			if(typeof(cData['type']) != 'undefined')
			{
				childElem = document.createElement(cData['type']);
			}
			else
			{
				childElem = false;
			}
			for(var attr in cData)
			{
				switch(attr)
				{
					case 'events':
					for(event in cData[attr])
					{
						switch(typeof(cData[attr][event]))
						{
							case 'object':
							for(subEvent in cData[attr][event])
							{
								addEventTo(childElem, cData[attr][event][subEvent]['type'], function(){cData[attr][event][subEvent]['function']}, false);
							}
							break;
							
							default:
							addEventTo(childElem, cData[attr][event]['type'], function(){cData[attr][event]['function']}, false);
							break;
						}
					}
					break;
					
					case 'listeners':
					for(event in cData[attr])
					{
						switch(typeof(cData[attr][event]))
						{
							case 'object':
							for(subEvent in cData[attr][event])
							{
								childElem.addListenerTo(childElem, cData[attr][event][subEvent]['type'], function(){cData[attr][event][subEvent]['function']});
							}
							break;
							
							default:
							childElem.addListenerTo(childElem, cData[attr][event]['type'], function(){cData[attr][event]['function']}, false);
							break;
						}
					}
					break;
					
					case 'type':
					break;
					
					default:
					switch(typeof(cData[attr]))
					{
						
						case 'object':
						addChildrenTo(par, cData[attr]);
						break;
						
						default:
						childElem.setattribute(attr, cData[attr]);
						break;
					}
					break;
				}
			}
			par.appendChild(childElem);
		}
	}
}

//function to show the sub menu	var remember = new Array();
var DHTML = (document.getElementById || document.all || document.layers);

function handleVis(e)
{
	getObj(e).each(function () {
		$(this).slideToggle('fast');
	});
}
//function to hcndle element visibility and hide others
function handleVisHideOther(iSub, iRowObj)
{
	if((oldIsub.length > 0) && (oldIrowObj.length > 0))
	{
		handleVis(oldIsub, oldIrowObj);
	}
	oldIsub = iSub;
	oldIrowObj = iRowObj;
	handleVis(iSub);
}

function confirmDialog(message)
{
	return confirm(message);
}

function confirmSubmit(_form_, message)
{
	if(confirm(message) == true)
	{
		getObj(_form_).get(0).submit();
	}
}

//get the object information
function getObj(selector, by, alert_obj, esc)
{
	esc = (esc == undefined) ? true : esc;
	switch(typeof selector)
	{
		case 'string':
		case 'number':
		break;
		
		case 'object':
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
		break;
		
		default:
		return false;
		break;
	}
	selector = (esc === true) ? jqEscape(selector) : selector;
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
		switch(["["].indexOf(selector[0]) != -1)
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
	return $(obj);
	
}

function jqEscape(val) 
{
   // return new String(val).replace(/[-[\]{}()*+?.,\\^$|]/g, '\\$&');
    return new String(val).replace(/[-{}()*+?.,\\^$|]/g, '\\$&');
}

function createDynSel(array, box, array2, selval, nodef, alert_val, misc)
{
	switch(typeof objectLength(array) == 'undefined')
	{
		case true:
		return;
		break;
	}
	type = (typeof misc.type == 'undefined') ? 'select' : misc.type;
	separator = typeof (misc.separator == 'undefined') ? 'br' : misc.separator;
	var oSel = getObj(box)[0];
	switch(type)
	{
		case 'select':
		clearSel(box);
		break;
		
		case 'checkbox':
		case 'radio':
		oSel.innerHTML = "";
		break;
	}
	switch(nodef)
	{
		case 1:
		case true:
		beg = -1;
		break;
		
		default:
		beg = 0;
		switch(type)
		{
			case 'select':
			oSel.options[0] = new Option('Select Option', '');
			break;
		}
		break;
	}
	array2 = (objectLength(array2) == 0) ? array : array2;
	index = 0;
	for(var i in array)
	{
		switch(array.hasOwnProperty(i) || (i == 'length'))
		{
			case true:
			beg++;
			switch(typeof array[i])
			{
				case "object":
				val1 = array[i].text;
				break;
				
				default:
				val1 = array[i];
				break;
			}
			switch(typeof array2[i])
			{
				case "object":
				val2 = (array2[i] != 'undefined') ? array2[i].value : array2[index].value;
				break;
				
				default:
				val2 = (array2[i] != 'undefined') ? array2[i] : array2[index];
				break;
			}
			selidx = (val2 == selval) ? true : false;
			switch(type)
			{
				case 'select':
				oSel.options[beg] = new Option(val1, val2, selidx);
				break;
				
				case 'checkbox':
				case 'radio':
				var input = document.createElement('input');
				input.type = misc.type;
				input.value = val1;
				input.title = val2;
				input.label = val2;
				input.name = (misc.name != 'undefined') ? misc.name+"["+index+"]" : misc.type+Date().getTime();
				input.id = (misc.name != 'undefined') ? misc.name+index : misc.type+Date().getTime();
				var label = document.createElement('label');
				label.for = input.id;
				label.innerHTML = val2;
				oSel.appendChild(input);
				oSel.appendChild(label);
				oSel.appendChild(document.createElement(misc.separator));
				break;
			}
			switch(selidx)
			{
				case true:
				oSel.selectedIndex = beg;
				break;
			}
			index++;
			break;
		}
	}
}

function clearSel(box)
{
	try {getObj(box).children().remove()} catch (error){};
}

function selectIndex(sel, val)
{
	getObj(sel+' option').eq(val).attr('selected', 'selected');
}

function parseStrCode(str)
{
	if(str)
	{
		code = str.split(';');
		for(i = 0; i < code.length; i++)
		{
			eval(code[i]+';');
		}	
	}
}

//
//	unhide = the id of the object to unhide
//
function startEditor(editor)
{
	alert("Starting editor");
	switch(editor.editor)
	{	
		case 'cke':
		CKEDITOR.replaceAll(function(textarea, config ){
			config.toolbar = editor.toolbar;
			config.uiColor = editor.color;
			config.skin = editor.skin;
			config.startupMode = editor.mode;
		});
		break;
	}
	if(editor.unhide)
	{
		handleVis(editor.unhide);
	}
}

function startSingleEditor(nameid, editor, finder)
{
	nameid = (typeof nameid == 'object') ? nameid : new Object(nameid);
	finder = (typeof finder == 'object') ? finder : {};
	switch(editor.editor)
	{
		case 'cke':
		for(var unique in nameid)
		{
			var editor_instance = CKEDITOR.replace(nameid[unique], editor);
			switch(finder.activate)
			{
				case true:
				case "true":
				case 1:
				switch((typeof CKFinder != 'undefined'))
				{
					case true:
					editor_instance.config = editor;
					CKFinder.setupCKEditor(editor_instance, finder);
					break;
				}
				break;
				
				default:
				break;
			}
		}
		break;
	}
}

//get value of a ckeditor textarea/field
function ckeVal(field, _form)
{
	try 
	{
		switch(typeof _form)
		{
			case 'string':
			case 'object':
			case 'number':
			try {
				var ret_val = getObj('#'+getObj(_form).attr('id')+' [name='+field+']', null, false, false).val();
			} catch(error) {};
			break;
		}
		switch((typeof ret_val == undefined) || !ret_val)
		{
			case true:
			try {
				editor = CKEDITOR.instances[field];
				var ret_val = editor.getData();
				ret_val = (!ret_val) ? getObj(field).val() : ret_val;
			} catch(error) {
				var ret_val = getObj(field).val();
			}
			break;
			
			default:
			var ret_val = getObj(field).val();
			break;
		}
		return ret_val;
	} catch(error) {}
}

function charsLeft(field, cntfield, maxlimit) 
{
	field = getObj(field).get(0);
	cntfield = getObj(cntfield).get(0);
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

function getValueFor(key, unique_class, unique)
{
	var ret_val = null;
	var _form = -1;
	try {_form = getObj(unique_class+"form"+unique).get(0);}
	catch (error) {_form = -1;}
	switch((_form != null && _form != -1) && (typeof _form == 'object'))
	{
		case true:
		switch(_form[key].value != 'undefined' || _form[key].value != null)
		{
			case true:
			ret_val = _form[key].value;
			break;
		}
		break;
	}
	//alert(key+" "+ret_val+" "+unique_class+key+unique);
	switch((typeof ret_val == 'undefined') || (ret_val == null))
	{
		case true:
		try {var obj = getObj(unique_class+key+unique).get(0);
		ret_val = (typeof obj == 'undefined') ? unique : ((obj.innerHTML == '') ? obj.value : obj.innerHTML);}
		catch (error)
		{ ret_val = null;}
		break;
	}
	return ret_val;
}

function replyTo(unique, unique_class, msg_field, title_field, quote, reply_to, jump)
{
	try {var author = getValueFor('author', unique_class, unique);} catch (error) {author = '';}
	try {var msg = getValueFor('message', unique_class, unique);} catch (error) {msg = '';}
	try {var date = getValueFor('date', unique_class, unique);} catch (error) {date = '';}
	try 
	{
		switch(title_field && (typeof getObj(title_field).get(0) == 'object'))
		{
			case true:
			var title_field = getObj(title_field).get(0);
			var current_title = getValueFor('title', unique_class, unique);
			title_field.value = current_title;
			break;
		}
	} catch(error){};
	switch(typeof getObj(msg_field).get(0))
	{
		case 'object':
		editor = CKEDITOR.instances[msg_field];
		switch(quote)
		{
			case true:
			try {
				//editor.setData("");
				bquote = new String("<blockquote>");
				max_len = (author.length+date.length)+24;
				var border = new String();
				for(i=0; i<max_len;i++)
				{
					border += '-';
				}
				bquote += border+'<br>'+author+' on '+date+'<br>'+border+msg+"</blockquote>";
				editor.setData(bquote+"<p></p>");
				editor.resize("100%", (bquote.length/2) + 20, true);
				editor.focus();
				//editor.scrollIntoView(alignTop);
			}
			catch (error)
			{
				var msg_field = getObj(msg_field).get(0);
				msg_field.value += author+" said: "+msg+'';
				msg_field.focus();
			}
			break;
			
			default:
			try {
				editor.setData("");
				editor.resize("100%", editor.config.height, true);
				editor.focus();
			}
			catch (error)
			{
				var msg_field = getObj(msg_field).get(0);
				msg_field.focus();
			}
			break;
		}
		break;
	}
	try {reply_to_field = getObj(reply_to).get(0);
		reply_to_field.value = getValueFor('reply_to', unique_class, unique);}
	catch (error) {}
	if((typeof jump) == 'string')
	{
		animateScroll(getObj(getObj(jump).get(0).parentNode).attr('id'), null, true);
		//window.location.hash = jump;
	}
	return false;
}

function doRequest(rUrl, rData, success, error, timeout, headers)
{
	switch(r.hasOwnProperty('token'))
	{
		case true:
		r.beforeSend = function (xhr) {xhr.setRequestHeader("Authorization", "Basic "+r.token);};
		break;
	}
	if (rUrl != undefined) {
	    //code
	    r.url = rUrl;
	}
	r.data = rData;
	r.success = success;
	r.error = (error == undefined) ? function (e) { console.log(e) } : error;
	r.timeout = (timeout !== undefined) ? timeout : 30000;
	r.type = 'POST';
	if(headers != undefined)
	{
		for(var key in headers)
		{
			r[key] = headers[key];
		}
	}
	var ret_val = $.ajax(r);
	return ret_val;
}

function doRequestFileData(_form, data)
{
	//make sure the form is setup to send files
	getObj(_form).attr('enctype', "multipart/form-data");
	getObj(_form).attr('encoding', "multipart/form-data");
	
	// match anything not a [ or ]
	regexp = /^[^[\]]+/;

	//Deliver files with ajax submission
	var data = (data == undefined) ? new FormData() : data;
	getObj(_form).find(":file").each(function (i, file) {
		var fileInputName = regexp.exec(file.name);
		data.append(fileInputName+'['+i+']', file);
	});
	return data;
}

function toHex(dec)
{ 
	var result = (parseInt(dec).toString(16)); 
	if(result.length ==1)
	{ 
		result= ("0" +result); 
	}
	return result.toUpperCase(); 
}

function rgbToHex(rgbInput)
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

function addElement(_type, json, hierarchy, chld_ins_pt, _parent)
{
	if(_type)
	{
		attribstring = '';
		element = '';
		switch(typeof(json))
		{
			case 'object':
			try 
			{
				element = document.createElement(_type);
				for(var attr in json)
				{
// 					if(((attr == 'name') || (attr == 'NAME')) && 
// 						((json[attr].indexOf('[') == -1) && (json[attr].indexOf(']') == -1)))
// 					{
// 						var name_idx = getNamedElems(attr).length + 1;
// 						json[attr] = json[attr]+'['+name_idx+']';
// 					}
					element.setAttribute(attr, json[attr]);
				}
			} 
			catch (e) 
			{
				for(var attr in json)
				{
// 					if(((attr == 'name') || (attr == 'NAME')) && 
// 						((json[attr].indexOf('[') == -1) && (json[attr].indexOf(']') == -1)))
// 					{
// 						var name_idx = getNamedElems(attr).length + 1;
// 						json[attr] = json[attr]+'['+name_idx+']';
// 					}
					attribstring = attribstring+attr+"='"+json[attr]+"' ";
				}
				element = document.createElement("<"+_type+" "+attribstring+"/>");
			}
			break;
				
			default:
			return false;
			break;
		}
		if(element)
		{
			var cur_time = new Date();
			cur_time = cur_time.getTime();
			if(hierarchy)
			{
				var rand_var = eval("var ins_pt_"+_parent+"_"+cur_time+" = createElementHierarchy('"+hierarchy+"', '"+chld_ins_pt+"', '"+_parent+"'); var ins_pt = ins_pt_"+_parent+"_"+cur_time+";");
			}
			else
			{
				var ins_pt = getObj(_parent);
			}
			var disabler = document.createElement('input');
			disabler.setAttribute('type', 'button');
			disabler.setAttribute('value', ' - ');
			disabler.setAttribute('title', 'Remove this');
			disabler.setAttribute('id', _parent+'_disabler_'+cur_time);
			if(ins_pt.lastElem)
			{
				ins_pt.lastElem.appendChild(element);
				ins_pt.lastElem.appendChild(disabler);
			}
			else
			{
				ins_pt.appendChild(element);
				ins_pt.appendChild(disabler);
			}
			addListenerTo(disabler.id, 'click', function(){removeChildren(ins_pt.topElem.id);});
		}
	}
	else
	{
		alert("Cannot create just any type of element...specify an element type please");
		return false;
	}
}

function removeChildren(from)
{
	getObj(from).children().remove();
}

function createElementHierarchy(_hierarchy, chld_ins_pt, _parent)
{
	if(!_hierarchy)
	{
		return false;
	}
	lastElem = false;
	topElem = false;
	parentElem = (getObj(_parent) === false) ? false : getObj(_parent).get(0);
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