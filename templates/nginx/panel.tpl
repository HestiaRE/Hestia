#=========================================================================#
# Panel Proxy Template (#878)                                             #
# DO NOT MODIFY THIS FILE! CHANGES WILL BE LOST WHEN REBUILDING DOMAINS   #
# https carries the panel. Plain http serves the site as the default      #
# template does, until the force-SSL switch sends it to https.            #
# The https part is the same in share/web/nginx/panel.tpl.                #
#=========================================================================#

server {
	listen      %ip%:%web_port%;
	listen      [%ip6%]:%web_port%;
	server_name %domain_idn% %alias_idn%;
	include %home%/%user%/conf/web/%domain%/nginx.crowdsec.conf*;
	include %home%/%user%/conf/web/%domain%/nginx.botlimit.conf*;
	root        %docroot%;
	index       index.php index.html index.htm;
	access_log  /var/log/%web_system%/domains/%domain%.log combined;
	access_log  /var/log/%web_system%/domains/%domain%.bytes bytes;
	error_log   /var/log/%web_system%/domains/%domain%.error.log error;

	include %home%/%user%/conf/web/%domain%/nginx.forcessl.conf*;

	location ~ /\.(?!well-known\/) {
		deny all;
		return 404;
	}

	location / {
		location ~* ^.+\.(jpeg|jpg|png|webp|gif|bmp|ico|svg|css|js)$ {
			expires max;
			fastcgi_hide_header "Set-Cookie";
		}

		location ~ [^/]\.php(/|$) {
			try_files $uri =404;

			include /etc/nginx/fastcgi_params;

			fastcgi_index index.php;
			fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;

			fastcgi_pass %backend_lsnr%;

			include %home%/%user%/conf/web/%domain%/nginx.fastcgi_cache.conf*;
		}
	}

	location /error/ {
		alias %home%/%user%/web/%domain%/document_errors/;
	}

	location /vstats/ {
		alias   %home%/%user%/web/%domain%/stats/;
		include %home%/%user%/web/%domain%/stats/auth.conf*;
	}

	include /etc/nginx/conf.d/phpmyadmin.inc*;
	include /etc/nginx/conf.d/phppgadmin.inc*;
	include %home%/%user%/conf/web/%domain%/nginx.conf_*;
}
#=HESTIARE-SSL-VHOST=#
#=========================================================================#
# Panel Proxy Template (#878)                                             #
# DO NOT MODIFY THIS FILE! CHANGES WILL BE LOST WHEN REBUILDING DOMAINS   #
#=========================================================================#

server {
	listen      %ip%:%front_ssl_port% ssl;
	listen      [%ip6%]:%front_ssl_port% ssl;
	server_name %domain_idn% %alias_idn%;
	include %home%/%user%/conf/web/%domain%/nginx.crowdsec.conf*;
	include %home%/%user%/conf/web/%domain%/nginx.botlimit.conf*;
	error_log   /var/log/%web_system%/domains/%domain%.error.log error;
	access_log  /var/log/%web_system%/domains/%domain%.log combined;
	access_log  /var/log/%web_system%/domains/%domain%.bytes bytes;

	ssl_certificate     %ssl_pem%;
	ssl_certificate_key %ssl_key%;

	# TLS 1.3 0-RTT anti-replay
	if ($anti_replay = 307) { return 307 https://$host$request_uri; }
	if ($anti_replay = 425) { return 425; }

	include %home%/%user%/conf/web/%domain%/nginx.hsts.conf*;

	# No nginx.location.d include: the cache fragment lands there, and panel pages must never be cached.
	location / {
		# Loopback, so closing the panel port to the outside keeps this path; the panel's own certificate is not checked.
		proxy_pass https://127.0.0.1:%panel_port%;

		# any proxy_set_header here cancels ALL inherited ones. Host carries the domain for the CSRF origin check,
		# X-Real-IP the client for login, fail2ban and the session pin.
		proxy_http_version 1.1;
		proxy_set_header Host $host;
		proxy_set_header X-Real-IP $remote_addr;
		proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
		proxy_set_header X-Forwarded-Proto $scheme;
		# The panel's FastCGI read_timeout; certificate orders and PHP installs run inside the request.
		proxy_read_timeout 600s;
		proxy_send_timeout 600s;

		# A restart of caddy or hestia-php cuts the running request: a page that retries beats a bare 502.
		proxy_intercept_errors on;
		error_page 502 504 = @panel_restart;
	}

	location @panel_restart {
		default_type text/html;
		add_header Cache-Control "no-store" always;
		return 503 '<!doctype html><html><head><meta charset="utf-8"><meta http-equiv="refresh" content="5"><title>Panel</title></head><body style="font-family:sans-serif;text-align:center;padding-top:4em"><p>The panel is restarting or busy. This page reloads in a few seconds.</p></body></html>';
	}

	include %home%/%user%/conf/web/%domain%/nginx.ssl.conf_*;
}
