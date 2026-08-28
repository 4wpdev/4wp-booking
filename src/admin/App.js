/**
 * Tabbed admin UI: Providers + Settings + Documentation (4WP Weather/Drive shell).
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
	Card,
	CardBody,
	CardHeader,
	Button,
	TextControl,
	TextareaControl,
	RadioControl,
	Spinner,
	Notice,
	ExternalLink,
} from '@wordpress/components';
import {
	TemplatePreview,
	emptyAppearance,
} from './TemplatePreview';
import StyleTab from './StyleTab';

const SETTINGS_PATH = '/forwp-booking/v1/settings';
const PREVIEW_PATH = '/forwp-booking/v1/preview';
const LIVE_SLUG = 'cliniccards';
const API_KEY_MASK_MAX_LEN = 512;

function maskForSavedApiKey( length ) {
	const n =
		typeof length === 'number' && length > 0
			? Math.min( length, API_KEY_MASK_MAX_LEN )
			: 0;
	return n > 0 ? '*'.repeat( n ) : '';
}

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

function ProvidersPlaceholderIntro() {
	return (
		<Card className="forwp-booking-intro-card">
			<CardBody>
				<h3 className="forwp-booking-intro-card__title">
					{ __( 'About this plugin', '4wp-booking' ) }
				</h3>
				<p className="forwp-booking-intro-card__text">
					{ __(
						'4WP Booking adds a native appointment calendar to WordPress. Visitors pick a service, date, and time. API secrets stay on the server.',
						'4wp-booking'
					) }
				</p>
				<p className="forwp-booking-intro-card__text">
					{ __(
						'ClinicCards is live. Google Calendar and Calendly are registered as roadmap stubs until those integrations ship.',
						'4wp-booking'
					) }
				</p>
				<p className="forwp-booking-intro-card__cta">
					{ __(
						'Pick a provider card below to configure credentials and load a live admin preview.',
						'4wp-booking'
					) }
				</p>
			</CardBody>
		</Card>
	);
}

function ProviderPreviewPanel( { row, refreshTick } ) {
	const [ panelLoading, setPanelLoading ] = useState( false );
	const [ payload, setPayload ] = useState( null );

	useEffect( () => {
		if ( ! row?.implemented ) {
			setPayload( null );
			return undefined;
		}

		let cancelled = false;
		setPanelLoading( true );

		apiFetch( {
			path: `${ PREVIEW_PATH }?provider=${ encodeURIComponent(
				row.slug
			) }&refresh=1`,
		} )
			.then( ( data ) => {
				if ( ! cancelled ) {
					setPayload( data );
				}
			} )
			.catch( ( e ) => {
				if ( ! cancelled ) {
					setPayload( {
						ready: false,
						offerings_count: 0,
						error:
							e?.message ||
							__( 'Preview request failed.', '4wp-booking' ),
					} );
				}
			} )
			.finally( () => {
				if ( ! cancelled ) {
					setPanelLoading( false );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ row?.implemented, row?.slug, refreshTick ] );

	if ( ! row ) {
		return null;
	}

	if ( ! row.implemented ) {
		return (
			<div className="forwp-booking-preview">
				<p className="forwp-booking-preview__caption">
					{ __( 'Frontend preview (schematic)', '4wp-booking' ) }
				</p>
				<div className="forwp-booking-preview__frame">
					<div className="forwp-booking-preview__chrome">
						<span className="forwp-booking-preview__dot" />
						<span className="forwp-booking-preview__dot" />
						<span className="forwp-booking-preview__dot" />
						<span className="forwp-booking-preview__chrome-label">
							{ row.label }
						</span>
					</div>
					<div className="forwp-booking-preview__card">
						<table className="forwp-booking-preview__table">
							<tbody>
								<tr>
									<th>{ __( 'Services', '4wp-booking' ) }</th>
									<td>—</td>
								</tr>
								<tr>
									<th>{ __( 'Calendar', '4wp-booking' ) }</th>
									<td>—</td>
								</tr>
								<tr>
									<th>{ __( 'Status', '4wp-booking' ) }</th>
									<td>{ __( 'Planned', '4wp-booking' ) }</td>
								</tr>
							</tbody>
						</table>
						<div
							className="forwp-booking-preview__filler"
							aria-hidden="true"
						/>
					</div>
					<p className="forwp-booking-preview__stub-note">
						{ __(
							'When this provider is implemented, live values will replace the placeholders.',
							'4wp-booking'
						) }
					</p>
				</div>
			</div>
		);
	}

	const prevError = payload?.error;
	const count = payload?.offerings_count;
	const names = Array.isArray( payload?.offerings )
		? payload.offerings
				.map( ( item ) => item.label )
				.filter( Boolean )
				.slice( 0, 3 )
				.join( ', ' )
		: '';

	return (
		<div className="forwp-booking-preview">
			<p className="forwp-booking-preview__caption">
				{ __( 'Frontend preview (live)', '4wp-booking' ) }
			</p>
			<div className="forwp-booking-preview__frame">
				<div className="forwp-booking-preview__chrome">
					<span className="forwp-booking-preview__dot" />
					<span className="forwp-booking-preview__dot" />
					<span className="forwp-booking-preview__dot" />
					<span className="forwp-booking-preview__chrome-label">
						{ row.label }
					</span>
				</div>
				<div className="forwp-booking-preview__card">
					{ panelLoading && (
						<div
							className="forwp-booking-preview__loading"
							aria-busy="true"
						>
							<Spinner />
						</div>
					) }
					{ prevError && ! panelLoading && (
						<div className="forwp-booking-preview__error">
							<Notice status="warning" isDismissible={ false }>
								{ prevError }
							</Notice>
						</div>
					) }
					<table className="forwp-booking-preview__table">
						<tbody>
							<tr>
								<th>{ __( 'Connection', '4wp-booking' ) }</th>
								<td>
									{ payload?.ready
										? __( 'Ready', '4wp-booking' )
										: __( 'Needs API key', '4wp-booking' ) }
								</td>
							</tr>
							<tr>
								<th>{ __( 'Services', '4wp-booking' ) }</th>
								<td>
									{ typeof count === 'number' ? String( count ) : '—' }
								</td>
							</tr>
							<tr>
								<th>{ __( 'Sample', '4wp-booking' ) }</th>
								<td>{ names || '—' }</td>
							</tr>
						</tbody>
					</table>
					{ ! panelLoading && ! prevError && (
						<div
							className="forwp-booking-preview__filler"
							aria-hidden="true"
						/>
					) }
				</div>
			</div>
		</div>
	);
}

function ProvidersTab() {
	setupApiFetch();

	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ success, setSuccess ] = useState( false );
	const [ rows, setRows ] = useState( [] );
	const [ selectedSlug, setSelectedSlug ] = useState( null );
	const [ credentialProvider, setCredentialProvider ] = useState( LIVE_SLUG );
	const [ apiKeyConfigured, setApiKeyConfigured ] = useState( false );
	const [ apiKeyLength, setApiKeyLength ] = useState( 0 );
	const [ apiKeyInput, setApiKeyInput ] = useState( '' );
	const [ apiKeyTouched, setApiKeyTouched ] = useState( false );
	const [ previewRefreshTick, setPreviewRefreshTick ] = useState( 0 );

	const load = useCallback( async ( opts = {} ) => {
		const refreshPreview = !! opts.refreshPreview;
		setLoading( true );
		setError( null );
		try {
			const data = await apiFetch( { path: SETTINGS_PATH } );
			setRows( data.providers || [] );
			setCredentialProvider( data.credential_provider || LIVE_SLUG );
			setApiKeyConfigured( !! data.api_key_configured );
			setApiKeyLength(
				typeof data.api_key_length === 'number' && data.api_key_length >= 0
					? data.api_key_length
					: 0
			);
			setApiKeyInput( '' );
			setApiKeyTouched( false );
			if ( refreshPreview ) {
				setPreviewRefreshTick( ( t ) => t + 1 );
			}
		} catch ( e ) {
			setError(
				e?.message || __( 'Could not load settings.', '4wp-booking' )
			);
		} finally {
			setLoading( false );
		}
	}, [] );

	useEffect( () => {
		load();
	}, [ load ] );

	const selectedRow =
		selectedSlug && rows.length
			? rows.find( ( r ) => r.slug === selectedSlug )
			: null;

	const selectProvider = ( slug ) => {
		setSelectedSlug( slug );
		setSuccess( false );
		const row = rows.find( ( r ) => r.slug === slug );
		if ( row?.implemented ) {
			setCredentialProvider( slug );
		}
		setApiKeyInput( '' );
		setApiKeyTouched( false );
	};

	const save = async () => {
		if ( ! selectedRow?.implemented ) {
			return;
		}

		setSaving( true );
		setError( null );
		setSuccess( false );
		try {
			const payload = {
				credential_provider: credentialProvider,
			};
			const nextKey = apiKeyInput.trim();
			if ( nextKey ) {
				payload.api_key = nextKey;
			}
			const data = await apiFetch( {
				path: SETTINGS_PATH,
				method: 'POST',
				data: payload,
			} );
			setRows( data.providers || [] );
			setApiKeyConfigured( !! data.api_key_configured );
			setApiKeyLength(
				typeof data.api_key_length === 'number' && data.api_key_length >= 0
					? data.api_key_length
					: 0
			);
			setApiKeyInput( '' );
			setApiKeyTouched( false );
			setPreviewRefreshTick( ( t ) => t + 1 );
			setSuccess( true );
		} catch ( e ) {
			setError(
				e?.message || __( 'Could not save settings.', '4wp-booking' )
			);
		} finally {
			setSaving( false );
		}
	};

	const onCardKeyDown = ( event, slug ) => {
		if ( event.key === 'Enter' || event.key === ' ' ) {
			event.preventDefault();
			selectProvider( slug );
		}
	};

	if ( loading ) {
		return (
			<div className="forwp-booking-admin-loading">
				<Spinner />
			</div>
		);
	}

	const showSavedKeyMask =
		apiKeyConfigured && ! apiKeyTouched && apiKeyLength > 0;

	return (
		<div className="forwp-booking-admin-providers">
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

			{ selectedSlug === null ? (
				<div className="forwp-booking-admin-detail-region">
					<ProvidersPlaceholderIntro />
				</div>
			) : (
				<div className="forwp-booking-admin-detail-region">
					<div className="forwp-booking-provider-detail-head">
						<h3 className="forwp-booking-admin-section-title forwp-booking-provider-detail-head__title">
							{ selectedRow?.label || __( 'Provider', '4wp-booking' ) }
						</h3>
						<Button
							variant="tertiary"
							onClick={ () => setSelectedSlug( null ) }
						>
							{ __( '← Back to overview', '4wp-booking' ) }
						</Button>
					</div>

					<div className="forwp-booking-split">
						<div className="forwp-booking-split__col forwp-booking-split__settings">
							{ selectedRow?.implemented ? (
								<>
									<p className="forwp-booking-admin-muted forwp-booking-split__lead">
										{ __(
											'API secret is stored in WordPress only; the calendar talks to REST without exposing keys.',
											'4wp-booking'
										) }
									</p>
									<div className="forwp-booking-admin-panel forwp-booking-admin-panel--embedded">
										<TextControl
											label={ __( 'API key', '4wp-booking' ) }
											type="password"
											autoComplete="new-password"
											readOnly={ showSavedKeyMask }
											value={
												showSavedKeyMask
													? maskForSavedApiKey( apiKeyLength )
													: apiKeyInput
											}
											onFocus={ () => {
												if ( showSavedKeyMask ) {
													setApiKeyTouched( true );
													setApiKeyInput( '' );
												}
											} }
											onChange={ ( v ) => {
												setApiKeyInput( v );
												setApiKeyTouched( true );
											} }
											help={
												apiKeyConfigured && ! apiKeyTouched
													? __(
															'A key is saved. Click the field, paste the new key, then Save. Saving empty keeps the current key.',
															'4wp-booking'
													  )
													: __(
															'Stored server-side only; never exposed to the frontend.',
															'4wp-booking'
													  )
											}
										/>
										{ selectedRow?.api_key_docs_url &&
											selectedRow?.api_key_docs_link_label && (
												<p className="forwp-booking-api-key-help">
													{ selectedRow.api_key_help_intro ? (
														<>
															{ selectedRow.api_key_help_intro }{ ' ' }
														</>
													) : null }
													<ExternalLink
														href={ selectedRow.api_key_docs_url }
													>
														{ selectedRow.api_key_docs_link_label }
													</ExternalLink>
												</p>
											) }
										<div
											className="forwp-booking-toolbar"
											role="group"
											aria-label={ __(
												'Credential actions',
												'4wp-booking'
											) }
										>
											<div className="forwp-booking-toolbar__primary">
												<Button
													variant="primary"
													className="forwp-booking-btn-dashicon"
													onClick={ save }
													disabled={ saving }
													isBusy={ saving }
												>
													<span
														className="dashicons dashicons-saved"
														aria-hidden="true"
													/>
													<span>{ __( 'Save', '4wp-booking' ) }</span>
												</Button>
												<Button
													variant="secondary"
													className="forwp-booking-btn-dashicon"
													onClick={ () =>
														load( { refreshPreview: true } )
													}
													disabled={ saving }
												>
													<span
														className="dashicons dashicons-update"
														aria-hidden="true"
													/>
													<span>{ __( 'Reload', '4wp-booking' ) }</span>
												</Button>
											</div>
										</div>
									</div>
								</>
							) : (
								<Notice status="info" isDismissible={ false }>
									{ __(
										'This provider is listed for architecture and roadmap planning only. No credentials or live requests are available yet.',
										'4wp-booking'
									) }
								</Notice>
							) }
						</div>
						<div className="forwp-booking-split__col forwp-booking-split__preview">
							<ProviderPreviewPanel
								row={ selectedRow }
								refreshTick={ previewRefreshTick }
							/>
						</div>
					</div>
				</div>
			) }

			<h3 className="forwp-booking-admin-section-title forwp-booking-registry-title">
				{ __( 'Provider registry', '4wp-booking' ) }
			</h3>
			<p className="forwp-booking-admin-muted forwp-booking-registry-hint">
				{ __( 'Click a card to configure or preview.', '4wp-booking' ) }
			</p>

			<div className="forwp-booking-provider-grid">
				{ rows.map( ( row ) => (
					<div
						key={ row.slug }
						className={
							'forwp-booking-provider-card-wrap' +
							( selectedSlug === row.slug ? ' is-selected' : '' )
						}
						role="button"
						tabIndex={ 0 }
						aria-pressed={ selectedSlug === row.slug }
						aria-label={ sprintf(
							/* translators: %s: provider name */
							__( 'Open details for %s', '4wp-booking' ),
							row.label
						) }
						onClick={ () => selectProvider( row.slug ) }
						onKeyDown={ ( e ) => onCardKeyDown( e, row.slug ) }
					>
						<Card className="forwp-booking-provider-card">
							<CardHeader>
								<div className="forwp-booking-provider-card-head">
									<div>
										<div className="forwp-booking-provider-label">
											{ row.label }
										</div>
										<div className="forwp-booking-provider-slug">
											<code>{ row.slug }</code>
										</div>
									</div>
									<span
										className={
											row.implemented
												? 'forwp-booking-badge forwp-booking-badge--live'
												: 'forwp-booking-badge forwp-booking-badge--planned'
										}
									>
										{ row.implemented
											? __( 'Live', '4wp-booking' )
											: __( 'Planned', '4wp-booking' ) }
									</span>
								</div>
							</CardHeader>
							<CardBody className="forwp-booking-provider-card-footer">
								<p className="forwp-booking-provider-status">
									{ row.status }
								</p>
							</CardBody>
						</Card>
					</div>
				) ) }
			</div>
		</div>
	);
}

