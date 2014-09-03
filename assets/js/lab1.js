// JavaScript Document

function NitmEntity () {
	var self = this;
	this.selfInit = false;
	this.current = '';
	this.forms = {
		roles: {
			ajaxSearch: "filter",
			ajaxForm: 'ajaxForm'
		}
	};
	
	this.buttons = {
		roles: ['ajaxForm']
	};
	this.actions = {
		roles: ['metaAction'],
		updateAction: 'updateAction',
		disabledOnClose: 'disabledOnClose',
		disabledOnResolve: 'disabledOnResolve',
		resolveAction: 'resolveAction',
		closeAction: 'closeAction',
		deleteAction: 'deleteAction',
		disableAction: 'disableAction',
		duplicateAction: 'duplicateAction',
		hasSpinner: 'spinnerAction',
	};
	this.views = {
		statusIndicator: 'statusIndicator',
		itemExtra: 'itemExtra'
	};
	this.modules = {};
	this.defaultInit = [
		'initSearch',
	];
	
	this.init = function (name, object) {
		$nitm.initModule(name, object);
		self.setCurrent(name);
	}
	
	this.initForms = function (containerId, currentIndex) {
		var container = $nitm.getObj((containerId == undefined) ? 'body' : containerId);
		self.setCurrent(currentIndex);
		try {
			var roles = self.modules[self.current].forms.roles
		} catch(error) {
			var roles = self.forms.roles;
		}
		$.map(roles, function(role, key) {
			$nitm.getObj(container).find("form[role~='"+role+"']").map(function() {
				var $form = $(this);
				$form.off('submit');
				$form.on('submit', function (e) {
					e.preventDefault();
					try {
						$data = $(this).data('yiiActiveForm');
						switch($data.validated)
						{
							case true:
							self.operation(this, null, currentIndex);
							break;
						}
					} catch (error) {
						self.operation(this, null, currentIndex);
					}
					return false;
				});
			});
		});
	}
	
	this.initMetaActions = function (containerId, currentIndex) {
		var container = $nitm.getObj((containerId == undefined) ? 'body' : containerId);
		$.map(self.actions.roles, function (v) {
			container.find("[role~='"+v+"']").map(function() {
				//$(this).off('click');
				$(this).on('click', function (e) {
					e.preventDefault();
					var elem = $(this);
					switch(true)
					{
						case elem.attr('role').indexOf(self.actions.deleteAction) != -1:
						if(confirm("Are you sure you want to delete this?"))
							var proceed = true;
						break;
						
						default:
						var proceed = true;
						break;
					}
					if(proceed === true)
					{
						$nitm.startSpinner(elem);
						$.post($(this).attr('href'), function (result) { 
							$nitm.stopSpinner(elem);
							switch(result.action)
							{
								case 'close':
								case 'disable':
								self.afterClose(result, currentIndex);
								break;
								
								case 'resolve':
								case 'complete':
								self.afterResolve(result, currentIndex);
								break;
								
								case 'duplicate':
								self.afterDuplicate(result, currentIndex);
								break;
								
								case'delete':
								self.afterDelete(result, currentIndex, elem);
								break;
							}
						}, 'json');
					}
				});
			});
		});
	}
	
	this.initSearch = function (containerId) {
		var container = $nitm.getObj((containerId == undefined) ? 'body' : containerId);
		$(container).find("form[role~='"+this.forms.roles.ajaxSearch+"']").map(function() {
			var _form = this;
			$(this).off('submit');
			var submitFunction = function (e) {
				e.preventDefault();
				var request = self.operation(_form, function(result, form, xmlHttp) {
					var replaceId = $(form).data('id');
					$nitm.notify(result.message, $nitm.classes.info, form);
					$nitm.getObj(replaceId).replaceWith(result.data);
					$(form).find(':input').removeAttr('disabled');
					history.pushState({}, result.message, xmlHttp.url);
				});
			}
			$(this).find(':input').on('change', function (e) {submitFunction(e)});
			$(this).on('submit', function (e) {submitFunction(e)});
		});
	}
	
	this.operation = function (form, callback, currentIndex) {
		self.setCurrent(currentIndex);
		var $form = $(form);
		data = $form.serializeArray();
		data.push({'name':'__format', 'value':'json'});
		data.push({'name':'getHtml', 'value':true});
		data.push({'name':'do', 'value':true});
		data.push({'name':'ajax', 'value':true});
		switch(!$(form).attr('action'))
		{
			case false:
			$($nitm).trigger('nitm-animate-submit-start', [form]);
			var request = $nitm.doRequest({
				url: $(form).attr('action'), 
				data: data,
				success: function (result){
					switch(typeof callback == 'function')
					{
						case true:
						callback(result, form, this);
						break;
						
						default:
						//if the module already has a method for this action
						try {
							self.modules[self.current]['after'+$nitm.safeFunctionName(result.action)](result, form, currentIndex);
						} catch(error) {
							if(typeof self['after'+$nitm.safeFunctionName(result.action)] == 'function') {
							self['after'+$nitm.safeFunctionName(result.action)](result, form, currentIndex);
							} else {
								switch(result.action)
								{							
									case 'complete':
									self.afterResolve(result, currentIndex);
									break;
									
									case 'disable':
									self.afterClose(result, currentIndex);
									break;
								}
							}
						}
						break;
					}
				},
				error: function () {
					$nitm.notify('Whoops somethign went wrong. Try again. If it keeps happening let the lazy  admin know!', $nitm.classes.error, form);
				},
				type: $form.attr('method'),
			});
			request.done(function () {
				$($nitm).trigger('nitm-animate-submit-stop', [form]);
			});
			break;
		}
		return request;
	}
	
	this.afterCreate = function (result, form, currentIndex) {
		self.setCurrent(currentIndex);
		if(result.success == true)
		{
			$(form).get(0).reset();
			var message = !result.message ? "Success! You can add another or view the newly added one" : result.message;
			$nitm.notify(message, $nitm.classes.success, form);
			if(result.data)
			{
				$nitm.place({append:false, index:1}, result.data, self.modules[self.current].views.containerId);
				self.initMetaActions('#'+self.modules[self.current].views.itemId+result.id);
			}
		}
		else
		{
			$nitm.notify((!result.message ? "Couldn't create item" : result.message), $nitm.classes.error, form);
		}
	}
	
	this.afterUpdate = function (result, form, currentIndex) {
		self.setCurrent(currentIndex);
		if(result.success)
		{
			var message = !result.message ? "Update successful!" : result.message;
			$nitm.notify(message, $nitm.classes.success, form);
			if(result.data)
			{
				//Remove any items that are related with role~=itemExtra
				$("[role~='"+self.views.itemExtra+result.id+"']").map(function () {
					$(this).remove();
				});
				//$nitm.getObj('#'+self.modules[self.current].views.itemId+result.id).replaceWith(result.data);
				self.initMetaActions('#'+self.modules[self.current].views.itemId+result.id);
			}
		}
		else
		{
			$nitm.notify((!result.message ? "Couldn't update item" : result.message), $nitm.classes.error, form);
		}
	}
	
	this.afterClose = function (result, currentIndex) {
		self.setCurrent(currentIndex);
		if(result.success)
		{
			var containers = $nitm.getObj("[id='"+self.modules[self.current].views.itemId+result.id+"']");
			containers.each(function(index, element) {
				var container = $(element);
				container.find("[role~='"+self.actions.disabledOnClose+"']").map(function () {
					switch($(this).css('visbility') == undefined)
					{
						case false:
						var visibility = ($(this).css('visbility') == 'hidden') ? 'visible' : 'hidden';
						$(this).css('visbility', visibility);
						break;	
											
						default:
						$(this).toggleClass(self.classes.hidden, result.data);
						break;
					}
				});
				container.find("[role~='"+self.views.replyForm+"']").toggleClass(self.classes.hidden, result.data);
				var actionElem = container.find("[role~='"+self.actions[result.action+'Action']+"']");
				actionElem.attr('title', result.title);
				actionElem.find(':first-child').replaceWith(result.actionHtml);
				var element = container.parent().find("[role~='"+self.views.statusIndicator+result.id+"']");
				element.removeClass().addClass(result.class);
			});
		}
	}
	
	this.afterDelete = function (result, currentIndex, elem) {
		self.setCurrent(currentIndex);
		if(result.success)
		{
			try {
				$nitm.module('tools').removeParent(elem);
			} catch (error) {
				var container = $nitm.getObj(self.modules[self.current].views.itemId+result.id);
				container.remove();
			}
		}
	}
	
	this.afterResolve = function (result, currentIndex) {
		self.setCurrent(currentIndex);
		if(result.success)
		{
			var containers = $nitm.getObj("[id='"+self.modules[self.current].views.itemId+result.id+"']");
			containers.each(function(index, element) {
				var container = $(element);
				container.parent().find("[role~='"+self.views.statusIndicator+result.id+"']").removeClass().addClass(result.class);
				var actionElem = container.find("[role~='"+self.actions[result.action+'Action']+"']");
				actionElem.attr('title', result.title);
				actionElem.html(result.actionHtml);
				container.find("[role~='"+self.actions.disabledOnResolve+"']").toggleClass(self.classes.hidden, result.data);
			});
		}
	}
	
	this.afterDuplicate = function (result, currentIndex) {
		self.setCurrent(currentIndex);
		if(result.success)
		{
			var containers = $nitm.getObj("[id='"+self.modules[self.current].views.itemId+result.id+"']");
			containers.each(function(index, element) {
				var container = $(element);
				container.removeClass().addClass(result.class);
				var actionElem = container.find("[role~='"+self.actions.duplicateAction+"']");
				actionElem.attr('title', result.title);
				actionElem.find(':first-child').replaceWith(result.actionHtml);
			});
		}
	}
	
	this.setCurrent = function (currentIndex) {
		self.current = (currentIndex == undefined) ? self.current : currentIndex.split(':').pop();
	}
}

$nitm.addOnLoadEvent(function () {
	$nitm.initModule('entity', new NitmEntity());
	$nitm.module('entity').initSearch();
	$(document).on('pjax:send', function (xhr, options) {
		$(xhr.target).fadeOut('slow');
	});
	$(document).on('pjax:complete', function (xhr, options) {
		$(xhr.target).fadeIn('slow');
	});
});