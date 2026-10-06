# rspamd controller web UI, gated behind the panel admin session, then reverse-proxied to the controller's
# unix socket (see share/rspamd/local.d/worker-controller.inc). The panel page /list/rspamd/ frames it, same-origin.
# Deployed to /etc/caddy/apps/rspamd.conf by h-add-sys-rspamd, removed by h-delete-sys-rspamd.
#
# Access control has two independent layers:
#  1. forward_auth asks the panel (rspamd-auth.php), which answers 2xx only for an admin session.
#  2. The socket is mode 0660, group _rspamd-ctrl, held by the Caddy process alone through a systemd drop-in
#     (share/rspamd/caddy-service.d/rspamd-ctrl.conf), never by the caddy user whose FPM pools would inherit it.
#     This keeps a customer with shell access off the controller.
#
# rspamd treats a unix-socket client as secure, so the panel path needs no rspamd login. X-Forwarded-* is
# stripped anyway, so a forged header can never make rspamd fall back to password auth against a spoofed client.
redir /rspamd /rspamd/ 308
handle /rspamd/* {
    forward_auth unix//run/hestia-php.sock {
        uri /rspamd-auth.php
        transport fastcgi {
            env SCRIPT_FILENAME /usr/local/hestia/web/rspamd-auth.php
            env SCRIPT_NAME /rspamd-auth.php
        }
    }
    uri strip_prefix /rspamd
    reverse_proxy unix//run/rspamd/controller.sock {
        header_up -X-Forwarded-For
        header_up -X-Forwarded-Proto
        header_up -X-Forwarded-Host
    }
}
