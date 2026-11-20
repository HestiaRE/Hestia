#!/bin/bash
# HestiaRE (#1329): the quota one-shot for a box whose initramfs dracut builds. The premount script is the same file
# the initramfs-tools hook ships; dracut sources its hooks, so it runs as its own program and its exits stay its own.

check() { return 0; }

depends() { return 0; }

install() {
	inst_multiple tune2fs e2fsck grep readlink cat
	inst_script "$moddir/hestia-quota.premount" /sbin/hestia-quota-arm
	inst_hook pre-mount 90 "$moddir/hestia-quota-hook.sh"
}