function SettingsTab( { onOpenStyle } ) {
	setupApiFetch();

	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ success, setSuccess ] = useState( false );
	const [ templates, setTemplates ] = useState( [] );
	const [ selectedTemplate, setSelectedTemplate ] = useState( 'advanced' );
	const [ bookingFlow, setBookingFlow ] = useState( 'staff' );
	const [ copy, setCopy ] = useState( {} );
	const [ copyDefaults, setCopyDefaults ] = useState( {} );
	const [ notifyEmails, setNotifyEmails ] = useState( '' );
	const [ adminEmail, setAdminEmail ] = useState( '' );
	const [ channels, setChannels ] = useState( [] );
	const [ telegramChatIds, setTelegramChatIds ] = useState( '' );
	const [ telegramTokenInput, setTelegramTokenInput ] = useState( '' );
	const [ telegramTokenConfigured, setTelegramTokenConfigured ] =
		useState( false );
	const [ telegramTokenLength, setTelegramTokenLength ] = useState( 0 );
	const [ telegramTokenTouched, setTelegramTokenTouched ] = useState( false );
	const [ appearance, setAppearance ] = useState( emptyAppearance() );
	const [ appearanceDefaults, setAppearanceDefaults ] = useState( {} );

	const applySettings = ( data ) => {
		setTemplates( data.templates || [] );
		setSelectedTemplate( data.default_template || 'advanced' );
		setBookingFlow( data.booking_flow || 'staff' );
		setCopy( data.copy || {} );
		setCopyDefaults( data.copy_defaults || {} );
		setNotifyEmails( data.notify_emails || '' );
		setAdminEmail( data.admin_email || '' );
		setChannels( data.channels || [] );
		setTelegramChatIds( data.telegram_chat_ids || '' );
		setTelegramTokenConfigured( !! data.telegram_token_configured );
		setTelegramTokenLength(
			typeof data.telegram_token_length === 'number' &&
				data.telegram_token_length >= 0
				? data.telegram_token_length
				: 0
		);
		setTelegramTokenInput( '' );
		setTelegramTokenTouched( false );
		setAppearance( { ...emptyAppearance(), ...( data.appearance || {} ) } );
		setAppearanceDefaults( data.appearance_defaults || {} );
	};

	const load = useCallback( async () => {
		setLoading( true );
		setError( null );
		try {
			const data = await apiFetch( { path: SETTINGS_PATH } );
			applySettings( data );
		} catch ( e ) {
			setError(
				e?.message || __( 'Could not load settings.', '4wp-booking' )
			);
		} finally {
			setLoading( false );
		}
	}, [] );

	useEffect( () => {
		load();
	}, [ load ] );

	const save = async () => {
		setSaving( true );
		setError( null );
		setSuccess( false );
		try {
			const data = await apiFetch( {
				path: SETTINGS_PATH,
				method: 'POST',
				data: {
					default_template: selectedTemplate,
					booking_flow: bookingFlow,
					copy,
					notify_emails: notifyEmails,
					telegram_chat_ids: telegramChatIds,
					...( telegramTokenTouched
						? { telegram_token: telegramTokenInput }
						: {} ),
				},
			} );
			applySettings( data );
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

	const selectedRow =
		templates.find( ( row ) => row.slug === selectedTemplate ) || null;

	const copyGroups = [
		{
			label: __( 'List', '4wp-booking' ),
			fields: [
				{
					key: 'offerings_title',
					label: __( 'List heading', '4wp-booking' ),
				},
				{
					key: 'offerings_description',
					label: __( 'Text under the doctor list', '4wp-booking' ),
					textarea: true,
					wide: true,
				},
			],
		},
		{
			label: __( 'Tabs & calendar', '4wp-booking' ),
			fields: [
				{ key: 'tab_service', label: __( 'Tab: by service', '4wp-booking' ) },
				{ key: 'tab_staff', label: __( 'Tab: by doctor', '4wp-booking' ) },
				{ key: 'tab_date', label: __( 'Tab: by date', '4wp-booking' ) },
				{ key: 'select_service', label: __( 'Select a service', '4wp-booking' ) },
				{ key: 'select_date', label: __( 'Select a date', '4wp-booking' ) },
				{ key: 'select_time', label: __( 'Select a time', '4wp-booking' ) },
			],
		},
		{
			label: __( 'Guest form', '4wp-booking' ),
			fields: [
				{ key: 'your_details', label: __( 'Your details', '4wp-booking' ), wide: true },
				{ key: 'first_name', label: __( 'First name', '4wp-booking' ) },
				{ key: 'last_name', label: __( 'Last name', '4wp-booking' ) },
				{ key: 'phone', label: __( 'Phone', '4wp-booking' ) },
				{ key: 'email', label: __( 'Email', '4wp-booking' ) },
				{ key: 'comment', label: __( 'Comment', '4wp-booking' ) },
				{ key: 'submit', label: __( 'Confirm booking', '4wp-booking' ), wide: true },
			],
		},
		{
			label: __( 'Messages', '4wp-booking' ),
			fields: [
				{ key: 'error_required', label: __( 'Error: required field', '4wp-booking' ) },
				{ key: 'error_name', label: __( 'Error: name', '4wp-booking' ) },
				{ key: 'error_phone', label: __( 'Error: phone', '4wp-booking' ) },
				{ key: 'error_email', label: __( 'Error: email', '4wp-booking' ) },
				{ key: 'success', label: __( 'Success message', '4wp-booking' ) },
				{ key: 'done_title', label: __( 'Done heading', '4wp-booking' ) },
				{ key: 'back', label: __( 'Back', '4wp-booking' ) },
			],
		},
		{
			label: __( 'Empty states', '4wp-booking' ),
			fields: [
				{
					key: 'no_offerings',
					label: __( 'Empty doctor list', '4wp-booking' ),
				},
				{
					key: 'no_services',
					label: __( 'Empty service list', '4wp-booking' ),
				},
				{ key: 'no_slots', label: __( 'No times on this day', '4wp-booking' ) },
				{
					key: 'no_month_slots',
					label: __( 'No free times this month', '4wp-booking' ),
					wide: true,
				},
			],
		},
	];

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

			<Card className="forwp-booking-settings-intro forwp-booking-notify">
				<CardBody>
					<h3 className="forwp-booking-admin-section-title">
						{ __( 'Notifications', '4wp-booking' ) }
					</h3>
					<p className="forwp-booking-admin-muted">
						{ __(
							'Each booking (with date and time) is copied here.',
							'4wp-booking'
						) }
					</p>

					<div className="forwp-booking-notify__block">
						<h4 className="forwp-booking-notify__label">
							{ __( 'Email', '4wp-booking' ) }
						</h4>
						<TextControl
							label={ __( 'Recipients', '4wp-booking' ) }
							hideLabelFromVision
							value={ notifyEmails }
							placeholder={ adminEmail }
							onChange={ setNotifyEmails }
							help={ __(
								'Leave empty to use the WordPress admin email. Separate addresses with commas.',
								'4wp-booking'
							) }
						/>
					</div>

					<div className="forwp-booking-notify__block">
						<h4 className="forwp-booking-notify__label">
							{ __( 'Messengers', '4wp-booking' ) }
						</h4>
						<ul className="forwp-booking-notify__channels">
							{ channels.map( ( row ) => (
								<li
									key={ row.slug }
									className={
										'forwp-booking-notify__channel' +
										( row.implemented
											? ' is-live'
											: ' is-planned' )
									}
								>
									<div className="forwp-booking-notify__channel-head">
										<span className="forwp-booking-notify__channel-name">
											{ row.label }
										</span>
										<span
											className={
												row.implemented
													? 'forwp-booking-badge forwp-booking-badge--live'
													: 'forwp-booking-badge forwp-booking-badge--planned'
											}
										>
											{ row.implemented
												? __( 'Live', '4wp-booking' )
												: __( 'Planned', '4wp-booking' ) }
										</span>
										<span className="forwp-booking-notify__channel-status">
											{ row.status }
										</span>
									</div>
									{ row.slug === 'telegram' && (
										<div className="forwp-booking-notify__channel-fields">
											<TextControl
												label={ __(
													'Bot token',
													'4wp-booking'
												) }
												type="password"
												autoComplete="off"
												readOnly={
													telegramTokenConfigured &&
													! telegramTokenTouched &&
													telegramTokenLength > 0
												}
												value={
													telegramTokenConfigured &&
													! telegramTokenTouched &&
													telegramTokenLength > 0
														? maskForSavedApiKey(
																telegramTokenLength
														  )
														: telegramTokenInput
												}
												onFocus={ () => {
													if (
														telegramTokenConfigured &&
														! telegramTokenTouched
													) {
														setTelegramTokenTouched(
															true
														);
														setTelegramTokenInput(
															''
														);
													}
												} }
												onBlur={ () => {
													if (
														telegramTokenConfigured &&
														telegramTokenTouched &&
														telegramTokenInput ===
															''
													) {
														setTelegramTokenTouched(
															false
														);
													}
												} }
												onChange={ ( v ) => {
													setTelegramTokenInput( v );
													setTelegramTokenTouched(
														true
													);
												} }
												help={ __(
													'From @BotFather. Stored on the server only.',
													'4wp-booking'
												) }
											/>
											<TextControl
												label={ __(
													'Chat IDs',
													'4wp-booking'
												) }
												value={ telegramChatIds }
												placeholder="-100…"
												onChange={ setTelegramChatIds }
												help={ __(
													'One or more, separated by commas.',
													'4wp-booking'
												) }
											/>
										</div>
									) }
								</li>
							) ) }
						</ul>
					</div>
				</CardBody>
			</Card>

			<Card className="forwp-booking-settings-intro">
				<CardBody>
					<h3 className="forwp-booking-admin-section-title">
						{ __( 'Default tab', '4wp-booking' ) }
					</h3>
					<p className="forwp-booking-admin-muted">
						{ __(
							'Guests always see all three tabs. This only chooses which one opens first.',
							'4wp-booking'
						) }
					</p>
					<RadioControl
						selected={ bookingFlow }
						options={ [
							{
								label: __( 'By service', '4wp-booking' ),
								value: 'service',
							},
							{
								label: __( 'By doctor', '4wp-booking' ),
								value: 'staff',
							},
							{
								label: __( 'By date', '4wp-booking' ),
								value: 'date',
							},
						] }
						onChange={ ( value ) => setBookingFlow( value || 'staff' ) }
					/>
				</CardBody>
			</Card>

			<Card className="forwp-booking-settings-copy">
				<CardBody>
					<h3 className="forwp-booking-admin-section-title">
						{ __( 'Form text', '4wp-booking' ) }
					</h3>
					<p className="forwp-booking-admin-muted">
						{ __(
							'Blank field = plugin default (site language). Text here overrides it.',
							'4wp-booking'
						) }
					</p>
					{ copyGroups.map( ( group ) => (
						<div key={ group.label } className="forwp-booking-copy-group">
							<h4 className="forwp-booking-copy-group__label">
								{ group.label }
							</h4>
							<div className="forwp-booking-copy-grid">
								{ group.fields.map( ( field ) => {
									const value = copy[ field.key ] || '';
									const placeholder =
										copyDefaults[ field.key ] || '';
									const onChange = ( next ) =>
										setCopy( ( current ) => ( {
											...current,
											[ field.key ]: next,
										} ) );
									const wrapClass = field.wide
										? 'forwp-booking-copy-grid__wide'
										: undefined;
									if ( field.textarea ) {
										return (
											<div
												key={ field.key }
												className={ wrapClass }
											>
												<TextareaControl
													label={ field.label }
													value={ value }
													placeholder={ placeholder }
													onChange={ onChange }
													rows={ 3 }
												/>
											</div>
										);
									}
									return (
										<div
											key={ field.key }
											className={ wrapClass }
										>
											<TextControl
												label={ field.label }
												value={ value }
												placeholder={ placeholder }
												onChange={ onChange }
											/>
										</div>
									);
								} ) }
							</div>
						</div>
					) ) }
				</CardBody>
			</Card>

			<h3 className="forwp-booking-admin-section-title forwp-booking-settings-layouts-title">
				{ __( 'Template', '4wp-booking' ) }
			</h3>
			<div className="forwp-booking-template-grid">
				{ templates.map( ( row ) => (
					<div
						key={ row.slug }
						className={
							'forwp-booking-template-card-wrap' +
							( selectedTemplate === row.slug ? ' is-selected' : '' )
						}
						role="button"
						tabIndex={ 0 }
						aria-pressed={ selectedTemplate === row.slug }
						onClick={ () => setSelectedTemplate( row.slug ) }
						onKeyDown={ ( event ) => {
							if ( event.key === 'Enter' || event.key === ' ' ) {
								event.preventDefault();
								setSelectedTemplate( row.slug );
							}
						} }
					>
						<Card className="forwp-booking-template-card">
							<CardHeader>
								<div className="forwp-booking-template-card-head">
									<span className="forwp-booking-template-label">
										{ row.label }
									</span>
									{ row.is_default && (
										<span className="forwp-booking-badge forwp-booking-badge--live">
											{ __( 'Default', '4wp-booking' ) }
										</span>
									) }
								</div>
							</CardHeader>
							<CardBody>
								<p className="forwp-booking-template-desc">
									{ row.description }
								</p>
								<Button
									variant="link"
									className="forwp-booking-template-style-link"
									onClick={ ( event ) => {
										event.preventDefault();
										event.stopPropagation();
										onOpenStyle();
									} }
								>
									{ __( 'Style settings', '4wp-booking' ) }
								</Button>
							</CardBody>
						</Card>
					</div>
				) ) }
				<div
					className="forwp-booking-template-card-wrap forwp-booking-style-card-wrap"
					role="link"
					tabIndex={ 0 }
					onClick={ onOpenStyle }
					onKeyDown={ ( event ) => {
						if ( event.key === 'Enter' || event.key === ' ' ) {
							event.preventDefault();
							onOpenStyle();
						}
					} }
				>
					<Card className="forwp-booking-template-card">
						<CardHeader>
							<div className="forwp-booking-template-card-head">
								<span className="forwp-booking-template-label">
									{ __( 'Style', '4wp-booking' ) }
								</span>
							</div>
						</CardHeader>
						<CardBody>
							<p className="forwp-booking-template-desc">
								{ __(
									'Primary color, background, text, border, radius, shadow, font.',
									'4wp-booking'
								) }
							</p>
							<Button
								variant="link"
								className="forwp-booking-template-style-link"
								onClick={ ( event ) => {
									event.preventDefault();
									event.stopPropagation();
									onOpenStyle();
								} }
							>
								{ __( 'Open style settings', '4wp-booking' ) }
							</Button>
						</CardBody>
					</Card>
				</div>
			</div>

			<Card className="forwp-booking-settings-preview-card">
				<CardBody>
					<h3 className="forwp-booking-admin-section-title">
						{ __( 'Preview', '4wp-booking' ) }
					</h3>
					<TemplatePreview
						template={ selectedTemplate }
						appearance={ appearance }
						defaults={ appearanceDefaults }
					/>
					<p className="forwp-booking-admin-muted">
						<Button variant="link" onClick={ onOpenStyle }>
							{ __( 'Open style settings', '4wp-booking' ) }
						</Button>
					</p>
				</CardBody>
			</Card>

			{ selectedRow && (
				<Card className="forwp-booking-settings-summary">
					<CardBody>
						<p>
							{ sprintf(
								/* translators: %s: template name */
								__( 'New embeds will start as %s.', '4wp-booking' ),
								selectedRow.label
							) }
						</p>
					</CardBody>
				</Card>
			) }

			<div className="forwp-booking-settings-actions">
				<Button
					variant="primary"
					onClick={ save }
					disabled={ saving }
					isBusy={ saving }
				>
					{ __( 'Save settings', '4wp-booking' ) }
				</Button>
			</div>
		</div>
	);
}

