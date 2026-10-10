<?php

namespace Tests\Unit;

use App\Support\Phone;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    public function test_it_matches_phones_with_different_formatting(): void
    {
        $this->assertTrue(Phone::matches('+250 788 994 400', '0788994400'));
        $this->assertTrue(Phone::matches('+250788994400', '250788994400'));
        $this->assertTrue(Phone::matches('0788-994-400', '+250788994400'));
    }

    public function test_it_rejects_different_phones(): void
    {
        $this->assertFalse(Phone::matches('+250788994400', '+250788994401'));
        $this->assertFalse(Phone::matches('', '+250788994400'));
    }
}
