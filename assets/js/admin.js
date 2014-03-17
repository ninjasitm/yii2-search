function more(_form, addTo, remove, clear, prepend)
{
	var clear_parent = ((clear != false) || (clear !== undefined)) ? clear : false;
	var remove_submit = (remove === false) ? false : true;
	animateSubmit(_form, true);
	var data = getObj(_form).serialize();
	var skip = {};
	switch(data != undefined)
	{
		case true:
		data.getHtml = true;
		var request = doRequest(getObj(_form).attr('action'), data);
		request.done(function(result)
		{
			switch(result.success && (result.data != ''))
			{
				case true:
				try {
					getObj('notify').html(result.message).removeClass('alert alert-failure');
				} catch (error) {}
				if(remove_submit) {var submit_button = getObj(_form).parent().detach();}
				ret_val = false;
				result.pour = new String(result.pour);
				var newElem = {'insert':true, 'append':((prepend === true) ? false : true), 'id':result.pour};
				switch(result.format)
				{
					case 'text':
					result.data = result.data[result.pour][result.pour];
					break;
				}
				var addto = (!addTo) ? result.pour : addTo;
				try {
					place(newElem, result.data, addto, result.format, clear_parent);
				} catch (error) {}
				if(!remove_submit) {animateSubmit(_form, false);}
				break;
				
				case false:
				var addto = (!addTo) ? result.pour : addTo;
				try {
					notify(result.message, 'bg-danger');
				} catch (error) {}
				switch(remove_submit) 
				{
					case false:
					getObj(addTo).append(submit_button);
					break;
				}
				animateSubmit(_form, false);
				break;
			}
		});
		break;
		
		default:
		animateSubmit(_form, false);
		break;
	}
}

function status(id, status, pour, img, form, container)
{
	var old_src = getObj(img).attr('src');
	getObj(img).attr('src', globals.loading_src);
	var request_data = {0:"status", 1:{'for':pour, 'action':status, 'id':id}};
	var request = doRequest({'module':'api', 'proc':'procedure', 'data':request_data}, null, function () {
		request.abort();
		getObj(img).attr('src', old_src);
		return false;
	}, 5000);
	request.done(function(result)
	{
		if(result)
		{
			switch(result['success'])
			{
				case true:
				getObj(form).children("input[name='a']").val(result.action);
				switch(result.indicate)
				{
					case 'closed':
					getObj(container).removeClass().toggleClass('item_closed')
					break;
					
					case 'resolved':
					getObj(container).removeClass().toggleClass('item_resolved_nc')
					break;
					
					case 'disabled':
					getObj(container).removeClass().toggleClass('item_disabled')
					break;
					
					case 'open':
					getObj(container).removeClass().toggleClass('item_open');
					break;
					
					default:
					getObj(container).removeClass().toggleClass('item_notice');
					break;
				}
				switch(result.status_action)
				{
					case 'Closed':
					var new_src = globals.locked_src;
					break;
					
					case 'Opened':
					case 'Re-Opened':
					var new_src = globals.unlocked_src;
					break;
					
					case 'Resolved':
					case 'Completed':
					switch(pour)
					{
						case 'iphosts':
						getObj(img).toggle();
						break;
						
						default:
						var new_src = globals.resolved_src;
						break;
					}
					break;
					
					case 'Incompleted':
					case 'Unresolved':
					switch(pour)
					{
						case 'iphosts':
						var new_src = old_src;
						break;
						
						default:
						var new_src = globals.unresolved_src;
						break;
					}
					break;
					
					case 'Enabled':
					var new_src = globals.online_src;
					break;
					
					case 'Disabled':
					var new_src = globals.offline_src;
					break;
				}
				switch(new_src != undefined)
				{
					case true:
					getObj(img).attr('src', new_src);
					break;
				}
				getObj(img).attr('title', 'Set this '+pour+' to: '+result.status_action);
				getObj(img).attr('alt', result.action);
				try {
					getObj('notify').html(result.message);
				} catch (error) {}
				switch(result.action)
				{
					case 'close':
					case 'open':
					try {
						getObj(container).find("[id^='actionsfor"+id+"']").toggle();
					} catch (error) {}
					break;
				}
				break;
				
				default:
				alert("Error processing request. Please try again or contact the admin");
				break;
			}
		}
	});
	return false;
}

