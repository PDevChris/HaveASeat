# 🪑 HaveASeat

**HaveASeat** is a **reworked and enhanced** PocketMine-MP plugin that allows players to **sit on stair blocks** like chairs. This version adds **extra features, improved stability, and modern PMMP 5.x support**, making it perfect for **lobbies, hubs, roleplay servers, and spawn areas**.

Built for **PocketMine-MP 5.0.0+**.

---

## ✨ Features

* Reworked version with **extra configuration options**

* Modernized for **PocketMine-MP 5.x**

* Sit on **stair blocks or slabs** by right-clicking

* Optional **/sit** command (allows players to sit without clicking a stair)

* Prevents multiple players from sitting on the same stair

* Fully **configurable** via `config.yml`

* Supports per-world enabling

* Damage protection while sitting

* Automatic stand-up when block breaks or player moves

* No texture pack required

---

## 📦 Installation

1. Download the **HaveASeat.phar** file
2. Place it in your server’s `plugins/` folder
3. Start the server once to generate the config
4. Edit `config.yml` as needed
5. Restart the server

---

## ⚙️ Configuration

```yaml
apply-worlds: true
# true = enable in all worlds
# or specify worlds: world, lobby, hub

allow-seat-high-height: false
# Allow sitting if the stair is higher than the player

allow-seat-upsidedown: false
# Allow sitting on upside-down stairs

allow-seat-while-sneaking: true
# Allow sitting while sneaking

stand-up-when-break-block: true
# Stand up if the stair block is broken

disable-damage-when-sit: true
# Prevent damage while sitting

register-sit-command: true
# Enable /sit command

send-tip-when-sit: "You are sitting on @b at @x @y @z"
# Placeholders:
# @b = block name
# @x @y @z = coordinates

try-to-sit-already-inuse: "This @b is already used by @p"
# Placeholders:
# @b = block name
# @p = sitting player's name
```

---

## 🕹️ Usage

* **Right-click** a stair block to sit
* Use **/sit** to sit on the stair you are looking at

Players will automatically stand up when:

* They move
* The stair block breaks
* They disconnect
* They change worlds

---

## 🔐 Permissions

| Permission              | Description          | Default |
| ----------------------- | -------------------- | ------- |
| `haveaseat.use`         | Allows player to sit | true    |
| `haveaseat.command.sit` | Allows use of /sit   | true    |

---

## 🧩 Compatibility

* ✅ PocketMine-MP **5.0.0+**
* ✅ PHP **8.1+**

---

## 🚀 Planned Features

* Permission-based per-world control
* Sitting on slabs and custom blocks
* Optional animations via resource pack
* Player cooldowns

---

## 🐞 Issues & Support

If you encounter bugs or have feature requests:

* Open an issue on GitHub
* Provide server version, plugin version, and error logs


