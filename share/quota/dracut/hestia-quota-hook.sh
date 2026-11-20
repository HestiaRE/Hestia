#!/bin/sh
# HestiaRE (#1329): sourced by dracut before the root is mounted. Called, not sourced: an exit in it would end the hook.
/sbin/hestia-quota-arm || :
