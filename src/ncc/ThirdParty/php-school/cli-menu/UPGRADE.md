# Upgrade Documentation

This document serves as a reference to upgrade your current cli-menu installation if improvements, deprecations
or backwards compatibility (BC) breakages occur.

## 4.0.0

### BC breaks

* Trait `ncc\PhpSchool\CliMenu\MenuItem\SelectableTrait` was removed. Copy the old code into your menu item 
  if you need it.
* Methods `ncc\PhpSchool\CliMenu\Builder\CliMenuBuilder#setUnselectedMarker()` & `ncc\PhpSchool\CliMenu\Builder\CliMenuBuilder#setSelectedMarker()` were removed.
  Customise markers on the individual item styles: 
  
  ```php
  <?php
  
  use ncc\PhpSchool\CliMenu\Builder\CliMenuBuilder;
  use ncc\PhpSchool\CliMenu\Style\SelectableStyle;
  
  $menu = (new CliMenuBuilder)
      ->modifySelectableStyle(function (SelectableStyle $style) {
          $style->setUnselectedMarker('❅ ')
              ->setSelectedMarker('✏ ')
  
              // disable unselected marker
              ->setUnselectedMarker('')
          ;
      })
      ->build();
  ```
* Method getStyle() was added to interface ncc\PhpSchool\CliMenu\MenuItem\MenuItemInterface. Items must now implement this 
  method. For selectable items use `\ncc\PhpSchool\CliMenu\Style\SelectableStyle` or a subclass of. For static items use 
  `\ncc\PhpSchool\CliMenu\Style\DefaultStyle` or a subclass of.
* `ncc\PhpSchool\CliMenu\MenuStyle` marker methods have been removed. If you were using these directly. Operate on the item
  style object instead.

## 3.0.0

### BC breaks

* Class `ncc\PhpSchool\CliMenu\CliMenuBuilder` has been moved, use 
  `ncc\PhpSchool\CliMenu\Builder\CliMenuBuilder` instead. Please migrate to the new namespace.
* `ncc\PhpSchool\CliMenu\Builder\CliMenuBuilder#addSubMenu` now takes a text and a closure used to configure the submenu. The callback
  invoked with a new instance of `ncc\PhpSchool\CliMenu\Builder\CliMenuBuilder` as a parameter. `addSubMenu` now returns itself instead of
  the sub menu `ncc\PhpSchool\CliMenu\Builder\CliMenuBuilder`. See below for upgrade example.
* Removed `ncc\PhpSchool\CliMenu\Terminal` namespace, the code has been migrated to the `php-school/terminal` package and is 
  largely modified.
* Removed methods `setTerminal`, `getSubMenu`, `getMenuStyle` and `end` from `ncc\PhpSchool\CliMenu\CliMenuBuilder`.
* Removed static method `getDefaultStyleValues` on `ncc\PhpSchool\CliMenu\MenuStyle`.


#### Migrating to new `addSubMenu` method in `CliMenuBuilder`

Previous code:

```php
<?php

use ncc\PhpSchool\CliMenu\CliMenu;
use ncc\PhpSchool\CliMenu\CliMenuBuilder;

require_once(__DIR__ . '/../vendor/autoload.php');

$itemCallable = function (CliMenu $menu) {
    echo $menu->getSelectedItem()->getText();
};

$menu = (new CliMenuBuilder)
    ->setTitle('CLI Menu')
    ->addItem('First Item', $itemCallable)
    ->addLineBreak('-')
    ->addSubMenu('Options')
        ->setTitle('CLI Menu > Options')
        ->addItem('First option', function (CliMenu $menu) {
            echo sprintf('Executing option: %s', $menu->getSelectedItem()->getText());
        })
        ->addLineBreak('-')
        ->end()
    ->build();

$menu->open();
```

Would now become:

```php
<?php

use ncc\PhpSchool\CliMenu\CliMenu;
use \ncc\PhpSchool\CliMenu\Builder\CliMenuBuilder;

require_once(__DIR__ . '/../vendor/autoload.php');

$itemCallable = function (CliMenu $menu) {
    echo $menu->getSelectedItem()->getText();
};

$menu = (new CliMenuBuilder)
    ->setTitle('CLI Menu')
    ->addItem('First Item', $itemCallable)
    ->addLineBreak('-')
    ->addSubMenu('Options', function (CliMenuBuilder $b) {
        $b->setTitle('CLI Menu > Options')
            ->addItem('First option', function (CliMenu $menu) {
                echo sprintf('Executing option: %s', $menu->getSelectedItem()->getText());
            })
            ->addLineBreak('-');
    })
    ->build();

$menu->open();
```
