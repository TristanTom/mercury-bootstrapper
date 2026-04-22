( function () {
	'use strict';

	var config = window.mercuryBootstrapper;
	if ( ! config ) {
		return;
	}

	var runButton = document.getElementById( 'mercury-bootstrapper-run' );
	var retryButton = document.getElementById( 'mercury-bootstrapper-retry' );
	var summary = document.getElementById( 'mercury-bootstrapper-summary' );
	var list = document.getElementById( 'mercury-step-list' );

	if ( ! runButton || ! list ) {
		return;
	}

	var STATUS_CLASSES = {
		ok: 'mercury-step--ok',
		skipped: 'mercury-step--skipped',
		error: 'mercury-step--error',
		running: 'mercury-step--running',
		pending: 'mercury-step--pending',
	};

	var STATUS_ICONS = {
		ok: '\u2713',
		skipped: '\u2298',
		error: '\u2717',
		running: '\u25CC',
		pending: '\u2022',
	};

	function setStepStatus( item, status, message ) {
		Object.keys( STATUS_CLASSES ).forEach( function ( key ) {
			item.classList.remove( STATUS_CLASSES[ key ] );
		} );
		item.classList.add( STATUS_CLASSES[ status ] || STATUS_CLASSES.pending );

		var icon = item.querySelector( '.mercury-step__icon' );
		if ( icon ) {
			icon.textContent = STATUS_ICONS[ status ] || STATUS_ICONS.pending;
		}

		var messageEl = item.querySelector( '.mercury-step__message' );
		if ( messageEl ) {
			messageEl.textContent = message || '';
		}
	}

	function runStep( stepId ) {
		var body = new URLSearchParams();
		body.set( 'action', config.action );
		body.set( 'nonce', config.nonce );
		body.set( 'step_id', stepId );

		return fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString(),
		} )
			.then( function ( response ) { return response.json(); } )
			.then( function ( json ) {
				if ( json && json.success && json.data ) {
					return json.data;
				}
				var msg = ( json && json.data && json.data.message ) || config.i18n.requestError;
				return { status: 'error', message: msg };
			} )
			.catch( function ( err ) {
				return { status: 'error', message: err && err.message ? err.message : config.i18n.requestError };
			} );
	}

	function collectTargetSteps( onlyFailed ) {
		var items = Array.prototype.slice.call( list.querySelectorAll( '.mercury-step' ) );
		if ( ! onlyFailed ) {
			return items;
		}
		return items.filter( function ( item ) {
			return item.classList.contains( STATUS_CLASSES.error );
		} );
	}

	function runAll( onlyFailed ) {
		runButton.disabled = true;
		retryButton.hidden = true;
		summary.textContent = config.i18n.running;

		var targets = collectTargetSteps( onlyFailed );
		var okCount = 0;
		var skippedCount = 0;
		var errorCount = 0;

		targets.forEach( function ( item ) {
			setStepStatus( item, 'pending', '' );
		} );

		var chain = Promise.resolve();
		targets.forEach( function ( item ) {
			var stepId = item.dataset.stepId;
			chain = chain.then( function () {
				setStepStatus( item, 'running', config.i18n.running );
				return runStep( stepId ).then( function ( result ) {
					var status = ( result && result.status ) || 'error';
					var message = ( result && result.message ) || '';
					setStepStatus( item, status, message );
					if ( status === 'ok' ) okCount += 1;
					else if ( status === 'skipped' ) skippedCount += 1;
					else errorCount += 1;
				} );
			} );
		} );

		chain.then( function () {
			runButton.disabled = false;
			var parts = [
				okCount + ' ok',
				skippedCount + ' skipped',
				errorCount + ' error',
			];
			summary.textContent = ( errorCount > 0 ? config.i18n.failed : config.i18n.completed ) +
				' — ' + parts.join( ', ' );
			retryButton.hidden = errorCount === 0;
		} );
	}

	runButton.addEventListener( 'click', function () { runAll( false ); } );
	if ( retryButton ) {
		retryButton.addEventListener( 'click', function () { runAll( true ); } );
	}
} )();
