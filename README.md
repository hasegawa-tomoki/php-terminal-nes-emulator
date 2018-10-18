An NES emulator written in PHP

![demo](https://github.com/hasegawa-tomoki/php-terminal-nes-emulator/blob/master/demo.gif)

Based on [bokuweb/flownes](https://github.com/bokuweb/flownes), [gabrielrcouto/php-terminal-gameboy-emulator](https://github.com/gabrielrcouto/php-terminal-gameboy-emulator).

Blog entry: https://www.hasegawa-tomoki.com/blog/2018/10/16/php-terminal-nes-emulator/ (Japanese)

# Requirements

* PHP >= 7.0
* Composer
* NES rom

# Install

```
$ git clone https://github.com/hasegawa-tomoki/php-terminal-nes-emulator.git
$ cd php-terminal-nes-emulator
$ composer install
```

[No composer?](https://getcomposer.org/doc/00-intro.md#locally)

# Run

```
$ php boot.php your-rom-file.nes
```

Compatible with mapper 0 rom files.

If you want to see colorful pictures, run with '-cpng' option.

```
$ php boot.php some.nes -cpng
```

You can see beautiful screenshots in `./screen` directory.

# Controls

```
  [W]
[A] [F]        [,] [.]
  [D]   [N] [M]
```

# Credit

* [bokuweb/flownes](https://github.com/bokuweb/flownes)
* [php-terminal-gameboy-emulator](https://github.com/gabrielrcouto/php-terminal-gameboy-emulator)  


# Legal

The purpose of this project was to study all the capabilities of PHP.

It does not have any commercial or profitable intentions.

The user is responsible to use this code and its content in the terms of the law.

The author is completely against piracy and respects all the copyrights, trademarks and patents of Nintendo.
