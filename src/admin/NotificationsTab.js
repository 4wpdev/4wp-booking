/**
 * Notifications tab — delivery channels (Email, Telegram, …) as first-class cards.
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
	Spinner,
	Notice,
} from '@wordpress/components';

const SETTINGS_PATH = '/forwp-booking/v1/settings';
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

function NotificationsIntro() {
	return (
		<Card className="forwp-booking-intro-card">
			<CardBody>
				<h3 className="forwp-booking-intro-card__title">
					{ __( 'Where bookings and forms go', '4wp-booking' ) }
				</h3>
				<p className="forwp-booking-intro-card__text">
					{ __(
						'Pick a delivery channel below. Email and Telegram are live. Other messengers are roadmap stubs.',
						'4wp-booking'
					) }
				</p>
				<p className="forwp-booking-intro-card__cta">
					{ __(
						'Open a card to configure recipients, bot credentials, or form forwarding.',
						'4wp-booking'
					) }
				</p>
			</CardBody>
		</Card>
	);
}

function TelegramSetupHelp() {
	return (
		<details className="forwp-booking-notify__help">
			<summary>
				{ __( 'How to connect a Telegram bot', '4wp-booking' ) }
			</summary>
			<ol className="forwp-booking-notify__steps">
				<li>
					{ __(
						'Open Telegram, search @BotFather → /newbot → copy the bot token into Bot token above.',
						'4wp-booking'
					) }
				</li>
				<li>
					{ __(
						'Create a group (or use an existing one). Add your bot as a member (admin is fine).',
						'4wp-booking'
					) }
				</li>
				<li>
					{ __(
						'Send any message in the group, then open https://api.telegram.org/botYOUR_TOKEN/getUpdates and find "chat":{"id": … } — that number is the Chat ID (groups often start with -100).',
						'4wp-booking'
					) }
				</li>
				<li>
					{ __(
						'Paste Chat ID(s) above, save, then place a test booking or form submit.',
						'4wp-booking'
					) }
				</li>
			</ol>
			<p className="forwp-booking-notify__help-note">
				{ __(
					'Tip: @userinfobot or @getidsbot can show a personal chat id. For a group, getUpdates after the bot is in the group is the reliable way.',
					'4wp-booking'
				) }
			</p>
		</details>
	);
}

export default function NotificationsTab() {
	setupApiFetch();

	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ success, setSuccess ] = useState( false );
	const [ selectedSlug, setSelectedSlug ] = useState( null );

	const [ notifyEmails, setNotifyEmails ] = useState( '' );
	const [ adminEmail, setAdminEmail ] = useState( '' );
	const [ channels, setChannels ] = useState( [] );
	const [ telegramChatIds, setTelegramChatIds ] = useState( '' );
	const [ telegramTokenInput, setTelegramTokenInput ] = useState( '' );
	const [ telegramTokenConfigured, setTelegramTokenConfigured ] =
		useState( false );
	const [ telegramTokenLength, setTelegramTokenLength ] = useState( 0 );
	const [ telegramTokenTouched, setTelegramTokenTouched ] = useState( false );
	const [ formTelegram, setFormTelegram ] = useState( {
		enabled: false,
		chat_ids: '',
		sources: {},
	} );
	const [ formSources, setFormSources ] = useState( [] );

	const applySettings = ( data ) => {
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
		setFormTelegram( {
			enabled: false,
			chat_ids: '',
			sources: {},
			...( data.form_telegram || {} ),
		} );
		setFormSources( data.form_sources || [] );
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

	const emailStatus = notifyEmails.trim()
		? __( 'Custom recipients', '4wp-booking' )
		: sprintf(
				/* translators: %s: admin email */
				__( 'Uses %s', '4wp-booking' ),
				adminEmail || __( 'admin email', '4wp-booking' )
		  );

	const deliveryRows = [
		{
			slug: 'email',
			label: __( 'Email', '4wp-booking' ),
			implemented: true,
			ready: true,
			status: emailStatus,
		},
		...channels,
	];

	const selectedRow =
		selectedSlug && deliveryRows.length
			? deliveryRows.find( ( r ) => r.slug === selectedSlug )
			: null;

	const selectChannel = ( slug ) => {
		setSelectedSlug( slug );
		setSuccess( false );
		setTelegramTokenInput( '' );
		setTelegramTokenTouched( false );
	};

	const save = async () => {
		if ( ! selectedRow?.implemented ) {
			return;
		}

		setSaving( true );
		setError( null );
		setSuccess( false );
		try {
			const payload = {};
			if ( selectedSlug === 'email' ) {
				payload.notify_emails = notifyEmails;
			}
			if ( selectedSlug === 'telegram' ) {
				payload.telegram_chat_ids = telegramChatIds;
				payload.form_telegram = formTelegram;
				if ( telegramTokenTouched ) {
					payload.telegram_token = telegramTokenInput;
				}
			}
			const data = await apiFetch( {
				path: SETTINGS_PATH,
				method: 'POST',
				data: payload,
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

	const onCardKeyDown = ( event, slug ) => {
		if ( event.key === 'Enter' || event.key === ' ' ) {
			event.preventDefault();
			selectChannel( slug );
		}
	};

	if ( loading ) {
		return (
			<div className="forwp-booking-admin-loading">
				<Spinner />
			</div>
		);
	}

	const showTelegramTokenMask =
		telegramTokenConfigured &&
		! telegramTokenTouched &&
		telegramTokenLength > 0;

	const activeFormSources = formSources.filter(
		( row ) => row.implemented && row.plugin_active
	);

	return (
		<div className="forwp-booking-admin-providers forwp-booking-admin-notifications">
			{ error && (
				<Notice
					status="error"
					isDismissible
					onRemove={ () => setError( null ) }
				>
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
					<NotificationsIntro />
				</div>
			) : (
				<div className="forwp-booking-admin-detail-region">
					<div className="forwp-booking-provider-detail-head">
						<h3 className="forwp-booking-admin-section-title forwp-booking-provider-detail-head__title">
							{ selectedRow?.label ||
								__( 'Channel', '4wp-booking' ) }
						</h3>
						<Button
							variant="tertiary"
							onClick={ () => setSelectedSlug( null ) }
						>
							{ __( '← Back to overview', '4wp-booking' ) }
						</Button>
					</div>

					<div className="forwp-booking-admin-panel forwp-booking-admin-panel--embedded forwp-booking-notify">
						{ selectedSlug === 'email' && (
							<>
								<p className="forwp-booking-admin-muted forwp-booking-split__lead">
									{ __(
										'Each booking with a date and time is emailed to these addresses.',
										'4wp-booking'
									) }
								</p>
								<TextControl
									label={ __( 'Recipients', '4wp-booking' ) }
									value={ notifyEmails }
									placeholder={ adminEmail }
									onChange={ setNotifyEmails }
									help={ __(
										'Leave empty to use the WordPress admin email. Separate addresses with commas.',
										'4wp-booking'
									) }
								/>
							</>
						) }

						{ selectedSlug === 'telegram' && (
							<>
								<p className="forwp-booking-admin-muted forwp-booking-split__lead">
									{ __(
										'Send booking notices (and optionally form submissions) to Telegram chats or groups.',
										'4wp-booking'
									) }
								</p>
								<div className="forwp-booking-notify__channel-fields forwp-booking-notify__channel-fields--stack">
									<TextControl
										label={ __(
											'Bot token',
											'4wp-booking'
										) }
										type="password"
										autoComplete="off"
										readOnly={ showTelegramTokenMask }
										value={
											showTelegramTokenMask
												? maskForSavedApiKey(
														telegramTokenLength
												  )
												: telegramTokenInput
										}
										onFocus={ () => {
											if ( showTelegramTokenMask ) {
												setTelegramTokenTouched( true );
												setTelegramTokenInput( '' );
											}
										} }
										onBlur={ () => {
											if (
												telegramTokenConfigured &&
												telegramTokenTouched &&
												telegramTokenInput === ''
											) {
												setTelegramTokenTouched(
													false
												);
											}
										} }
										onChange={ ( v ) => {
											setTelegramTokenInput( v );
											setTelegramTokenTouched( true );
										} }
										help={ __(
											'From @BotFather. Stored on the server only.',
											'4wp-booking'
										) }
									/>
									<TextControl
										label={ __(
											'Chat IDs (bookings)',
											'4wp-booking'
										) }
										value={ telegramChatIds }
										placeholder="-100…"
										onChange={ setTelegramChatIds }
										help={ __(
											'One or more chat or group IDs, separated by commas.',
											'4wp-booking'
										) }
									/>
								</div>

								<TelegramSetupHelp />

								<div className="forwp-booking-notify__block forwp-booking-notify__block--forms">
									<h4 className="forwp-booking-notify__label">
										{ __(
											'Forms → Telegram',
											'4wp-booking'
										) }
									</h4>
									<p className="forwp-booking-admin-muted">
										{ __(
											'Same bot. Optional separate chat IDs for form submissions.',
											'4wp-booking'
										) }
									</p>

									<label className="forwp-booking-notify__switch">
										<input
											type="checkbox"
											checked={ !! formTelegram.enabled }
											onChange={ ( event ) =>
												setFormTelegram( {
													...formTelegram,
													enabled: event.target
														.checked,
												} )
											}
										/>
										<span>
											{ __(
												'Forward form submissions to Telegram',
												'4wp-booking'
											) }
										</span>
									</label>

									{ formTelegram.enabled && (
										<div className="forwp-booking-notify__forms-panel">
											<TextControl
												label={ __(
													'Form chat IDs (optional)',
													'4wp-booking'
												) }
												value={
													formTelegram.chat_ids || ''
												}
												placeholder={
													telegramChatIds || '-100…'
												}
												onChange={ ( value ) =>
													setFormTelegram( {
														...formTelegram,
														chat_ids: value,
													} )
												}
												help={ __(
													'Leave empty to reuse booking Chat IDs. Set different IDs to send forms to another group.',
													'4wp-booking'
												) }
											/>

											{ activeFormSources.length ===
											0 ? (
												<p className="forwp-booking-notify__empty">
													{ __(
														'No supported form plugin is active. Install Contact Form 7, WPForms, or Gravity Forms — it will appear here automatically.',
														'4wp-booking'
													) }
												</p>
											) : (
												<ul className="forwp-booking-notify__channels">
													{ activeFormSources.map(
														( row ) => {
															const sourceState =
																( formTelegram.sources &&
																	formTelegram
																		.sources[
																		row
																			.slug
																	] ) ||
																{};
															return (
																<li
																	key={
																		row.slug
																	}
																	className="forwp-booking-notify__channel is-live"
																>
																	<div className="forwp-booking-notify__channel-head">
																		<span className="forwp-booking-notify__channel-name">
																			{
																				row.label
																			}
																		</span>
																		<span className="forwp-booking-badge forwp-booking-badge--live">
																			{ __(
																				'Active',
																				'4wp-booking'
																			) }
																		</span>
																	</div>
																	<div className="forwp-booking-notify__channel-fields forwp-booking-notify__channel-fields--forms">
																		<label className="forwp-booking-notify__switch forwp-booking-notify__switch--compact">
																			<input
																				type="checkbox"
																				checked={
																					sourceState.enabled !==
																					false
																				}
																				onChange={ (
																					event
																				) =>
																					setFormTelegram(
																						{
																							...formTelegram,
																							sources:
																								{
																									...( formTelegram.sources ||
																										{} ),
																									[ row.slug ]:
																										{
																											...sourceState,
																											enabled:
																												event
																													.target
																													.checked,
																										},
																								},
																						}
																					)
																				}
																			/>
																			<span>
																				{ __(
																					'Send to Telegram',
																					'4wp-booking'
																				) }
																			</span>
																		</label>
																		<TextControl
																			label={ __(
																				'Skip form IDs',
																				'4wp-booking'
																			) }
																			value={
																				sourceState.exclude_ids ||
																				''
																			}
																			onChange={ (
																				value
																			) =>
																				setFormTelegram(
																					{
																						...formTelegram,
																						sources:
																							{
																								...( formTelegram.sources ||
																									{} ),
																								[ row.slug ]:
																									{
																										...sourceState,
																										exclude_ids:
																											value,
																									},
																							},
																					}
																				)
																			}
																			help={ __(
																				'Optional. Comma-separated IDs to ignore.',
																				'4wp-booking'
																			) }
																		/>
																	</div>
																</li>
															);
														}
													) }
												</ul>
											) }
										</div>
									) }
								</div>
							</>
						) }

						{ selectedRow &&
							! selectedRow.implemented &&
							selectedSlug !== 'email' &&
							selectedSlug !== 'telegram' && (
								<Notice status="info" isDismissible={ false }>
									{ __(
										'This channel is listed for roadmap planning only. No credentials or live delivery yet.',
										'4wp-booking'
									) }
								</Notice>
							) }

						{ selectedRow?.implemented && (
							<div
								className="forwp-booking-toolbar"
								role="group"
								aria-label={ __(
									'Notification actions',
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
										<span>
											{ __( 'Save', '4wp-booking' ) }
										</span>
									</Button>
									<Button
										variant="secondary"
										className="forwp-booking-btn-dashicon"
										onClick={ load }
										disabled={ saving }
									>
										<span
											className="dashicons dashicons-update"
											aria-hidden="true"
										/>
										<span>
											{ __( 'Reload', '4wp-booking' ) }
										</span>
									</Button>
								</div>
							</div>
						) }
					</div>
				</div>
			) }

			<h3 className="forwp-booking-admin-section-title forwp-booking-registry-title">
				{ __( 'Delivery channels', '4wp-booking' ) }
			</h3>
			<p className="forwp-booking-admin-muted forwp-booking-registry-hint">
				{ __( 'Click a card to configure.', '4wp-booking' ) }
			</p>

			<div className="forwp-booking-provider-grid">
				{ deliveryRows.map( ( row ) => (
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
							/* translators: %s: channel name */
							__( 'Open details for %s', '4wp-booking' ),
							row.label
						) }
						onClick={ () => selectChannel( row.slug ) }
						onKeyDown={ ( event ) =>
							onCardKeyDown( event, row.slug )
						}
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
