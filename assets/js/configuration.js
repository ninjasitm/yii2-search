
function Configuration()
{	
	var self = this;
	this.views = {
		containers: {
			section: 'sections_container',
			configuration: 'configuration_container',
			showSection: 'show_section',
			createValue: 'create_value_container'
		}
	};
	this.type = {
		default: 'db',
		current: 'db',
	};
	this.forms = {
		confirmThese: [
			'deleteSection', 
			'deleteValue',
		],
		allowCreate: ['createNewValue'],
		actions : {
			create: '/configuration/create',
			del: '/configuration/delete',
			update: '/configuration/update',
			undelete: '/configuration/undelete'
		}
	};
	this.buttons = {
		allowUpdate: ['updateFieldButton']
	};
	this.blocks = {
		allowUpdate: ['updateFieldDiv']
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
	this.defaultInit = [
		'initChanging',
	];

	this.init = function (container) {
		this.defaultInit.map(function (method, key) {
			if(typeof self[method] == 'function')
			{
				self[method](container);
			}
		});
	}
	
	//functions
	this.initChanging = function () {
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
	
	this.initDeleting = function (containerId, result) {
		var containerId = (containerId == undefined) ? 'body' : containerId;
		var container = $nitm.getObj(containerId);
		this.forms.confirmThese.map(function (v) {
			var form = container.find("form[role='"+v+"']");
			form.off('submit');
			switch(v)
			{
				case 'deleteSection':
				switch(result != undefined)
				{
					case true:
					form.find("input[id='configer\-cfg_s']").val(result.section);
					break;
				break;
				}
			}
			form.on('submit', function (e) {
				e.preventDefault();
				switch(v)
				{
					case 'deleteSection':
					var value = form.find("input[id='configer\-cfg_s']").val();
					var message = "Are you sure you want to delete section: "+value;
					var shouldConfirm = true;
					break;
					
					case 'deleteValue':
					var message = $(this).find(':submit').attr('title');
					var shouldConfirm = false;
					break;
				}
				switch(shouldConfirm)
				{
					case true:
					if(confirm(message)) self.operation(this);
					break;
					
					default:
					self.operation(this);
					break;
				}
				return false;
			});
		});
	}
	
	this.initUpdating = function (containerId) {
		var containerId = (containerId == undefined) ? 'body' : containerId;
		var container = $nitm.getObj(containerId);
		this.buttons.allowUpdate.map(function (v) {
			var button = container.find("[role='"+v+"']");
			button.on('click', function (e) {
				e.preventDefault();
				self.update(this);
			});
		});
		
		this.blocks.allowUpdate.map(function (v) {
			var block = container.find("[role='"+v+"']");
			var fn = function (e) {
				self.update(this);
			};
			block.on('click', fn);
			block.data('action', fn);
		});
	}
	
	this.initCreating = function (containerId) {
		var containerId = (containerId == undefined) ? 'body' : containerId;
		var container = $nitm.getObj(containerId);
		this.forms.allowCreate.map(function (v) {
			var form = container.find("form[role='"+v+"']");
			form.off('submit');
			form.on('submit', function (e) {
				e.preventDefault();
				return self.operation(this);
			});
		});
	}
	
	
	this.operation = function (form) {
		/*
		 * This is to support yii active form validation and prevent multiple submitssions
		 */
		try {
			$data = $(form).data('yiiActiveForm');
			if(!$data.validated)
				return false;
		} catch (error) {}
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
								
							case 'update':
							self.afterUpdate(result);
							break;
								
							case 'delete':
							self.afterDelete(result, form);
							break;
								
							case 'create':
							case 'undelete':
							self.afterCreate(result, form);
							break;
						}
					},
					function () {
						$nitm.notify('Error Could not perform configuration action. Please try again', $nitm.classes.error, false);
						//try { self.restore(form);} catch(error) {}
					}
				);
				break;
		}
	}
	
	this.afterGet = function(result) {
		var nClass = $nitm.classes.warning;
		if(result.data)
		{
			message = 'Successfully loaded clean configuration information';
			nClass = $nitm.classes.success;
			var container = $('#'+self.views.containers.section).html(result.data);
			var triggers = ['updateFieldDiv', 'updateFieldButton'];
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
						self.update(elem);
					};
					$(this).on('click', function (e) {
						fn();
					});
					$(this).data('action', fn);
				})
			});
			self.prepareDeleting('#'+self.views.containers.configuration, result);
			//self.prepareCreateing('#'+self.views.containers.section);
			//self.prepareUpdating('#'+self.views.containers.section);
		}
		else
		{
			message = 'Error empty configuration information';
		}
		$nitm.notify(message, nClass, false);
	}
	
	this.afterCreate = function(result, form) {
		var nClass = $nitm.classes.warning;
		if(result.success)
		{
			nClass = $nitm.classes.success;
		}
		$nitm.notify(result.message, nClass, false);
		var _form = $(form);
		switch(_form.attr('role'))
		{
			case 'undeleteValue':
			switch(result.success)
			{
				case true:
				//if this value was recently deleted and is now re-createed then enabled deleting
				_form.find(':submit').removeClass('').addClass('btn btn-danger').html('del');
				_form.attr('action', self.forms.actions.del);
				_form.attr('role', 'deleteValue');
				_form.find(':input').removeAttr('disabled');
				$nitm.getObj('value_'+result.container).removeClass('disabled');
				break;
			}
			break;
			
			default:
			$nitm.getObj('#'+self.views.containers.createValue).before($(result.data));
			self.prepareDeleting('#'+'value_'+result.unique_id);
			self.prepareUpdating('#'+'value_'+result.unique_id);
			break;
		}
		form.reset();
	}
	
	this.afterUpdate = function (result) {
		var nClass = $nitm.classes.warning;
		var iClass = $nitm.classes.information;
		if(result.success)
		{
			nClass = $nitm.classes.success;
		}
		else
		{
			iClass = $nitm.classes.warning;
			$nitm.getObj(result.container+'.div').html(result.old_value);
		}
		$nitm.getObj(result.container+'.div').removeClass().addClass(iClass);
		$nitm.notify(result.message, nClass, false);
	}
	
	this.afterDelete = function(result, form) {
		var nClass = $nitm.classes.warning;
		if(result.success)
		{
			nClass = $nitm.classes.success;
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
				$nitm.getObj('#'+self.views.containers.showSection).find("select :selected").remove();
				break;
				
				default:
				_form.find(':submit').removeClass().addClass('btn btn-warning').html('undel').attr('title', "Are you sure you want to undelete this value?");
				_form.attr('action', self.forms.actions.undelete);
				_form.attr('role', 'undeleteValue');
				_form.append("<input type='hidden' name='Configer[cfg_v\]' id='configer-cfg_v' value='"+$nitm.getObj(result.container+'.div').html()+"'/>");
				var container = $nitm.getObj('value_'+result.container);
				container.addClass('disabled');
				container.children().map(function() {
					switch($(this).attr('role'))
					{
						case 'undeleteValue':
						break;
						
						default:
						$(this).attr('disabled', true);
						$(this).addClass('disabled');
						break;
					}
				});
				break;
			}
			break;
		}
	}
	
	this.restore = function(form) {
		try {
			var cellId = $(form).find('input[name="cellId"]').val();
			var oldData = $(form).find('input[name="oldValue"]').val();
			var container = $(form).find('input[name="container"]').val();
			$nitm.getObj(cellId).html(oldData.stripslashes());
		} catch(error) {}
		//$nitm.getObj(cellId).on('click', $nitm.getObj(cellId).data('action'));
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
			 *		self.update($nitm.getObj(cellId));
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
			var form = $nitm.getObj('update_value_form_'+container);
			form.find("[role='value']").val(newData);
			self.operation(form.get(0));
			$nitm.getObj(cellId).css('border','none');
			/*var container = $nitm.getObj(cellId).html('<div id="'+cellId+'">'+newData+'</div>');
			 *	container.off('click');
			 *	container.on('click', function () {
			 *		self.update($nitm.getObj(cellId));
		});*/
		}
		$nitm.getObj(cellId).html(newData.stripslashes());
		//re-enable the onclick functionality
		$nitm.getObj(cellId).on('click', $nitm.getObj(cellId).data('action'));
		
	}
	
	this.update = function (elem) {
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

String.prototype.createslashes = function() {
	return this.replace(/([\\\"\'\.])/g, "\\$1").replace(/\u0000/g, "\\0");
}

String.prototype.stripslashes = function () {
	return this.replace('/\0/g', '0').replace('/\(.)/g', '$1');
}

$nitm.addOnLoadEvent(function () {
	$nitm.initModule('configuration', new Configuration());
});