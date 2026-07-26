<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class KaryawanOrderViewSecurityTest extends TestCase
{
    public function test_detail_and_receipt_templates_do_not_parse_order_data_as_html(): void
    {
        $historyTemplate = file_get_contents(resource_path('views/karyawan/orders/history.blade.php'));
        $indexTemplate = file_get_contents(resource_path('views/karyawan/orders/index.blade.php'));

        $this->assertStringNotContainsString('innerHTML', $historyTemplate);
        $this->assertStringNotContainsString('insertAdjacentHTML', $historyTemplate);
        $this->assertStringNotContainsString('outerHTML', $historyTemplate);
        $this->assertStringContainsString('element.textContent = text', $historyTemplate);

        $this->assertStringNotContainsString('innerHTML', $indexTemplate);
        $this->assertStringNotContainsString('insertAdjacentHTML', $indexTemplate);
        $this->assertStringNotContainsString('outerHTML', $indexTemplate);
        $this->assertStringContainsString('name.textContent', $indexTemplate);
        $this->assertStringContainsString("document.getElementById('receipt-content').cloneNode(true)", $indexTemplate);
    }

    public function test_payment_proof_button_json_encodes_customer_name_for_javascript(): void
    {
        $rendered = Blade::render(
            "<button onclick='viewProof(@json(\$customerName))'></button>",
            ['customerName' => '<img src=x onerror=alert(1)>'],
        );

        $this->assertStringNotContainsString('<img', $rendered);
        $this->assertStringContainsString('\\u003Cimg', $rendered);
    }
}
