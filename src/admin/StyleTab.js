/**
 * Style panel: widget appearance, linked from Settings template cards.
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
	Card,
	CardBody,
	Button,
	Spinner,
	Notice,
} from '@wordpress/components';
import {
	AppearanceFields,
	TemplatePreview,
	emptyAppearance,
} from './TemplatePreview';

const SETTINGS_PATH = '/forwp-booking/v1/settings';

function setupApiFetch() {
	if (
		typeof window === 'undefined' ||
		! window.forwpBookingAdmin ||
		window.forwpBookingAdmin.__middlewareApplied
	) {
		return;
	}
	const { restRoot, nonce } = window.forwpBookingAdmin;
	const root = restRoot.endsWith( '/' ) ? restRoot : `${ restRoot }/`;
	apiFetch.use( apiFetch.createRootURLMiddleware( root ) );
	apiFetch.use( apiFetch.createNonceMiddleware( nonce ) );
	window.forwpBookingAdmin.__middlewareApplied = true;
}

export default function StyleTab( { active } ) {
	setupApiFetch();

	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ success, setSuccess ] = useState( false );
	const [ template, setTemplate ] = useState( 'advanced' );
	const [ appearance, setAppearance ] = useState( emptyAppearance() );
	const [ appearanceDefaults, setAppearanceDefaults ] = useState( {} );
	const [ appearanceSources, setAppearanceSources ] = useState( {} );

	const apply = ( data ) => {
		setTemplate( data.default_template || 'advanced' );
		setAppearance( { ...emptyAppearance(), ...( data.appearance || {} ) } );
		setAppearanceDefaults( data.appearance_defaults || {} );
		setAppearanceSources( data.appearance_sources || {} );
	};

	const load = useCallback( async () => {
		setLoading( true );
		setError( null );
		try {
			const data = await apiFetch( { path: SETTINGS_PATH } );
			apply( data );
		} catch ( e ) {
			setError(
				e?.message || __( 'Could not load settings.', '4wp-booking' )
			);
		} finally {
			setLoading( false );
		}
	}, [] );

	useEffect( () => {
		if ( active ) {
			load();
		}
	}, [ active, load ] );

	const save = async () => {
		setSaving( true );
		setError( null );
		setSuccess( false );
		try {
			const data = await apiFetch( {
				path: SETTINGS_PATH,
				method: 'POST',
				data: { appearance },
			} );
			apply( data );
			setSuccess( true );
		} catch ( e ) {
			setError(
				e?.message || __( 'Could not save settings.', '4wp-booking' )
			);
		} finally {
			setSaving( false );
		}
	};

	if ( loading ) {
		return (
			<div className="forwp-booking-admin-loading">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="forwp-booking-admin-settings">
			{ error && (
				<Notice status="error" isDismissible onRemove={ () => setError( null ) }>
					{ error }
				</Notice>
			) }
			{ success && (
				<Notice
					status="success"
					isDismissible
					onRemove={ () => setSuccess( false ) }
				>
					{ __( 'Settings saved.', '4wp-booking' ) }
				</Notice>
			) }

			<Card className="forwp-booking-settings-preview-card">
				<CardBody>
					<h3 className="forwp-booking-admin-section-title">
						{ __( 'Style', '4wp-booking' ) }
					</h3>
					<p className="forwp-booking-admin-muted">
						{ __(
							'These colors and fonts apply to both templates. Empty fields use Elementor / theme.json.',
							'4wp-booking'
						) }
					</p>
					<TemplatePreview
						template={ template }
						appearance={ appearance }
						defaults={ appearanceDefaults }
					/>
					<AppearanceFields
						appearance={ appearance }
						defaults={ appearanceDefaults }
						sources={ appearanceSources }
						onChange={ setAppearance }
					/>
				</CardBody>
			</Card>

			<div className="forwp-booking-settings-actions">
				<Button
					variant="primary"
					onClick={ save }
					disabled={ saving }
					isBusy={ saving }
				>
					{ __( 'Save style', '4wp-booking' ) }
				</Button>
			</div>
		</div>
	);
}
