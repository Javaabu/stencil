<?php

namespace Javaabu\Stencil\Testing;

use Javaabu\Stencil\Form;

/**
 * For test cases: $this->assertForm(ArticleForm::make()->model($article))->assertFieldRequired('title').
 */
trait InteractsWithStencil
{
    public function assertForm(Form $form): AssertableForm
    {
        return AssertableForm::make($form);
    }
}
