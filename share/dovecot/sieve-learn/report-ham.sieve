# HestiaRE - hand the message to the learner. Same for both dovecot generations; only the rules
# that trigger it differ. :copy so a failing learn never swallows the mail the customer moved.
require ["vnd.dovecot.pipe", "copy", "imapsieve", "environment", "variables"];

if environment :matches "imap.user" "*" {
	set "username" "${1}";
}

# Out of Spam into a trash folder is a delete, not a verdict.
if environment :matches "imap.mailbox" "*" {
	set "mailbox" "${1}";
}
if string :is "${mailbox}" [ "Trash", "Deleted Messages" ] {
	stop;
}

pipe :copy "hestia-rspamd-learn" [ "ham", "${username}" ];
