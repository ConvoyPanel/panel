# Writing interface and CLI text

These rules cover every string a person reads in Convoy: page and card text, dialogs, toasts, form errors, Artisan commands, and the container start-up scripts. Check new or changed text against them before committing it.

## Checklist

1. **Cut what the screen already says.** If a title, label, icon or the control itself already says it, don't repeat it. A page or card gets no description when its title covers it, and the line under an empty state never repeats that state's heading. If there's nothing the reader can act on, write nothing.
2. **Labels are phrases, explanations are sentences.** Titles, headings, buttons, field labels, badges and CLI status lines are the shortest phrase that works, with no period. Errors, dialog bodies and results are full sentences.
3. **Be specific.** Name the thing and what happens to it. A delete dialog asks `Delete <name>?` and its body says what else goes with it. It never asks `Are you sure?`.
4. **Errors say what went wrong, then what to do.** Don't blame the reader, and don't use "oops", "sorry" or "please". Don't write "Something went wrong" when the cause is known. Put the fix in its own sentence at the end.
5. **Write the way you'd say it.** Use contractions and active voice. Avoid idioms, words the reader doesn't use ("poll", "resource", "string"), and things acting like people ("v4 keeps serving", "the console answered"). Read it aloud before committing.
6. **Use "your" and "my" only when the sentence needs them.** Write `Backups`, not `Your Backups`. Never use "we".
7. **Use one word per thing.** An action or object has the same name on the button, in the dialog title, in the toast and in the docs.

## Conventions

| | Use | Don't use |
|---|---|---|
| Capitalization | Title Case for titles, headings, buttons, tabs, field labels and badges. Sentence case for descriptions, messages, toasts and placeholders. | A sentence-case title on one page and Title Case on the next |
| Empty states | `No backups`. Under a label that already names the thing, `None`. Add a second line only when there's an action to take. | `There are no backups.`, `You have no servers.` |
| Delete dialogs | Title `Delete <name>?`. The body starts with "Deleting this \<thing\>" and states the consequence: `Deleting this template only removes it from Convoy and keeps it on the Proxmox node, but servers can no longer be built or reinstalled from it.` End with `This can't be undone.` when that's true and not obvious. | `Are you sure you want to delete this?`, `This action is irreversible.` |
| Create flows | The button and its dialog title use the same words: `Create User`. | `New User` opening `Create a User` |
| Toasts | Say what happened: `Restoring from <name>`, `Power action sent.` | `Successfully sent power action. It may take a moment to process.` |
| Form errors | `Required`, `Must be at most 191 characters`, `Must be at least 8 characters, with an uppercase letter, a lowercase letter, a number and one of !@#$%^&*` | `String must contain at least 1 character(s)`, `Please limit up to 40 characters` |
| Admin area | `Admin` in menus, `Exit Admin` in the banner | `Admin Control Panel`, `Admin Center`, `Administration` |
| Waiting screens | The title says the state, and the message says how long it takes or what to do: `Installing` / `This can take up to 15 minutes.` | `Your server is being installed. This can take from 1-15 minutes.` |

## Command-line output

Artisan commands and the container start-up scripts follow the checklist, plus these rules (from [clig.dev](https://clig.dev/)):

- **Keep other programs' output out of the terminal.** Compose, pgloader, migrations and PHP warnings go to the log. When a command fails, show the end of its output above the error.
- **Status lines are short phrases.** Examples: `==> Downloading images` and `Saving the backup to <path> ... done`. They start with a capital letter. After `error:` or `warning:`, the text starts in lowercase.
- **Errors end with the fix:** `error: couldn't download Convoy's images. Check that this host can reach ghcr.io and Docker Hub, then run the installer again.` Write "run the installer again" or "run the import again", not "re-run" or "run this again".
- **After a step that changes something, say what changed and what to run next.**
- **Name things the way the operator does.** Write `v4` for the old panel and `v4 install` for its directory. Don't mention internal services the operator hasn't heard of, such as "the console". Say what failed instead: `the upload failed with HTTP 502.`
- **End every `--help` with two or three real examples.**

## Before and after

| Before | After | Rule |
|---|---|---|
| `Everything currently in a state you have to do something about.` (under "Attention") | Removed | 1 |
| `No failed servers, no failed backups.` (under "All clear") | Removed | 1 |
| `Live panel health and capacity.` (under "Overview") | Removed | 1 |
| `Are you sure you want to delete this token? Subsequent requests to the API relying on this token will immediately fail.` | `Anything using this token loses access to the API immediately.` | 1, 3 |
| `Failed to poll server status. Retrying in 5 seconds...` | `Couldn't get the server's status. Retrying in 5 seconds...` | 4, 5 |
| `Start your server on a fresh slate.` | `Erases this server's disk and installs the template you pick.` | 3, 5 |
| `Okay, I got it` | `Done` | 2 |
| `Is TLS Enabled?` | `Use TLS` | 2 |
| `v4 wasn't changed and keeps running.` | `This dry run didn't change v4, which is still online.` | 5 |
| `no Convoy 5 install in $dir (set CONVOY_DIR if it is somewhere else).` | `there's no Convoy 5 install in $dir. If it's somewhere else, set CONVOY_DIR to its directory.` | 4 |

## Sources

- [Apple Human Interface Guidelines: Writing](https://developer.apple.com/design/human-interface-guidelines/writing)
- [WWDC22: Writing for interfaces](https://developer.apple.com/videos/play/wwdc2022-10037)
- [Microsoft Writing Style Guide: top 10 tips](https://learn.microsoft.com/en-us/style-guide/top-10-tips-style-voice)
- [GitHub Primer: empty states](https://primer.style/ui-patterns/empty-states)
- [Command Line Interface Guidelines](https://clig.dev/)
