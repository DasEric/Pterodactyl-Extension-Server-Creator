# Server Creator for Pterodactyl

[![Release](https://img.shields.io/github/v/release/CCBlueX/Pterodactyl-Extension-Server-Creator?style=flat-square)](https://github.com/CCBlueX/Pterodactyl-Extension-Server-Creator/releases/latest)
[![Downloads](https://img.shields.io/github/downloads/CCBlueX/Pterodactyl-Extension-Server-Creator/total?style=flat-square)](https://github.com/CCBlueX/Pterodactyl-Extension-Server-Creator/releases)
[![Pterodactyl](https://img.shields.io/badge/Pterodactyl-2.0-0e4688?style=flat-square)](https://pterodactyl.io)
[![License](https://img.shields.io/github/license/CCBlueX/Pterodactyl-Extension-Server-Creator?style=flat-square)](LICENSE)

Let users create their own servers within a quota, and duplicate servers in one click.

![Create a server](docs/screenshots/create.png)

## Install

1. Download `server-creator-<version>.pteroext` from the [latest release](https://github.com/CCBlueX/Pterodactyl-Extension-Server-Creator/releases/latest).
2. Install and enable it under **Admin → Extensions**.
3. Under **Admin → Server Creator**, add a game (or pick one under **Popular games**) and give users a quota.

## Features

- **Game picker**: users pick a game, then size it with sliders in GiB and cores, or click the size to type it. Shows what's left and marks a recommended size.
- **Popular games**: Counter-Strike 2, Valheim, Terraria, Palworld, Project Zomboid and more come with sizes and artwork. Missing eggs are imported from the Pterodactyl egg catalog.
- **Automatic extra ports**: select the egg variables that need ports. Their values are filled before validation and installation; users do not enter port numbers. Automatic mode accepts non-consecutive free ports on the same node and IP, regardless of variable input order.
- **Consecutive ports**: existing games and presets retain their ordered port blocks. Use the advanced consecutive mode when an egg needs fixed offsets from its primary port. Minimum port count can reserve ports without variables; selected variables automatically increase the required count.
- **Port ranges**: limit a game to ports such as `25565-25600`, so each game gets its own ports on a shared node.
- **Quotas**: limit servers, memory, disk, CPU, databases, backups and ports, for everyone or per user.
- **Resources page**: owners resize, switch to another game and delete (opt-in) on their server's Resources page. A switch brings the new game's egg, limits and ports, and egg changes from other extensions keep the game and ports in step.
- **Safe placement**: new servers only go to nodes with room, in the locations you choose.
- **Duplicate**: copy any server from its admin page, optionally with all its files.

![Resources page](docs/screenshots/resources.png)

![Admin games and quotas](docs/screenshots/admin.png)

Users only see the **Create server** button once their quota allows at least one server. The default quota is 0.

### Updating port configuration

The extension migration adds `port_mode` without changing existing servers or variable order. Existing games and presets remain in consecutive mode; newly added custom games default to automatic mode. Port variables must be ENV keys belonging to the selected egg, not display names. The admin selector shows both. The allocation limit remains separate from the ports required by the game; this update does not increase user quotas.

Back up the game configuration before changing modes. Switching an existing game to automatic mode opts into name-sorted variable assignment for subsequent creation/game-switch operations. Do not downgrade an automatic configuration to an older release without first reviewing its consecutive-port requirements. Updating the extension alone does not reassign running servers.

---

Inspired by the Pelican plugins User Creatable Servers, User Game Server Creator, Egg Images and Duplicate Server.
