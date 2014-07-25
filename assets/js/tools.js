/**
 * NITM Javascript Tools
 * Tools which allow some generic functionality not provided by Bootstrap
 * © NITM 2014
 */

function Tools ()
{
	self = this;
	this.defaultInit = [
		'initVisibility',
		'initRemoveParent',
		'initDisableParent',
		'initCloneParent',
		'initBsMultipleModal',
		'initDynamicDropdown',
		'initDynamicValue',
		'initOffCanvasMenu',
		'initAutocompleteSelect',
		'initSubmitSelect',
	];
	
	this.init = function (containerId) {
		this.coreInit(containerId);
	}
	
	this.coreInit = function (containerId) {
		this.defaultInit.map(function (method, key) {
			if(typeof self[method] == 'function')
				self[method](containerId);
		});
	}
	
	/**
	 * Submit a form on change of dropdown input
	 */
	this.initSubmitSelect = function (containerId) {
		var container = $nitm.getObj((containerId == undefined) ? 'body' : containerId);	
		container.find("[role~='changeSubmit']").map(function(e) {
			$(this).off('change');
			$(this).on('change', function (event) {
				window.location.replace($(this).val());
			});
		});
	}
	
	/**
	 * Use data attributes to load a URL into a container/element
	 */
	this.initVisibility = function (containerId) {
		var container = $nitm.getObj((containerId == undefined) ? 'body' : containerId);
		//enable hide/unhide functionality with optional data retrieval
		container.find("[role~='visibility']").map(function(e) {
			var id = $(this).data('id');
			switch(id != undefined)
			{
				case true:
				var dynamicFunction = function (object) {
					var element = $nitm.getObj(id);
					var url = $(object).data('url');
					var on = $(object).data('on');
					url = !url ? $(object).attr('href') : url;
					var getUrl = true;
					switch(on != undefined)
					{
						case true:
						if($(on).get(0) == undefined) getUrl = false;
						break;
					}
					switch((url != undefined) && (url != '#') && (url.length >= 2) && getUrl)
					{
						case true:
						var ret_val = $.ajax({
							url: url, 
							dataType: 'html',
							complete: function (result) {
								self.evalScripts(result.responseText, function (responseText) {
									element.html(responseText);
								});
							}
						});
						break;
					}
					var success = ($(object).data('success') != undefined) ? $(object).data('success') : null;
					eval(success);
					if($(object).data('toggle')) $nitm.handleVis($(object).data('toggle'));
					$nitm.handleVis(id);
					return ret_val;
				}
				$(this).off('click');
				switch($(this).data('run-once'))
				{
					case true:
					case 1:
					$(this).one('click', function (e) {
						var element = this;
						e.preventDefault();
						$nitm.startSpinner(this);
						$.when(dynamicFunction(this)).done(function () {
							$nitm.stopSpinner(element);
						});
					});
					break;
					
					default:
					$(this).on('click', function (e) {
						var element = this;
						e.preventDefault();
						$nitm.startSpinner(this);
						$.when(dynamicFunction(this)).done(function () {
							$nitm.stopSpinner(element);
						});
					});
					break;
				}
				break;
			}
		});
	}
	
	/**
	 * Populate another dropdown with data from the current dropdown
	 */
	this.initDynamicDropdown = function (containerId) {
		var container = $nitm.getObj((containerId == undefined) ? 'body' : containerId);		
		container.find("[role~='dynamicDropdown']").map(function(e) {
			var id = $(this).data('id');
			switch(id != undefined)
			{
				case true:
				$(this).off('change');
				$(this).on('change', function (e) {
					e.preventDefault();
					var element = $nitm.getObj('#'+id);
					var url = $(this).data('url');
					switch((url != '#') && (url.length >= 2))
					{
						case true:
							element.removeAttr('disabled');
							element.empty();	
							var ret_val = $.get(url+$(this).find(':selected').val()).done( function (result) {
								var result = $.parseJSON(result);
								element.append( $('<option></option>').val('').html('Select value...') );
								if(typeof result == 'object')
								{
									$.each(result, function(val, text) {
										element.append( $('<option></option>').val(text.value).html(text.label) );
									});
								}
							}, 'json');
							break;
					}
					return ret_val;
				});
				break;
			}
		});
	}
	
	/**
	 * Set the value for an element using data attributes
	 */
	this.initDynamicValue = function (containerId) {
		var container = $nitm.getObj((containerId == undefined) ? 'body' : containerId);
		//enable hide/unhide functionality with optional data retrieval
		container.find("[role~='dynamicValue']").map(function(e) {
			switch($(this).data('id') != undefined)
			{
				case true:
				var ret_val = null;
				var dynamicFunction = function (object) {
					var id = $(object).data('id');
					var element = $nitm.getObj(id);
					var url = $(object).data('url');
					var on = $(object).data('on');
					switch((url != '#') && (url.length >= 2))
					{
						case true:
						element.removeAttr('disabled');
						element.empty();	
						var selected = !$(object).find(':selected').val() ? '' : $(object).find(':selected').val();
						switch(on != undefined)
						{
							case true:
							if($(on).get(0) == undefined) return false;
							break;
						}
						switch($(object).data('type'))
						{
							case 'html':
							var ret_val = $.ajax({
								url: url+selected, 
								dataType: 'html',
								complete: function (result) {
									self.evalScripts(result.responseText, function (responseText) {
										element.html(responseText);
									});
								}
							});
							break;
							
							case 'callback':
							var callback = $(object).data('callback');
							var ret_val = $.ajax({
								url: url+selected, 
								dataType: 'json',
								complete: function (result) {
									callback(result);
								}
							});
							break;
							
							default:
							var ret_val = $.ajax({
								url: url+selected, 
								dataType: 'text',
								complete: function (result) {
									element.val(result.responseText);
								}
							});
							break;
						}
						break;
					}
					return ret_val; 
				}
				$(this).off('click');
				switch($(this).data('run-once'))
				{
					case true:
					case 1:
					$(this).one('click', function (e) {
						var element = this;
						e.preventDefault();
						$nitm.startSpinner(this);
						$.when(dynamicFunction(this)).done(function () {
							$nitm.stopSpinner(element);
						});
					});
					break;
					
					default:
					$(this).on('click', function (e) {
						var element = this;
						e.preventDefault();
						$nitm.startSpinner(this);
						$.when(dynamicFunction(this)).done(function () {
							$nitm.stopSpinner(element);
						});
					});
					break;
				}
				break;
			}
		});
	}
	
	/**
	 * THis is used to evaluate remote js files returned in ajax calls
	 */
	this.evalScripts = function (text, callback, options) {
		var dom = $(text);
		//Need to find top level and nested scripts in returned text
		var scripts = dom.find('script');
		$.merge(scripts, dom.filter('script'));
		//Load remote scripts before ading content to DOM
		scripts.each(function(){
			if(this.src) {
				$.getScript(this.src);
				$(this).remove();
			}
		});
		if (typeof callback == 'function') {
			$(document).one('ajaxStop', function () {
				switch(options != undefined)
				{
					case true:
					var existing = (options.context == undefined) ? false : options.context.attr('id');
					break;
					
					default:
					var existing = false;
					break;
				}
				var existingWrapper = !existing ? false : $nitm.getObj(existing).find("[role='nitmToolsAjaxWrapper']").attr('id');
				switch(!existingWrapper)
				{
					case false:
					var wrapperId = existingWrapper;
					var wrapper = $('#'+wrapperId);
					wrapper.html('').html(dom.html());
					break;
					
					default:
					var wrapperId = 'nitm-tools-ajax-wrapper'+Date.now();
					var wrapper = $('<div id="'+wrapperId+'" role="nitmToolsAjaxWrapper">');
					wrapper.append(dom);
					break;
				}
				var contents = $('<div>').append(wrapper);
				//Execute basic init on new content
				(function () {
					return $.Deferred(function (deferred) {
						try {
							//Remove the scripts here so that they don't get run right away.
							contents.find('script').remove();
							callback(contents.html());
						} catch (error) {console.log(error)};
						deferred.resolve();
					}).promise();
				})().then(function () {
					self.coreInit(wrapperId);
					var scriptText = '';
					/*
					 *Now we can run the scripts that were not run.
					 *We'll collect them and then execute them all at once
					 */
					scripts.each(function(){
						if($(this).text()) {
							contents.append($(this));
							scriptText += $(this).text();
						}
					});
					eval(scriptText);
					delete scripts;
				});
			});
		}
	}
	
	/**
	 * Fix for handling slow loading remote js with pjax.
	 * We need to hook onto the before send function and not execute
	 * until the scripts have been loaded.
	 */
	this.pjaxAjaxStop = function () {
		$(document).on('pjax:beforeSend', function (event, xhr, options) {
			var success = options.success;
			options.success = function () {
				self.evalScripts(xhr.responseText, function (responseText) {
					success(responseText, status, xhr);
				}, options);
			}
		});
	};
	
	/**
	 * Remove the parent element up to a certain depth
	 */
	this.initRemoveParent = function (containerId) {
		var container = $nitm.getObj((containerId == undefined) ? 'body' : containerId);
		//enable hide/unhide functionality
		container.find("[role~='removeParent']").map(function(e) {
			$(this).on('click', function (e) {
				e.preventDefault();
				self.removeParent(this);
				return false;
			});
		});
	}
	
	/**
	 * Remove the parent element up to a certain depth
	 */
	this.initCloneParent = function (containerId) {
		var container = $nitm.getObj((containerId == undefined) ? 'body' : containerId);
		//enable hide/unhide functionality
		container.find("[role~='cloneParent']").map(function(e) {
			$(this).on('click', function (e) {
				e.preventDefault();
				self.cloneParent(this);
				return false;
			});
		});
	}
	
	/**
	 * Remove the parent element up to a certain depth
	 */
	this.removeParent = function (elem)
	{
		var $element = $(elem);
		var levels = ($element.data('depth') == undefined) ? -1 : $element.data('depth');
		switch($element.data('parent') != undefined)
		{
			case true:
			var parent = $element.parents($element.data('parent')).eq(levels);
			break;
		}
		parent.remove();
	}
	
	/**
	 * Remove the parent element up to a certain depth
	 */
	this.cloneParent = function (elem)
	{
		var $element = $(elem);
		var clone = $($element.data('clone')).clone();
		clone.find('input').not(':hidden').each(function (){$(this).val('')});
		clone.attr('id', clone.attr('id')+Date.now());
		var to = $nitm.getObj($element.data('to'));
		if($element.data('after') != undefined) {
			clone.insertAfter(to.find($element.data('after')));
		}
		else if($element.data('before') != undefined)  {
			clone.insertBefore(to.find($element.data('before')));
		} else {
			to.append(clone);
		}
		eval("var afterClone = "+$element.data('after-clone'));
		if(typeof afterClone == 'function'){
			afterClone(clone, to, elem);
		}
	}
	
	/**
	 * Initialize remove parent elements
	 */
	this.initDisableParent = function (containerId) {
		var container = $nitm.getObj((containerId == undefined) ? 'body' : containerId);
		//enable hide/unhide functionality
		container.find("[role~='disableParent']").map(function(e) {
			$(this).on('click', function (e) {
				e.preventDefault();
				self.disableParent(this);
			});
		});
	}
	
	
	/**
	 * Disable the parent element up to a certain depth
	 */
	this.disableParent = function (elem, levels, parentOptions, disablerOptions, dontDisableFields) {
		var $element = $(elem);
		switch($element.data('parent') != undefined)
		{
			case true:
			var parent = $nitm.getObj($element.data('parent'));
			break;
			
			default:
			var levels = ($element.data('depth') == undefined) ? ((levels == undefined) ? 1 : levels): $element.data('depth');
			var parent = $element.parent();
			for(i = 0; i<levels; i++)
			{
				parent = parent.parent();
			}
			break;
		}
		//If we're dealing with a form, start from the submit button
		switch($element.prop('tagName'))
		{
			case 'FORM':
				var elem = $element.find(':submit').get(0);
				break;
		}
		
		/*
		 * For some reason this cdoe block doesn't make sense...
		 */
		$element.attr('role', 'disableParent');
		//get and set the role of the element activating this removal process
		var thisRole = $(this).attr('role');
		$(this).attr('role', (thisRole == undefined) ? 'disableParent' : thisRole);
		var thisRole = $(this).attr('role');
		
		//get and set the disabled data attribute
		switch($element.data('disabled'))
		{
			case 1:
			case true:
			var disabled = 1;
			break;
				
			default:
			var disabled = ($element.data('disabled') == undefined) ? 1 : 0;
			break;
		}
		$element.data('disabled', !disabled);
		
		var _defaultDisablerOptions = {
			size: !$element.attr('class') ? 'btn-sm' : $element.attr('class'),
			indicator: ((disabled == 1) ? 'refresh' : 'ban')
		};
		//change the button to determine the curent status
		var _disablerOptions = {};
		for(var attribute in _defaultDisablerOptions)
		{
			try {
				_disablerOptions[attribute] = (disablerOptions.hasOwnProperty(attribute)) ? disablerOptions[attribute] : _defaultDisablerOptions[attribute];
			} catch(error) {
				_disablerOptions[attribute] = _defaultDisablerOptions[attribute];
			}
			
		};
		$element.removeClass().addClass(_disablerOptions.class+' '+_disablerOptions.size).html("<span class='fa fa-"+_disablerOptions.indicator+"'></span>");
		
		//now perform disabling on parent
		var _defaultParentOptions = {
			class: 'alert '+((disabled == 1) ? 'bg-disabled' : 'bg-success')
		};
		var elemEvents = ['click'];
		parent.find(':input,:button,a').map(function () {
			switch($(this).attr('role'))
			{
				case thisRole:
					break;
					
				default:
					switch($(this).data('keep-enabled') || ($(this).attr('name') == '_csrf'))
					{
						case false:
							switch(disabled == 1)
							{
								case true:
									var _class = 'warning';
									var _icon = 'plus';
									break;
									
								default:
									var _class = 'danger';
									var _icon = 'ban';
									break;
							}
							switch(dontDisableFields)
							{
								case false:
								case undefined:
									for(var event in elemEvents)
									{
										switch(disabled)
										{
											case true:
												$(this).on(event, function (event) {
													return false;
												});
												break;
												
											case false:
												$(this).on(event, function (event) {
													$(this).trigger(event);
												});
												break;
										}
									}
									switch(disabled)
									{
										case 1:
										case true:
											$(this).attr('disabled', disabled);
											break;
											
										default:
											$(this).removeAttr('disabled');
											break;
									}
									break;
							}
					}
					break;
			}
		});
		
		var _parentOptions = {};
		for(var attribute in _defaultParentOptions)
		{
			try {
				_parentOptions[attribute] = (parentOptions.hasOwnProperty(attribute)) ? parentOptions[attribute] : _defaultParentOptions[attribute];
			} catch(error) {
				_parentOptions[attribute] = _defaultParentOptions[attribute];
			}
			
		}
		parent.removeClass().addClass(_parentOptions.class);
	}
	
	/**
	 * Fix for loading multiple boostrap modals
	 */
	this.initBsMultipleModal = function () {
		//to support multiple modals
		$(document).on('hidden.bs.modal', function (e) {
			$(e.target).removeData('bs.modal');
			//Fix a bug in modal which doesn't properly reload remote content
			$(e.target).find('.modal-content').html('');
		});
	}
	
	/**
	 * Custom auto complete handler
	 */
	this.initAutocompleteSelect = function (containerId) {
		var container = $nitm.getObj((containerId == undefined) ? 'body' : containerId);
		container.find("[role~='autocompleteSelect']").each(function() {
			$(this).on('autocompleteselect', function (e, ui) {
				e.preventDefault();
				var element = $(this).data('real-input');
				var appendTo = $(this).data('append-html');
				switch(appendTo != undefined)
				{
					case true:
					switch(ui.item.html != undefined)
					{
						case true:
						$nitm.getObj(appendTo).append($(ui.item.html));
						break;
					}
					break;
				}
				switch(element != undefined)
				{
					case true:
					$nitm.getObj(element).val(ui.item.value);
					$(this).val(ui.item.text);
					break;
						
					default:
					$(this).val(ui.item.value);
					break;
				}
			});
		});
	}
	
	/**
	 * Off canvas menu support
	 */
	this.initOffCanvasMenu = function (containerId) {
		var container = $nitm.getObj((containerId == undefined) ? 'body' : containerId);
		$(document).ready(function () {
			$("[data-toggle='offcanvas']").click(function () {
				$('.row-offcanvas').toggleClass('active')
			});
		});
	}
}

$nitm.initModule('tools', new Tools());