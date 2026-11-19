# HestiaRE operator docs

For whoever runs a box: what a subsystem promises, what it does not, and which command to reach
for. On an installed box they are under `/usr/local/hestia/docs/`.

| document | for |
|---|---|
| [`troubleshooting.md`](troubleshooting.md) | an install that stopped: resume, force a stage, regenerate the recipe; a failed update |
| [`update.md`](update.md) | updating a box: what a run does in order, the run directory, the point of no return |
| [`backup.md`](backup.md) | backing up and restoring a customer: modes, retention, adoption, remote targets, restic |
| [`docker.md`](docker.md) | rootless Docker per customer: the model, publishing, databases, limits, what to tell a customer |

The release notes are in [`CHANGELOG.md`](../CHANGELOG.md), how to report a vulnerability in
[`SECURITY.md`](../SECURITY.md).
