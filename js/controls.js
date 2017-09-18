/* global wp, _ */
/* exported CustomizePostsCSS */
/* eslint no-magic-numbers: ["error", { "ignore": [0,1,2] }] */
/* eslint complexity: ["error", 5] */

var CustomizePostsCSS = (function() {
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
			metaKey: '',
			themeSupport: '',
			l10n: {},
			editorSettings: {}
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

		component.extendSections();
	};

	/**
	 * Extend existing sections and future sections added with the Custom CSS control.
	 *
	 * @return {void}
	 */
	component.extendSections = function extendSections() {
		function addSectionControls( section ) {
			if ( section.extended( component.api.Posts.PostSection ) ) {
				section.contentsEmbedded.done( function addControl() {
					component.addControl( section );
				} );
			}
		}
		component.api.section.each( addSectionControls );
		component.api.section.bind( 'add', addSectionControls );
	};

	/**
	 * Add the Custom CSS control to section.
	 *
	 * @param {wp.customize.Section} section - Section.
	 * @returns {wp.customize.Control|null} The control.
	 */
	component.addControl = function( section ) {
		var control, controlId, settingId, postTypeObj;
		postTypeObj = component.api.Posts.data.postTypes[ section.params.post_type ];
		if ( ! postTypeObj.supports[ component.data.themeSupport ] ) {
			return null;
		}

		settingId = 'postmeta[' + section.params.post_type + '][' + String( section.params.post_id ) + '][' + component.data.metaKey + ']';
		controlId = settingId;

		if ( component.api.control.has( controlId ) ) {
			return component.api.control( controlId );
		}

		control = new component.api.CodeEditorControl( controlId, {
			params: {
				section: section.id,
				priority: 100,
				label: component.data.l10n.control_label,
				active: true,
				content: '<li class="customize-control customize-control-code_editor"></li>',
				description: '',
				code_type: 'text/css',
				editor_settings: component.data.editorSettings,
				settings: {
					'default': settingId
				},
				type: 'code_editor'
			}
		} );

		// Register.
		component.api.control.add( control.id, control );

		return control;
	};

	return component;

})();
