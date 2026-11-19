# AGENTS.md

## Writing

Every string a person reads follows [docs/writing.md](docs/writing.md). That covers page and card text, dialogs, toasts, form errors and command-line output. Read it before adding or changing any of them. In short:

1. Cut what the screen already says. A page or card gets no description when its title covers it.
2. Labels are phrases and explanations are sentences.
3. Be specific: name the thing and what happens to it, never "Are you sure?".
4. Errors say what went wrong, then what to do, with the fix as its own sentence at the end.
5. Write the way you'd say it: contractions, active voice, no idioms, nothing acting like a person.
6. Use "your" only when the sentence needs it, and never "we".
7. Use one word per thing everywhere it appears.

## Issue references

When a change has a GitHub issue, end both its changelog entry and its commit body with it:

- Changelog: `([#123](https://github.com/ConvoyPanel/panel/issues/123))`, or `([#123](…), in part)` when the change covers only some of the issue.
- Commit body: `Closes #123` on its own last line, or `Refs #123` when the change covers only some of the issue.
