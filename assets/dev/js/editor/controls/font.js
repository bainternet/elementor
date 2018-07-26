var ControlSelect2View = require( 'elementor-controls/select2' );

module.exports = ControlSelect2View.extend( {
	_enqueuedFonts: [],
	$previewContainer: null,
	enqueueFont: function( font ) {
		if ( -1 !== this._enqueuedFonts.indexOf( font ) ) {
			return;
		}
		var fontType = elementor.config.controls.font.options[ font ],
			fontUrl,

			subsets = {
				'ru_RU': 'cyrillic',
				'uk': 'cyrillic',
				'bg_BG': 'cyrillic',
				'vi': 'vietnamese',
				'el': 'greek',
				'he_IL': 'hebrew'
			};

		switch ( fontType ) {
			case 'googlefonts' :
				fontUrl = 'https://fonts.googleapis.com/css?family=' + font + ':100,100italic,200,200italic,300,300italic,400,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic';

				if ( subsets[ elementor.config.locale ] ) {
					fontUrl += '&subset=' + subsets[ elementor.config.locale ];
				}

				break;

			case 'earlyaccess' :
				var fontLowerString = font.replace( /\s+/g, '' ).toLowerCase();
				fontUrl = 'https://fonts.googleapis.com/earlyaccess/' + fontLowerString + '.css';
				break;
		}

		if ( ! _.isEmpty( fontUrl ) ) {
			$('head').find( 'link:last' ).after( '<link href="' + fontUrl + '" rel="stylesheet" type="text/css">' );
		}

		this._enqueuedFonts.push( font );
	},

	getSelect2Options: function() {
		return {
			dir: elementor.config.is_rtl ? 'rtl' : 'ltr',
			templateSelection: this.fontPreviewTemplate,
			templateResult: this.fontPreviewTemplate
		};
	},

	onReady: function() {
		var self = this;
		this.ui.select.select2( this.getSelect2Options() );
		this.ui.select.on( 'select2:open', function( event ) {
			self.$previewContainer = $( '.select2-results__options[role="tree"]:visible' );
			// load initial?
			setTimeout( function() {
				self.enqueueFontsInView();
			}, 650);
			self.$previewContainer.on( 'scroll', function(){  self.scrollStopDetection.onScroll.apply( self ); } );
		})
	},

	scrollStopDetection: {
		idle: 350,
		timeOut: null,
		onScroll: function() {
			var parent = this,
				self = this.scrollStopDetection;
			clearTimeout( self.timeOut );
			self.timeOut = setTimeout( function() {
				parent.enqueueFontsInView();
			}, self.idle );
		}
	},

	enqueueFontsInView: function() {
		var self = this,
			containerOffset = this.$previewContainer.offset(),
			top = containerOffset.top,
			bottom = top + this.$previewContainer.innerHeight(),
			fontsInView = [];
		this.$previewContainer.children().find( 'li:visible' ).each( function( index, font ) {
			var offset = $( font ).offset();
			if ( offset && offset.top > top && offset.top < bottom ) {
				fontsInView.push( $( font ) );
			}
		});
		$.each( fontsInView, function( i, font ) {
			var fontFamily = $( font ).find('span').html();
			self.enqueueFont( fontFamily );
		});
	},

	fontPreviewTemplate: function( state ) {
		if (!state.id) {
			return state.text;
		}
		var $state = $(
			'<span style="font-family: \'' + state.element.value.toString() + '\'">' + state.text + '</span>'
		);
		return $state;
	},

	templateHelpers: function() {
		var helpers = ControlSelect2View.prototype.templateHelpers.apply( this, arguments ),
			fonts = this.model.get( 'options' );

		helpers.getFontsByGroups = function( groups ) {
			var filteredFonts = {};

			_.each( fonts, function( fontType, fontName ) {
				if ( _.isArray( groups ) && _.contains( groups, fontType ) || fontType === groups ) {
					filteredFonts[ fontName ] = fontName;
				}
			} );

			return filteredFonts;
		};

		return helpers;
	}
} );
