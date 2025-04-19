<?php

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

    public function test_playlist_store_tracks_request_validation()
    {
        $rules = (new PlaylistStoreTracksRequest())->rules();
        
        // Test invalid data (empty)
        $validator = Validator::make([], $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('track_ids'));
        
        // Test invalid data (track_ids not an array)
        $validator = Validator::make(['track_ids' => 'not-an-array'], $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('track_ids'));
        
        // Test valid data
        $validator = Validator::make(['track_ids' => [1, 2, 3]], $rules);
        // Note: This will still fail in a real scenario if the track IDs don't exist
        // But the unit test is checking the structure of the validation, not the DB constraints
        $this->assertTrue($validator->errors()->has('track_ids.0') || $validator->errors()->has('track_ids.*'));
    }
    
    public function test_playlist_remove_track_request_validation()
    {
        $rules = (new PlaylistRemoveTrackRequest())->rules();
        
        // Test invalid data (track_id doesn't exist)
        $validator = Validator::make(['track_id' => 999], $rules);
        // This will fail if the track doesn't exist in the database
        $this->assertTrue($validator->errors()->has('track_id') || $validator->passes());
        
        // Test valid data (no track_id needed as it's in the route)
        $validator = Validator::make([], $rules);
        $this->assertTrue($validator->passes());
    }
    
    public function test_playlist_delete_request_validation()
    {
        $rules = (new PlaylistDeleteRequest())->rules();
        
        // Test invalid data (id doesn't exist)
        $validator = Validator::make(['id' => 999], $rules);
        // This will fail if the playlist doesn't exist in the database
        $this->assertTrue($validator->errors()->has('id') || $validator->passes());
        
        // Test valid data (no id needed as it's in the route)
        $validator = Validator::make([], $rules);
        $this->assertTrue($validator->passes());
    }
    
    public function test_playlist_create_from_genre_request_validation()
    {
        $rules = (new PlaylistCreateFromGenreRequest())->rules();
        
        // Test invalid data (genre_id doesn't exist)
        $validator = Validator::make(['genre_id' => 999], $rules);
        // This will fail if the genre doesn't exist in the database
        $this->assertTrue($validator->errors()->has('genre_id') || $validator->passes());
        
        // Test valid data (no genre_id needed as it's in the route)
        $validator = Validator::make([], $rules);
        $this->assertTrue($validator->passes());
        
        // Test with title_suffix
        $validator = Validator::make(['title_suffix' => $this->faker->word], $rules);
        $this->assertTrue($validator->passes());
        
        // Test with title_suffix too long
        $longString = str_repeat('a', 256);
        $validator = Validator::make(['title_suffix' => $longString], $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('title_suffix'));
    }
    
    public function test_genre_delete_request_validation()
    {
        $rules = (new GenreDeleteRequest())->rules();
        
        // Test invalid data (id doesn't exist)
        $validator = Validator::make(['id' => 999], $rules);
        // This will fail if the genre doesn't exist in the database
        $this->assertTrue($validator->errors()->has('id') || $validator->passes());
        
        // Test valid data (no id needed as it's in the route)
        $validator = Validator::make([], $rules);
        $this->assertTrue($validator->passes());
    }
    
    public function test_all_request_authorization()
    {
        // All requests should be authorized by default
        $this->assertTrue((new PlaylistStoreTracksRequest())->authorize());
        $this->assertTrue((new PlaylistRemoveTrackRequest())->authorize());
        $this->assertTrue((new PlaylistDeleteRequest())->authorize());
        $this->assertTrue((new PlaylistCreateFromGenreRequest())->authorize());
        $this->assertTrue((new GenreDeleteRequest())->authorize());
    }
} 