![Version 4 release announcement banner](https://github.com/ConvoyPanel/panel/assets/37554696/4629321b-7214-4eb1-8cc5-85c89229b5bf)


![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/convoypanel/panel/tests.yml?branch=develop)
![Discord](https://img.shields.io/discord/746612878261616700?label=Discord&logo=Discord&logoColor=white)
![GitHub Releases](https://img.shields.io/github/downloads/convoypanel/panel/latest/total)

# Convoy

Convoy is a modern and performant KVM server management panel for hosting businesses. It's built with PHP and React and proven technology like Proxmox, making it easier to drop Convoy in an existing system.

Stop paying hundreds of dollars for unreliable and slow software. Subscribe to a license to use Convoy today for $6/node/mo

![Screenshot of Convoy](https://imgur.com/GsORIRQ.png)

## Install

Convoy 5 runs as a container image on a dedicated host with Docker:

```bash
curl -fsSL https://install.convoypanel.com/5 | sudo bash
```

To upgrade a v4 install, rehearse on a copy of its data first (v4 keeps serving), then run the import for real:

```bash
curl -fsSL https://install.convoypanel.com/5 | sudo bash -s -- --import /var/www/convoy --dry-run
convoy import-v4 /var/www/convoy
```

[docs/deployment.md](docs/deployment.md) covers the options, updating with `convoy upgrade`, and what the import does.

## Documentation

-   [Panel Documentation](https://docs.convoypanel.com)
-   [Discord Community](https://discord.convoypanel.com)

## Acknowledgements

Please [visit this page](https://convoypanel.com/docs/project/about.html#acknowledgements) on our website to view acknowledgements.

## License

Convoy is licensed under our own proprietary license.
