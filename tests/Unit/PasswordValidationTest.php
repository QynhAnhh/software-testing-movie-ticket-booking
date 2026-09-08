<?php

use PHPUnit\Framework\TestCase;

class PasswordValidationTest extends TestCase
{
    /**
     * @dataProvider passwordProvider
     */
    public function testPasswordBoundary(
        string $password,
        bool $expected
    ): void {

        $length = strlen($password);

        // Logic theo yêu cầu hệ thống
        $actual = $length >= 6 && $length <= 20;

        $this->assertSame($expected, $actual);
    }


    public static function passwordProvider(): array
    {
        return [

            // TC-AH-04: 5 ký tự
            'TC-AH-04 - 5 ký tự' => [
                'abcde',
                false
            ],

            // TC-AH-05: 6 ký tự
            'TC-AH-05 - 6 ký tự' => [
                'abcdef',
                true
            ],

            // TC-AH-06: 7 ký tự
            'TC-AH-06 - 7 ký tự' => [
                'abcdefg',
                true
            ],

            // TC-AH-07: 19 ký tự
            'TC-AH-07 - 19 ký tự' => [
                'abcdefghijklmnopqrs',
                true
            ],

            // TC-AH-08: 20 ký tự
            'TC-AH-08 - 20 ký tự' => [
                'abcdefghijklmnopqrst',
                true
            ],

            // TC-AH-09: 21 ký tự
            'TC-AH-09 - 21 ký tự' => [
                'abcdefghijklmnopqrstu',
                false
            ],
        ];
    }
}
