<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RegistrationSwitchTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public static function registrationValueProvider(): array
    {
        return [
            '开启状态'   => ['1', true],
            '关闭状态'   => ['0', false],
            '布尔开启'   => [true, true],
            '布尔关闭'   => [false, false],
            'false 字符串' => ['false', false],
            'OFF 字符串'  => ['OFF', false],
            'no 字符串'   => ['no', false],
            '空字符串'   => ['', true],
            '其他值'     => ['enabled', true],
        ];
    }

    #[DataProvider('registrationValueProvider')]
    public function test_registration_allowed_parses_setting_value(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, registration_allowed($value));
    }

    public function test_admin_setting_contains_allow_register_switch(): void
    {
        $content = $this->read('resources/beike/admin/views/pages/setting/basic.blade.php');

        $this->assertStringContainsString('name="allow_register"', $content);
        $this->assertStringContainsString("system_setting('base.allow_register', '1')", $content);
    }

    public function test_login_page_hides_register_tab_and_form(): void
    {
        $file = 'themes/default/account/login.blade.php';

        $this->assertGuarded($file, "switchTab('register')");
        $this->assertGuarded($file, '<el-form v-show="activeTab === \'register\'"');
    }

    public function test_header_hides_wishlist_entry_and_register_text(): void
    {
        $file = 'themes/default/layout/header.blade.php';

        $this->assertGuarded($file, "shop_route('account.wishlist.index')");
        $this->assertStringContainsString(
            "registration_allowed() ? __('shop/login.login_and_sign') : __('shop/login.login')",
            $this->read($file)
        );
    }

    public function test_header_hides_account_and_cart_entries(): void
    {
        $file = 'themes/default/layout/header.blade.php';

        $this->assertGuarded($file, 'icons/account.svg');
        $this->assertGuarded($file, 'icons/cart.svg');
        $this->assertGuarded($file, 'alt="account"');
        $this->assertGuarded($file, 'alt="cart"');
    }

    public function test_product_templates_hide_wishlist_buttons(): void
    {
        $this->assertGuarded('themes/default/shared/product.blade.php', 'btn-wishlist');
        $this->assertGuarded('themes/default/product/product.blade.php', 'add-wishlist');
        $this->assertGuarded('themes/default/components/account/sidebar.blade.php', "shop_route('account.wishlist.index')");
    }

    public function test_checkout_hides_guest_login_cards_when_registration_disabled(): void
    {
        $content = $this->read('themes/default/checkout.blade.php');

        $this->assertStringContainsString('!current_customer() && is_mobile() && registration_allowed()', $content);
        $this->assertStringContainsString('!current_customer() && !is_mobile() && registration_allowed()', $content);
    }

    public function test_register_controller_rejects_requests_when_registration_disabled(): void
    {
        $content = $this->read('beike/Shop/Http/Controllers/Account/RegisterController.php');

        $this->assertSame(2, substr_count($content, 'if (! registration_allowed())'));
        $this->assertSame(2, substr_count($content, "trans('shop/login.register_disabled')"));
    }

    /**
     * 断言目标元素处于 registration_allowed() 条件分支内（中间未出现 @endif）
     */
    private function assertGuarded(string $relativePath, string $needle): void
    {
        $pattern = '/@if \(registration_allowed\(\)\)(?:(?!@endif).)*?' . preg_quote($needle, '/') . '/s';

        $this->assertMatchesRegularExpression(
            $pattern,
            $this->read($relativePath),
            "{$relativePath} 中的 {$needle} 未受注册开关控制"
        );
    }

    private function read(string $relativePath): string
    {
        $path = $this->root . '/' . $relativePath;

        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
