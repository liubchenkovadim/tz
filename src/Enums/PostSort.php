<?php

declare(strict_types=1);

namespace App\Enums;

enum PostSort: string
{
    case DATE_DESC  = 'date_desc';
    case DATE_ASC   = 'date_asc';
    case VIEWS_DESC = 'views_desc';
    case VIEWS_ASC  = 'views_asc';

    public static function fromString(?string $value): self
    {
        if ($value === null) {
            return self::DATE_DESC;
        }

        return self::tryFrom($value) ?? self::DATE_DESC;
    }

    public function getOrderBySql(): string
    {
        return match ($this) {
            self::DATE_DESC  => 'p.published_at DESC',
            self::DATE_ASC   => 'p.published_at ASC',
            self::VIEWS_DESC => 'p.views_count DESC, p.published_at DESC',
            self::VIEWS_ASC  => 'p.views_count ASC, p.published_at DESC',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::DATE_DESC  => 'Сначала новые',
            self::DATE_ASC   => 'Сначала старые',
            self::VIEWS_DESC => 'Самые популярные (по просмотрам)',
            self::VIEWS_ASC  => 'Наименее популярные',
        };
    }
}