function visibility(id, pour, caller)
{
	data = {};
	data.get_html = false;
	data.for = pour;
	data.unique = id;
	var request_data = {0:"visibility", 1:data};
	var request = doRequest({'module':'api', 'proc':'procedure', 'data':request_data});
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

function vote(id, vote, pour, upid, downid)
{
	img = (vote == 'up') ? upid : downid;
	old_src = getObj(img).attr('src');
	getObj(img).attr('src', globals.loading_src);
	var request_data = {0:"vote", 1:{'for':pour, 'vote':vote, 'id':id}};
	var request = doRequest({'module':'api', 'proc':'procedure', 'data':request_data});
	request.done(function(result)
	{
		if(result)
		{
			switch(result['max'])
			{
				case true:
				getObj(upid).hide('slow');
				getObj(upid).attr('oldonclick', getObj(downid).attr('onclick'));
				getObj(upid).click(void(0));
				break;
				
				default:
				switch(getObj(upid).css('display'))
				{
					case 'none':
					getObj(upid).show('slow');
					getObj(upid).click(getObj(downid).attr('oldonclick'));
					break;
				}
				break;
			}
			switch(result['min'])
			{
				case true:
				getObj(downid).hide('slow');
				getObj(downid).attr('oldonclick', getObj(downid).attr('onclick'));
				getObj(downid).click(void(0));
				break;
				
				default:
				switch(getObj(downid).css('display'))
				{
					case 'none':
					getObj(downid).show('slow');
					getObj(downid).click(getObj(downid).attr('oldonclick'));
					break;
				}
				break;
			}
			try {
				getObj('percent'+id).html(Math.round(result['score']*100));
				getObj('indicator'+id).css('background', 'rgba(255,51,0,'+result['score']+')');
			}catch(error) {}
		}
	});
	getObj(img).attr('src', old_src);
}

function add(data, addTo, check, _form)
{
	animateSubmit(_form, true);
	switch(check != undefined)
	{
		case true:
		if(validate(_form, check) === false) {animateSubmit(_form, false); return false};
		break;
	}
	//return true here in case ajax query fails
	var ret_val = false;
	switch(data != undefined)
	{
		case true:
		data.get_html = true;
		var request_data = {0:"add", 1:data};
		var request = doRequest({'module':'api', 'proc':'procedure', 'data':request_data});
		request.done(function(result)
		{
			switch(result.success)
			{
				case true:
				ret_val = false;
				result.pour = new String(result.pour);
				switch(result.pour.indexOf('replies') != -1)
				{
					case true:
					var newElem = {'insert':true, 'append':false, 'id':'note', 'index':-1};
					break;
					
					default:
					switch(result.pour.valueOf())
					{
						case 'notes':
						var newElem = {'insert':true, 'append':false, 'id':'note', 'index':0};
						break;
					}
					break;
				}
				place(newElem, result.data, addTo, result.format);
				getObj(_form).get(0).reset();
				try {
					getObj(_form).find("textarea").each(function () {
						CKEDITOR.instances[this.id].updateElement();
						CKEDITOR.instances[this.id].setData("");
						$(this).text("");
						$(this).val("");
					});
				}catch (error) {}
				break;
			}
		});
		break;
	}
	animateSubmit(_form, false);
	return ret_val;
}

//function to validate file form data
function checkEmptyValues(_form_, fields)
{
	var ret_val = true;
	switch((fields != ''))
	{
		case true:
		_form = getObj(_form_);
		switch(typeof _form)
		{
			case 'object':
			var proceed = true;
			for(var n in fields)
			{
				var matched = _form.find("[name^='"+fields[n]+"']");
				//if we can't find any elements with this name it may be setup as an array: blah[]
				matched = (matched.length >= 1) ? matched : _form.find("[name^='"+fields[n]+"[']");
				switch(matched.length >= 1)
				{
					case true:
					matched.each(function (key, elem) {
						fail = false;
						if(elem.disabled == true)
						{	
							return true;
						}
						switch(elem.type)
						{
							case 'textarea':
							var ta_focus = true;
							var elem_val = ckeVal(getObj(elem).attr('id'), _form.id);
							break;
							
							default:
							var ta_focus = false;
							var elem_val = elem.value;
							break;
						}
						
						switch((typeof elem_val == undefined) || !elem_val)
						{
							case true:
							var message = "This value ("+elem.name+") shouldn't be empty...";
							fail = true;
							break
						}
						switch(fail)
						{
							case true:
							alert(message);
							switch(ta_focus)
							{
								case true:
								setCKEFocus(elem);
								break;
								
								default:
								setFocus(elem);
								break;
							}
							proceed = false;
							return false;
							break;
						}
					});
					switch(proceed)
					{
						case false:
						return false;
						break;
					}
					break;
				}
			}
			return true;
			break;
		}
		break;
		
		default:
		return true;
		break;
	}
}

function bindForRecentActivity(ids, events, funcs)
{
	var t_func = typeof funcs;
	var event_handler = false;
	var functions = {};
	switch(t_func)
	{
		case 'function':
		case 'object':
		switch(t_func == 'function')
		{
			case true:
			functions[0] = funcs;
			break;
		}
		event_handler = true;
		break;
	}
	ids.map(function (n) {
		events.map(function (e, i) {
			getObj(n).on(e, function () {
				getObj(n).attr('recentActivity', true);
			});
			switch(event_handler)
			{
				case true:
				var f = (typeof functions[i] == 'function') ? functions[i] : functions[0];
				getObj(n).on(e, f);
				break;
			}
		});
	});
	CKEDITOR.on('instanceCreated', function(i) {
		switch($.inArray(i.editor.name, ids) != -1)
		{
			case true:
			i.editor.on('contentDom', function() {
				events.map(function (e, idx) {
					i.editor.document.on(e, function(event) {
						getObj(i.editor.name).attr('recentActivity', true);
					});
					switch(event_handler)
					{
						case true:
						var f = (typeof functions[idx] == 'function') ? functions[idx] : functions[0];
						getObj(i.editor.name).on(e, f);
						break;
					}
				});
			});
			break;
		}
	});
}

function place(newElem, data, addTo, format, clear)
{
	switch(typeof(newElem))
	{
		case 'object':
		switch(newElem.insert)
		{
			case true:
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
				$('#'+addTo).find(clear).html('');
				break;
				
				case 'boolean':
				if(clear === true) {getObj('#'+addTo).html('')};
				break;
			}
			switch(newElem.append)
			{
				case false:
				try 
				{
					switch($('#'+addTo).children().length)
					{
						case 0:
						$('#'+addTo).append(newElement).next().hide().slideDown('fast').effect('pulsate', {times:1}, 150);
						break;
						
						default:
						switch($('#'+addTo+' :first-child').attr('id'))
						{
							case 'noreplies':
							$('#'+addTo+' :first-child').hide();
							newElement.prependTo('#'+addTo).hide().slideDown('fast').effect('pulsate', {times:1}, 150);
							break;
							
							default:
							switch(newElem.index)
							{
								case -1:
								newElement.prependTo('#'+addTo).hide().slideDown('fast').effect('pulsate', {times:1}, 150);
								break;
								
								default:
								$('#'+addTo).children().eq(newElem.index).after(newElement).next().hide().slideDown('fast').effect('pulsate', {times:2}, 150);
								break;
							}
							break;
						}
						break;
					}
					//animateScroll(scrollToPos, addTo);
				}catch(error){}
				break;
				
				case true:
				try 
				{
					switch(1)
					{
						case 1:
						switch($('#'+addTo+' :first-child').attr('id'))
						{
							case 'noreplies':
							$('#'+addTo+' :first-child').remove();
							break;
						}
						newElement.appendTo('#'+addTo);
						$('#'+addTo).hide().slideDown('fast').effect('pulsate', {times:1}, 150);
						break;
					}
					//animateScroll(scrollToPos, addTo);
				}catch(error){}
				break;
			}
			break;
		}
		break;
	}
}

