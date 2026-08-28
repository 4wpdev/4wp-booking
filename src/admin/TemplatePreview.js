/**
 * Template schematic preview + simple appearance controls.
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	RangeControl,
	SelectControl,
	TextControl,
} from '@wordpress/components';

const SHADOWS = {
	none: 'none',
	soft: '0 16px 48px rgba(17, 24, 39, 0.07)',
	medium: '0 12px 32px rgba(17, 24, 39, 0.14)',
};

const EMPTY = {
	border_radius: '',
	box_shadow: '',
	border_width: '',
	border_color: '',
	bg_color: '',
	text_color: '',
	font_family: '',
	primary_color: '',
};

export function emptyAppearance() {
	return { ...EMPTY };
}

export function resolvedAppearance( appearance, defaults ) {
	const out = { ...( defaults || {} ) };
	Object.keys( EMPTY ).forEach( ( key ) => {
		const raw = appearance?.[ key ];
		if ( raw !== '' && raw != null ) {
			out[ key ] = raw;
		}
	} );
	return out;
}

export function appearanceCssVars( resolved ) {
	const font = resolved.font_family || 'inherit';
	const fontCss =
		font !== 'inherit' && ! font.includes( ',' ) && ! /\s/.test( font )
			? `"${ font }", sans-serif`
			: font;
	return {
		'--forwp-booking-accent': resolved.primary_color || '#111827',
		'--forwp-booking-bg': resolved.bg_color || '#ffffff',
		'--forwp-booking-text': resolved.text_color || '#111827',
		'--forwp-booking-border': resolved.border_color || '#e5e7eb',
		'--forwp-booking-border-width': `${ resolved.border_width || 1 }px`,
		'--forwp-booking-radius': `${ resolved.border_radius || 20 }px`,
		'--forwp-booking-shadow':
			SHADOWS[ resolved.box_shadow ] || SHADOWS.soft,
		'--forwp-booking-font': fontCss,
	};
}

function hexOrFallback( value, fallback ) {
	return /^#[0-9a-fA-F]{6}$/.test( value || '' ) ? value : fallback;
}

function ColorRow( { label, value, fallback, onChange } ) {
	const shown = hexOrFallback( value || fallback, '#111827' );
	return (
		<div className="forwp-booking-color-row">
			<span className="forwp-booking-color-row__label">{ label }</span>
			<input
				type="color"
				aria-label={ label }
				value={ shown }
				onChange={ ( event ) => onChange( event.target.value ) }
			/>
			<TextControl
				hideLabelFromVision
				label={ label }
				value={ value }
				placeholder={ fallback }
				onChange={ onChange }
			/>
		</div>
	);
}

function AdvancedPreview() {
	return (
		<div className="forwp-booking-preview__board">
			<div className="forwp-booking-preview__col">
				<span className="forwp-booking-preview__kicker">Doctor</span>
				<span className="forwp-booking-preview__chip is-active">A</span>
				<span className="forwp-booking-preview__chip">B</span>
			</div>
			<div className="forwp-booking-preview__col forwp-booking-preview__col--cal">
				<div className="forwp-booking-preview__grid">
					{ Array.from( { length: 21 } ).map( ( _, i ) => (
						<span
							key={ i }
							className={
								i === 12
									? 'forwp-booking-preview__day is-active'
									: 'forwp-booking-preview__day'
							}
						/>
					) ) }
				</div>
			</div>
			<div className="forwp-booking-preview__col">
				<span className="forwp-booking-preview__kicker">Times</span>
				<span className="forwp-booking-preview__slot">10:00</span>
				<span className="forwp-booking-preview__slot is-active">
					10:30
				</span>
				<span className="forwp-booking-preview__slot">11:00</span>
			</div>
		</div>
	);
}

function CalendarPreview() {
	return (
		<div className="forwp-booking-preview__compact">
			<div className="forwp-booking-preview__tabs">
				<span className="forwp-booking-preview__tab">By service</span>
				<span className="forwp-booking-preview__tab">By doctor</span>
				<span className="forwp-booking-preview__tab is-active">
					By date
				</span>
			</div>
			<div className="forwp-booking-preview__compact-body">
				<div className="forwp-booking-preview__grid">
					{ Array.from( { length: 14 } ).map( ( _, i ) => (
						<span
							key={ i }
							className={
								i === 8
									? 'forwp-booking-preview__day is-active'
									: 'forwp-booking-preview__day'
							}
						/>
					) ) }
				</div>
				<div className="forwp-booking-preview__col">
					<span className="forwp-booking-preview__slot is-active">
						10:00
					</span>
					<span className="forwp-booking-preview__slot">10:30</span>
				</div>
			</div>
		</div>
	);
}

export function TemplatePreview( { template, appearance, defaults } ) {
	const resolved = resolvedAppearance( appearance, defaults );
	const style = appearanceCssVars( resolved );
	const isAdvanced = template === 'advanced';

	return (
		<div className="forwp-booking-preview" style={ style }>
			<div className="forwp-booking-preview__shell">
				{ isAdvanced ? <AdvancedPreview /> : <CalendarPreview /> }
			</div>
		</div>
	);
}

export function AppearanceFields( {
	appearance,
	defaults,
	sources,
	onChange,
} ) {
	const resolved = resolvedAppearance( appearance, defaults );
	const setField = ( key, value ) =>
		onChange( { ...appearance, [ key ]: value } );

	const from = [];
	if ( sources?.elementor ) {
		from.push( 'Elementor' );
	}
	if ( sources?.theme_json ) {
		from.push( 'theme.json' );
	}
	const sourceHint =
		from.length > 0
			? sprintfSources( from )
			: __(
					'Empty fields keep the plugin defaults.',
					'4wp-booking'
			  );

	return (
		<div className="forwp-booking-appearance">
			<p className="forwp-booking-admin-muted">{ sourceHint }</p>
			<div className="forwp-booking-appearance-grid">
				<ColorRow
					label={ __( 'Primary color', '4wp-booking' ) }
					value={ appearance.primary_color }
					fallback={ defaults.primary_color }
					onChange={ ( v ) => setField( 'primary_color', v ) }
				/>
				<ColorRow
					label={ __( 'Background', '4wp-booking' ) }
					value={ appearance.bg_color }
					fallback={ defaults.bg_color }
					onChange={ ( v ) => setField( 'bg_color', v ) }
				/>
				<ColorRow
					label={ __( 'Text color', '4wp-booking' ) }
					value={ appearance.text_color }
					fallback={ defaults.text_color }
					onChange={ ( v ) => setField( 'text_color', v ) }
				/>
				<ColorRow
					label={ __( 'Border color', '4wp-booking' ) }
					value={ appearance.border_color }
					fallback={ defaults.border_color }
					onChange={ ( v ) => setField( 'border_color', v ) }
				/>
				<RangeControl
					label={ __( 'Border radius', '4wp-booking' ) }
					value={ Number( resolved.border_radius || 0 ) }
					min={ 0 }
					max={ 48 }
					onChange={ ( v ) =>
						setField( 'border_radius', String( v ?? 0 ) )
					}
				/>
				<RangeControl
					label={ __( 'Border size', '4wp-booking' ) }
					value={ Number( resolved.border_width || 0 ) }
					min={ 0 }
					max={ 8 }
					onChange={ ( v ) =>
						setField( 'border_width', String( v ?? 0 ) )
					}
				/>
				<SelectControl
					label={ __( 'Box shadow', '4wp-booking' ) }
					value={ appearance.box_shadow || resolved.box_shadow || 'soft' }
					options={ [
						{ label: __( 'None', '4wp-booking' ), value: 'none' },
						{ label: __( 'Soft', '4wp-booking' ), value: 'soft' },
						{
							label: __( 'Medium', '4wp-booking' ),
							value: 'medium',
						},
					] }
					onChange={ ( v ) => setField( 'box_shadow', v ) }
				/>
				<TextControl
					label={ __( 'Font family', '4wp-booking' ) }
					value={ appearance.font_family }
					placeholder={ defaults.font_family || 'inherit' }
					onChange={ ( v ) => setField( 'font_family', v ) }
				/>
			</div>
			<Button
				variant="secondary"
				onClick={ () => onChange( emptyAppearance() ) }
			>
				{ __( 'Reset to site defaults', '4wp-booking' ) }
			</Button>
		</div>
	);
}

function sprintfSources( from ) {
	return (
		__(
			'Empty fields use the project: ',
			'4wp-booking'
		) + from.join( ' + ' ) + '.'
	);
}
