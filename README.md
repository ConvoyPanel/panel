Documentation can be found here: https://docs.convoypanel.com

Discord server: https://discord.convoypanel.com

## Debugging via XDebug

This method is Docker ONLY. I will not provide any support for non-Docker users.

1. Find your IP address.

### MacOS
`ipconfig getifaddr en0`

### Windows WSL
`grep nameserver /etc/resolv.conf | cut -d ' ' -f2`

### Debian & Debian-based distributions
`hostname -I | cut -d ' ' -f1`

2. Replace `999.999.999.999` in `XDEBUG_CONFIG="client_host=999.999.999.999 ...` with your IP address

3. Use these guides to configure your IDE

### PHPStorm Guide 1 (untested)
https://web.archive.org/web/20210804050747/https://www.srijan.net/resources/how-to-run-xdebug-using-phpstorm-in-docker

### PHPStorm Guide 2 (untested)
https://dev.to/jackmiras/xdebug-in-phpstorm-with-docker-2al8

### VSCode Guide (tested & working)
https://dev.to/jackmiras/xdebug-in-vscode-with-docker-379l