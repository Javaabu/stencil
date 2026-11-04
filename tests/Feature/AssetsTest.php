<?php

namespace Javaabu\Stencil\Tests\Feature;

use Javaabu\Manifest\Facades\Manifest;
use Javaabu\Stencil\Components\Step;
use Javaabu\Stencil\Components\Wizard;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AssetsTest extends TestCase
{
    #[Test]
    public function it_serves_the_prebuilt_script_and_styles(): void
    {
        Form::make()->schema([Wizard::make([Step::make('އެކައުންޓް')->schema([TextInput::make('email')])])])->toHtml();

        $tags = Manifest::renderScripts() . Manifest::renderStyles();

        $this->assertMatchesRegularExpression('#src="[^"]*stencil[^"]*/assets/stencil-[\w-]+\.js"#', $tags);
        $this->assertMatchesRegularExpression('#href="[^"]*stencil[^"]*/assets/stencil-[\w-]+\.css"#', $tags);
    }

    #[Test]
    public function it_requires_only_the_styles_for_a_plain_form(): void
    {
        Manifest::fake();

        Form::make()->schema([TextInput::make('name')])->toHtml();

        Manifest::assertAssetRequired('stencil::styles');
        Manifest::assertAssetNotRequired('stencil::stencil');
    }

    #[Test]
    public function it_can_leave_the_assets_to_the_app(): void
    {
        Manifest::fake();
        config(['stencil.assets' => false]);

        Form::make()->schema([TextInput::make('name')])->toHtml();

        Manifest::assertAssetNotRequired('stencil::styles');
    }
}
