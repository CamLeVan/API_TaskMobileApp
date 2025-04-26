<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'team_id' => 'required|exists:teams,id'
        ]);
        
        // Check if user is in the team
        $user = $request->user();
        $teamId = $request->team_id;
        
        if (!$user->teams()->where('teams.id', $teamId)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        
        // Store file
        $path = Storage::disk('public')->putFileAs(
            "teams/{$teamId}/chat", 
            $file, 
            $filename
        );
        
        // Generate thumbnail if it's an image
        $thumbnailUrl = null;
        $fileType = $file->getMimeType();
        
        if (strpos($fileType, 'image/') === 0) {
            // Create thumbnails directory if it doesn't exist
            $thumbnailDir = "teams/{$teamId}/chat/thumbnails";
            if (!Storage::disk('public')->exists($thumbnailDir)) {
                Storage::disk('public')->makeDirectory($thumbnailDir);
            }
            
            // Simple thumbnail implementation - in production use proper image library
            $thumbnailPath = "{$thumbnailDir}/{$filename}";
            $thumbnailUrl = url(Storage::disk('public')->url($thumbnailPath));
            
            // For now, just use the original file as thumbnail
            // In production, resize images using Intervention Image library
            Storage::disk('public')->copy("teams/{$teamId}/chat/{$filename}", $thumbnailPath);
        }
        
        return response()->json([
            'file_url' => url(Storage::disk('public')->url($path)),
            'thumbnail_url' => $thumbnailUrl,
            'file_type' => $fileType,
            'file_size' => $file->getSize(),
            'file_name' => $file->getClientOriginalName()
        ]);
    }
}