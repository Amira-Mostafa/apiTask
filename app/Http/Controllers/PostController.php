<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostCollection;
use App\Http\Resources\PostResource;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    use HttpResponses;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = Post::with('tags')->orderby('pinned', 'desc')->get();
        return new PostCollection($posts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['coverImage'] = $request->file('coverImage')->store('coverImage');
        try {
            $post = Auth::user()->posts()->create($validatedData);
            $post->tags()->attach($validatedData['tags']);
            return new PostResource($post);
        } catch (\Exception $e) {
            return $this->error('', 'data was not created',  401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $post = Post::with('tags')->findOrfail($id);
        return new PostResource($post);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, string $id)
    {

        $validatedData = $request->validated();
        // $validatedData['pinned'] = $request->has('pinned');
        if ($request->hasFile('coverImage')) {
            $validatedData['coverImage'] = $request->file('cover_image')->store('cover_images');
        }
        $post = Auth::user()->posts()->findOrFail($id);
        $post->update($validatedData);
        if ($validatedData['tags']) {
            $post->tags()->sync([$validatedData['tags']]);
        }
        return new PostResource($post);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post) //soft delete
    {
        $post->delete();
        return $this->success('post was deleted succefully');
    }

    public function trashed()
    {
        $posts = Auth::user()->posts()->onlyTrashed()->get();
        return PostResource::collection($posts);
    }

    public function restore(string $id)
    {
        $post = Post::onlyTrashed()->where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $post->restore();
        return $this->success('post was restored succefully');
    }

    public function forceDelete(string $id)
    {
        $post = Post::onlyTrashed()->where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $post->forceDelete();
        return $this->success('post was forceDeleted succefully');
    }
}
