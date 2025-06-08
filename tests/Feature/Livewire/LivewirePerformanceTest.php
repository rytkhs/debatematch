<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ContactForm;
use App\Livewire\ConnectionStatus;
use App\Livewire\FlashMessage;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LivewirePerformanceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function contact_form_handles_high_volume_submissions()
    {
        $startTime = microtime(true);

        // 複数のフォーム送信をシミュレート
        for ($i = 0; $i < 10; $i++) {
            Livewire::test(ContactForm::class)
                ->set('type', 'bug_report')
                ->set('name', "Test User {$i}")
                ->set('email', "test{$i}@example.com")
                ->set('subject', "Test Subject {$i}")
                ->set('message', "This is test message {$i} with sufficient length.")
                ->call('submit')
                ->assertSet('submitted', true);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // 10回の送信が5秒以内に完了することを確認
        $this->assertLessThan(5.0, $executionTime, 'Contact form submissions took too long');

        // データベースに正しく保存されていることを確認
        $this->assertDatabaseCount('contacts', 10);
    }

    #[Test]
    public function connection_status_handles_rapid_status_changes()
    {
        $room = Room::factory()->create();
        $users = User::factory()->count(20)->create();

        $startTime = microtime(true);

        $livewire = Livewire::test(ConnectionStatus::class, ['room' => $room]);

        // 大量のユーザーのオンライン/オフライン状態変更をシミュレート
        foreach ($users as $user) {
            $livewire->call('handleMemberOnline', ['id' => $user->id]);
            $livewire->call('handleMemberOffline', ['id' => $user->id]);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // 40回の状態変更が2秒以内に完了することを確認
        $this->assertLessThan(2.0, $executionTime, 'Connection status updates took too long');
    }

    #[Test]
    public function flash_message_handles_rapid_message_updates()
    {
        $startTime = microtime(true);

        $livewire = Livewire::test(FlashMessage::class);

        // 大量のメッセージ更新をシミュレート
        for ($i = 0; $i < 50; $i++) {
            $livewire->call('showFlashMessage', "Message {$i}", 'info');
            $livewire->call('hideFlashMessage');
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // 100回の操作が1秒以内に完了することを確認
        $this->assertLessThan(1.0, $executionTime, 'Flash message updates took too long');
    }

    #[Test]
    public function livewire_components_memory_usage_is_reasonable()
    {
        $initialMemory = memory_get_usage();

        // 複数のコンポーネントを同時にテスト
        $components = [];

        for ($i = 0; $i < 10; $i++) {
            $room = Room::factory()->create();
            $components[] = Livewire::test(ConnectionStatus::class, ['room' => $room]);
            $components[] = Livewire::test(ContactForm::class);
            $components[] = Livewire::test(FlashMessage::class);
        }

        $peakMemory = memory_get_peak_usage();
        $memoryUsed = $peakMemory - $initialMemory;

        // メモリ使用量が50MB以下であることを確認
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, 'Memory usage is too high');

        // コンポーネントが正常に動作することを確認
        foreach ($components as $component) {
            $this->assertNotNull($component);
        }
    }

    #[Test]
    public function livewire_components_handle_concurrent_requests()
    {
        $room = Room::factory()->create();
        $users = User::factory()->count(5)->create();

        $startTime = microtime(true);

        // 複数のコンポーネントで同時操作をシミュレート
        $livewires = [];

        foreach ($users as $user) {
            $livewires[] = Livewire::actingAs($user)->test(ContactForm::class);
            $livewires[] = Livewire::test(ConnectionStatus::class, ['room' => $room]);
        }

        // 各コンポーネントで操作を実行
        foreach ($livewires as $index => $livewire) {
            if ($index % 2 === 0) {
                // ContactForm
                $livewire->set('type', 'bug_report')
                    ->set('name', "User {$index}")
                    ->set('email', "user{$index}@example.com")
                    ->set('subject', "Subject {$index}")
                    ->set('message', "Message {$index} with sufficient length.")
                    ->call('submit');
            } else {
                // ConnectionStatus
                $livewire->call('handleConnectionLost')
                    ->call('handleConnectionRestored');
            }
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // 同時操作が3秒以内に完了することを確認
        $this->assertLessThan(3.0, $executionTime, 'Concurrent operations took too long');
    }
}
