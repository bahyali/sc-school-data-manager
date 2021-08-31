debug:
	php -dxdebug.mode=debug -dxdebug.start_with_request=yes -dxdebug.client_port=9003 -dxdebug.client_host=127.0.0.1 -S localhost:8000 server.php