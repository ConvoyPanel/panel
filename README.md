# Convoy
[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2FConvoyPanel%2Fpanel.svg?type=shield)](https://app.fossa.com/projects/git%2Bgithub.com%2FConvoyPanel%2Fpanel?ref=badge_shield)

Documentation can be found here: https://docs.convoypanel.com

Discord server: https://discord.convoypanel.com

## Debugging via XDebug

This method is Docker ONLY. I will not provide any support for non-Docker users.

1. Find your IP address.

#### MacOS
`ipconfig getifaddr en0`

#### Windows WSL
`grep nameserver /etc/resolv.conf | cut -d ' ' -f2`

#### Debian & Debian-based distributions
`hostname -I | cut -d ' ' -f1`

2. Replace `999.999.999.999` in `XDEBUG_CONFIG="client_host=999.999.999.999 ...` with your IP address

3. Use these guides to configure your IDE

#### PHPStorm Guide 1 (untested)
https://web.archive.org/web/20210804050747/https://www.srijan.net/resources/how-to-run-xdebug-using-phpstorm-in-docker

#### PHPStorm Guide 2 (untested)
https://dev.to/jackmiras/xdebug-in-phpstorm-with-docker-2al8

#### VSCode Guide (tested & working)
https://dev.to/jackmiras/xdebug-in-vscode-with-docker-379l


VSCode PHP Debugger Configuration
```
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for XDebug on Docker",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/var/www/": "${workspaceFolder}"
      }
    }
  ]
}

```

## Acknowledgements

Convoy wouldn't have been possible without these organizations and people

- Advin Servers - provided several development servers (at least $200 of hardware) to help me develop this SaaS
- Kjartann#0149 (Discord) - donated over $200 to Convoy development and encouraged me to keep on working on this panel!
- Pterodactyl Panel - provided the architecture and a lot of backend/boilerplate code for Convoy.

## License
[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2FConvoyPanel%2Fpanel.svg?type=large)](https://app.fossa.com/projects/git%2Bgithub.com%2FConvoyPanel%2Fpanel?ref=badge_large)