/*
	Need to convert form data to serialized json in order to prpoerly send it to broswer/server
*/
(function($){
    $.fn.serializeObject = function(){

        var self = this,
            json = {},
            push_counters = {},
            patterns = {
                "validate": /^[a-zA-Z][a-zA-Z0-9_]*(?:\[(?:\d*|[a-zA-Z0-9_]+)\])*$/,
                "key":      /[a-zA-Z0-9_]+|(?=\[\])/g,
                "push":     /^$/,
                "fixed":    /^\d+$/,
                "named":    /^[a-zA-Z0-9_]+$/
            };


        this.build = function(base, key, value){
            base[key] = value;
            return base;
        };

        this.push_counter = function(key){
            if(push_counters[key] === undefined){
                push_counters[key] = 0;
            }
            return push_counters[key]++;
        };

        $.each($(this).serializeArray(), function(){

            // skip invalid keys
            if(!patterns.validate.test(this.name)){
                return;
            }

            var k,
                keys = this.name.match(patterns.key),
                merge = this.value,
                reverse_key = this.name;

            while((k = keys.pop()) !== undefined){

                // adjust reverse_key
                reverse_key = reverse_key.replace(new RegExp("\\[" + k + "\\]$"), '');

                // push
                if(k.match(patterns.push)){
                    merge = self.build([], self.push_counter(reverse_key), merge);
                }

                // fixed
                else if(k.match(patterns.fixed)){
                    merge = self.build([], k, merge);
                }

                // named
                else if(k.match(patterns.named)){
                    merge = self.build({}, k, merge);
                }
            }

            json = $.extend(true, json, merge);
        });

        return json;
    };
})(jQuery);