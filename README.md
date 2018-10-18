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

# License

The MIT License (MIT)

Copyright (c) 2018 @hasegawa-tomoki ( @tomzoh )

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

# Legal

The purpose of this project was to study all the capabilities of PHP.

It does not have any commercial or profitable intentions.

The user is responsible to use this code and its content in the terms of the law.

The author is completely against piracy and respects all the copyrights, trademarks and patents of Nintendo.
