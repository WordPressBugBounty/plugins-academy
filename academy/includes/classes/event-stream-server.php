<?php
/**
 * A lightweight server-sent event (SSE) handler for streaming real-time data to browsers using EventSource.
 *
 * @version 1.0.0
 */

namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EventStreamServer
 *
 * A lightweight server-sent event (SSE) handler for streaming real-time data to browsers using EventSource.
 *
 * @example
 * ```php
 * $sse = new EventStreamServer();
 * $sse->listen(function () use ($sse) {
 *     $sse->emit_event([
 *         'event'   => 'ping',
 *         'message' => 'hello',
 *         'time'    => current_time('mysql'),
 *     ]);
 *
 *     // Optionally close stream under a condition
 *     if ( some_condition() ) {
 *         $sse->emit_event([
 *             'event'   => 'end',
 *             'message' => 'done',
 *         ], true);
 *     }
 * });
 * ```
 */
class EventStreamServer {

	/**
	 * Whether the connection should remain open.
	 *
	 * @var bool
	 */
	private bool $connected = true;

	/**
	 * @var int
	 */
	private int $id = 0;

	private bool $is_reconnect = false;

	/**
	 * Prepares the HTTP headers and environment for a Server-Sent Events stream.
	 *
	 * Disables buffering and compression, sets correct headers, and ends any existing output buffers.
	 */
	protected function setup_headers(): void {
		// phpcs:disable
		$previous = error_reporting( error_reporting() ^ E_WARNING ); // Disable warnings temporarily

		// Required headers for SSE
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );

		// Prevent Apache buffering
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', 1 );
		}

		// Disable PHP buffering
		@ini_set( 'output_buffering', 'off' );
		@ini_set( 'zlib.output_compression', 0 );
		@ini_set( 'implicit_flush', 1 );

		// NGINX-specific buffering control
		if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) && stripos( $_SERVER['SERVER_SOFTWARE'], 'nginx' ) !== false ) {
			header( 'X-Accel-Buffering: no' );
			header( 'Content-Encoding: none' );
		}

		$this->id = intval( wp_unslash( $_SERVER['HTTP_LAST_EVENT_ID'] ?? 0 ) );
		$this->is_reconnect = isset( $_SERVER['HTTP_LAST_EVENT_ID'] );

		// Restore error reporting previous state.
		error_reporting( $previous );

		// Prevent script timeout
		set_time_limit( 0 );

		// Clean existing output buffers
		while ( ob_get_level() != 0 ) {
			ob_end_flush();
		}

		ob_implicit_flush( 1 );
		flush();

		// phpcs:enable
	}

	/**
	 * Starts the event stream and repeatedly invokes the provided callback.
	 *
	 * The callback should use emit_event() to send data to the client.
	 *
	 * @param callable $callback The function that emits events. Called continuously while connected.
	 */
	public function listen( callable $callback ): void {
		$this->setup_headers();

		// Initial padding to prevent browser-side buffering (especially in IE)
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo ':' . str_repeat( ' ', 2048 ) . "\n\n";
		flush();

		$start = time();

		echo 'retry: ' . 1000 . "\n";

		while ( $this->connected ) {
			$upTime = ( time() - $start );

			if ( 0 === $upTime % 300 ) {
				// No updates needed, send a comment to keep the connection alive.
				// From https://developer.mozilla.org/en-US/docs/Server-sent_events/Using_server-sent_events
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo ': ' . sha1( mt_rand() ) . "\n\n";
			}

			try {
				call_user_func( $callback );
			} catch ( \Exception $e ) {

				$this->emit_event( [
					'event'   => 'error',
					'message' => $e->getMessage(),
					'code'    => $e->getCode(),
				] );
			}

			@ob_flush();
			flush();

			// if the connection has been closed by the client we better exit the loop
			if ( connection_aborted() || $upTime > 600 ) {
				break;
			}

			// Prevent tight infinite loop
			usleep( 100000 ); // 0.1 second
		}//end while
	}

	public function terminate(): void {
		$this->connected = false;
		sleep( 1 ); // Delay to allow client to receive final message
		exit;
	}

	/**
	 * Emits a Server-Sent Event to the client.
	 *
	 * @param array $data {
	 *     The data to send in the event.
	 *
	 * @type string $event Optional. The event name. Defaults to 'message'.
	 * @type string $type Optional. message type for js.
	 * @type mixed $n Additional fields included as JSON in the data payload.
	 * }
	 *
	 * @param bool  $terminate Optional. Whether to terminate the connection after sending. Default false.
	 *
	 * @return void
	 */
	public function emit_event( array $data = [], bool $terminate = false ): void {
		$event = $data['event'] ?? 'message';
		unset( $data['event'] );

		echo "id: {$this->get_new_id()}\n";// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "event: {$event}\n";// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo 'data: ' . wp_json_encode( $data ) . "\n\n";

		// Browser padding for IE
		echo ':' . str_repeat( ' ', 2048 ) . "\n\n";// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		@ob_flush();
		flush();

		if ( $terminate ) {
			$this->terminate();
		}
	}

	public function get_new_id(): int {
		return $this->id ++;
	}
}

// End of file event-stream-server.php.
