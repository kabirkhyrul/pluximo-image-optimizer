<?php
/**
 * Admin settings page template.
 *
 * @package PluximoImageOptimizer
 * @var array{auto_convert: string, conversion_timing: string, output_format: string, quality: int, keep_backup: string, backup_delete_timing: string, total_saved: int, total_count: int, is_supported: bool, engine_name: string, is_avif_supported: bool, avif_engine_name: string, is_writable: bool} $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$auto_convert         = $data['auto_convert'] ?? '1';
$conversion_timing    = $data['conversion_timing'] ?? 'immediate';
$output_format        = $data['output_format'] ?? 'auto';
$quality              = $data['quality'] ?? 82;
$keep_backup          = $data['keep_backup'] ?? '1';
$backup_delete_timing = $data['backup_delete_timing'] ?? 'immediate';
$total_saved          = (int) ( $data['total_saved'] ?? 0 );
$total_count          = (int) ( $data['total_count'] ?? 0 );
$is_supported         = (bool) ( $data['is_supported'] ?? false );
$engine_name          = (string) ( $data['engine_name'] ?? '' );
$is_avif_supported    = (bool) ( $data['is_avif_supported'] ?? false );
$avif_engine_name     = (string) ( $data['avif_engine_name'] ?? '' );
$is_writable          = (bool) ( $data['is_writable'] ?? true );

// Determine initial quality preset name.
$quality_preset = 'balanced';
if ( 65 === $quality ) {
	$quality_preset = 'smaller';
} elseif ( 92 === $quality ) {
	$quality_preset = 'higher';
} elseif ( 82 !== $quality ) {
	$quality_preset = 'custom';
}
?>

<div class="wrap png2webp-wrap">

	<?php if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) : ?>
		<div class="notice notice-success is-dismissible png2webp-notice">
			<p>
				<i data-lucide="check-circle" class="png2webp-icon png2webp-icon-success"></i>
				<span><?php echo esc_html__( 'Settings saved successfully.', 'pluximo-image-optimizer' ); ?></span>
			</p>
		</div>
	<?php endif; ?>

	<!-- Header -->
	<header class="png2webp-header">
		<div class="png2webp-header-title">
			<div class="png2webp-header-heading-row">
				<h1><?php echo esc_html__( 'PNG to WebP & AVIF Converter', 'pluximo-image-optimizer' ); ?></h1>
				<span class="png2webp-status-pill <?php echo ( $is_supported && $is_writable ) ? 'png2webp-status-ok' : 'png2webp-status-warn'; ?>">
					<i data-lucide="circle-dot" class="png2webp-icon"></i>
					<?php echo ( $is_supported && $is_writable ) ? esc_html__( 'Server Ready', 'pluximo-image-optimizer' ) : esc_html__( 'Attention Required', 'pluximo-image-optimizer' ); ?>
				</span>
			</div>
			<p><?php echo esc_html__( 'Optimize your WordPress images with safe, task-oriented batch workflows.', 'pluximo-image-optimizer' ); ?></p>
		</div>
		<div class="png2webp-header-stats">
			<div class="png2webp-stat-box">
				<span class="png2webp-stat-value" id="png2webp-stat-count"><?php echo esc_html( number_format_i18n( $total_count ) ); ?></span>
				<span class="png2webp-stat-label"><?php echo esc_html__( 'Images Converted', 'pluximo-image-optimizer' ); ?></span>
			</div>
			<div class="png2webp-stat-box">
				<span class="png2webp-stat-value" id="png2webp-stat-saved"><?php echo esc_html( size_format( $total_saved, 2 ) ); ?></span>
				<span class="png2webp-stat-label"><?php echo esc_html__( 'Space Saved', 'pluximo-image-optimizer' ); ?></span>
			</div>
		</div>
	</header>

	<!-- Compact Diagnostics Bar -->
	<div class="png2webp-diagnostics-bar">
		<div class="png2webp-diagnostics-summary">
			<?php if ( $is_supported && $is_writable ) : ?>
				<i data-lucide="check-circle" class="png2webp-icon png2webp-icon-success"></i>
				<span>
					<?php
					if ( $is_avif_supported ) {
						echo esc_html__( 'Your server supports WebP and AVIF · Upload folder is writable', 'pluximo-image-optimizer' );
					} else {
						echo esc_html__( 'Your server supports WebP · Upload folder is writable', 'pluximo-image-optimizer' );
					}
					?>
				</span>
			<?php else : ?>
				<i data-lucide="alert-triangle" class="png2webp-icon png2webp-icon-warning"></i>
				<span>
					<?php
					if ( ! $is_supported ) {
						echo esc_html__( 'Server image library support is missing. WebP conversion disabled.', 'pluximo-image-optimizer' );
					} elseif ( ! $is_writable ) {
						echo esc_html__( 'Upload directory is not writable by server.', 'pluximo-image-optimizer' );
					}
					?>
				</span>
			<?php endif; ?>
		</div>
		<details class="png2webp-tech-details">
			<summary class="png2webp-tech-summary">
				<?php echo esc_html__( 'View technical details', 'pluximo-image-optimizer' ); ?>
				<i data-lucide="chevron-down" class="png2webp-icon png2webp-chevron"></i>
			</summary>
			<div class="png2webp-tech-grid">
				<div class="png2webp-tech-item">
					<span class="png2webp-tech-label"><?php echo esc_html__( 'WebP Engine:', 'pluximo-image-optimizer' ); ?></span>
					<span class="png2webp-tech-value"><?php echo $is_supported ? esc_html( $engine_name ) : esc_html__( 'Unavailable', 'pluximo-image-optimizer' ); ?></span>
				</div>
				<div class="png2webp-tech-item">
					<span class="png2webp-tech-label"><?php echo esc_html__( 'AVIF Engine:', 'pluximo-image-optimizer' ); ?></span>
					<span class="png2webp-tech-value"><?php echo $is_avif_supported ? esc_html( $avif_engine_name ) : esc_html__( 'Not supported by PHP', 'pluximo-image-optimizer' ); ?></span>
				</div>
				<div class="png2webp-tech-item">
					<span class="png2webp-tech-label"><?php echo esc_html__( 'PHP Version:', 'pluximo-image-optimizer' ); ?></span>
					<span class="png2webp-tech-value"><?php echo esc_html( PHP_VERSION ); ?></span>
				</div>
				<div class="png2webp-tech-item">
					<span class="png2webp-tech-label"><?php echo esc_html__( 'Upload Directory:', 'pluximo-image-optimizer' ); ?></span>
					<span class="png2webp-tech-value"><?php echo $is_writable ? esc_html__( 'Writable', 'pluximo-image-optimizer' ) : esc_html__( 'Read-Only / Permission Denied', 'pluximo-image-optimizer' ); ?></span>
				</div>
			</div>
		</details>
	</div>

	<!-- Primary Task Workflow Card: Optimize Existing Images -->
	<section class="png2webp-card png2webp-card-primary" aria-labelledby="png2webp-bulk-title">
		<div class="png2webp-card-header">
			<h2 id="png2webp-bulk-title">
				<i data-lucide="images" class="png2webp-icon"></i>
				<?php echo esc_html__( 'Optimize Existing Images', 'pluximo-image-optimizer' ); ?>
			</h2>
			<span class="png2webp-step-badge" id="png2webp-step-badge"><?php echo esc_html__( 'Step 1 of 3: Scan', 'pluximo-image-optimizer' ); ?></span>
		</div>

		<!-- Step 1: Scan Ready State -->
		<div id="png2webp-scan-ready-state" class="png2webp-workflow-state">
			<div class="png2webp-workflow-hero">
				<p class="png2webp-workflow-lead">
					<?php echo esc_html__( 'Scan your WordPress Media Library to analyze PNG images and calculate potential storage savings before converting.', 'pluximo-image-optimizer' ); ?>
				</p>
				<div class="png2webp-action-group">
					<button id="png2webp-scan-btn" type="button" class="button button-primary button-hero png2webp-main-btn">
						<i data-lucide="search" class="png2webp-icon"></i>
						<span><?php echo esc_html__( 'Scan Media Library', 'pluximo-image-optimizer' ); ?></span>
					</button>
					<span class="png2webp-reassurance-text">
						<i data-lucide="shield-check" class="png2webp-icon png2webp-icon-shield"></i>
						<?php echo esc_html__( 'Nothing will be changed during the scan.', 'pluximo-image-optimizer' ); ?>
					</span>
				</div>
			</div>
		</div>

		<!-- Step 2: Review Scan Results State -->
		<div id="png2webp-review-state" class="png2webp-workflow-state" style="display:none;">
			<div class="png2webp-review-summary-card">
				<div class="png2webp-review-grid">
					<div class="png2webp-review-stat">
						<span class="png2webp-stat-num" id="png2webp-eligible-count">0</span>
						<span class="png2webp-stat-desc"><?php echo esc_html__( 'Eligible PNG Images', 'pluximo-image-optimizer' ); ?></span>
					</div>
					<div class="png2webp-review-stat">
						<span class="png2webp-stat-num" id="png2webp-total-size">0 MB</span>
						<span class="png2webp-stat-desc"><?php echo esc_html__( 'Current Total Size', 'pluximo-image-optimizer' ); ?></span>
					</div>
					<div class="png2webp-review-stat">
						<span class="png2webp-stat-num png2webp-text-highlight" id="png2webp-est-savings">0 MB</span>
						<span class="png2webp-stat-desc"><?php echo esc_html__( 'Estimated Space Savings (~45%)', 'pluximo-image-optimizer' ); ?></span>
					</div>
					<div class="png2webp-review-stat">
						<span class="png2webp-stat-num" id="png2webp-skipped-count">0</span>
						<span class="png2webp-stat-desc"><?php echo esc_html__( 'Skipped / Unreadable Files', 'pluximo-image-optimizer' ); ?></span>
					</div>
				</div>

				<div class="png2webp-safety-status-bar" id="png2webp-safety-status-bar">
					<!-- Dynamically updated by JS based on backup setting -->
				</div>
			</div>

			<div class="png2webp-workflow-actions">
				<button id="png2webp-start-btn" type="button" class="button button-primary button-hero png2webp-main-btn">
					<i data-lucide="play" class="png2webp-icon"></i>
					<span id="png2webp-start-btn-text"><?php echo esc_html__( 'Start Bulk Conversion', 'pluximo-image-optimizer' ); ?></span>
				</button>
				<button id="png2webp-rescan-btn" type="button" class="button button-secondary">
					<?php echo esc_html__( 'Re-scan Library', 'pluximo-image-optimizer' ); ?>
				</button>
			</div>
		</div>

		<!-- Step 3: Conversion Progress State -->
		<div id="png2webp-progress-state" class="png2webp-workflow-state" style="display:none;" aria-live="polite">
			<div class="png2webp-progress-header">
				<span class="png2webp-progress-current-filename" id="png2webp-current-file"><?php echo esc_html__( 'Preparing image queue...', 'pluximo-image-optimizer' ); ?></span>
				<span class="png2webp-progress-percentage" id="png2webp-progress-percent">0%</span>
			</div>
			
			<div class="png2webp-progress-bar-wrap" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="png2webp-progressbar-container">
				<div id="png2webp-progress-bar" class="png2webp-progress-bar" style="width: 0%;"></div>
			</div>

			<div class="png2webp-progress-controls-row">
				<span id="png2webp-progress-status" class="png2webp-progress-status-text">
					<?php echo esc_html__( '0 of 0 images processed', 'pluximo-image-optimizer' ); ?>
				</span>
				<div class="png2webp-live-btn-group">
					<button type="button" id="png2webp-pause-btn" class="button button-secondary">
						<i data-lucide="pause" class="png2webp-icon"></i>
						<span><?php echo esc_html__( 'Pause', 'pluximo-image-optimizer' ); ?></span>
					</button>
					<button type="button" id="png2webp-cancel-btn" class="button button-link-delete">
						<?php echo esc_html__( 'Cancel', 'pluximo-image-optimizer' ); ?>
					</button>
				</div>
			</div>

			<div id="png2webp-log-box" class="png2webp-log-box" tabindex="0" role="region" aria-label="<?php echo esc_attr__( 'Conversion Activity Log', 'pluximo-image-optimizer' ); ?>"></div>
		</div>

		<!-- Step 4: Final Results & Summary State -->
		<div id="png2webp-results-state" class="png2webp-workflow-state" style="display:none;">
			<div class="png2webp-results-banner">
				<div class="png2webp-results-icon-wrap">
					<i data-lucide="check-circle" class="png2webp-icon png2webp-icon-success"></i>
				</div>
				<div class="png2webp-results-text">
					<h3><?php echo esc_html__( 'Bulk Conversion Complete!', 'pluximo-image-optimizer' ); ?></h3>
					<p id="png2webp-results-summary-text">
						<?php echo esc_html__( 'All eligible PNG images have been converted to optimized web formats.', 'pluximo-image-optimizer' ); ?>
					</p>
				</div>
			</div>

			<div class="png2webp-workflow-actions">
				<button id="png2webp-new-scan-btn" type="button" class="button button-primary">
					<?php echo esc_html__( 'Start New Scan', 'pluximo-image-optimizer' ); ?>
				</button>
				<button id="png2webp-download-log-btn" type="button" class="button button-secondary" style="display:none;">
					<i data-lucide="download" class="png2webp-icon"></i>
					<span><?php echo esc_html__( 'Download Error Log', 'pluximo-image-optimizer' ); ?></span>
				</button>
			</div>
		</div>
	</section>

	<!-- Main Settings Form -->
	<form id="png2webp-settings-form" method="post" action="options.php">
		<?php
		settings_fields( 'png2webp_settings_group' );
		do_settings_sections( 'png2webp_settings_group' );
		?>

		<div class="png2webp-settings-grid">

			<!-- Card A: Optimize New Uploads -->
			<section class="png2webp-card" aria-labelledby="png2webp-uploads-title">
				<h2 id="png2webp-uploads-title">
					<i data-lucide="upload-cloud" class="png2webp-icon"></i>
					<?php echo esc_html__( 'Optimize New Uploads', 'pluximo-image-optimizer' ); ?>
				</h2>

				<!-- Toggle: Auto Optimization -->
				<div class="png2webp-field-group">
					<label class="png2webp-switch-label">
						<input type="checkbox" name="png2webp_auto_convert" value="1" <?php checked( '1', $auto_convert ); ?> />
						<span class="png2webp-switch-slider"></span>
						<span class="png2webp-switch-text"><?php echo esc_html__( 'Automatic optimization on upload', 'pluximo-image-optimizer' ); ?></span>
					</label>
					<p class="description">
						<?php echo esc_html__( 'Automatically converts new PNG uploads using the processing timing selected below.', 'pluximo-image-optimizer' ); ?>
					</p>
				</div>

				<fieldset class="png2webp-fieldset">
					<legend class="png2webp-legend"><?php echo esc_html__( 'Upload Conversion Timing', 'pluximo-image-optimizer' ); ?></legend>
					<div class="png2webp-radio-stack">
						<label class="png2webp-radio-option"><input type="radio" name="png2webp_conversion_timing" value="immediate" <?php checked( 'immediate', $conversion_timing ); ?> /><div class="png2webp-radio-label-wrap"><strong><?php echo esc_html__( 'Convert immediately', 'pluximo-image-optimizer' ); ?></strong><p class="description"><?php echo esc_html__( 'Converts the PNG during the upload request.', 'pluximo-image-optimizer' ); ?></p></div></label>
						<label class="png2webp-radio-option"><input type="radio" name="png2webp_conversion_timing" value="cron" <?php checked( 'cron', $conversion_timing ); ?> /><div class="png2webp-radio-label-wrap"><strong><?php echo esc_html__( 'Convert with WP-Cron', 'pluximo-image-optimizer' ); ?></strong><p class="description"><?php echo esc_html__( 'Queues conversion after upload. The PNG stays available until WP-Cron runs.', 'pluximo-image-optimizer' ); ?></p></div></label>
					</div>
				</fieldset>

				<hr class="png2webp-divider" />

				<!-- Format Selection Cards -->
				<fieldset class="png2webp-fieldset">
					<legend class="png2webp-legend"><?php echo esc_html__( 'Target Output Format', 'pluximo-image-optimizer' ); ?></legend>
					<div class="png2webp-selection-cards">
						
						<!-- Card 1: Automatic -->
						<label class="png2webp-choice-card <?php echo 'auto' === $output_format ? 'is-selected' : ''; ?>">
							<input type="radio" name="png2webp_output_format" value="auto" <?php checked( 'auto', $output_format ); ?> />
							<div class="png2webp-choice-content">
								<div class="png2webp-choice-header">
									<span class="png2webp-choice-title"><?php echo esc_html__( 'Automatic', 'pluximo-image-optimizer' ); ?></span>
									<span class="png2webp-recommended-tag"><?php echo esc_html__( 'Recommended', 'pluximo-image-optimizer' ); ?></span>
								</div>
								<p class="png2webp-choice-desc">
									<?php echo esc_html__( 'Best compatible format for each image automatically', 'pluximo-image-optimizer' ); ?>
								</p>
							</div>
						</label>

						<!-- Card 2: WebP -->
						<label class="png2webp-choice-card <?php echo 'webp' === $output_format ? 'is-selected' : ''; ?>">
							<input type="radio" name="png2webp_output_format" value="webp" <?php checked( 'webp', $output_format ); ?> />
							<div class="png2webp-choice-content">
								<div class="png2webp-choice-header">
									<span class="png2webp-choice-title">WebP</span>
								</div>
								<p class="png2webp-choice-desc">
									<?php echo esc_html__( 'Excellent compatibility and smaller file sizes', 'pluximo-image-optimizer' ); ?>
								</p>
							</div>
						</label>

						<!-- Card 3: AVIF -->
						<label class="png2webp-choice-card <?php echo 'avif' === $output_format ? 'is-selected' : ''; ?> <?php echo ! $is_avif_supported ? 'is-disabled' : ''; ?>">
							<input type="radio" name="png2webp_output_format" value="avif" <?php checked( 'avif', $output_format ); ?> <?php disabled( ! $is_avif_supported ); ?> />
							<div class="png2webp-choice-content">
								<div class="png2webp-choice-header">
									<span class="png2webp-choice-title">AVIF</span>
									<?php if ( ! $is_avif_supported ) : ?>
										<span class="png2webp-unsupported-tag"><?php echo esc_html__( 'Unsupported on server', 'pluximo-image-optimizer' ); ?></span>
									<?php endif; ?>
								</div>
								<p class="png2webp-choice-desc">
									<?php echo esc_html__( 'Maximum compression ratio, but requires slightly more CPU to generate', 'pluximo-image-optimizer' ); ?>
								</p>
							</div>
						</label>

					</div>
				</fieldset>

				<hr class="png2webp-divider" />

				<!-- Quality Control Presets -->
				<fieldset class="png2webp-fieldset">
					<legend class="png2webp-legend"><?php echo esc_html__( 'Compression Quality', 'pluximo-image-optimizer' ); ?></legend>
					<div class="png2webp-preset-buttons" role="radiogroup" aria-label="<?php echo esc_attr__( 'Quality Presets', 'pluximo-image-optimizer' ); ?>">
						<button type="button" class="button png2webp-preset-btn <?php echo 'balanced' === $quality_preset ? 'active' : ''; ?>" data-preset="balanced" data-value="82">
							<span><?php echo esc_html__( 'Balanced', 'pluximo-image-optimizer' ); ?></span>
							<small><?php echo esc_html__( 'Recommended (82%)', 'pluximo-image-optimizer' ); ?></small>
						</button>
						<button type="button" class="button png2webp-preset-btn <?php echo 'smaller' === $quality_preset ? 'active' : ''; ?>" data-preset="smaller" data-value="65">
							<span><?php echo esc_html__( 'Smaller files', 'pluximo-image-optimizer' ); ?></span>
							<small><?php echo esc_html__( 'High savings (65%)', 'pluximo-image-optimizer' ); ?></small>
						</button>
						<button type="button" class="button png2webp-preset-btn <?php echo 'higher' === $quality_preset ? 'active' : ''; ?>" data-preset="higher" data-value="92">
							<span><?php echo esc_html__( 'Higher quality', 'pluximo-image-optimizer' ); ?></span>
							<small><?php echo esc_html__( 'Crisp details (92%)', 'pluximo-image-optimizer' ); ?></small>
						</button>
						<button type="button" class="button png2webp-preset-btn <?php echo 'custom' === $quality_preset ? 'active' : ''; ?>" data-preset="custom">
							<span><?php echo esc_html__( 'Custom', 'pluximo-image-optimizer' ); ?></span>
							<small><?php echo esc_html__( 'Manual slider', 'pluximo-image-optimizer' ); ?></small>
						</button>
					</div>

					<div id="png2webp-custom-quality-wrap" class="png2webp-slider-container" style="<?php echo 'custom' === $quality_preset ? 'display:flex;' : 'display:none;'; ?>">
						<input type="range" id="png2webp_quality" name="png2webp_quality" min="1" max="100" value="<?php echo esc_attr( (string) $quality ); ?>" aria-label="<?php echo esc_attr__( 'Custom Quality Slider', 'pluximo-image-optimizer' ); ?>" />
						<span class="png2webp-slider-value"><output id="quality_output"><?php echo esc_html( (string) $quality ); ?></output>%</span>
					</div>

					<div class="png2webp-estimate-callout">
						<i data-lucide="info" class="png2webp-icon"></i>
						<span><?php echo esc_html__( 'Estimated reduction: 35–55% · Suitable for photographs and product images.', 'pluximo-image-optimizer' ); ?></span>
					</div>
				</fieldset>

			</section>

			<!-- Card B: Advanced Safety & Backup Settings -->
			<section class="png2webp-card" aria-labelledby="png2webp-safety-title">
				<h2 id="png2webp-safety-title">
					<i data-lucide="shield" class="png2webp-icon"></i>
					<?php echo esc_html__( 'Safety & Backup Settings', 'pluximo-image-optimizer' ); ?>
				</h2>

				<fieldset class="png2webp-fieldset">
					<legend class="png2webp-legend"><?php echo esc_html__( 'Original File Preservation', 'pluximo-image-optimizer' ); ?></legend>
					
					<div class="png2webp-radio-stack">
						<!-- Option 1: Preserve (Default) -->
						<label class="png2webp-radio-option">
							<input type="radio" name="png2webp_keep_backup" value="1" <?php checked( '1', $keep_backup ); ?> />
							<div class="png2webp-radio-label-wrap">
								<strong><?php echo esc_html__( 'Preserve original PNG files (Recommended)', 'pluximo-image-optimizer' ); ?></strong>
								<p class="description">
									<?php
									/* translators: %s: Backup folder path */
									printf( esc_html__( 'Saves a backup copy of original PNG files in %s before converting.', 'pluximo-image-optimizer' ), '<code>wp-content/uploads/png-to-webp-backups/</code>' );
									?>
								</p>
							</div>
						</label>

						<!-- Option 2: Delete original -->
						<label class="png2webp-radio-option">
							<input type="radio" name="png2webp_keep_backup" value="0" <?php checked( '0', $keep_backup ); ?> />
							<div class="png2webp-radio-label-wrap">
								<strong><?php echo esc_html__( 'Delete originals after successful conversion', 'pluximo-image-optimizer' ); ?></strong>
								<p class="description">
									<?php echo esc_html__( 'Frees up disk space immediately. Note: Converted images cannot be reverted back to original PNG files if removed.', 'pluximo-image-optimizer' ); ?>
								</p>
							</div>
						</label>
					</div>

					<div class="png2webp-info-box">
						<i data-lucide="info" class="png2webp-icon"></i>
						<div>
							<strong><?php echo esc_html__( 'Storage Impact Notice', 'pluximo-image-optimizer' ); ?></strong>
							<p><?php echo esc_html__( 'Preserving original PNGs safely retains your original source assets, but requires extra disk space until backups are cleared.', 'pluximo-image-optimizer' ); ?></p>
						</div>
					</div>
				</fieldset>

				<hr class="png2webp-divider" />

				<fieldset class="png2webp-fieldset">
					<legend class="png2webp-legend"><?php echo esc_html__( 'Backup Deletion Timing', 'pluximo-image-optimizer' ); ?></legend>
					<div class="png2webp-radio-stack">
						<label class="png2webp-radio-option"><input type="radio" name="png2webp_backup_delete_timing" value="immediate" <?php checked( 'immediate', $backup_delete_timing ); ?> /><div class="png2webp-radio-label-wrap"><strong><?php echo esc_html__( 'Delete immediately', 'pluximo-image-optimizer' ); ?></strong><p class="description"><?php echo esc_html__( 'Deletes matching backups as soon as the Media Library item is deleted.', 'pluximo-image-optimizer' ); ?></p></div></label>
						<label class="png2webp-radio-option"><input type="radio" name="png2webp_backup_delete_timing" value="cron" <?php checked( 'cron', $backup_delete_timing ); ?> /><div class="png2webp-radio-label-wrap"><strong><?php echo esc_html__( 'Delete with WP-Cron', 'pluximo-image-optimizer' ); ?></strong><p class="description"><?php echo esc_html__( 'Queues matching backup files for background deletion.', 'pluximo-image-optimizer' ); ?></p></div></label>
					</div>
				</fieldset>
			</section>

		</div>

		<!-- Sticky Bottom Action Bar -->
		<div id="png2webp-sticky-bar" class="png2webp-sticky-bar" aria-live="polite">
			<div class="png2webp-sticky-inner">
				<div class="png2webp-sticky-status">
					<i data-lucide="alert-triangle" class="png2webp-icon png2webp-icon-warning"></i>
					<span><?php echo esc_html__( 'You have unsaved changes', 'pluximo-image-optimizer' ); ?></span>
				</div>
				<div class="png2webp-sticky-actions">
					<button type="button" id="png2webp-discard-btn" class="button button-secondary">
						<?php echo esc_html__( 'Discard changes', 'pluximo-image-optimizer' ); ?>
					</button>
					<button type="submit" id="png2webp-save-btn" class="button button-primary png2webp-save-btn" disabled>
						<?php echo esc_html__( 'Save changes', 'pluximo-image-optimizer' ); ?>
					</button>
				</div>
			</div>
		</div>

	</form>

</div>
