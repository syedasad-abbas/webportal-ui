<?php

namespace Tests\Feature\Admin;

use App\Models\CallLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecordingBulkDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recording_path')->nullable();
            $table->timestamps();
        });
        config(['filesystems.recordings_disk' => 'recordings']);
        Storage::fake('recordings');
    }

    private function recording(?string $path): CallLog
    {
        if ($path !== null) {
            Storage::disk('recordings')->put($path, 'test audio');
        }

        return CallLog::create(['recording_path' => $path]);
    }

    private function allowDelete(): void
    {
        Gate::shouldReceive('authorize')->once()->with('recording.delete')->andReturnNull();
    }

    public function test_deletes_only_selected_recordings_and_files(): void
    {
        $first = $this->recording('first.wav');
        $second = $this->recording('second.wav');
        $kept = $this->recording('keep.wav');
        $this->allowDelete();

        $this->deleteJson(route('admin.recordings.bulk-delete'), ['ids' => [$first->id, $second->id]])
            ->assertOk()->assertJson(['deleted' => 2]);

        Storage::disk('recordings')->assertMissing(['first.wav', 'second.wav']);
        Storage::disk('recordings')->assertExists('keep.wav');
        $this->assertDatabaseMissing('call_logs', ['id' => $first->id]);
        $this->assertDatabaseMissing('call_logs', ['id' => $second->id]);
        $this->assertDatabaseHas('call_logs', ['id' => $kept->id]);
    }

    public function test_requires_delete_permission_before_mutating_anything(): void
    {
        $recording = $this->recording('keep.wav');
        Gate::shouldReceive('authorize')->once()->with('recording.delete')
            ->andThrow(new AuthorizationException);

        $this->deleteJson(route('admin.recordings.bulk-delete'), ['ids' => [$recording->id]])->assertForbidden();
        Storage::disk('recordings')->assertExists('keep.wav');
        $this->assertDatabaseHas('call_logs', ['id' => $recording->id]);
    }

    public function test_rejects_invalid_selections_before_deleting_files(): void
    {
        $recording = $this->recording('keep.wav');
        $withoutRecording = $this->recording(null);
        $selections = [[], [$recording->id, $recording->id], [$recording->id, 99999],
            [$recording->id, $withoutRecording->id], ['invalid'], range(1, 101)];
        Gate::shouldReceive('authorize')->times(count($selections))->with('recording.delete')->andReturnNull();

        foreach ($selections as $ids) {
            $this->deleteJson(route('admin.recordings.bulk-delete'), ['ids' => $ids])->assertUnprocessable();
            Storage::disk('recordings')->assertExists('keep.wav');
            $this->assertDatabaseHas('call_logs', ['id' => $recording->id]);
        }
    }

    public function test_missing_files_can_be_removed_from_the_listing(): void
    {
        $recording = $this->recording('missing.wav');
        Storage::disk('recordings')->delete('missing.wav');
        $this->allowDelete();

        $this->deleteJson(route('admin.recordings.bulk-delete'), ['ids' => [$recording->id]])
            ->assertOk()->assertJson(['deleted' => 1]);
        $this->assertDatabaseMissing('call_logs', ['id' => $recording->id]);
    }

    public function test_file_deletion_failure_preserves_the_call_log(): void
    {
        $recording = $this->recording('keep.wav');
        $this->allowDelete();
        $disk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $disk->shouldReceive('path')->with('')->andReturn('/var/recordings/');
        $disk->shouldReceive('exists')->with('keep.wav')->andReturnTrue();
        $disk->shouldReceive('delete')->with('keep.wav')->andReturnFalse();
        Storage::shouldReceive('disk')->with('recordings')->andReturn($disk);

        $this->deleteJson(route('admin.recordings.bulk-delete'), ['ids' => [$recording->id]])->assertStatus(500);
        $this->assertDatabaseHas('call_logs', ['id' => $recording->id]);
    }

    public function test_selection_controls_render_for_desktop_and_mobile_with_delete_permission(): void
    {
        $recording = $this->recording('sample.wav');
        $recording->setRelation('user', null);
        Gate::shouldReceive('check')->with('recording.delete')->andReturnTrue();
        Gate::shouldReceive('check')->with('recording.download')->andReturnFalse();

        $html = view('backend.pages.recordings._table', ['recordings' => collect([$recording])])->render();

        $this->assertStringContainsString(route('admin.recordings.bulk-delete'), $html);
        $this->assertStringContainsString('Select all on this page', $html);
        $this->assertStringContainsString('Delete selected', $html);
        $this->assertSame(2, substr_count($html, 'x-model="selectedRecordings"'));
    }

    public function test_selection_controls_are_hidden_without_delete_permission(): void
    {
        $recording = $this->recording('sample.wav');
        $recording->setRelation('user', null);
        Gate::shouldReceive('check')->andReturnFalse();

        $html = view('backend.pages.recordings._table', ['recordings' => collect([$recording])])->render();

        $this->assertStringNotContainsString('Delete selected', $html);
        $this->assertStringNotContainsString('x-model="selectedRecordings"', $html);
    }
}
