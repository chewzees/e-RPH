<?php
require_once 'db_connect.php';
secure_session_start();

// Force JSON-only output and turn PHP warnings/notices into exceptions
header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);
set_error_handler(function($severity, $message, $file, $line){
	throw new ErrorException($message, 0, $severity, $file, $line);
});
register_shutdown_function(function(){
	$err = error_get_last();
	if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
		http_response_code(500);
		echo json_encode([ 'success' => false, 'error' => 'Fatal error: ' . $err['message'] ]);
	}
});

// Helpers
function json_error($message, $code = 400) {
	http_response_code($code);
	echo json_encode([ 'success' => false, 'error' => $message ]);
	exit;
}

try {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		json_error('Invalid request method', 405);
	}

	$csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
	if (!verify_csrf_token($csrf)) {
		json_error('Invalid CSRF token', 403);
	}

	$mode = isset($_POST['mode']) && $_POST['mode'] === 'append' ? 'append' : 'replace';

	if (!isset($_FILES['files'])) {
		json_error('No files uploaded');
	}

	$files = $_FILES['files'];
	$max_files = 43;

	// Normalize uploaded files structure
	$uploads = [];
	if (is_array($files['name'])) {
		for ($i = 0; $i < count($files['name']); $i++) {
			$uploads[] = [
				'name' => $files['name'][$i],
				'tmp_name' => $files['tmp_name'][$i],
				'error' => $files['error'][$i],
				'size' => $files['size'][$i],
				'type' => $files['type'][$i],
			];
		}
	} else {
		$uploads[] = $files;
	}

	if (count($uploads) > $max_files) {
		$uploads = array_slice($uploads, 0, $max_files);
	}

	$allowed_ext = ['jpg','jpeg','png','gif','webp'];
	$target_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'background' . DIRECTORY_SEPARATOR;
	$target_url_base = 'uploads/background/';

	// Ensure directory exists
	if (!is_dir($target_dir)) {
		if (!mkdir($target_dir, 0777, true)) {
			json_error('Failed to create upload directory');
		}
	}

	// If replace mode, clear existing files
	if ($mode === 'replace') {
		$existing = glob($target_dir . '*');
		if ($existing) {
			foreach ($existing as $ex) {
				if (is_file($ex)) { @unlink($ex); }
			}
		}
	}

	$stored_urls = [];
	$finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;

	foreach ($uploads as $up) {
		if (!isset($up['error']) || $up['error'] !== UPLOAD_ERR_OK) {
			continue; // skip invalid
		}
		$tmp = $up['tmp_name'];
		$orig = $up['name'];
		$ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
		if (!in_array($ext, $allowed_ext, true)) {
			continue;
		}
		// Basic image validation
		$mime_ok = true;
		if ($finfo) {
			$mime = finfo_file($finfo, $tmp);
			$mime_ok = is_string($mime) && preg_match('/^image\//', $mime);
		}
		if (!$mime_ok) { continue; }
		if (@getimagesize($tmp) === false) { continue; }

		$unique = uniqid('bg_', true) . '.' . $ext;
		$dest = $target_dir . $unique;
		if (!move_uploaded_file($tmp, $dest)) {
			continue;
		}
		$stored_urls[] = $target_url_base . $unique;
		if (count($stored_urls) >= $max_files) { break; }
	}

	if ($finfo) { finfo_close($finfo); }

	if (empty($stored_urls)) {
		json_error('No valid images were uploaded');
	}

	echo json_encode([ 'success' => true, 'urls' => $stored_urls ]);
	exit;

} catch (Throwable $e) {
	json_error('Server error: ' . $e->getMessage(), 500);
}


