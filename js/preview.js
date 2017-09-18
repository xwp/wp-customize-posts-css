/* global wp, _, jQuery*/
/* exported CustomizePostsCSSPreview */
/* eslint no-magic-numbers: ["error", { "ignore": [0,1,2,3] }] */
/* eslint complexity: ["error", 5] */

var CustomizePostsCSSPreview = (function( $ ) {
	'use strict';

	var component = {
		/**
		 * @alias wp.customize
		 * @type {object}
		 */
		api: null,

		/**
		 * @type {object}
		 */
		data: {
			metaKey: ''
		}
	};

	/**
	 * Init component.
	 *
	 * @param {object} api Customizer JS API (wp.customize).
	 * @param {object} data Data.
	 * @returns {void}
	 */
	component.init = function init( api, data ) {

		/**
		 * @alias wp.customize
		 */
		component.api = api;

		if ( data ) {
			_.extend( component.data, data );
		}

		component.addStylePreviewing();
	};

	/**
	 * Add previewing for post Custom CSS setting changes.
	 *
	 * @return {void}
	 */
	component.addStylePreviewing = function addStylePreviewing() {
		function handleSetting( setting ) {
			var idParts = setting.id.replace( /]/g, '' ).split( /\[/ );
			if ( 'postmeta' === idParts[0] && idParts[3] === component.data.metaKey ) {
				component.watchSettingChanges( setting, parseInt( idParts[2], 10 ) );
			}
		}
		component.api.each( handleSetting );
		component.api.bind( 'add', handleSetting );
	};

	/**
	 * Ensure style element for given setting and post ID.
	 *
	 * @param {wp.customize.Value} setting - Setting.
	 * @param {number} postId - Post ID.
	 * @returns {void}
	 */
	component.watchSettingChanges = function addStyleElement( setting, postId ) {
		var className = 'post-custom-css-' + String( postId ), writeStyleElement;

		writeStyleElement = function() {
			var style = $( '.' + className );
			if ( 0 === style.length ) {
				style = $( '<style></style>', {
					'class': className,
					text: setting.get()
				} );
				$( 'head' ).append( style );
			}
			style.text( setting.get() );
		};

		writeStyleElement();
		setting.bind( writeStyleElement );
	};

	return component;

})( jQuery );
