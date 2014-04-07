
function Configuration(items)
{	
	var self = this;
	this.classes = {
		warning: 'alert alert-warning',
		success: 'alert alert-success',
		information: 'alert alert-info',
		error: 'alert alert-info',
	};
	this.views = {
		containers: {
			section: 'sections_container',
			configuration: 'configuration_container',
			showSection: 'show_section',
			addValue: 'add_value_container'
		}
	};
	this.type = {
		default: 'db',
		current: 'db',
	};
	this.forms = {
		confirmThese: [
			'delete_section', 
			'delete_value',
		],
		allowAdd: ['add_new_value'],
		actions : {
			add: '/configuration/add',
			del: '/configuration/delete',
			edit: '/configuration/edit',
			undelete: '/configuration/undelete'
		}
	};
	this.buttons = {
		allowUpdate: ['edit_field_button']
	};
	this.blocks = {
		allowUpdate: ['edit_field_div']
	};
	this.dropdowns = {
		submitOnChange: [
			'config_type', 
			'config_container',
			'show_section'
		]
	};
	
	this.iObj = "_input";
	this.dm = 'configer';
	this.fromSession = true;
	
	//functions
	this.prepareChanging = function () {
		this.dropdowns.submitOnChange.map(function (v) {
			var form = $('#'+v);
			switch(v)
			{
				case 'show_section':
				form.off('submit');
				form.on('submit', function (e) {
					e.preventDefault();
					self.operation(this);
				});
				break;
			}
			form.find('select').on('change', function (e) {
				form.submit();
			});
		});
	}
	
	this.prepareDeleting = function (container, result) {
		var container = (container == undefined) ? 'body' : container;
		this.forms.confirmThese.map(function (v) {
			var form = $nitm.getObj(container+" "+"form[role='"+v+"']");
			form.off('submit');
			form.on('submit', function (e) {
				e.preventDefault();
				switch(v)
				{
					case 'delete_section':
					switch(result != undefined)
					{
						case true:
						try {
							var value = result.section;
							form.find("input[id='configer-cfg_s']").val(result.section);
						} catch (error) {
							var value = form.find("input[id='configer-cfg_s']").val();
						};
						break;
						
						default:
						var value = form.find("input[id='configer-cfg_s']").val();
						break;
					};
					var message = "Are you sure you want to delete section: "+value;
					break;
					
					case 'delete_value':
					var message = $(this).find(':submit').attr('title');
					break;
				}
				switch(confirm(message))
				{
					case true:
					self.operation(this);
					break;
				}
				return false;
			});
		});
	}
	
	this.prepareUpdating = function (container) {	
		var container = (container == undefined) ? '' : container;
		this.buttons.allowUpdate.map(function (v) {
			var button = $nitm.getObj(container+"[role='"+v+"']");
			button.on('click', function (e) {
				e.preventDefault();
				self.edit(this);
			});
		});
		
		this.blocks.allowUpdate.map(function (v) {
			var block = $nitm.getObj(container+"[role='"+v+"']");
			fn = function (e) {
				self.edit(this);
			};
			block.on('click', fn);
			block.data('action', fn);
		});
	}
	
	this.prepareAdding = function (container) {
		var container = (container == undefined) ? 'body' : container;
		this.forms.allowAdd.map(function (v) {
			var form = $nitm.getObj(container+" "+"form[role='"+v+"']");
			form.off('submit');
			form.on('submit', function (e) {
				e.preventDefault();
				self.operation(this);
			});
		});
	}
	
	
	this.operation = function (form) {
		data = $(form).serializeArray();
		data.push({'name':'__format', 'value':'json'});
		data.push({'name':'getHtml', 'value':true});
		data.push({'name':'do', 'value':true});
		data.push({'name':'ajax', 'value':true});
		switch(!$(form).attr('action'))
		{
			case false:
			var request = $nitm.doRequest($(form).attr('action'), 
					data,
					function (result) {
						switch(result.action)
						{
							case 'get':
							self.afterGet(result);
							break;
								
							case 'edit':
							self.afterEdit(result);
							break;
								
							case 'delete':
							self.afterDelete(result, form);
							break;
								
							case 'create':
							case 'undelete':
							self.afterAdd(result, form);
							break;
						}
					},
					function () {
						$nitm.notify('Error Could not perform configuration action. Please try again', self.classes.error, false);
					}
				);
				break;
		}
	}
	
	this.afterGet = function(result) {
		var nClass = self.classes.warning;
		if(result.data)
		{
			message = 'Successfully loaded clean configuration information';
			nClass = self.classes.success;
			var container = $('#'+self.views.containers.section).html(result.data);
			var triggers = ['edit_field_div', 'edit_field_button'];
			$.map(triggers, function (v) {
				container.find("[role='"+v+"']").map(function (e) {
					switch(new String(this.tagName).toLowerCase())
					{
						case 'button':
							var elem = $nitm.getObj($(this).data('id')).get(0);
							break;
							
						default:
							var elem = this;
							break;
					}
					var fn = function (e) {
						self.edit(elem);
					};
					$(this).on('click', function (e) {
						fn();
					});
					$(this).data('action', fn);
				})
			});
			self.prepareDeleting('#'+self.views.containers.configuration, result);
			self.prepareAdding('#'+self.views.containers.section);
			self.prepareUpdating('#'+self.views.containers.section);
		}
		else
		{
			message = 'Error empty configuration information';
		}
		$nitm.notify(message, nClass, false);
	}
	
	this.afterAdd = function(result, form) {
		var nClass = self.classes.warning;
		if(result.success)
		{
			nClass = self.classes.success;
		}
		$nitm.notify(result.message, nClass, false);
		var _form = $(form);
		switch(_form.attr('role'))
		{
			case 'undelete_value':
			switch(result.success)
			{
				case true:
				//if this value was recently deleted and is now re-added then enabled deleting
				_form.find(':submit').removeClass('').addClass('btn btn-danger').html('del');
				_form.attr('action', self.forms.actions.del);
				_form.attr('role', 'delete_value');
				_form.find(':input').attr('disabled', false);
				$nitm.getObj('value_'+result.container).removeClass('disabled');
				break;
			}
			break;
			
			default:
			$('#'+self.views.containers.addValue).before($(result.data));
			self.prepareDeleting('#'+'value_'+result.unique_id);
			self.prepareUpdating('#'+'value_'+result.unique_id);
			break;
		}
	}
	
	this.afterEdit = function (result) {
		var nClass = self.classes.warning;
		var iClass = self.classes.information;
		if(result.success)
		{
			nClass = self.classes.success;
		}
		else
		{
			iClass = self.classes.warning;
			$nitm.getObj(result.container+'.div').html(result.old_value);
		}
		$nitm.getObj(result.container+'.div').removeClass().addClass(iClass);
		$nitm.notify(result.message, nClass, false);
	}
	
	this.afterDelete = function(result, form) {
		var nClass = self.classes.warning;
		if(result.success)
		{
			nClass = self.classes.success;
		}
		$nitm.notify(result.message, nClass, false);
		switch(result.success)
		{
			case true:
			var _form = $(form);
			switch(result.value)
			{
				//We just deleted a section
				case null:
				$('#'+self.views.containers.showSection).find("select :selected").remove();
				break;
				
				default:
				_form.find(':submit').removeClass().addClass('btn btn-warning').html('undel').attr('title', "Are you sure you want to undelete this value?");
				_form.attr('action', self.forms.actions.undelete);
				_form.attr('role', 'undelete_value');
				_form.append("<input type='hidden' name='Configer[cfg_v\]' id='configer-cfg_v' value='"+$nitm.getObj(result.container+'.div').html()+"'/>");
				var container = $nitm.getObj('value_'+result.container);
				container.addClass('disabled');
				container.children().map(function() {
					switch($(this).attr('role'))
					{
						case 'undelete_value':
						break;
						
						default:
						$(this).attr('disabled', true);
						break;
					}
				});
				break;
			}
			break;
		}
	}
	
	this.parse = function(form) {
		var cellId = $(form).find('input[name="cellId"]').val();
		var inputId = $(form).find('input[name="inputId"]').val();
		var oldData = $(form).find('input[name="oldValue"]').val();
		var container = $(form).find('input[name="container"]').val();
		var newData = new String($nitm.getObj(inputId).val());
		var newDataEnc = new String(escape(newData));
		oldData = new String(oldData);
		var stop = false;
		if(!newData)
		{
			stop = true;
			$nitm.notify('Empty Data\nNo Update', 'alert', false);
		}
		if(newData.localeCompare(oldData) == 0)
		{
			stop = true;
			$nitm.notify('Duplicate Data\nNo Update', 'alert', false);
		}
		if (stop)
		{
			/*input = $('<div id="'+cellId+'">'+newData+'</div>');
			 *	input.off('click');
			 *	input.on('click', function () {
			 *		self.edit($nitm.getObj(cellId));
		});*/
			//$nitm.getObj(container).html(newData);
		}
		else
		{
			var obj = /^(\s*)([\W\w]*)(\b\s*$)/;
			if(obj.test(newData)) 
			{ 
				newData = newData.replace(obj, '$2'); 
			}
			var obj = /  /g;
			while(newData.match(obj)) 
			{ 
				newData = newData.replace(obj, " "); 
			}
			if(newData == 'NULL' || newData == 'null')
			{
				newData = '';
			}
			newData = newData.toString();
			var form = $nitm.getObj('edit_value_form_'+container);
			form.find("[role='value']").val(newData);
			self.operation(form.get(0));
			$nitm.getObj(cellId).css('border','none');
			/*var container = $nitm.getObj(cellId).html('<div id="'+cellId+'">'+newData+'</div>');
			 *	container.off('click');
			 *	container.on('click', function () {
			 *		self.edit($nitm.getObj(cellId));
		});*/
		}
		$nitm.getObj(cellId).html(newData.stripslashes());
		//re-enable the onclick functionality
		$nitm.getObj(cellId).on('click', $nitm.getObj(cellId).data('action'));
		
	}
	
	this.edit = function (elem) {
		var id = $(elem).prop('id');
		var container = $(elem).data('id');
		var type = $(elem).data('type');
		var value = new String($(elem).html());
		var oldValue = new String(value.trim());
		var size = oldValue.length;
		switch(type)
		{
			case 'xml':
				var style = 'font-weight:normal;font-size:12pt;';
				break;
				
			default:
				var style = 'font-weight:normal;font-size:12pt;';
				break;
		}
		form = $("<form name='activeForm' id='activeForm_"+container+"' class='form-horizontal' onsubmit='return false;'></form><br>");
		form.append("<input type='hidden' name='container' value='"+container+"'>");
		form.append("<input type='hidden' name='cellId' value='"+id+"'>");
		form.append("<input type='hidden' name='inputId' value='"+id+this.iObj+"'>");
		form.append("<input type='hidden' name='oldValue' value='"+oldValue+"'>");
		if(size > 96)
		{
			var cols = ($nitm.getObj(id).attr('offsetWidth') / 10) * 1.5;
			var rows = Math.round(size/96) + Math.round((size/108)/8);
			var input = $('<textarea id="'+id+this.iObj+'" class="form-control" rows='+rows+'>'+value+'</textarea>');
			input.on('blur', function () {
				self.parse(form.get(0));
			});
			form.append(input);
			form.append("<br /><noscript><input value='OK' type='submit'></noscript>");
		}
		else
		{
			var input = $('<input class="form-control" size="'+size+'" type="text" id="'+id+this.iObj+'"\>');
			input.val(value);
			input.on('blur', function () {
				self.parse(form.get(0));
			});
			form.append(input);
			//need to do this here because the input doesn't get recognized unless the form is closed out
			form.append("<br /><noscript><input value='OK' type='submit'></noscript>");
			//then we can assign te value
			$nitm.getObj(id+this.iObj).attr('value', value);
		}
		form.on('submit', function () {
			e.preventDefault();
			self.parse(this);
		});
		$nitm.getObj(id).html('').append(form);
		//disable onclick functionality
		$nitm.getObj(id).off('click');
		$nitm.getObj(id+this.iObj).focus();
	}
}


String.prototype.trim = function () {
  return this.replace(/^\s*(\S*(\s+\S+)*)\s*$/, "$1");
}

String.prototype.addslashes = function() {
	return this.replace(/([\\\"\'\.])/g, "\\$1").replace(/\u0000/g, "\\0");
}

String.prototype.stripslashes = function () {
	return this.replace('/\0/g', '0').replace('/\(.)/g', '$1');
}

$nitm.configuration = new Configuration();