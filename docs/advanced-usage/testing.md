---
title: Testing
sidebar_position: 5
---

`Javaabu\Stencil\Testing\AssertableForm` checks a form's schema, its state, its rules and its output. Get one with `$form->assertable()`, or add the `InteractsWithStencil` trait to your test case and call `$this->assertForm($form)`. Every assertion returns the same object, so they chain:

```php
use Javaabu\Stencil\Testing\InteractsWithStencil;

class IslandFormTest extends TestCase
{
    use InteractsWithStencil;

    #[Test]
    public function it_asks_for_the_ward_once_an_atoll_is_picked(): void
    {
        $atoll = Atoll::factory()->create();

        $this->assertForm(IslandForm::make()->model(Island::class))
            ->assertHasField('name', TextInput::class)
            ->assertFieldRequired('name')
            ->assertFieldProp('name', 'thaana', true)
            ->assertFieldHidden('ward_id')
            ->fillState(['atoll_id' => $atoll->id])
            ->assertFieldVisible('ward_id')
            ->assertRules('name', ['required', 'string', 'max:255'])
            ->assertInvalid(['name' => ''], 'name')
            ->assertValidated(['name' => 'މާލެ', 'atoll_id' => $atoll->id], ['name' => 'މާލެ', 'atoll_id' => $atoll->id, 'ward_id' => null])
            ->assertRendersField('ward_id');
    }
}
```

| Assertion | Checks |
| --- | --- |
| `fillState(array)` | Not an assertion: merges values into the state that closures and conditions see |
| `assertFields([...])` | The fields, in order |
| `assertHasField(name, ?class)`, `assertMissingField(name)` | A field exists (of a class) |
| `assertHasComponent(key)` | A layout or display component exists (`section-0`, `tab-address`) |
| `assertFieldVisible(name)`, `assertFieldHidden(name)` | For the current state, counting conditions |
| `assertFieldEnabled(name)`, `assertFieldDisabled(name)` | |
| `assertFieldRequired(name)`, `assertFieldNotRequired(name)` | |
| `assertFieldLive(name)` | |
| `assertFieldLabel(name, label)` | |
| `assertFieldProp(name, prop, value)` | A prop drivers get (`thaana`, `options`, `maxLength`, …) |
| `assertOptions(name, [value => label])` | |
| `assertState(name, value)` | |
| `assertRules(name, [...])`, `assertHasRule(name, rule)` | Rules for the current state, compared as strings |
| `assertNotValidated(name)` | The field has no rules for the current state |
| `assertValid(input)`, `assertInvalid(input, fields)` | |
| `assertValidated(input, data)` | What `validate()` returns |
| `assertRendersField(name)`, `assertDoesNotRenderField(name)`, `assertSee(text)` | The Blade output |
| `assertJsonPath(path, value)` | The JSON output: `assertJsonPath('components.0.props.heading', 'ތަފްޞީލް')` |

Test pages as usual: javaabu/forms' markup for Blade, `assertInertia()` for Inertia (the form is an array prop: `->where('form.state.name', 'މާލެ')`).

To test a live form's route, post to it with a token from the rendered form:

```php
$token = IslandForm::make()->model($island)->toArray()['live']['token'];

$this->actingAs($admin)
    ->postJson(route('stencil.evaluate'), ['_stencil' => $token, '_stencil_changed' => 'atoll_id', 'atoll_id' => $atoll->id])
    ->assertOk()
    ->assertJsonPath('schema.state.atoll_id', $atoll->id);
```

For drivers, see [Drivers](./drivers.md#testing-a-driver).
