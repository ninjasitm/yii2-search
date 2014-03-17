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
					'initBsMultipleModal',
					'initDynamicDropdown',
					'initOffCanvasMenu',
					'initAutocompleteSelect',
					'initSubmitSelect'
				];
}

Tools.prototype.init = function () {
	this.defaultInit.map(function (method, key) {
		if(typeof self[method] == 'function')
		{
			self[method]();
		}
	});
}

/**
 * Submit a form on change of dropdown input
 */
Tools.prototype.initSubmitSelect = function (container) {
	var container = (container == undefined) ? 'body' : container;
	$(container).find("[role='changeSubmit']").map(function(e) {
		$(this).on('change', function (event) {
			window.location.replace($(this).val());
		});
	});
}

/**
 * Use data attributes to load a URL into a container/element
 */
Tools.prototype.initVisibility = function (container) {
	var container = (container == undefined) ? 'body' : container;
	//enable hide/unhide functionality with optional data retrieval
	
	$(container).find("[role='visibility']").map(function(e) {
		var id = $(this).data('id');
		switch(id != undefined)
		{
			case true:
			$(this).on('click', function (e) {
				e.preventDefault();
				var container = $('#'+id);
				var url = $(this).data('url');
				switch(url != undefined)
				{
					case true:
					$.get(url, function (result) {
						container.html(result);
					});
					break;
				}
				var success = ($(this).data('success') != undefined) ? $(this).data('success') : null;
				eval(success);
				container.toggle();
			});
			break;
		}
	});
}

/**
 * Populate another dropdown with data from the current dropdown
 */
Tools.prototype.initDynamicDropdown = function (container) {
	var container = (container == undefined) ? 'body' : container;
	
	$(container).find("[role='dynamicDropdown']").map(function(e) {
		var id = $(this).data('id');
		switch(id != undefined)
		{
			case true:
				$(this).on('change', function (e) {
					e.preventDefault();
					var container = getObj('#'+id);
					var url = $(this).data('url');
					switch(url != undefined)
					{
						case true:
							container.removeAttr('disabled');
							container.empty();	$.get(url+$(this).find(':selected').val()).done( function (result) {
								var result = $.parseJSON(result);
								container.append( $('<option></option>').val('').html('Select value...') );
								if(typeof result == 'object')
								{
									$.each(result, function(val, text) {
										container.append( $('<option></option>').val(text.value).html(text.label) );
									});
								}
							}, 'json');
							break;
					}
				});
				break;
		}
	});
}

/**
 * Set the value for an element sing data attributes
 */
Tools.prototype.initDynamicValue = function (container) {
	var container = (container == undefined) ? 'body' : container;
	//enable hide/unhide functionality with optional data retrieval
	
	$(container).find("[role='dynamicValue']").map(function(e) {
		var id = $(this).data('id');
		switch(id != undefined)
		{
			case true:
				$(this).on('click', function (e) {
					e.preventDefault();
					var container = getObj('#'+id);
					var url = $(this).data('url');
					switch(url != undefined)
					{
						case true:
							container.removeAttr('disabled');
							container.empty();	$.get(url+$(this).find(':selected').val()).done( function (result) {
								var result = $.parseJSON(result);
								container.val(result);
							}, 'json');
							break;
					}
				});
				break;
		}
	});
}

/**
 * Remove the parent element up to a certain depth
 */
Tools.prototype.initRemoveParent = function (container) {
	var container = (container == undefined) ? '' : container;
	//enable hide/unhide functionality
	$(container).find("[role='removeParent']").map(function(e) {
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
Tools.prototype.removeParent = function (elem, levels)
{	
	var levels = ($(elem).data('depth') == undefined) ? ((levels == undefined) ? 1 : levels): $(elem).data('depth');
	var parent = $(elem).parent();
	for(i = 0; i<levels; i++)
	{
		parent = parent.parent();
	}
	parent.remove();
}

/**
 * Initialize remove parent elements
 */
Tools.prototype.initDisableParent = function (container) {
	var container = (container == undefined) ? '' : container;
	//enable hide/unhide functionality
	$(container).find("[role='removeParent']").map(function(e) {
		$(this).on('click', function (e) {
			self.disableParent(this);
			return false;
		});
	});
}


/**
 * Disable the parent element up to a certain depth
 */
Tools.prototype.disableParent = function (elem, levels, parentOptions, disablerOptions, dontDisableFields) {
	var levels = ($(elem).data('depth') == undefined) ? ((levels == undefined) ? 1 : levels): $(elem).data('depth');
	var parent = $(elem).parent();
	for(i = 0; i<levels; i++)
	{
		parent = parent.parent();
	}
	//If we're dealing with a form, start from the submit button
	switch($(elem).prop('tagName'))
	{
		case 'FORM':
		var elem = $(elem).find(':submit').get(0);
		break;
	}
	$(elem).attr('role', 'disableParentTrigger');
	//get and set the role of the element activating this removal process
	var thisRole = $(this).attr('role');
	$(this).attr('role', (thisRole == undefined) ? 'disableParentTrigger' : thisRole);
	var thisRole = $(this).attr('role');
	
	//get and set the disabled data attribute
	switch($(elem).data('disabled'))
	{
		case 1:
		case true:
		var disabled = 1;
		break;
		
		default:
		var disabled = 0;
		break;
	}
	$(elem).data('disabled', !disabled);
	
	var _defaultDisablerOptions = {
		class: 'btn '+((disabled == 1) ? 'btn-success' : 'btn-danger'), 
		size: 'btn-sm',
		indicator: ((disabled == 1) ? 'repeat' : 'remove')
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
	$(elem).removeClass().addClass(_disablerOptions.class+' '+_disablerOptions.size).html("<span class='glyphicon glyphicon-"+_disablerOptions.indicator+"'></span>");
	
	//now perform disabling on parent
	var _defaultParentOptions = {
		class: 'alert '+((disabled == 1) ? 'alert-disabled' : 'alert-success')
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
					var _icon = 'remove';
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
Tools.prototype.initBsMultipleModal = function () {
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
Tools.prototype.initAutocompleteSelect = function (container) {
	var container = (container == undefined) ? 'body' : container;
	$(container).find("[role='autocompleteSelect']").each(function() {
		$(this).on('autocompleteselect', function (e, ui) {
			e.preventDefault();
			var options = $(this).data('select');
			switch(options.hasOwnProperty('container'))
			{
				case true:
				getObj(options.container).val(ui.item.value);
				$(this).val(ui.item.label);
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
Tools.prototype.initOffCanvasMenu = function (container) {
	var container = (container == undefined) ? '' : container;
	$(document).ready(function () {
		$("[data-toggle='offcanvas']").click(function () {
			$('.row-offcanvas').toggleClass('active')
		});
	});
}

addOnLoadEvent(function () {
	var t = new Tools();
	t.init();
});