<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CopilotVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_copilot_is_not_rendered_as_a_floating_widget(): void
    {
        config(['services.openai.key' => null]);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-copilot', false)
            ->assertDontSee('Abrir SuHomes Copilot');
    }

    public function test_copilot_chat_module_is_shown_when_openai_api_key_is_configured(): void
    {
        config([
            'services.openai.key' => 'test-openai-api-key',
            'services.openai.show_costs' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('copilot.index'))
            ->assertOk()
            ->assertSee('data-copilot', false)
            ->assertSee('¿En qué te podemos ayudar?')
            ->assertSee('SuHomes Copilot')
            ->assertSee('Chats recientes')
            ->assertSee('Uso mensual');
    }

    public function test_copilot_usage_summary_is_hidden_when_costs_are_disabled(): void
    {
        config([
            'services.openai.key' => 'test-openai-api-key',
            'services.openai.show_costs' => false,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('copilot.index'))
            ->assertOk()
            ->assertSee('data-copilot', false)
            ->assertDontSee('Uso mensual')
            ->assertDontSee('<strong data-copilot-usage-cost', false);
    }

    public function test_usage_reply_omits_costs_when_disabled(): void
    {
        config([
            'services.openai.key' => 'test-openai-api-key',
            'services.openai.show_costs' => false,
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('copilot.chat'), [
                'message' => '¿Cuántos tokens he usado?',
            ])
            ->assertOk()
            ->assertJsonPath('message.content', fn (string $content): bool => str_contains($content, 'tokens') && ! str_contains($content, 'costo estimado')
            );
    }

    public function test_copilot_module_route_is_available_even_without_an_openai_key(): void
    {
        config(['services.openai.key' => '   ']);

        $this->actingAs(User::factory()->create())
            ->get(route('copilot.index'))
            ->assertOk()
            ->assertSee('data-copilot', false)
            ->assertSee('¿En qué te podemos ayudar?');
    }

    public function test_saved_chats_can_be_reopened_and_deleted_individually(): void
    {
        config(['services.openai.key' => null]);
        $user = User::factory()->create();

        $firstConversation = $this->actingAs($user)
            ->postJson(route('copilot.chat'), ['message' => 'Dame un resumen ejecutivo'])
            ->assertOk()
            ->json('conversation_id');

        $secondConversation = $this->actingAs($user)
            ->postJson(route('copilot.chat'), ['message' => 'Que cobranza esta pendiente?'])
            ->assertOk()
            ->json('conversation_id');

        $this->assertNotSame($firstConversation, $secondConversation);

        $this->actingAs($user)
            ->getJson(route('copilot.history'))
            ->assertOk()
            ->assertJsonPath('conversation_id', $secondConversation)
            ->assertJsonCount(2, 'messages')
            ->assertJsonCount(2, 'conversations');

        $this->actingAs($user)
            ->getJson(route('copilot.history', ['conversation_id' => $firstConversation]))
            ->assertOk()
            ->assertJsonPath('conversation_id', $firstConversation)
            ->assertJsonCount(2, 'conversations');

        $this->actingAs($user)
            ->deleteJson(route('copilot.reset'), ['conversation_id' => $firstConversation])
            ->assertOk()
            ->assertJsonCount(1, 'conversations');

        $this->assertDatabaseMissing('ai_conversations', ['uuid' => $firstConversation]);
        $this->assertDatabaseHas('ai_conversations', ['uuid' => $secondConversation]);
    }
}
