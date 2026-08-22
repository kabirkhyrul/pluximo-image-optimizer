jQuery(document).ready(function ($) {
	if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
		lucide.createIcons();
	}

	var pngIds = [];
	var totalPngs = 0;
	var currentIndex = 0;
	var totalSavedBytesSession = 0;
	var convertedCountSession = 0;
	var errorCountSession = 0;
	var logEntries = [];

	var isPaused = false;
	var isCancelled = false;

	// Initial form dirty tracking
	var $form = $('#png2webp-settings-form');
	var initialFormData = $form.serialize();

	function checkFormDirty() {
		var currentFormData = $form.serialize();
		if (currentFormData !== initialFormData) {
			$('#png2webp-sticky-bar').addClass('is-visible');
			$('#png2webp-save-btn').prop('disabled', false);
		} else {
			$('#png2webp-sticky-bar').removeClass('is-visible');
			$('#png2webp-save-btn').prop('disabled', true);
		}
	}

	$form.on('change input', 'input, select, textarea', function () {
		checkFormDirty();
	});

	// Format Selection Cards
	$('.png2webp-choice-card input[type="radio"]').on('change', function () {
		$('.png2webp-choice-card').removeClass('is-selected');
		$(this).closest('.png2webp-choice-card').addClass('is-selected');
	});

	// Quality Preset Buttons
	$('.png2webp-preset-btn').on('click', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var preset = $btn.data('preset');

		$('.png2webp-preset-btn').removeClass('active');
		$btn.addClass('active');

		if (preset === 'custom') {
			$('#png2webp-custom-quality-wrap').css('display', 'flex');
			$('#png2webp_quality').focus();
		} else {
			var val = $btn.data('value');
			$('#png2webp-custom-quality-wrap').hide();
			$('#png2webp_quality').val(val);
			$('#quality_output').val(val);
		}
		checkFormDirty();
	});

	$('#png2webp_quality').on('input', function () {
		$('#quality_output').val($(this).val());
		// If manually dragged, set custom preset button active if value doesn't match preset
		var val = parseInt($(this).val(), 10);
		if (val !== 82 && val !== 65 && val !== 92) {
			$('.png2webp-preset-btn').removeClass('active');
			$('.png2webp-preset-btn[data-preset="custom"]').addClass('active');
		}
	});

	// Discard changes
	$('#png2webp-discard-btn').on('click', function (e) {
		e.preventDefault();
		$form[0].reset();
		// Restore format cards
		$('.png2webp-choice-card').removeClass('is-selected');
		$('.png2webp-choice-card input[type="radio"]:checked').closest('.png2webp-choice-card').addClass('is-selected');

		// Restore quality presets
		var qVal = parseInt($('#png2webp_quality').val(), 10);
		$('#quality_output').val(qVal);
		$('.png2webp-preset-btn').removeClass('active');
		if (qVal === 82) {
			$('.png2webp-preset-btn[data-preset="balanced"]').addClass('active');
			$('#png2webp-custom-quality-wrap').hide();
		} else if (qVal === 65) {
			$('.png2webp-preset-btn[data-preset="smaller"]').addClass('active');
			$('#png2webp-custom-quality-wrap').hide();
		} else if (qVal === 92) {
			$('.png2webp-preset-btn[data-preset="higher"]').addClass('active');
			$('#png2webp-custom-quality-wrap').hide();
		} else {
			$('.png2webp-preset-btn[data-preset="custom"]').addClass('active');
			$('#png2webp-custom-quality-wrap').css('display', 'flex');
		}

		initialFormData = $form.serialize();
		checkFormDirty();
	});

	// Logging helper
	function appendLog(message, type) {
		var $logBox = $('#png2webp-log-box');
		$logBox.show();
		var typeClass = type || 'info';
		var timestamp = new Date().toLocaleTimeString();
		var logText = '[' + timestamp + '] ' + message;
		logEntries.push(logText);

		var entry = $('<div class="png2webp-log-entry ' + typeClass + '"></div>').text('> ' + logText);
		$logBox.append(entry);
		$logBox.scrollTop($logBox[0].scrollHeight);
	}

	function formatBytes(bytes) {
		if (bytes === 0) return '0 B';
		var k = 1024;
		var sizes = ['B', 'KB', 'MB', 'GB'];
		var i = Math.floor(Math.log(bytes) / Math.log(k));
		return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
	}

	// Step 1: Scan Media Library
	$('#png2webp-scan-btn, #png2webp-rescan-btn').on('click', function (e) {
		e.preventDefault();
		var $btn = $(this);
		$btn.prop('disabled', true);

		if ($btn.attr('id') === 'png2webp-scan-btn') {
			$btn.find('span').text(png2webp_data.i18n.scanning_btn);
		}

		logEntries = [];
		$('#png2webp-log-box').empty().hide();

		$.ajax({
			url: png2webp_data.ajax_url,
			type: 'POST',
			data: {
				action: 'png2webp_bulk_scan',
				nonce: png2webp_data.nonce
			},
			success: function (response) {
				$btn.prop('disabled', false);
				$('#png2webp-scan-btn span').text(png2webp_data.i18n.scan_btn);

				if (response.success) {
					pngIds = response.data.ids || [];
					totalPngs = response.data.count || 0;
					currentIndex = 0;
					totalSavedBytesSession = 0;
					convertedCountSession = 0;
					errorCountSession = 0;

					if (totalPngs === 0) {
						alert(png2webp_data.i18n.no_pngs);
					} else {
						// Populate review state
						$('#png2webp-eligible-count').text(totalPngs);
						$('#png2webp-total-size').text(response.data.total_formatted || '0 B');
						$('#png2webp-est-savings').text(response.data.estimated_saved_format || '0 B');
						$('#png2webp-skipped-count').text(response.data.skipped_count || 0);

						var safetyHtml = '';
						if (response.data.backup_enabled) {
							safetyHtml = '<i data-lucide="shield-check" class="png2webp-icon png2webp-icon-success"></i>' +
								'<span><strong>Safe Mode Active:</strong> Original PNG files will be safely backed up in <code>wp-content/uploads/png-to-webp-backups/</code></span>';
						} else {
							safetyHtml = '<i data-lucide="alert-triangle" class="png2webp-icon png2webp-icon-warning"></i>' +
								'<span><strong>Direct Mode Active:</strong> Original PNG files will be replaced after conversion.</span>';
						}
						$('#png2webp-safety-status-bar').html(safetyHtml);
						if (typeof lucide !== 'undefined' && lucide.createIcons) {
							lucide.createIcons();
						}

						$('#png2webp-start-btn-text').text('Start Bulk Conversion (' + totalPngs + ' images)');

						$('#png2webp-scan-ready-state').hide();
						$('#png2webp-review-state').show();
						$('#png2webp-step-badge').text('Step 2 of 3: Review & Convert');
					}
				} else {
					alert(response.data.message || png2webp_data.i18n.error);
				}
			},
			error: function () {
				$btn.prop('disabled', false);
				$('#png2webp-scan-btn span').text(png2webp_data.i18n.scan_btn);
				alert(png2webp_data.i18n.error);
			}
		});
	});

	// Step 2: Start Conversion
	$('#png2webp-start-btn').on('click', function (e) {
		e.preventDefault();
		if (pngIds.length === 0) return;

		isPaused = false;
		isCancelled = false;

		$('#png2webp-review-state').hide();
		$('#png2webp-progress-state').show();
		$('#png2webp-step-badge').text('Step 3 of 3: Converting...');

		$('#png2webp-pause-btn').find('span').text(png2webp_data.i18n.pause_btn);
		updateProgress(0);

		appendLog('Starting bulk conversion batch of ' + totalPngs + ' images...', 'info');
		processNextItem();
	});

	// Pause / Resume Button
	$('#png2webp-pause-btn').on('click', function (e) {
		e.preventDefault();
		if (isPaused) {
			isPaused = false;
			$(this).find('span').text(png2webp_data.i18n.pause_btn);
			appendLog('Resuming conversion queue...', 'info');
			processNextItem();
		} else {
			isPaused = true;
			$(this).find('span').text(png2webp_data.i18n.resume_btn);
			appendLog(png2webp_data.i18n.paused_status, 'warning');
		}
	});

	// Cancel Button
	$('#png2webp-cancel-btn').on('click', function (e) {
		e.preventDefault();
		if (confirm('Are you sure you want to cancel the conversion batch? Progress made so far will be saved.')) {
			isCancelled = true;
			appendLog(png2webp_data.i18n.cancelled_status, 'error');
			finishBatch();
		}
	});

	function updateProgress(processedCount) {
		var percent = totalPngs > 0 ? Math.round((processedCount / totalPngs) * 100) : 0;
		$('#png2webp-progress-bar').css('width', percent + '%');
		$('#png2webp-progress-percent').text(percent + '%');
		var statusText = png2webp_data.i18n.processed_status
			.replace('%1$d', processedCount)
			.replace('%2$d', totalPngs)
			.replace('%3$d', percent);
		$('#png2webp-progress-status').text(statusText);
	}

	function processNextItem() {
		if (isPaused || isCancelled) return;

		if (currentIndex >= pngIds.length) {
			appendLog(png2webp_data.i18n.complete, 'success');
			finishBatch();
			return;
		}

		var attachmentId = pngIds[currentIndex];
		var displayNum = currentIndex + 1;

		$('#png2webp-current-file').text('Converting attachment #' + attachmentId + ' (' + displayNum + ' of ' + totalPngs + ')...');

		$.ajax({
			url: png2webp_data.ajax_url,
			type: 'POST',
			data: {
				action: 'png2webp_bulk_process_item',
				attachment_id: attachmentId,
				nonce: png2webp_data.nonce
			},
			success: function (response) {
				if (isCancelled) return;

				if (response.success) {
					var info = response.data;
					convertedCountSession++;
					totalSavedBytesSession += (info.saved_bytes || 0);

					var msg = png2webp_data.i18n.converted_msg
						.replace('%1$d', displayNum)
						.replace('%2$d', totalPngs)
						.replace('%3$d', info.attachment_id)
						.replace('%4$s', info.filename)
						.replace('%5$s', info.saved_formatted);
					appendLog(msg, 'success');
				} else {
					errorCountSession++;
					var errMsg = (response.data.message || png2webp_data.i18n.error);
					var msg = png2webp_data.i18n.error_item_msg
						.replace('%1$d', displayNum)
						.replace('%2$d', totalPngs)
						.replace('%3$d', attachmentId)
						.replace('%4$s', errMsg);
					appendLog(msg, 'error');
				}

				currentIndex++;
				updateProgress(currentIndex);

				if (!isPaused && !isCancelled) {
					processNextItem();
				}
			},
			error: function () {
				if (isCancelled) return;
				errorCountSession++;
				var msg = png2webp_data.i18n.ajax_error_msg
					.replace('%1$d', displayNum)
					.replace('%2$d', totalPngs)
					.replace('%3$d', attachmentId);
				appendLog(msg, 'error');

				currentIndex++;
				updateProgress(currentIndex);

				if (!isPaused && !isCancelled) {
					processNextItem();
				}
			}
		});
	}

	function finishBatch() {
		$('#png2webp-progress-state').hide();
		$('#png2webp-results-state').show();
		$('#png2webp-step-badge').text('Step 3 of 3: Complete');

		// Update Header Stats live
		var $countStat = $('#png2webp-stat-count');
		var currentHeaderCount = parseInt($countStat.text().replace(/,/g, ''), 10) || 0;
		$countStat.text((currentHeaderCount + convertedCountSession).toLocaleString());

		var summaryMsg = 'Successfully converted ' + convertedCountSession + ' images and saved ' + formatBytes(totalSavedBytesSession) + ' of disk space.';
		if (errorCountSession > 0) {
			summaryMsg += ' (' + errorCountSession + ' items encountered errors)';
			$('#png2webp-download-log-btn').show();
		} else {
			$('#png2webp-download-log-btn').show(); // Allow log export
		}
		$('#png2webp-results-summary-text').text(summaryMsg);
		if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
			lucide.createIcons();
		}
	}

	// Download Log Button
	$('#png2webp-download-log-btn').on('click', function (e) {
		e.preventDefault();
		var content = logEntries.join('\n');
		var blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
		var url = URL.createObjectURL(blob);
		var a = document.createElement('a');
		a.href = url;
		a.download = 'png-to-webp-conversion-log.txt';
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		URL.revokeObjectURL(url);
	});

	// Start New Scan
	$('#png2webp-new-scan-btn').on('click', function (e) {
		e.preventDefault();
		$('#png2webp-results-state').hide();
		$('#png2webp-scan-ready-state').show();
		$('#png2webp-step-badge').text('Step 1 of 3: Scan');
	});
});