function DocumentationTab() {
	return (
		<div className="forwp-booking-admin-docs forwp-booking-docs-layout">
			<div className="forwp-booking-docs-layout__main">
				<Card>
					<CardBody>
						<h3>{ __( 'Overview', '4wp-booking' ) }</h3>
						<p>
							{ __(
								'4WP Booking renders a native calendar. Requests go through WordPress REST. Provider API keys never reach the browser.',
								'4wp-booking'
							) }
						</p>
						<p>
							{ __(
								'ClinicCards is the first implemented provider. Google Calendar and Calendly appear as roadmap stubs.',
								'4wp-booking'
							) }
						</p>
						<h3>{ __( 'Shortcode', '4wp-booking' ) }</h3>
						<pre className="forwp-booking-cli-snippet">
							<code>[forwp_booking]</code>
						</pre>
						<pre className="forwp-booking-cli-snippet">
							<code>[forwp_booking flow="date"]</code>
						</pre>
						<p>
							{ __(
								'Optional attributes: provider, template (advanced or calendar), flow (staff, date, or service). Example: [forwp_booking template="advanced"]',
								'4wp-booking'
							) }
						</p>
						<h3>{ __( 'Gutenberg and Elementor', '4wp-booking' ) }</h3>
						<p>
							{ __(
								'Insert the 4WP Booking block, or the Elementor widget if Elementor is active. Elementor is optional.',
								'4wp-booking'
							) }
						</p>
					</CardBody>
				</Card>
			</div>
		</div>
	);
}

