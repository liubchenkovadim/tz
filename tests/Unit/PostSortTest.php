<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\PostSort;
use PHPUnit\Framework\TestCase;

class PostSortTest extends TestCase
{
    public function testFromStringReturnsCorrectEnum(): void
    {
        $this->assertSame(PostSort::DATE_DESC, PostSort::fromString('date_desc'));
        $this->assertSame(PostSort::DATE_ASC, PostSort::fromString('date_asc'));
        $this->assertSame(PostSort::VIEWS_DESC, PostSort::fromString('views_desc'));
        $this->assertSame(PostSort::VIEWS_ASC, PostSort::fromString('views_asc'));

        // Fallbacks
        $this->assertSame(PostSort::DATE_DESC, PostSort::fromString(null));
        $this->assertSame(PostSort::DATE_DESC, PostSort::fromString('unknown_value'));
    }

    public function testGetOrderBySql(): void
    {
        $this->assertSame('p.published_at DESC', PostSort::DATE_DESC->getOrderBySql());
        $this->assertSame('p.published_at ASC', PostSort::DATE_ASC->getOrderBySql());
        $this->assertSame('p.views_count DESC, p.published_at DESC', PostSort::VIEWS_DESC->getOrderBySql());
        $this->assertSame('p.views_count ASC, p.published_at DESC', PostSort::VIEWS_ASC->getOrderBySql());
    }

    public function testGetLabelIsRussian(): void
    {
        $this->assertSame('Сначала новые', PostSort::DATE_DESC->getLabel());
        $this->assertSame('Сначала старые', PostSort::DATE_ASC->getLabel());
        $this->assertSame('Самые популярные (по просмотрам)', PostSort::VIEWS_DESC->getLabel());
        $this->assertSame('Наименее популярные', PostSort::VIEWS_ASC->getLabel());
    }
}

