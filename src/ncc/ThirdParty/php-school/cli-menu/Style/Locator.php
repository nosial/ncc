<?php

declare(strict_types=1);

namespace ncc\PhpSchool\CliMenu\Style;

use ncc\PhpSchool\CliMenu\MenuItem\AsciiArtItem;
use ncc\PhpSchool\CliMenu\MenuItem\CheckboxItem;
use ncc\PhpSchool\CliMenu\MenuItem\LineBreakItem;
use ncc\PhpSchool\CliMenu\MenuItem\MenuItemInterface;
use ncc\PhpSchool\CliMenu\MenuItem\MenuMenuItem;
use ncc\PhpSchool\CliMenu\MenuItem\RadioItem;
use ncc\PhpSchool\CliMenu\MenuItem\SelectableItem;
use ncc\PhpSchool\CliMenu\MenuItem\SplitItem;
use ncc\PhpSchool\CliMenu\MenuItem\StaticItem;
use ncc\PhpSchool\CliMenu\Style\Exception\InvalidStyle;
use function ncc\PhpSchool\CliMenu\Util\mapWithKeys;

class Locator
{
    /**
     * @var array
     */
    private $itemStyleMap = [
        StaticItem::class => DefaultStyle::class,
        AsciiArtItem::class => DefaultStyle::class,
        LineBreakItem::class => DefaultStyle::class,
        SplitItem::class => DefaultStyle::class,
        SelectableItem::class => SelectableStyle::class,
        MenuMenuItem::class => SelectableStyle::class,
        CheckboxItem::class => CheckboxStyle::class,
        RadioItem::class => RadioStyle::class,
    ];

    /**
     * @var array
     */
    private $styles;

    public function __construct()
    {
        $this->styles = [
            DefaultStyle::class => new DefaultStyle(),
            SelectableStyle::class => new SelectableStyle(),
            CheckboxStyle::class => new CheckboxStyle(),
            RadioStyle::class => new RadioStyle()
        ];
    }

    /**
     * For each of our unmodified item styles, we replace ours with the versions
     * from the given style locator.
     *
     * @param Locator $other
     */
    public function importFrom(self $other) : void
    {
        $this->styles = mapWithKeys(
            $this->styles,
            function ($styleClass, ItemStyle $instance) use ($other) {
                return $instance->hasChangedFromDefaults()
                    ? $instance
                    : $other->getStyle($styleClass);
            }
        );
    }

    public function getStyle(string $styleClass) : ItemStyle
    {
        if (!isset($this->styles[$styleClass])) {
            throw InvalidStyle::unregisteredStyle($styleClass);
        }

        return $this->styles[$styleClass];
    }

    public function setStyle(ItemStyle $itemStyle, string $styleClass) : void
    {
        if (!isset($this->styles[$styleClass])) {
            throw InvalidStyle::unregisteredStyle($styleClass);
        }

        if (!$itemStyle instanceof $styleClass) {
            throw InvalidStyle::notSubClassOf($styleClass);
        }

        $this->styles[$styleClass] = $itemStyle;
    }

    public function hasStyleForMenuItem(MenuItemInterface $item) : bool
    {
        return isset($this->itemStyleMap[get_class($item)]);
    }

    public function getStyleForMenuItem(MenuItemInterface $item) : ItemStyle
    {
        if (!isset($this->itemStyleMap[get_class($item)])) {
            throw InvalidStyle::unregisteredItem(get_class($item));
        }

        $styleClass = $this->itemStyleMap[get_class($item)];

        return $this->getStyle($styleClass);
    }

    public function registerItemStyle(string $itemClass, ItemStyle $itemStyle) : void
    {
        if (isset($this->itemStyleMap[$itemClass])) {
            throw InvalidStyle::itemAlreadyRegistered($itemClass);
        }

        $this->itemStyleMap[$itemClass] = get_class($itemStyle);
        $this->styles[get_class($itemStyle)] = $itemStyle;
    }
}