export default function App() {
	const [ activeTab, setActiveTab ] = useState( 'providers' );

	return (
		<div className="forwp-booking-admin-app">
			<div className="forwp-booking-tab-panel components-tab-panel">
				<div
					className="components-tab-panel__tabs"
					role="tablist"
					aria-label={ __( '4WP Booking', '4wp-booking' ) }
				>
					<button
						type="button"
						role="tab"
						id="forwp-booking-tab-providers"
						className={
							'components-button components-tab-panel__tabs-item forwp-booking-tab-providers' +
							( activeTab === 'providers' ? ' is-active' : '' )
						}
						aria-selected={ activeTab === 'providers' }
						aria-controls="forwp-booking-panel-providers"
						tabIndex={ activeTab === 'providers' ? 0 : -1 }
						onClick={ () => setActiveTab( 'providers' ) }
					>
						{ __( 'Providers', '4wp-booking' ) }
					</button>
					<button
						type="button"
						role="tab"
						id="forwp-booking-tab-settings"
						className={
							'components-button components-tab-panel__tabs-item forwp-booking-tab-settings' +
							( activeTab === 'settings' ? ' is-active' : '' )
						}
						aria-selected={ activeTab === 'settings' }
						aria-controls="forwp-booking-panel-settings"
						tabIndex={ activeTab === 'settings' ? 0 : -1 }
						onClick={ () => setActiveTab( 'settings' ) }
					>
						{ __( 'Settings', '4wp-booking' ) }
					</button>
					<button
						type="button"
						role="tab"
						id="forwp-booking-tab-style"
						className={
							'components-button components-tab-panel__tabs-item forwp-booking-tab-style' +
							( activeTab === 'style' ? ' is-active' : '' )
						}
						aria-selected={ activeTab === 'style' }
						aria-controls="forwp-booking-panel-style"
						tabIndex={ activeTab === 'style' ? 0 : -1 }
						onClick={ () => setActiveTab( 'style' ) }
					>
						{ __( 'Style', '4wp-booking' ) }
					</button>
					<button
						type="button"
						role="tab"
						id="forwp-booking-tab-documentation"
						className={
							'components-button components-tab-panel__tabs-item forwp-booking-tab-docs' +
							( activeTab === 'documentation' ? ' is-active' : '' )
						}
						aria-selected={ activeTab === 'documentation' }
						aria-controls="forwp-booking-panel-documentation"
						tabIndex={ activeTab === 'documentation' ? 0 : -1 }
						onClick={ () => setActiveTab( 'documentation' ) }
					>
						{ __( 'Documentation', '4wp-booking' ) }
					</button>
				</div>
				<div
					id="forwp-booking-panel-providers"
					role="tabpanel"
					aria-labelledby="forwp-booking-tab-providers"
					className="components-tab-panel__tab-content"
					hidden={ activeTab !== 'providers' }
				>
					<ProvidersTab />
				</div>
				<div
					id="forwp-booking-panel-settings"
					role="tabpanel"
					aria-labelledby="forwp-booking-tab-settings"
					className="components-tab-panel__tab-content"
					hidden={ activeTab !== 'settings' }
				>
					<SettingsTab onOpenStyle={ () => setActiveTab( 'style' ) } />
				</div>
				<div
					id="forwp-booking-panel-style"
					role="tabpanel"
					aria-labelledby="forwp-booking-tab-style"
					className="components-tab-panel__tab-content"
					hidden={ activeTab !== 'style' }
				>
					<StyleTab active={ activeTab === 'style' } />
				</div>
				<div
					id="forwp-booking-panel-documentation"
					role="tabpanel"
					aria-labelledby="forwp-booking-tab-documentation"
					className="components-tab-panel__tab-content"
					hidden={ activeTab !== 'documentation' }
				>
					<DocumentationTab />
				</div>
			</div>
		</div>
	);
}
