# Adminer, served by Panel-Caddy from its own caddy FPM pool at the fixed /adminer/; h-add-sys-adminer deploys it
# to /etc/caddy/apps/adminer.conf, imported inside the :8083 site block. Not behind forward_auth, like phpMyAdmin:
# the gate is Adminer's own DB login plus the firewall on :8083. The login-servers plugin limits the login form to
# the local servers, so it cannot be pointed at another host; h-add-sys-adminer refuses to install without it.
redir /adminer /adminer/ 308
handle_path /adminer/* {
    root * /usr/share/adminer
    php_fastcgi unix//run/hestia-adminer.sock
    file_server
}
