import {
	render,
	unmountComponentAtNode,
} from 'react-dom';

const ControlMultipleBaseItemView = require( 'elementor-controls/base-multiple' );

class ControlReactComponentView extends ControlMultipleBaseItemView {
	constructor( ...args ) {
		super( ...args );
		this.cache = {};
	}

	getContainerElement() {
		return this.ui.componentPlaceholder[ 0 ];
	}

	getComponent() {
		return <div>Hello Yourself</div>;
		const Component = this.model.get( 'component' ),
			props = this.model.get( 'props' );

		return <Component
			{ ... props }
			containerElement={ this.getContainerElement() } />;
	}

	renderComponent() {
		return render( this.getComponent(),
			this.getContainerElement()
		);
	}

	ui() {
		const ui = super.ui();

		ui.componentPlaceholder = '.elementor-react-component-placeholder';

		return ui;
	}

	events() {
		return jQuery.extend( ControlMultipleBaseItemView.prototype.events.apply( this, arguments ), {
			//'click @ui.iconPickers': 'openPicker',
		} );
	}

	onReady() {
		alert( 'ready' );
	}

	onRender() {
		super.onRender();
		this.renderComponent();
	}

	onBeforeDestroy() {
		unmountComponentAtNode( this.getContainerElement() );
		this.$el.remove();
	}
}
module.exports = ControlReactComponentView;
