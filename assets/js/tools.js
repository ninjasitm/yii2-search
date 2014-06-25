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
	'initSubmitSelect',
	'initScrolledIntoView'
	];
	
	this.init = function () {
		this.defaultInit.map(function (method, key) {
			if(typeof $nitm.tools[method] == 'function')
			{
				$nitm.tools[method]();
			}
		});
	}
	
	/**
	 * Submit a form on change of dropdown input
	 */
	this.initSubmitSelect = function (containerId) {
		var container = $nitm.getObj((containerId == undefined) ? 'body' : containerId);	
		container.find("[role='changeSubmit']").map(function(e) {
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
		container.find("[role='visibility']").map(function(e) {
			switch($(this).data('id') != undefined)
			{
				case true:
					$(this).on('click', function (e) {
						e.preventDefault();
						var id = $(this).data('id');
						var element = $('#'+id);
						var url = $(this).data('url');
						url = !url ? $(this).attr('href') : url;
						switch(url != undefined)
						{
							case true:
							$.get(url, function (result) {
								element.html(result);
							});
							break;
						}
						var success = ($(this).data('success') != undefined) ? $(this).data('success') : null;
						eval(success);
						$nitm.handleVis(id);
					});
					break;
			}
		});
	}
	
	/**
	 * Use data attributes to load a URL into a container/element
	 */
	this.initScrolledIntoView = function (containerId) {
		_self = this;
		var container = $nitm.getObj((containerId == undefined) ? 'body' : containerId);	
		this._watch = [];
		this.$window = $(window),
		this._buffer = null;
		
		this.init = function () {
			/**
			 * Scrolled into view plugin
			 */
			var pluginName = 'scrolledIntoView',
			settings = {
				scrolledin: null,
				scrolledout: null
			}
			
			$.fn[pluginName] = function( options ) {
				var options = $.extend({}, settings, options);
				this.each( function () {
					var $el = $(this),
						   instance = $.data( this, pluginName );
						   if ( instance ) {
							   instance.options = options;
						   } else {
							   $.data(this, pluginName, _self.monitor( $el, options ) );
							   $el.on( 'remove', $.proxy( function () {
								   $.removeData(this, pluginName);
								   self.unmonitor( instance );
							   }, this ) );
						   }
				});
				return this;
			}
			
			/**
			 * Intiialze scroll monitor
			 */
			this.monitorElement(window);
			this.monitorElement("[role~='scrolledIntoViewContainer']");
			
			
			/*
			 * Find elements that need to be activated on scrolledIntoView
			 */
			container.find("[role~='onScrolledIntoView']").map(function(e) {
				var inCallback = window[$(this).data('on-scrolled-in')];
				var outCallback = window[$(this).data('on-scrolled-out')];
				switch(_self.test($(this)))
				{
					case true:
						try {inCallback()} catch(error) {};
						break;
						
					default:
						$(this)
						.scrolledIntoView()
						.on('scrolledin', function () { try {inCallback()} catch(error) {}})
						.on('scrolledout', function () { try {outCallback()} catch(error) {}});
						break;
				}
			});
			return this;
		}
		
		this.monitorElement = function (containerId) {
			var container = (container == undefined) ? 'body' : container;
			$(container).on('scroll', function (e) {
				if ( !this._buffer ) {
					_self._buffer = setTimeout(function () {
						_self.isInView(e);
						_self._buffer = null;
					}, 300);
					_self.monitor($(container));
				}
			});
		}
		
		this.monitor = function( element, options ) {
			var item = { element: element, options: options, invp: false };
			_self._watch.push(item);
			return item;
		}
		
		
		this.unmonitor = function( item ) {
			for ( var i=0;i<_watch.length;i++ ) {
				if ( _self._watch[i] === item ) {
					_self._watch.splice( i, 1 );
					item.element = null;
					break;
				}
			}
		}
		
		
		this.test = function ($el) {
			var docViewTop = this.$window.scrollTop(),
			docViewBottom = docViewTop + this.$window.height(),
			elemTop = $el.offset().top,
			elemBottom = elemTop + $el.height();
			
			return ((elemBottom >= docViewTop) && (elemTop <= docViewBottom)
			&& (elemBottom <= docViewBottom) &&  (elemTop >= docViewTop) );
		}
		
		this.isInView = function (e) {
			
			$.each(_self._watch, function () {
				
				if ( _self.test( this.element ) ) {
					if ( !this.invp ) {
						this.invp = true;
						if ( this.options && this.options.scrolledin ) this.options.scrolledin.call( this.element, e );
				   this.element.trigger( 'scrolledin', e );
					}
				} else if ( this.invp ) {
					this.invp = false;
					if ( this.options.scrolledout ) this.options.scrolledout.call( this.element, e );
				   this.element.trigger( 'scrolledout', e );
				}
			});
		}
		this.init();
	}
	
	/**
	 * Populate another dropdown with data from the current dropdown
	 */
	this.initDynamicDropdown = function (containerId) {
		var container = $nitm.getObj((containerId == undefined) ? 'body' : containerId);		
		container.find("[role='dynamicDropdown']").map(function(e) {
			var id = $(this).data('id');
			switch(id != undefined)
			{
				case true:
					$(this).on('change', function (e) {
						e.preventDefault();
						var element = $nitm.getObj('#'+id);
						var url = $(this).data('url');
						switch(url != undefined)
						{
							case true:
								element.removeAttr('disabled');
								element.empty();	$.get(url+$(this).find(':selected').val()).done( function (result) {
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
		
		container.find("[role='dynamicValue']").map(function(e) {
			var id = $(this).data('id');
			switch(id != undefined)
			{
				case true:
					$(this).on('click', function (e) {
						e.preventDefault();
						var element = $nitm.getObj('#'+id);
						var url = $(this).data('url');
						switch(url != undefined)
						{
							case true:
								element.removeAttr('disabled');
								element.empty();	$.get(url+$(this).find(':selected').val()).done( function (result) {
									var result = $.parseJSON(result);
									element.val(result);
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
	this.initRemoveParent = function (containerId) {
		var container = $nitm.getObj((containerId == undefined) ? 'body' : containerId);
		//enable hide/unhide functionality
		container.find("[role='removeParent']").map(function(e) {
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
	this.removeParent = function (elem, levels)
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
	this.initDisableParent = function (containerId) {
		var container = $nitm.getObj((containerId == undefined) ? 'body' : containerId);
		//enable hide/unhide functionality
		container.find("[role='removeParent']").map(function(e) {
			$(this).on('click', function (e) {
				self.disableParent(this);
				return false;
			});
		});
	}
	
	
	/**
	 * Disable the parent element up to a certain depth
	 */
	this.disableParent = function (elem, levels, parentOptions, disablerOptions, dontDisableFields) {
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
		container.find("[role='autocompleteSelect']").each(function() {
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

$nitm.addOnLoadEvent(function () {
	$nitm.tools = new Tools();
	$nitm.tools.init();
});