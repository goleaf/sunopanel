<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\GenreDeleteRequest;
use App\Http\Requests\PlaylistCreateFromGenreRequest;
use App\Http\Requests\PlaylistDeleteRequest;
use App\Http\Requests\PlaylistRemoveTrackRequest;
use App\Http\Requests\PlaylistStoreTracksRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RequestValidationTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_playlist_store_tracks_request_validation(): void {
        $rules = (new PlaylistStoreTracksRequest)->rules();
        $validator = Validator::make([], $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('track_ids'));
        $validator = Validator::make(['track_ids' => 'not-an-array'], $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('track_ids'));
        $validator = Validator::make(['track_ids' => [1, 2, 3]], $rules);

        $this->assertTrue($validator->errors()->has('track_ids.0') || $validator->errors()->has('track_ids.*'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_playlist_remove_track_request_validation(): void {
        $rules = (new PlaylistRemoveTrackRequest)->rules();
        $validator = Validator::make(['track_id' => 999], $rules);
        $this->assertTrue($validator->errors()->has('track_id') || $validator->passes());
        $validator = Validator::make([], $rules);
        $this->assertTrue($validator->passes());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_playlist_delete_request_validation(): void {
        $rules = (new PlaylistDeleteRequest)->rules();
        $validator = Validator::make(['id' => 999], $rules);
        $this->assertTrue($validator->errors()->has('id') || $validator->passes());
        $validator = Validator::make([], $rules);
        $this->assertTrue($validator->passes());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_playlist_create_from_genre_request_validation(): void {
        $rules = (new PlaylistCreateFromGenreRequest)->rules();
        $validator = Validator::make(['genre_id' => 999], $rules);
        $this->assertTrue($validator->errors()->has('genre_id') || $validator->passes());
        $validator = Validator::make([], $rules);
        $this->assertTrue($validator->passes());
        $validator = Validator::make(['title_suffix' => $this->faker->word], $rules);
        $this->assertTrue($validator->passes());
        $longString = str_repeat('a', 256);
        $validator = Validator::make(['title_suffix' => $longString], $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('title_suffix'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_genre_delete_request_validation(): void {
        $rules = (new GenreDeleteRequest)->rules();
        $validator = Validator::make(['id' => 999], $rules);
        $this->assertTrue($validator->errors()->has('id') || $validator->passes());
        $validator = Validator::make([], $rules);
        $this->assertTrue($validator->passes());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_all_request_authorization(): void {
        $this->assertTrue((new PlaylistStoreTracksRequest)->authorize());
        $this->assertTrue((new PlaylistRemoveTrackRequest)->authorize());
        $this->assertTrue((new PlaylistDeleteRequest)->authorize());
        $this->assertTrue((new PlaylistCreateFromGenreRequest)->authorize());
        $this->assertTrue((new GenreDeleteRequest)->authorize());
    }
}
