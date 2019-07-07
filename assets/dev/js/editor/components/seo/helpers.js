import { helpers as YoastSEOHelpers } from 'yoastseo';
import Component from 'react';

export default class Results extends Component {
	getScoreItemsAsHTML( items ) {
		return items.map( ( item ) => this.getScoreItemAsHTML( item ) );
	}

	getScoreItemAsHTML( item ) {
		return <li className={ `yoast__item yoast__item--${ item.rating }` }>
			{ item.text.replace( /<(?:.|\n)*?>/gm, '' ) }
		</li>;
	}

	getScores( assessor ) {
		const scores = [];
		Object.entries( this.getScoresWithRatings( assessor ) ).forEach( ( [ key, item ] ) => {
			return scores.push( this.addRating( item ) );
		} );

		return scores;
	}

	addRating( item ) {
		return {
			rating: item.rating,
			text: item.text,
			identifier: item.getIdentifier(),
		};
	}

	getScoresWithRatings( assessor ) {
		const { scoreToRating } = YoastSEOHelpers;
		const scores = assessor.getValidResults().map( ( result ) => {
			if ( ! isObject( result ) || ! result.getIdentifier() ) {
				return '';
			}
			result.rating = scoreToRating( result.score );
			return result;
		} );

		return scores.filter( ( result ) => result !== '' );
	}

	render() {
		return <div className={'yoast'}>
			<h3 className={ 'yoast__heading' }>
				SEO
			</h3>
			<ul className={ 'yoast__items' }>
				{ this.getScoreItemsAsHTML( this.getScores( this.props.seo ) ) }
			</ul>
			<h3 className={ 'yoast__heading' }>
				Content
			</h3>
			<ul className={ 'yoast__items yoast__items--bottom' }>
				{ this.getScoreItemsAsHTML( this.getScores( this.props.content ) ) }
			</ul>
		</div>;
	}
}