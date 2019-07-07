//import { Researcher, Paper, SnippetPreview, App } from 'yoastseo';
import { Paper, SEOAssessor, ContentAssessor } from 'yoastseo';
import Jed from 'jed';
import Render from 'react';
import Results from "./helpers";

class SEOManager extends elementorModules.ViewModule {
	constructor() {
		super();
		this.contentAssessor = new ContentAssessor( this.i18n() );
		this.seoAssessor = new SEOAssessor( this.i18n() );

		this.paper = new Paper();

		this.presenter = new Presenter();

		this.assessContent( this.paper );
		this.assessSEO( this.paper );
	}

	i18n() {
		return new Jed( {
			domain: `js-text-analysis`,
			locale_data: {
				'js-text-analysis': { '': {} },
			},
		} );
	}

	setContent( content ) {
		content.text = 'this is the content';//snarkdown(content.text)

		const data = Object.assign( {}, {
			text: this.paper.getText(),
			keyword: this.paper.getKeyword(),
			description: this.paper.getDescription(),
			title: this.paper.getTitle(),
			titleWidth: this.paper.getTitleWidth(),
			url: this.paper.getUrl(),
			locale: this.paper.getLocale(),
			permalink: this.paper.getPermalink(),
		}, content );

		this.paper = new Paper( data.text, omit( data, 'text' ) );

		this.assessContent( this.paper );
		this.assessSEO( this.paper );
	}

	assessContent( paper ) {
		this.contentAssessor.assess( paper );
	}

	assessSEO( paper ) {
		this.seoAssessor.assess( paper );
	}

	getScores() {
		const PlaceholderComponent = '';
		Render( <Results
			seo={ this.seoAssessor }
			content={ this.contentAssessor } />,
			PlaceholderComponent
		);
	}

	getScoresAsHTML( score ) {
		return this.presenter.getScoresAsHTML( score, this.getScores() );
	}

	getDefaultElements() {
		const elements = {};
		const selectors = this.getSettings( 'selectors' );

		jQuery.each( selectors, ( element, selector ) => {
			elements[ '$' + element ] = jQuery( selector );
		} );

		return elements;
	}

	getDefaultSettings() {
		return {
			classes: {},
			selectors: {},
		};
	}

	bindEvents() {}

	onRender() {

	}
}
