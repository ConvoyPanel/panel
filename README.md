![Version 4 release announcement banner](https://github.com/ConvoyPanel/panel/assets/37554696/4629321b-7214-4eb1-8cc5-85c89229b5bf)


![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/convoypanel/panel/tests.yml?branch=develop)
![Discord](https://img.shields.io/discord/746612878261616700?label=Discord&logo=Discord&logoColor=white)
![GitHub Releases](https://img.shields.io/github/downloads/convoypanel/panel/latest/total)
[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2FConvoyPanel%2Fpanel.svg?type=shield)](https://app.fossa.com/projects/git%2Bgithub.com%2FConvoyPanel%2Fpanel?ref=badge_shield)

# Convoy

Convoy is a modern and performant KVM server management panel for hosting businesses. It's built with PHP and React and proven technology like Proxmox, making it easier to drop Convoy in an existing system.

Stop paying hundreds of dollars for unreliable and slow software. Subscribe to a license to use Convoy today for $6/node/mo

![Screenshot of Convoy](https://imgur.com/GsORIRQ.png)

## Documentation

-   [Panel Documentation](https://docs.convoypanel.com)
-   [Discord Community](https://discord.convoypanel.com)

## Installation

Convoy installs onto a dedicated host with one command:

```bash
curl -fsSL https://install.convoypanel.com | sudo bash
```

See [docs/deployment.md](docs/deployment.md) for requirements, TLS, upgrades and
running against an external database, and [docs/configuration.md](docs/configuration.md)
for every supported setting.

## Local Development

Local dev runs on [ddev](https://ddev.com) (Postgres 17):

```bash
ddev start
ddev composer install
ddev artisan migrate
ddev npm install && ddev npm run build   # or: ddev npm run dev for HMR
```

The app is served at https://convoy.ddev.site. See [AGENTS.md](AGENTS.md) for details on
running Artisan/Composer/npm, regenerating typed frontend artifacts, and database snapshots.

## Acknowledgements

Please [visit this page](https://convoypanel.com/docs/project/about.html#acknowledgements) on our website to view acknowledgements.

## License

Convoy is licensed under our own proprietary license.

[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2FConvoyPanel%2Fpanel.svg?type=large)](https://app.fossa.com/projects/git%2Bgithub.com%2FConvoyPanel%2Fpanel?ref=badge_large)
